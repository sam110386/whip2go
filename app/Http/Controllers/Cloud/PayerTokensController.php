<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\StripeProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayerTokensController extends LegacyAppController
{
    private array $userFields = [
        'id', 'first_name', 'middle_name', 'last_name', 'email', 'photo',
        'contact_number', 'address', 'ss_no', 'dob', 'city', 'state', 'zip',
        'is_driver', 'is_passenger', 'currency', 'timezone',
    ];

    public function initiate(Request $request)
    {
        $authorization = $request->header('Authorization', '');

        $userObj = session('userObj');
        if (empty($authorization) && !empty($userObj)) {
            return response()->json([
                'status'   => true,
                'message'  => 'you are logged in userObj',
                'view_url' => url('/insurance/roi/display'),
            ]);
        }

        $jwtToken = trim(str_replace('Basic', '', $authorization));

        try {
            $user = DB::table('users')
                ->where('status', 1)
                ->where('is_driver', 1)
                ->where('is_verified', 1)
                ->where('is_admin', 0)
                ->where('token', $jwtToken)
                ->select($this->userFields)
                ->first();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Sorry, seems you are also logged in on another device/browser. Please login back',
                ], 402);
            }

            session(['userObj' => (array) $user]);

            return response()->json([
                'status'   => true,
                'message'  => 'you are logged in',
                'view_url' => config('app.url') . '/insurance/payer_tokens/index',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Please log back in',
            ], 400);
        }
    }

    public function index(Request $request, $ruleid = '')
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $bookings = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->leftJoin('insurance_payers as InsurancePayer', 'InsurancePayer.order_deposit_rule_id', '=', 'OrderDepositRule.id')
            ->where('VehicleReservation.renter_id', $userObj['id'])
            ->whereIn('VehicleReservation.status', [0, 1])
            ->whereNotNull('InsurancePayer.id')
            ->select(
                'VehicleReservation.id',
                'OrderDepositRule.id as order_deposit_rule_id',
                'InsurancePayer.id as insurance_payer_id',
                'Vehicle.vehicle_name'
            )
            ->get();

        if (!empty($ruleid)) {
            session(['ruleid' => $ruleid]);
        }

        if ($bookings->count() === 1 && !session()->has('ruleid')) {
            $ruleid = $bookings->first()->order_deposit_rule_id;
            session(['ruleid' => $ruleid]);
        }

        if (session()->has('ruleid')) {
            $ruleid = session('ruleid');
        }

        $insurancePayerTokens = DB::table('insurance_payer_tokens')
            ->where('user_id', $userObj['id'])
            ->where('order_rule_id', $ruleid)
            ->orderBy('id', 'DESC')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $rules = $bookings->pluck('vehicle_name', 'order_deposit_rule_id')->all();

        return view('cloud.insurance.payer_tokens.index', [
            'title_for_layout'    => 'Manage Your CC Details',
            'InsurancePayerTokens' => $insurancePayerTokens,
            'rules'                => $rules,
        ]);
    }

    public function makedefault($id = null)
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $id = $this->decodeId($id);

        $token = DB::table('insurance_payer_tokens')
            ->where('user_id', $userObj['id'])
            ->where('id', $id)
            ->first();

        if (!empty($token)) {
            $stripe = new StripeProcessor();
            $stripe->makeCardDefault($token->stripe_token, $token->card_id);
            return redirect()->back()->with('success', 'Card configured as default, successfully');
        }

        return redirect()->back()->with('error', 'Sorry, this is default CC record, this cant be deleted.');
    }

    public function delete($id = null)
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $id = $this->decodeId($id);

        $token = DB::table('insurance_payer_tokens')
            ->where('user_id', $userObj['id'])
            ->where('id', $id)
            ->first();

        if (!empty($token) && !$token->is_default) {
            DB::table('insurance_payer_tokens')->where('id', $id)->delete();
            return redirect()->back()->with('success', 'Record has been deleted succesfully');
        }

        return redirect()->back()->with('error', 'Sorry, this is default CC record, this cant be deleted.');
    }

    public function add(Request $request)
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        if ($request->isMethod('post')) {
            $dataValues = $request->input('InsurancePayerToken');
            $dataValues['user_id'] = $userObj['id'];
            $dataObj = json_decode(json_encode($dataValues));

            $ruleid = session('ruleid');

            $insurancePayer = DB::table('insurance_payers')
                ->where('order_deposit_rule_id', $ruleid)
                ->first();

            if (empty($insurancePayer->stripe_key)) {
                return redirect()->action([self::class, 'index'])
                    ->with('error', 'Sorry, stripe key is not configured yet by ROI vendor');
            }

            $existingToken = DB::table('insurance_payer_tokens')
                ->where('user_id', $userObj['id'])
                ->first();

            $stripe = new StripeProcessor();
            $return = $stripe->addNewCard(
                $dataObj,
                !empty($existingToken) ? $existingToken->stripe_token : '',
                $insurancePayer->stripe_key
            );

            if ($return['status'] !== 'success') {
                return redirect()->back()->with('error', $return['message']);
            }

            $dataTosave = [
                'created'      => date('Y-m-d H:i:s'),
                'is_default'   => 0,
                'user_id'      => $userObj['id'],
                'card'         => substr($dataObj->credit_card_number, -4),
                'card_funding' => $return['card_funding'] ?? '',
                'stripe_token' => $return['stripe_token'] ?? '',
                'card_id'      => $return['card_id'] ?? '',
                'order_rule_id' => session('ruleid'),
            ];

            if (!empty($existingToken)) {
                $dataTosave['is_default'] = 1;
            }

            $newId = DB::table('insurance_payer_tokens')->insertGetId($dataTosave);

            if (!empty($existingToken) && ($dataObj->default ?? 0) == 1 && !empty($dataTosave['card_id'])) {
                $stripe->makeCardDefault($existingToken->stripe_token, $dataTosave['card_id']);
                DB::table('insurance_payer_tokens')
                    ->where('user_id', $userObj['id'])
                    ->where('order_rule_id', $dataTosave['order_rule_id'])
                    ->where('id', '!=', $newId)
                    ->update(['is_default' => 0]);
            }

            return redirect()->action([self::class, 'index'])
                ->with('success', 'Card has been added successfully.');
        }

        return view('cloud.insurance.payer_tokens.add', [
            'listTitle' => 'Add CC Details',
        ]);
    }
}
