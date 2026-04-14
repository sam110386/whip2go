<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrepaidPlansController extends LegacyAppController
{
    private array $allowedStatus = [];

    public function __construct()
    {
        parent::__construct();
        // Populated at runtime from Common component equivalent
        $this->allowedStatus = array_keys(
            app()->bound('common') ? app('common')->getReservationStatus(true, true) : []
        );
    }

    public function loadBookingPlans(Request $request): JsonResponse
    {
        if (!$request->filled('lease_id') || !$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Your request processed successfully']);
        }

        $leaseId = base64_decode($request->input('lease_id'));

        $prepaidPlans = DB::table('reservation_prepaid_plans')
            ->where('reservation_id', $leaseId)
            ->get();

        $chargeButton = $prepaidPlans->where('status', '!=', 3)->isNotEmpty();

        $orderDepositRule = DB::table('order_deposit_rules')
            ->where('vehicle_reservation_id', $leaseId)
            ->select('tax')
            ->first();

        $tax = $orderDepositRule->tax ?? 0;
        $encodedLeaseId = base64_encode($leaseId);

        $prepaidplanView = view('elements.vehiclereservation._prepaidplan', compact(
            'chargeButton', 'prepaidPlans', 'tax', 'encodedLeaseId'
        ))->render();

        return response()->json(['status' => true, 'message' => '', 'prepaidplan' => $prepaidplanView]);
    }

    public function changestatus(Request $request): JsonResponse
    {
        $return = ['status' => 'error', 'message' => 'Sorry, something went wrong'];

        if (!$request->filled('planid') || !$request->isMethod('post')) {
            return response()->json($return);
        }

        $planid = $request->input('planid');
        $status = $request->input('status');

        $plan = DB::table('reservation_prepaid_plans')
            ->where('id', $planid)
            ->whereIn('status', [0, 1])
            ->first();

        if (empty($plan)) {
            $return['message'] = 'Sorry, plan details are not found';
            return response()->json($return);
        }

        DB::table('reservation_prepaid_plans')
            ->where('id', $plan->id)
            ->update(['status' => $status]);

        return response()->json(['status' => 'success', 'message' => 'Your request processed successfully']);
    }

    public function retrypayment(Request $request): JsonResponse
    {
        $return = ['status' => 'error', 'message' => 'Sorry, something went wrong'];

        if (!$request->filled('planid') || !$request->isMethod('post')) {
            return response()->json($return);
        }

        $planid = $request->input('planid');

        $plan = DB::table('reservation_prepaid_plans')
            ->where('id', $planid)
            ->whereIn('status', [1, 2])
            ->first();

        if (empty($plan)) {
            $return['message'] = 'Sorry, plan details are not found or not active or already charged';
            return response()->json($return);
        }

        $amountToCharge = $plan->amount;

        if ($amountToCharge <= 0) {
            DB::table('reservation_prepaid_plans')->where('id', $plan->id)->update(['status' => 3]);
            $return['message'] = 'Sorry, seems everything is paid already';
            return response()->json($return);
        }

        $vehicleReservation = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->where('VehicleReservation.id', $plan->reservation_id)
            ->when(!empty($this->allowedStatus), fn($q) => $q->whereIn('VehicleReservation.status', $this->allowedStatus))
            ->select('OrderDepositRule.tax', 'VehicleReservation.id', 'VehicleReservation.user_id', 'VehicleReservation.renter_id', 'VehicleReservation.start_datetime')
            ->first();

        if (empty($vehicleReservation)) {
            DB::table('reservation_prepaid_plans')->where('id', $plan->id)->update(['status' => 0]);
            $return['message'] = 'Sorry, related pending booking is not found';
            return response()->json($return);
        }

        $currency = DB::table('users')->where('id', $vehicleReservation->user_id)->value('currency');

        $dataToPass = [
            'id'             => $vehicleReservation->id,
            'renter_id'      => $vehicleReservation->renter_id,
            'start_datetime' => $vehicleReservation->start_datetime,
            'amount'         => $amountToCharge,
            'tax'            => sprintf('%0.2f', ($vehicleReservation->tax * $amountToCharge / 100)),
        ];

        $paymentProcessor = new \PaymentProcessor();
        $return = $paymentProcessor->chargeInitialFeeForVehicleReservation($dataToPass, $currency);

        $dataToUpdate = ['id' => $plan->id];
        if ($return['status'] === 'success') {
            DB::table('reservation_prepaid_plans')->where('id', $plan->id)->update([
                'charged_on'   => now(),
                'last_attempt' => now(),
                'status'       => 3,
            ]);
        } else {
            DB::table('reservation_prepaid_plans')->where('id', $plan->id)->update([
                'last_attempt_error' => $return['message'],
                'status'             => 2,
            ]);
        }

        return response()->json($return);
    }

    public function chargeallpayments(Request $request): JsonResponse
    {
        $return = ['status' => 'error', 'message' => 'Sorry, something went wrong'];

        if (!$request->filled('bookingid') || !$request->isMethod('post')) {
            return response()->json($return);
        }

        $bookingid = base64_decode($request->input('bookingid'));
        $selected = $request->input('selected', []);

        if (empty($selected)) {
            $return['message'] = 'Please selected atleast 1 record.';
            return response()->json($return);
        }

        $vehicleReservation = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->where('VehicleReservation.id', $bookingid)
            ->when(!empty($this->allowedStatus), fn($q) => $q->whereIn('VehicleReservation.status', $this->allowedStatus))
            ->select('OrderDepositRule.tax', 'VehicleReservation.id', 'VehicleReservation.user_id', 'VehicleReservation.renter_id', 'VehicleReservation.start_datetime')
            ->first();

        if (empty($vehicleReservation)) {
            DB::table('reservation_prepaid_plans')
                ->where('reservation_id', $bookingid)
                ->update(['status' => 0]);
            $return['message'] = 'Sorry, related pending booking is not found';
            return response()->json($return);
        }

        $prepaidPlans = DB::table('reservation_prepaid_plans')
            ->where('reservation_id', $bookingid)
            ->whereIn('id', $selected)
            ->whereIn('status', [0, 1, 2])
            ->get();

        if ($prepaidPlans->isEmpty()) {
            $return['message'] = 'Sorry, plan details are not found or not active or already charged';
            return response()->json($return);
        }

        $amountToCharge = $prepaidPlans->sum('amount');

        if ($amountToCharge <= 0) {
            DB::table('reservation_prepaid_plans')
                ->where('reservation_id', $bookingid)
                ->whereIn('id', $selected)
                ->update(['status' => 3]);
            $return['message'] = 'Sorry, seems everything is paid already';
            return response()->json($return);
        }

        $currency = DB::table('users')->where('id', $vehicleReservation->user_id)->value('currency');

        $dataToPass = [
            'id'             => $vehicleReservation->id,
            'renter_id'      => $vehicleReservation->renter_id,
            'start_datetime' => $vehicleReservation->start_datetime,
            'amount'         => $amountToCharge,
            'tax'            => sprintf('%0.2f', ($vehicleReservation->tax * $amountToCharge / 100)),
        ];

        $paymentProcessor = new \PaymentProcessor();
        $return = $paymentProcessor->chargeInitialFeeForVehicleReservation($dataToPass, $currency);

        if ($return['status'] !== 'success') {
            return response()->json($return);
        }

        foreach ($prepaidPlans as $plan) {
            DB::table('reservation_prepaid_plans')->where('id', $plan->id)->update([
                'charged_on'   => now(),
                'last_attempt' => now(),
                'status'       => 3,
            ]);
        }

        return response()->json($return);
    }
}
