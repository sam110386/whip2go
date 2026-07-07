<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\RevSetting;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class PayoutsController extends LegacyAppController
{
    public function index(Request $request)
    {

        if ($request->input('search') === 'EXPORT') {
            return $this->adminExport($request);
        }

        $title = "Payouts";
        $sessionLimitName = "payouts_limit";
        $dateFrom = $request->input('Search.date_from') ?? $request->input('date_from');
        $dateTo = $request->input('Search.date_to') ?? $request->input('date_to');
        $listType = $request->input('Search.listtype') ?? $request->input('listtype');
        $payoutId = $request->input('Search.payout_id') ?? $request->input('payout_id');
        $userId = $request->input('Search.user_id') ?? $request->input('user_id');


        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sessionLimitName, $limit);
        } else {
            $limit = $request->session()->get($sessionLimitName, $this->recordsPerPage);
        }

        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if (empty($listType)) {

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = Carbon::now()->format('Y-m-d');
            }

            $query = CsPayout::query()
                ->when($dateFrom, function ($query, $dateFrom) {
                    $query->where('processed_on', '>=', Carbon::parse($dateFrom)->toDateTimeString());
                })
                ->when($dateTo, function ($query, $dateTo) {
                    $query->where('processed_on', '<=', Carbon::parse($dateTo)->toDateTimeString());
                })
                ->when($payoutId, function ($query, $payoutId) {
                    $query->where('id', $payoutId);
                })
                ->when($userId, function ($query, $userId) {
                    $query->where('user_id', $userId);
                });

            if ($sort === 'id') {
                $query->orderBy('id', $direction);
            } else {
                $query->orderBy('processed_on', 'desc');
            }

            $payoutLists = $query->paginate($limit);

        } else {
            $payoutLists = CsPayoutTransaction::query()
                ->with([
                    'csOrder:id,vehicle_id,renter_id,increment_id',
                    'csOrder.vehicle:id,vehicle_name',
                    'csOrder.renter:id,first_name,last_name'
                ])
                ->where('status', 1)
                ->when($userId, function ($query, $userId) {
                    $query->where('user_id', $userId);
                })
                ->orderBy('id', 'desc')
                ->paginate($limit);
        }

        $viewData = [
            'title' => $title,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'listtype' => $listType,
            'payout_id' => $payoutId,
            'user_id' => $userId,
            'limit' => $limit,
            'refundTypeValue' => $this->commonService->getRefundType(),
            'paymentTypeValue' => $this->commonService->getPayoutTypeValue(1),
            'payoutlists' => $payoutLists,
        ];

        if ($request->ajax()) {
            return view('admin.payouts.elements.index', $viewData);
        }

        return view('admin.payouts.index', $viewData);
    }
    public function adminExport(Request $request)
    {
        $dateFrom = $request->input('date_from') ?? $request->input('Search.date_from');
        $dateTo = $request->input('date_to') ?? $request->input('Search.date_to');
        $payoutId = $request->input('payout_id') ?? $request->input('Search.payout_id');
        $userId = $request->input('user_id') ?? $request->input('Search.user_id');

        if (empty($userId)) {
            return back()->with('error', 'Sorry, please choose dealer first.');
        }

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = Carbon::now()->toDateString();
        }

        $query = CsPayoutTransaction::query()
            ->select([
                'cs_payout_transactions.*',
                'cs_orders.id as order_id',
                'cs_orders.parent_id',
                'cs_orders.start_datetime',
                'cs_orders.end_datetime',
                'cs_orders.pickup_address',
                'cs_orders.increment_id',
                'cs_orders.timezone',
                'renter.first_name',
                'renter.last_name',
                'vehicles.vehicle_name',
                'vehicles.vin_no',
                'cs_order_payments.rent',
                'cs_order_payments.dia_fee',
                'cs_order_payments.type as payment_type',
                'cs_order_payments.tax',
                'cs_order_payments.amount as payment_amount',
                'cs_order_deposit_rules.id as deposit_rule_id',
                'cs_order_deposit_rules.cs_order_id as deposit_order_id',
                'cs_order_deposit_rules.write_down_allocation',
                'cs_order_deposit_rules.finance_allocation',
                'cs_order_deposit_rules.maintenance_allocation',
                'cs_order_deposit_rules.insurance_payer',
            ])
            ->leftJoin('cs_orders', 'cs_orders.id', '=', 'cs_payout_transactions.cs_order_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_orders.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'cs_orders.renter_id')
            ->leftJoin('cs_order_payments', function ($join) {
                $join->on('cs_order_payments.id', '=', 'cs_payout_transactions.cs_payment_id')
                    ->where('cs_order_payments.status', 1);
            })
            ->leftJoin('cs_order_deposit_rules', function ($join) {
                $join->on('cs_order_deposit_rules.cs_order_id', '=', 'cs_orders.id')
                    ->orOn('cs_order_deposit_rules.cs_order_id', '=', 'cs_orders.parent_id');
            });

        if (!empty($dateFrom) || !empty($dateTo) || !empty($payoutId) || !empty($userId)) {

            $query->leftJoin('cs_payouts', 'cs_payouts.id', '=', 'cs_payout_transactions.cs_payout_id')
                ->whereNotNull('cs_payouts.id');

            if (!empty($dateFrom)) {
                $query->where('cs_payouts.processed_on', '>=', Carbon::parse($dateFrom)->toDateString());
            }

            if (!empty($dateTo)) {
                $query->where('cs_payouts.processed_on', '<=', Carbon::parse($dateTo)->toDateString());
            }

            if (!empty($payoutId)) {
                $query->where('cs_payouts.id', $payoutId);
            }

            if (!empty($userId)) {
                $query->where('cs_payouts.user_id', $userId);
            }
        }

        $ordersData = $query->orderBy('cs_payout_transactions.id', 'DESC')
            ->limit(1500)
            ->get();

        if ($ordersData->isEmpty()) {
            return back()->with('error', 'Sorry, No record found for selected criteria.');
        }

        $paymentTypeValue = $this->commonService->getPayoutTypeValue(1);
        $revSettingObj = RevSetting::where('user_id', $userId)->first();
        $taxIncluded = isset($revSettingObj->tax_included) && $revSettingObj->tax_included == 0 ? false : true;
        $revshare = $revSettingObj->rev ?? config('legacy.OWNER_PART', 85);

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=payout.csv',
        ];

        return response()->streamDownload(function () use ($ordersData, $taxIncluded, $revshare, $paymentTypeValue) {
            $fp = fopen('php://output', 'w');

            $csvHeader = [
                "No",
                "Booking No",
                "Type",
                "Car",
                "VIN",
                "Start Date",
                "End Date",
                "Driver Name",
                "Usage Fee",
                "Booking Fee",
                "TAX",
                "Gross Usage Fee",
                "Refund",
                "DIA Commission",
                "Misc Fee",
                "Transfer Amount",
                "Write Down Allocation",
                "Finance Allocation",
                "Maintenance Allocation",
                "Tax",
                "Misc Fee",
                "Reconciliation",
                "Actual Paid Amount",
                "Payout#",
                "Payment Type"
            ];
            fputcsv($fp, $csvHeader);

            $i = 1;
            foreach ($ordersData as $row) {
                $amount = $diacomission = 0;
                $isRefund = $row->refund > 0;

                if (!$isRefund && in_array($row->payment_type, [2, 16, 3])) {
                    if ($taxIncluded) {
                        $amount = sprintf('%0.2f', (($row->payment_amount - $row->dia_fee) * $revshare / 100));
                        $diacomission = $row->payment_amount - $amount;
                    } else {
                        $amount = sprintf('%0.2f', ($row->rent * $revshare / 100)) + $row->tax;
                        $diacomission = sprintf('%0.2f', ($row->rent * (100 - $revshare) / 100));
                    }
                }

                if (!$isRefund && $row->payment_type == 5) {
                    $amount = sprintf('%0.2f', ($row->payment_amount * $revshare / 100));
                    $diacomission = $row->payment_amount - $amount;
                }

                if (!$isRefund && $row->payment_type == 7) {
                    $amount = sprintf('%0.2f', ($row->payment_amount * $revshare / 100));
                    $diacomission = $row->payment_amount - $amount;
                }

                if (!$isRefund && $row->payment_type == 6) {
                    $amount = sprintf('%0.2f', ($row->payment_amount * 90 / 100));
                }

                if (!$isRefund && in_array($row->payment_type, [4, 14])) {
                    $amount = ($row->insurance_payer == 4) ? 0 : $row->payment_amount;
                }

                $stripeConversionFee = ($isRefund) ? 0 : (($row->stripe_amt > 0) ? sprintf('%0.2f', ($row->amount - $row->stripe_amt)) : 0);
                $hasAllocationData = !$isRefund && !empty($row->write_down_allocation) && !in_array($row->payment_type, [4, 14]);
                $baseAllocationAmt = ($row->payment_amount - $row->dia_fee - $row->tax);
                $writeDownAllocation = $hasAllocationData ? sprintf('%0.2f', ($baseAllocationAmt * $row->write_down_allocation / 100)) : 0;
                $financeAllocation = $hasAllocationData ? sprintf('%0.2f', ($baseAllocationAmt * $row->finance_allocation / 100)) : 0;
                $maintenanceAllocation = $hasAllocationData ? sprintf('%0.2f', ($baseAllocationAmt * $row->maintenance_allocation / 100)) : 0;
                $startDate = $row->start_datetime ? Carbon::parse($row->start_datetime, 'UTC')->setTimezone($row->timezone)->format('m/d/Y') : '';
                $endDate = $row->end_datetime ? Carbon::parse($row->end_datetime, 'UTC')->setTimezone($row->timezone)->format('m/d/Y') : '';

                $csvRow = [
                    $i++,
                    $row->increment_id,
                    $row->parent_id ? "Extended" : "",
                    $row->vehicle_name,
                    $row->vin_no,
                    $startDate,
                    $endDate,
                    $row->first_name . ' ' . $row->last_name,
                    $isRefund ? 0 : $row->rent,
                    $isRefund ? 0 : $row->dia_fee,
                    $isRefund ? 0 : $row->tax,
                    $isRefund ? 0 : $row->payment_amount,
                    $row->refund,
                    $isRefund ? 0 : number_format($diacomission, 2, '.', ''),
                    $stripeConversionFee,
                    $isRefund ? 0 : sprintf('%.2f', ($row->amount - $stripeConversionFee)),
                    $writeDownAllocation,
                    $financeAllocation,
                    $maintenanceAllocation,
                    $row->tax,
                    $stripeConversionFee,
                    $isRefund ? 0 : sprintf('%.2f', ($writeDownAllocation + $financeAllocation + $maintenanceAllocation + $row->tax - $stripeConversionFee)),
                    $isRefund ? 0 : (($row->stripe_amt > 0) ? $row->stripe_amt : $row->amount),
                    $row->cs_payout_id,
                    $paymentTypeValue[$row->payment_type] ?? "",
                ];

                fputcsv($fp, $csvRow);
            }

            fclose($fp);
        }, 'payout.csv', $headers);

    }
    public function transactions(Request $request)
    {
        $csPayoutId = $request->input('payoutid');

        $transactions = CsPayoutTransaction::where('cs_payout_id', $csPayoutId)
            ->with([
                'csOrder:id,increment_id,vehicle_id,renter_id,start_datetime',
                'csOrder.vehicle:id,vehicle_name',
                'csOrder.renter:id,first_name,last_name'
            ])
            ->orderBy('id', 'DESC')
            ->get();

        return view('admin.payouts.transactions', [
            'refundTypeValue' => $this->commonService->getRefundType(),
            'paymentTypeValue' => $this->commonService->getPayoutTypeValue(1),
            'transactions' => $transactions
        ]);
    }
}
