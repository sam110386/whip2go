<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use Illuminate\Support\Facades\DB;

trait InsuranceToken {

    public function _getinsurancetoken($conditions) {
        $return = ['status' => false, 'message' => "Invalid Booking ID", 'result' => []];
        if (empty($conditions)) return $return;

        $lease = CsOrder::where($conditions)->select('id', 'parent_id', 'user_id')->first();
        if (!$lease) {
            return ['status' => false, 'message' => "Sorry, respected booking couldn't be found.", 'result' => []];
        }
        
        $orderId = $lease->parent_id ?: $lease->id;
        $orderDepositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();
        if (!$orderDepositRule) return $return;

        if (in_array($orderDepositRule->insurance_payer, [5, 6, 7])) {
            $quote = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $orderDepositRule->vehicle_reservation_id)
                ->first();
            if (!$quote || empty($quote->insurance_card)) {
                return ['status' => false, 'message' => "Driver hasn't uploaded insurance token yet.", 'result' => []];
            }
            return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/reservation/' . $quote->insurance_card)]];
        }

        $payer = DB::table('insurance_payers')->where('order_deposit_rule_id', $orderDepositRule->id)->first();
        if (!$payer || empty($payer->insurance_card)) {
            return ['status' => false, 'message' => "Insurance card not found.", 'result' => []];
        }
        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/reservation/' . $payer->insurance_card)]];
    }

    public function _getDeclarationDoc($conditions) {
        $return = ['status' => false, 'message' => "Invalid Booking ID", 'result' => []];
        if (empty($conditions)) return $return;

        $lease = CsOrder::where($conditions)->select('id', 'parent_id', 'user_id')->first();
        if (!$lease) return $return;

        $orderId = $lease->parent_id ?: $lease->id;
        $orderDepositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();
        if (!$orderDepositRule) return $return;

        if (in_array($orderDepositRule->insurance_payer, [5, 6, 7])) {
            $quote = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $orderDepositRule->vehicle_reservation_id)
                ->first();
            if (!$quote || empty($quote->declaration_doc)) {
                return ['status' => false, 'message' => "Declaration doc not found.", 'result' => []];
            }
            return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/reservation/' . $quote->declaration_doc)]];
        }

        $payer = DB::table('insurance_payers')->where('order_deposit_rule_id', $orderDepositRule->id)->first();
        if (!$payer || empty($payer->declaration_doc)) {
            return ['status' => false, 'message' => "Declaration doc not found.", 'result' => []];
        }
        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/reservation/' . $payer->declaration_doc)]];
    }
}
