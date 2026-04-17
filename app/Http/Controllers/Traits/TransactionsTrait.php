<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsPayoutTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

trait TransactionsTrait {

    public function _getTransactionHistory(Request $request, $userId = null) {
        $query = CsOrder::query()
            ->from('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->select('CsOrder.*', 'User.first_name', 'User.last_name')
            ->whereIn('CsOrder.status', [2, 3]);

        if ($userId) {
            $query->where('CsOrder.user_id', $userId);
        }

        if ($request->filled('Search.keyword')) {
            $keyword = $request->input('Search.keyword');
            $fieldname = $request->input('Search.searchin');
            if ($fieldname == "2") {
                $query->where('CsOrder.vehicle_name', 'LIKE', "%$keyword%");
            } elseif ($fieldname == "3") {
                $query->where('CsOrder.increment_id', $keyword);
            }
        }

        if ($request->filled('Search.date_from')) {
            $query->where('CsOrder.start_datetime', '>=', Carbon::parse($request->input('Search.date_from'))->toDateString());
        }
        if ($request->filled('Search.date_to')) {
            $query->where('CsOrder.end_datetime', '<=', Carbon::parse($request->input('Search.date_to'))->toDateString());
        }

        return $query->orderBy('CsOrder.id', 'DESC')->paginate($request->input('Record.limit', 20))->withQueryString();
    }

    public function _adjustFee($orderId, $feeType, $newData, $userId = null) {
        try {
            return DB::transaction(function() use ($orderId, $feeType, $newData, $userId) {
                $csOrder = CsOrder::findOrFail($orderId);
                if ($userId && $csOrder->user_id != $userId) {
                    throw new \Exception("Unauthorized");
                }

                $oldAmount = 0;
                $newAmount = number_format($newData['new_total'] ?? 0, 2, '.', '');
                
                switch($feeType) {
                    case 'rental': $oldAmount = $csOrder->paid_amount; break;
                    case 'insurance': $oldAmount = $csOrder->insurance_amt; break;
                    case 'deposit': $oldAmount = $csOrder->deposit; break;
                    case 'initial_fee': $oldAmount = $csOrder->initial_fee; break;
                    case 'emf': $oldAmount = $csOrder->emf_amt; break;
                    case 'toll': $oldAmount = $csOrder->toll_amt; break;
                }

                if ($oldAmount == $newAmount) {
                    return ['status' => 'success', 'message' => "No change needed"];
                }

                // Placeholder for PaymentProcessor::charge/refund Balance
                Log::info("Adjusting $feeType for order $orderId: $oldAmount -> $newAmount");
                
                // Assuming success for simulation
                $updateData = [];
                switch($feeType) {
                    case 'rental': $updateData['paid_amount'] = $newAmount; break;
                    case 'insurance': $updateData['insurance_amt'] = $newAmount; break;
                    case 'deposit': $updateData['deposit'] = $newAmount; break;
                    case 'initial_fee': $updateData['initial_fee'] = $newAmount; break;
                    case 'emf': $updateData['emf_amt'] = $newAmount; break;
                    case 'toll': $updateData['toll_amt'] = $newAmount; break;
                }
                
                $csOrder->update($updateData);

                return ['status' => 'success', 'message' => "Adjustment processed successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error adjusting $feeType: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function _refundFee($orderId, $feeType, $userId = null) {
        try {
            return DB::transaction(function() use ($orderId, $feeType, $userId) {
                $csOrder = CsOrder::findOrFail($orderId);
                if ($userId && $csOrder->user_id != $userId) {
                    throw new \Exception("Unauthorized");
                }

                // Placeholder for PaymentProcessor refund
                Log::info("Full refund for $feeType on order $orderId");

                $updateData = [];
                switch($feeType) {
                    case 'rental': $updateData['paid_amount'] = 0; break;
                    case 'insurance': $updateData['insurance_amt'] = 0; break;
                    case 'deposit': $updateData['deposit'] = 0; break;
                    case 'initial_fee': $updateData['initial_fee'] = 0; break;
                    case 'emf': $updateData['emf_amt'] = 0; break;
                    case 'toll': $updateData['toll_amt'] = 0; break;
                }
                $updateData['details'] = ($csOrder->details ?? '') . "\n Full $feeType Refunded";
                $csOrder->update($updateData);

                return ['status' => 'success', 'message' => "Refund processed successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error refunding $feeType: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
