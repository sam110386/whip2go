<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\OrderExtlog;
use Illuminate\Support\Facades\DB;

trait ReportingTrait
{
    /**
     * Get extension logs for a given order or set of orders.
     * Used across Reports and potentially other controllers.
     *
     * @param int|array $id
     * @return \Illuminate\Support\Collection
     */
    protected function _getExtLogs($id)
    {
        $ids = is_array($id) ? $id : [$id];

        return OrderExtlog::from('order_extlogs as OrderExtlog')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'OrderExtlog.owner')
            ->select('OrderExtlog.*', 'Owner.first_name', 'Owner.last_name')
            ->whereIn('OrderExtlog.cs_order_id', $ids)
            ->get();
    }

    /**
     * Common revenue calculation for booking details.
     * Centralizing this to avoid duplication in _details and _autorenewddetails.
     */
    protected function calculateRevenueBreakdown($payments, $csorder, $revshare)
    {
        $diAFeeFactor = (100 - (float)$revshare) / 100;
        $totalPaid = 0;
        $paidInitialFee = 0;
        $totalGrandPaid = 0;
        $totalDiaFee = 0;
        $dealerPaidInsurance = 0;

        foreach ($payments as $payment) {
            $isRenter = empty($payment->payer_id) || $payment->payer_id == $csorder->renter_id;
            
            if ($isRenter) {
                $totalGrandPaid += $payment->amount;
            } else {
                $dealerPaidInsurance += $payment->amount;
            }

            // Rental / Extended types
            if (in_array($payment->type, [2, 16])) {
                $base = $payment->amount - $payment->tax - $payment->dia_fee;
                $totalPaid += $base;
                $totalDiaFee += ($base * $diAFeeFactor);
            }

            // Initial Fee type
            if ($payment->type == 3) {
                $base = $payment->amount - $payment->tax;
                $paidInitialFee += $base;
                $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFeeFactor);
            }
        }

        return [
            'totalPaid'            => $totalPaid,
            'paidInitialFee'       => $paidInitialFee,
            'downpaymentPaid'     => $totalPaid + $paidInitialFee,
            'totalGrandPaid'       => $totalGrandPaid,
            'totalDiaFee'          => $totalDiaFee,
            'dealerPaidInsurance' => $dealerPaidInsurance,
        ];
    }
}
