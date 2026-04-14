<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/InsuranceToken.php
 *
 * Looks up insurance token and declaration documents for bookings.
 */
trait InsuranceTokenTrait
{
    protected function _getinsurancetoken($conditions)
    {
        $return = ['status' => false, 'message' => __("Invalid Booking ID"), 'result' => []];
        if (empty($conditions)) {
            return $return;
        }

        $Lease = DB::table('cs_orders')
            ->where($conditions)
            ->select('id', 'parent_id', 'user_id')
            ->first();

        if (empty($Lease)) {
            return ['status' => false, 'message' => __("Sorry, respecteced booking couldnt find, so you can't perform this action now."), 'result' => []];
        }

        $orderId = !empty($Lease->parent_id) ? $Lease->parent_id : $Lease->id;
        $OrderDepositRule = DB::table('order_deposit_rules')
            ->where('cs_order_id', $orderId)
            ->select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->first();

        if (in_array($OrderDepositRule->insurance_payer, [6, 5, 7])) {
            $DriverFinancedInsuranceQuote = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $OrderDepositRule->vehicle_reservation_id)
                ->first();

            if (empty($DriverFinancedInsuranceQuote) || empty($DriverFinancedInsuranceQuote->insurance_card)) {
                return ['status' => false, 'message' => __("Sorry, Driver didnt upload insurance token yet. He agreed to manage it himself."), 'result' => []];
            }
            return ['status' => true, 'message' => "Success", 'result' => ['file' => config('app.url') . '/files/reservation/' . $DriverFinancedInsuranceQuote->insurance_card]];
        }

        $InsurancePayer = DB::table('insurance_payers')
            ->where('order_deposit_rule_id', $OrderDepositRule->id)
            ->first();

        if (empty($InsurancePayer) || empty($InsurancePayer->insurance_card)) {
            return ['status' => false, 'message' => __("Sorry, Driver didnt upload insurance token yet. He agreed to manage it himself."), 'result' => []];
        }
        return ['status' => true, 'message' => "Success", 'result' => ['file' => config('app.url') . '/files/reservation/' . $InsurancePayer->insurance_card]];
    }

    protected function _getDeclarationDoc($conditions)
    {
        $return = ['status' => false, 'message' => __("Invalid Booking ID"), 'result' => []];
        if (empty($conditions)) {
            return $return;
        }

        $Lease = DB::table('cs_orders')
            ->where($conditions)
            ->select('id', 'parent_id', 'user_id')
            ->first();

        if (empty($Lease)) {
            return ['status' => false, 'message' => __("Sorry, respecteced booking couldnt find, so you can't perform this action now."), 'result' => []];
        }

        $orderId = !empty($Lease->parent_id) ? $Lease->parent_id : $Lease->id;
        $OrderDepositRule = DB::table('order_deposit_rules')
            ->where('cs_order_id', $orderId)
            ->select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->first();

        if (in_array($OrderDepositRule->insurance_payer, [6, 5, 7])) {
            $DriverFinancedInsuranceQuote = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $OrderDepositRule->vehicle_reservation_id)
                ->first();

            if (empty($DriverFinancedInsuranceQuote) || empty($DriverFinancedInsuranceQuote->declaration_doc)) {
                return ['status' => false, 'message' => __("Sorry, Driver didnt upload insurance token yet. He agreed to manage it himself."), 'result' => []];
            }
            return ['status' => true, 'message' => "Success", 'result' => ['file' => config('app.url') . '/files/reservation/' . $DriverFinancedInsuranceQuote->declaration_doc]];
        }

        $InsurancePayer = DB::table('insurance_payers')
            ->where('order_deposit_rule_id', $OrderDepositRule->id)
            ->first();

        if (empty($InsurancePayer) || empty($InsurancePayer->declaration_doc)) {
            return ['status' => false, 'message' => "sorry, Dealer didnt upload declaration doc yet.", 'result' => []];
        }
        return ['status' => true, 'message' => "Success", 'result' => ['file' => config('app.url') . '/files/reservation/' . $InsurancePayer->declaration_doc]];
    }
}
