<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Intercom Mapi – mobile payment helpers invoked from Intercom popup actions.
 *
 * Mirrors legacy Intercom/MservicesController + MapiController flow.
 * Heavy business logic (traits ActiveBookingTotalPending, ValidateExtensionRequestTrait,
 * PasstimeActivateVehicle) are called from their existing legacy service layer.
 */
class IntercomMapiController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $_security = '7750ca3559e5b8e1f442103368fcgc';

    protected static int $_STATUSFAIL = 0;
    protected static int $_STATUSSUCCESS = 1;

    protected $userObj;

    protected array $_userfields = [
        'id', 'first_name', 'middle_name', 'last_name', 'email', 'photo',
        'contact_number', 'address', 'ss_no', 'dob', 'city', 'state', 'zip',
        'licence_type', 'licence_number', 'licence_state', 'licence_exp_date',
        'is_renter', 'is_owner', 'is_driver', 'is_passenger',
        'license_doc_1', 'license_doc_2', 'is_staff', 'staff_parent',
        'checkr_status', 'auto_renew', 'uberlyft_verified', 'bank', 'currency', 'address_doc',
    ];

    /**
     * Authenticate via X-Security header + userid param.
     */
    private function authenticateMapiUser(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $xsecurity = $request->header('X-Security', $request->header('x-security', ''));
        if (empty($xsecurity) || strtolower($xsecurity) !== $this->_security) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Sorry, seems you are spam bot'], 402);
        }

        $userid = $request->route('userid');
        $user = DB::table('users')
            ->where('status', 1)
            ->where('is_verified', 1)
            ->where('is_admin', 0)
            ->where('id', $userid)
            ->select($this->_userfields)
            ->first();

        if (empty($user)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Sorry, seems you are also logged in on another device/browser. Please login back'], 402);
        }

        $this->userObj = (array) $user;
        return null;
    }

    public function getMyTransactions(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $postData = json_decode($request->getContent());
        $userId = $this->userObj['id'];

        $page = 1;
        $offset = 0;
        $limit = 50;
        if (isset($postData->page)) {
            $page = $postData->page;
            $offset = (($page - 1) * 50);
        }
        if (isset($postData->limit)) {
            $limit = (int) $postData->limit;
        }

        $leases = DB::table('cs_order_payments')
            ->leftJoin('cs_orders', 'cs_orders.id', '=', 'cs_order_payments.cs_order_id')
            ->where('cs_orders.renter_id', $userId)
            ->where('cs_order_payments.status', 1)
            ->select(
                'cs_orders.increment_id as Booking_Id',
                'cs_orders.currency',
                'cs_orders.timezone',
                'cs_order_payments.amount',
                'cs_order_payments.charged_at',
                DB::raw("(CASE
                    WHEN cs_order_payments.type=1 then 'Deposit Fee'
                    WHEN cs_order_payments.type=2 then 'Usage Fee'
                    WHEN cs_order_payments.type=3 then 'Initial Fee'
                    WHEN cs_order_payments.type=4 then 'Insurance & Fees'
                    WHEN cs_order_payments.type=5 then 'Cancelation Fee'
                    WHEN cs_order_payments.type=6 then 'Misc/Violations'
                    WHEN cs_order_payments.type=7 then 'Custom Balance Fee'
                    WHEN cs_order_payments.type=14 then 'Ins Extra Usage'
                    WHEN cs_order_payments.type=16 then 'Extra Usage Fee'
                    WHEN cs_order_payments.type=19 then 'Lateness Fee'
                    ELSE 'Custom Balance Fee'
                END) as title"),
                'cs_orders.start_datetime',
                'cs_orders.end_datetime'
            )
            ->groupBy('cs_order_payments.id')
            ->orderByDesc('cs_order_payments.created')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($leases->isEmpty()) {
            return response()->json(['error' => 'sorry, no record found.']);
        }

        $result = [];
        foreach ($leases as $lease) {
            $tz = $lease->timezone ?? 'UTC';
            $chargedAt = $lease->charged_at
                ? Carbon::parse($lease->charged_at)->setTimezone($tz)->format('m-d-Y')
                : '';
            $startDate = $lease->start_datetime
                ? Carbon::parse($lease->start_datetime)->setTimezone($tz)->format('Y-m-d')
                : '';
            $endDate = $lease->end_datetime
                ? Carbon::parse($lease->end_datetime)->setTimezone($tz)->format('Y-m-d')
                : '';

            $result[] = [
                'Booking_Id' => $lease->Booking_Id ?? '',
                'amount' => $lease->amount ?? '',
                'charged_at' => $chargedAt,
                'title' => $lease->title ?? '',
                'start_datetime' => $startDate,
                'end_datetime' => $endDate,
                'currency' => $lease->currency ?? '',
            ];
        }

        return response()->json($result);
    }

    public function checkbalance(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $userId = $this->userObj['id'];

        $orderData = DB::table('cs_orders')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_orders.vehicle_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'cs_orders.user_id')
            ->where('cs_orders.renter_id', $userId)
            ->whereIn('cs_orders.status', [0, 1])
            ->select('cs_orders.*', 'vehicles.modified as vehicle_modified', 'vehicles.last_mile', 'vehicles.passtime_status', 'Owner.distance_unit')
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => 'sorry, you dont have any active booking.']);
        }

        if ($orderData->passtime_status == 1) {
            return response()->json(['success' => 'Your car status is active and it should be in ready state']);
        }

        return response()->json([
            'success' => "Your booking {$orderData->increment_id} car status is disabled and you need to make a payment to activate your car.",
        ]);
    }

    public function checkBookingExtension(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $userId = $this->userObj['id'];

        $orderData = DB::table('cs_orders')
            ->where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => 'Sorry, you dont have any active booking with us.']);
        }

        $depositRule = DB::table('order_deposit_rules')
            ->where('cs_order_id', $orderData->parent_id ?: $orderData->id)
            ->select('rental', 'insurance', 'tax')
            ->first();

        $leastPayment = $depositRule
            ? sprintf('%0.2f', ($depositRule->rental + $depositRule->insurance) * 2)
            : '0.00';

        return response()->json([
            'success' => "Your booking {$orderData->increment_id} extension check complete. Minimum payment required: \${$leastPayment}.",
        ]);
    }

    public function extendBooking(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $postData = json_decode($request->getContent());
        $userId = $this->userObj['id'];

        $emergency = false;
        if (!empty($postData->extdate)) {
            try {
                $d = new \DateTime($postData->extdate);
                $extdate = $d->format('Y-m-d 11:00:00');
            } catch (\Exception $e) {
                return response()->json(['error' => 'Please enter some valid date']);
            }
        } else {
            $extdate = date('Y-m-d H:00:00', strtotime('+3 hours'));
            $emergency = true;
        }

        $orderData = DB::table('cs_orders')
            ->where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => 'Sorry, you dont have any active booking with us.']);
        }

        $note = 'I requested extension through chat';

        DB::table('order_extlogs')->insert([
            'cs_order_id' => $orderData->id,
            'ext_date' => $extdate,
            'note' => $note,
            'owner' => $userId,
            'amt' => 0,
            'admin_count' => $emergency ? 1 : 0,
            'created' => now(),
        ]);

        return response()->json(['success' => 'Your vehicle should be enabled shortly']);
    }

    public function makeAdvancePayment(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $postData = json_decode($request->getContent());
        if (empty($postData) || empty($postData->amount) || (float) $postData->amount == 0) {
            return response()->json(['error' => 'Please enter some valid amount']);
        }

        $userId = $this->userObj['id'];

        $orderData = DB::table('cs_orders')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_orders.vehicle_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'cs_orders.user_id')
            ->where('cs_orders.renter_id', $userId)
            ->where('cs_orders.status', 1)
            ->select('cs_orders.*', 'vehicles.modified as vehicle_modified', 'vehicles.last_mile', 'Owner.distance_unit')
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => 'Sorry, seems you dont have any active booking with us.']);
        }

        try {
            $paymentProcessor = app(\App\Services\Legacy\PaymentProcessor::class);
            $res = $paymentProcessor->chargeAmtToUser($postData->amount, $userId, 'DIA Partial Pay', $orderData->currency);

            if (($res['status'] ?? '') !== 'success') {
                return response()->json(['error' => $res['message'] ?? 'Payment failed']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['success' => 'Your request processed successfully']);
    }

    public function emergencyBookingExtend(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $extdate = date('Y-m-d H:00:00', strtotime('+3 hours'));
        $userId = $this->userObj['id'];

        $orderData = DB::table('cs_orders')
            ->where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => 'Sorry, you dont have any active booking with us.']);
        }

        $isAlreadyExtended = DB::table('order_extlogs')
            ->where('cs_order_id', $orderData->id)
            ->whereDate('ext_date', date('Y-m-d'))
            ->where('admin_count', 1)
            ->count();

        if ($isAlreadyExtended) {
            return response()->json(['error' => 'Sorry, you already used this feature today.']);
        }

        $note = 'I requested extension through chat';

        DB::table('order_extlogs')->insert([
            'cs_order_id' => $orderData->id,
            'ext_date' => $extdate,
            'note' => $note,
            'owner' => $userId,
            'amt' => 0,
            'admin_count' => 1,
            'created' => now(),
        ]);

        return response()->json(['success' => 'Your vehicle should be enabled shortly']);
    }
}
