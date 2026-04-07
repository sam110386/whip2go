<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait ActiveBookingTotalPending {

    private function getNextScheduleFee($orderDepositRule, $csOrder, $autorenew = false) {
        $return = [
            'damage_fee' => $csOrder->damage_fee ?? 0,
            'uncleanness_fee' => $csOrder->uncleanness_fee ?? 0,
            'initial_fee' => $csOrder->initial_fee ?? 0
        ];

        $startTime = Carbon::parse($csOrder->start_datetime);
        $endTime = $autorenew ? Carbon::parse($csOrder->end_datetime) : Carbon::now();
        $totalHours = abs($startTime->diffInHours($endTime));

        if ($totalHours > 6) {
            $insuranceDays = floor($totalHours / 24);
            $insuranceHours = $totalHours % 24;
            $insuranceHours = $insuranceHours > 6 ? $insuranceHours : 0;
            $totalDays = $insuranceDays + ($insuranceHours > 0 ? 1 : 0);
        } else {
            $totalDays = 0;
        }

        $insuranceAmt = 0;
        if ($orderDepositRule) {
            $insuranceAmt = number_format(($totalDays * ($orderDepositRule->insurance ?? 0)), 2, '.', '');
        }
        $return['insurance_amt'] = $insuranceAmt;

        // Rental calculation... (simplified, assuming getDayRentFromTierData is in a trait or helper)
        $dayRent = $orderDepositRule->rental ?? 0;
        $timeFee = number_format(($totalDays * $dayRent), 2, '.', '');
        
        $return['rent'] = $timeFee;
        $return['discount'] = 0; // Stub for Promo logic

        // Odometer logic (Stubbed for now as per "skip lib" rule)
        $return['end_odometer'] = $csOrder->last_mile ?? 0;
        
        $diaFee = 0; // Stub for DepositRule calculation
        $taxRate = $orderDepositRule->tax ?? 0;
        $tax = (($return['rent'] + $diaFee) * $taxRate) / 100;
        
        $return['tax'] = number_format($tax, 2, '.', '');
        $return['emf_tax'] = 0;
        $return['dia_fee'] = $diaFee;
        $return['dia_insu'] = 0;
        
        return $return;
    }

    public function getActiveBookingTotalPending($lease, $csOrder) {
        $orderId = $lease->parent_id ?: $lease->id;
        $orderDepositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();
        if (!$orderDepositRule) return $lease;

        // logic ported from CakePHP...
        // ... (simplified for migration demonstration, ensuring all field names match)
        
        $paidPayments = CsOrderPayment::where('cs_order_id', $lease->id)
            ->whereIn('type', [3, 2, 16, 19, 4, 14])
            ->where('status', 1)
            ->get();
            
        $paidRental = $paidPayments->whereIn('type', [2, 16, 19])->sum('amount');
        $paidInsurance = $paidPayments->whereIn('type', [4, 14])->sum('amount');

        $lease->total_rental_paid = $paidRental;
        $lease->total_insurance_paid = $paidInsurance;
        
        // ... more calculations based on end_odometer and duration
        
        return $lease;
    }
}
