<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\LeadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadApiController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $version = 'v1';
    private static int $_STATUSFAIL = 0;
    private static int $_STATUSSUCCESS = 1;
    private $userObj;

    public function authToken(Request $request)
    {
        $postData = json_decode($request->getContent(), true) ?? [];
        $return = ['status' => self::$_STATUSFAIL, 'message' => 'Invalid inputs'];

        if (empty($postData['username']) || empty($postData['password'])) {
            return response()->json($return);
        }

        $aData = DB::table('users')
            ->where(function ($q) use ($postData) {
                $q->where('username', trim($postData['username']))
                    ->orWhere('email', trim($postData['username']));
            })
            ->select('id', 'password')
            ->first();

        if (empty($aData)) {
            return response()->json(['message' => 'Sorry, you are not registered user.', 'status' => 0]);
        }

        $hash = sha1(\config('legacy.security_salt', '') . $postData['password']);
        if (empty($aData->password) || $aData->password !== $hash) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Sorry, your password does not match, please try again']);
        }

        $token = bin2hex(random_bytes(4));
        DB::table('users')->where('id', $aData->id)->update(['token' => $token]);

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'auth_token' => $token,
        ]);
    }

    public function refreshToken(Request $request)
    {
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Token refreshed']);
    }

    public function saveLeads(Request $request)
    {
        $userObj = $this->authenticateApiUser($request);
        if ($userObj instanceof \Illuminate\Http\JsonResponse) {
            return $userObj;
        }

        $dataValues = json_decode($request->getContent(), true) ?? [];
        $return = ['status' => self::$_STATUSFAIL, 'message' => 'Invalid input body', 'result' => []];

        if (empty($dataValues) || empty($userObj->id) || !isset($dataValues[0])) {
            return response()->json($return);
        }

        $return = ['status' => self::$_STATUSSUCCESS, 'message' => 'Lead data is processed successfully', 'result' => []];

        foreach ($dataValues as $dataValue) {
            if (empty($dataValue['phone']) || empty($dataValue['email'])) {
                continue;
            }
            $return['result'][] = $this->saveLead($dataValue, $userObj);
        }

        return response()->json($return);
    }

    public function leadStatus(Request $request)
    {
        $userObj = $this->authenticateApiUser($request);
        if ($userObj instanceof \Illuminate\Http\JsonResponse) {
            return $userObj;
        }

        $dataValues = json_decode($request->getContent(), true) ?? [];
        $return = ['status' => self::$_STATUSFAIL, 'message' => 'Invalid input body', 'result' => []];

        if (empty($dataValues) || empty($userObj->id) || !isset($dataValues['phone']) || !isset($dataValues['email'])) {
            return response()->json($return);
        }

        $identifier = '';
        if (!empty($dataValues['phone'])) {
            $identifier = substr(preg_replace('/[^0-9]/', '', $dataValues['phone']), -10);
        } elseif (!empty($dataValues['email'])) {
            $identifier = trim($dataValues['email']);
        }

        $leadObj = DB::table('cs_leads')
            ->where('admin_id', $userObj->id)
            ->where(function ($q) use ($identifier) {
                $q->where('phone', $identifier)->orWhere('email', $identifier);
            })
            ->first();

        if (empty($leadObj)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Sorry, lead record not found']);
        }

        $intecomContact = [];
        if (!empty($leadObj->intercom_id)) {
            $intecomContact = (new LeadService())->pullIntercomContact($leadObj->intercom_id);
        }

        $VehicleReservation = null;
        if (!empty($leadObj->user_id)) {
            $VehicleReservation = DB::table('vehicle_reservations')
                ->where('renter_id', $leadObj->user_id)
                ->orderByDesc('id')
                ->first();
        }

        $leadDetails = [
            'first_name' => $leadObj->first_name,
            'last_name' => $leadObj->last_name,
            'email' => $leadObj->email,
            'phone' => $leadObj->phone,
            'status' => empty($leadObj->user_id) ? 'Lead Only' : 'Registered User',
        ];

        $intercom = ['created_at' => '', 'last_seen_at' => ''];
        if (!empty($intecomContact) && is_object($intecomContact)) {
            $intercom['id'] = $intecomContact->id;
            $intercom['created_at'] = date('Y-m-d H:i:s', $intecomContact->created_at);
            $intercom['last_seen_at'] = date('Y-m-d H:i:s', $intecomContact->last_seen_at);
        }

        $bookingDetails = ['pending_booking' => 'No', 'active_booking' => 'No', 'cancel_note' => ''];
        if (!empty($VehicleReservation)) {
            if ($VehicleReservation->status != 1) {
                $bookingDetails['pending_booking'] = 'Yes';
            }
            if ($VehicleReservation->status == 1) {
                $bookingDetails['active_booking'] = 'Yes';
            }
            if ($VehicleReservation->status == 2) {
                $bookingDetails['cancel_note'] = $VehicleReservation->cancel_note ?? '';
            }
        }

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => 'Lead status is as following',
            'result' => [
                'lead_details' => $leadDetails,
                'intercom_contact' => $intercom,
                'booking_details' => $bookingDetails,
            ],
        ]);
    }

    public function leads(Request $request)
    {
        $userObj = $this->authenticateApiUser($request);
        if ($userObj instanceof \Illuminate\Http\JsonResponse) {
            return $userObj;
        }

        $dataValues = json_decode($request->getContent(), true) ?? [];

        $query = DB::table('cs_leads')->where('admin_id', $userObj->id);

        if (!empty($dataValues['date_from'])) {
            $query->where('created', '>=', Carbon::parse($dataValues['date_from'])->startOfDay());
        }
        if (!empty($dataValues['date_to'])) {
            $query->where('created', '<=', Carbon::parse($dataValues['date_to'])->endOfDay());
        }

        $limit = 20;
        $page = $dataValues['page'] ?? 1;
        $offset = (($page - 1) * $limit);

        $leads = $query->select('id', 'phone', 'first_name', 'last_name', 'email', 'status', 'user_id', 'intercom_id')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'No result found', 'result' => []]);
        }

        $tempLeads = [];
        foreach ($leads as $lead) {
            $bookingDetails = ['pending_booking' => 'No', 'active_booking' => 'No', 'cancel_note' => ''];

            if (!empty($lead->user_id)) {
                $vr = DB::table('vehicle_reservations')
                    ->where('renter_id', $lead->user_id)
                    ->orderByDesc('id')
                    ->first();

                if (!empty($vr)) {
                    if ($vr->status != 1) {
                        $bookingDetails['pending_booking'] = 'Yes';
                    }
                    if ($vr->status == 1) {
                        $bookingDetails['active_booking'] = 'Yes';
                    }
                    if ($vr->status == 2) {
                        $bookingDetails['cancel_note'] = $vr->cancel_note ?? '';
                    }
                }
            }

            $tempLeads[] = [
                'lead_details' => (array) $lead,
                'intercom_contact' => ['created_at' => '', 'last_seen_at' => ''],
                'booking_details' => $bookingDetails,
            ];
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Result found', 'result' => $tempLeads]);
    }

    private function saveLead(array $record, $userObj): array
    {
        try {
            $phone = substr(preg_replace('/[^0-9]/', '', $record['phone']), -10);
            $exists = DB::table('cs_leads')->where('phone', $phone)->exists();
            if ($exists) {
                return [$record['phone'] => 'Lead with phone number ' . $record['phone'] . ' already exists'];
            }

            $dataToSave = [
                'admin_id' => $userObj->id,
                'sub_admin_id' => $userObj->id,
                'phone' => $phone,
                'first_name' => $record['first_name'] ?? '',
                'last_name' => $record['last_name'] ?? '',
                'email' => $record['email'] ?? '',
            ];

            $existingUser = DB::table('users')
                ->where('username', $phone)
                ->orWhere('contact_number', 'LIKE', '%' . $phone)
                ->first();

            if (!empty($existingUser)) {
                $dataToSave['user_id'] = $existingUser->id;
                $dataToSave['status'] = 1;
                try {
                    DB::table('admin_user_associations')->insert([
                        'user_id' => $existingUser->id,
                        'admin_id' => $userObj->id,
                    ]);
                } catch (\Exception $e) {
                    // ignore duplicate
                }
            }

            (new LeadService())->pushToIntercom($dataToSave);
            DB::table('cs_leads')->insert($dataToSave);

            return [$record['phone'] => 'Lead saved successfully'];
        } catch (\Exception $e) {
            return [$record['phone'] => $e->getMessage()];
        }
    }

    private function authenticateApiUser(Request $request)
    {
        $authorization = $request->header('Authorization', '');
        $jwtToken = trim(str_replace('Basic', '', $authorization));

        if (empty($jwtToken)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Please log back in'], 400);
        }

        $user = DB::table('users')
            ->where('token', $jwtToken)
            ->where('status', 1)
            ->whereIn('role_id', [2, 7])
            ->where('is_admin', 1)
            ->first();

        if (empty($user)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Please log back in'], 400);
        }

        return $user;
    }
}
