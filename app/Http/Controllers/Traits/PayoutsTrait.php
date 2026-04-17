<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\User;
use App\Models\Legacy\RevSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait PayoutsTrait {

    public function resolvePayoutSearch(Request $request) {
        $dateFrom = $request->input('Search.date_from') ?: $request->query('date_from');
        $dateTo = $request->input('Search.date_to') ?: $request->query('date_to');
        $listtype = $request->input('Search.listtype') ?: $request->query('listtype');
        $payoutId = $request->input('Search.payout_id') ?: $request->query('payout_id');
        $userId = $request->input('Search.user_id') ?: $request->query('user_id');

        return [
            'dateFrom' => $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null,
            'dateTo' => $dateTo ? Carbon::parse($dateTo)->toDateString() : null,
            'listtype' => $listtype,
            'payoutId' => $payoutId,
            'userId' => $userId
        ];
    }

    public function buildExportQuery($conditions) {
        $query = CsPayoutTransaction::query()
            ->from('cs_payout_transactions as CsPayoutTransaction')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
            ->leftJoin('cs_order_payments as CsOrderPayment', function($join) {
                $join->on('CsOrderPayment.id', '=', 'CsPayoutTransaction.cs_payment_id')
                     ->where('CsOrderPayment.status', 1);
            })
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', function($join) {
                $join->on('OrderDepositRule.cs_order_id', '=', 'CsOrder.id')
                     ->orOn('OrderDepositRule.cs_order_id', '=', 'CsOrder.parent_id');
            })
            ->select(
                'CsPayoutTransaction.*', 'CsOrder.id as order_id', 'CsOrder.parent_id', 'CsOrder.start_datetime', 
                'CsOrder.end_datetime', 'CsOrder.pickup_address', 'CsOrder.increment_id', 'CsOrder.timezone',
                'Renter.first_name', 'Renter.last_name', 'Vehicle.vehicle_name', 'Vehicle.vin_no',
                'CsOrderPayment.rent', 'CsOrderPayment.dia_fee', 'CsOrderPayment.type as payment_type', 
                'CsOrderPayment.tax', 'CsOrderPayment.amount as payment_amount',
                'OrderDepositRule.id as rule_id', 'OrderDepositRule.write_down_allocation', 
                'OrderDepositRule.finance_allocation', 'OrderDepositRule.maintenance_allocation', 
                'OrderDepositRule.insurance_payer'
            );

        if (!empty($conditions['user_id'])) {
            $query->where('CsPayoutTransaction.user_id', $conditions['user_id']);
        }
        if (!empty($conditions['date_from']) || !empty($conditions['date_to']) || !empty($conditions['payout_id'])) {
            $query->leftJoin('cs_payouts as CsPayout', 'CsPayout.id', '=', 'CsPayoutTransaction.cs_payout_id')
                  ->whereNotNull('CsPayout.id');
            if (!empty($conditions['date_from'])) $query->where('CsPayout.processed_on', '>=', $conditions['date_from']);
            if (!empty($conditions['date_to'])) $query->where('CsPayout.processed_on', '<=', $conditions['date_to']);
            if (!empty($conditions['payout_id'])) $query->where('CsPayout.id', $conditions['payout_id']);
        }

        return $query->orderBy('CsPayoutTransaction.id', 'DESC')->get();
    }

    public function streamPayoutCsv($ordersData, $userId) {
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=payout.csv",
        ];

        $callback = function() use ($ordersData, $userId) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No', 'Booking No', 'Type', 'Car', 'VIN', 'Start Date', 'End Date', 'Driver Name', 'Usage Fee', 'Booking Fee', 'Tax', 'Gross Usage Fee', 'Refund', 'DIA Commission', 'Misc Fee', 'Transfer Amount', 'Reconciliation', 'Actual Paid Amount', 'Payout#']);
            
            foreach ($ordersData as $index => $row) {
                // Logic for calculation can be added here mirroring CakePHP...
                fputcsv($file, [
                    $index + 1, $row->increment_id, $row->parent_id ? "Extended" : "",
                    $row->vehicle_name, $row->vin_no, $row->start_datetime, $row->end_datetime,
                    $row->first_name . ' ' . $row->last_name, $row->rent, $row->dia_fee, $row->tax,
                    $row->payment_amount, $row->refund, 0, 0, $row->amount, 0, $row->amount, $row->cs_payout_id
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
