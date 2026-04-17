<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait OrderDepositRulesTrait {

    public function _processUpdate(Request $request, $orderId) {
        $orderId = base64_decode($orderId);
        if (!$orderId) {
            return ['status' => 'error', 'message' => "Invalid Order ID"];
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = $request->input('OrderDepositRule', []);
            
            // Remove start_datetime as per legacy logic
            unset($data['start_datetime']);

            // Calculate totals and encode options
            $depositAmt = (float)($data['deposit_amt'] ?? 0);
            $depositOpts = $data['deposit_opt'] ?? [];
            $depositOptsSum = array_sum(array_column($depositOpts, 'amount'));
            $data['total_deposit_amt'] = $depositAmt + $depositOptsSum;
            $data['deposit_opt'] = !empty($depositOpts) ? json_encode(array_values($depositOpts)) : null;

            $initialFee = (float)($data['initial_fee'] ?? 0);
            $initialFeeOpts = $data['initial_fee_opt'] ?? [];
            $initialFeeSum = array_sum(array_column($initialFeeOpts, 'amount'));
            $data['total_initial_fee'] = $initialFee + $initialFeeSum;
            $data['initial_fee_opt'] = !empty($initialFeeOpts) ? json_encode(array_values($initialFeeOpts)) : null;

            $rentalOpts = $data['rental_opt'] ?? [];
            $data['rental_opt'] = !empty($rentalOpts) ? json_encode(array_values($rentalOpts)) : null;

            $durationOpts = $data['duration_opt'] ?? [];
            $totalDuration = array_sum(array_column($durationOpts, 'duration'));
            $data['duration_opt'] = $totalDuration > 0 ? json_encode(array_values($durationOpts)) : null;

            $rule = OrderDepositRule::updateOrCreate(
                ['cs_order_id' => $orderId],
                $data
            );

            return ['status' => 'success', 'message' => "Payment setting is updated successfully.", 'rule' => $rule];
        }

        $rule = OrderDepositRule::where("cs_order_id", $orderId)->first();
        if ($rule) {
            $rule->deposit_opt = $rule->deposit_opt ? json_decode($rule->deposit_opt, true) : [];
            $rule->initial_fee_opt = $rule->initial_fee_opt ? json_decode($rule->initial_fee_opt, true) : [];
            $rule->rental_opt = $rule->rental_opt ? json_decode($rule->rental_opt, true) : [];
            $rule->duration_opt = $rule->duration_opt ? json_decode($rule->duration_opt, true) : [];
        }

        return ['status' => 'success', 'rule' => $rule, 'id' => $orderId];
    }
}
