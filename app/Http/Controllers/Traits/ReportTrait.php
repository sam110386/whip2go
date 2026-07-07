<?php
namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\SummaryReport;
use Carbon\Carbon;

trait ReportTrait
{
    protected function _details(Request $request)
    {
        $id = $request->input('order');
        $csorder = [];
        $subOrders = [];
        $lastOrder = [];
        $payments = [];
        $downpaymentPaid = 0;
        $Siblingbooking = [];
        $extlogs = [];
        $calculation = [];
        $insurance_payer = '';
        $totalGrandPaid = 0;
        $OrderDepositRule = [];
        $totalDiaFee = 0;

        if (!empty($id)) {
            $lastOrder = $csorder = CsOrder::with('user:id,first_name,last_name,contact_number')->find($id);
            $subOrders = CsOrder::query()
                ->selectRaw('SUM(rent) as rent,
                            SUM(dia_fee) as dia_fee,
                            SUM(tax + emf_tax) as tax,
                            SUM(initial_fee) as initial_fee,
                            SUM(initial_fee_tax) as initial_fee_tax,
                            SUM(extra_mileage_fee) as extra_mileage_fee,
                            SUM(lateness_fee) as lateness_fee,
                            SUM(damage_fee) as damage_fee,
                            SUM(uncleanness_fee) as uncleanness_fee,
                            SUM(insurance_amt) as insurance_amt,
                            SUM(dia_insu) as dia_insu,
                            SUM(toll) as toll,
                            SUM(pending_toll) as pending_toll,
                            SUM(end_odometer) as end_odometer,
                            SUM(initial_discount) as initial_discount,
                            SUM(discount) as discount
                        ')
                ->where('parent_id', $id)
                ->orWhere('id', $id)
                ->first();

            if ($csorder->status == 3) {
                $lastOrder = CsOrder::where('parent_id', $id)->orderBy('id', 'desc')->first();
            }

            $realBookingId = $csorder->parent_id ?? $csorder->id;
            $OrderDepositRule = OrderDepositRule::where('cs_order_id', $realBookingId)->first();
            $siblingBooking = CsOrder::where('id', $csorder->id)
                ->orWhere('parent_id', $csorder->id)
                ->pluck('increment_id', 'id');
            $siblingBookings = $siblingBooking->keys()->all();
            $RevSetting = RevSetting::where('user_id', $lastOrder->user_id)->first();
            $revshare = $RevSetting->rental_rev ?? config('legacy.OWNER_PART', 0);
            $diAFee = (100 - $revshare * 1);
            $payments = CsOrderPayment::whereIn('cs_order_id', $siblingBookings)
                ->where('status', 1)
                ->get(['id', 'transaction_id', 'rent', 'amount', 'tax', 'dia_fee', 'type', 'charged_at']);
            $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = 0;

            if (!empty($payments)) {
                foreach ($payments as $payment) {
                    $totalGrandPaid += $payment->amount;

                    if (in_array($payment->type, [2, 19, 16])) {
                        $totalPaid += ($payment->amount - $payment->tax - $payment->dia_fee);
                        $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                    }

                    if (in_array($payment->type, [3])) {
                        $paidInitialFee += ($payment->amount - $payment->tax);
                        $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                    }
                }
            }

            $downpaymentPaid = $totalPaid + $paidInitialFee;
            $extlogs = $this->_getExtLogs($siblingBookings);

            $calculation = !empty($OrderDepositRule->calculation) ? json_decode($OrderDepositRule->calculation, 1) : [];
            $insurance_payer = $this->commonService->getInsurancePayer($OrderDepositRule->insurance_payer);

        }

        return view('report.pastdues.details', compact(
            'csorder',
            'subOrders',
            'lastOrder',
            'payments',
            'downpaymentPaid',
            'Siblingbooking',
            'extlogs',
            'calculation',
            'insurance_payer',
            'totalGrandPaid',
            'OrderDepositRule',
            'totalDiaFee'
        ));
    }
    private function _getExtLogs($id)
    {
        return OrderExtlog::with('owner:id,first_name,last_name')
            ->where('cs_order_id', $id)
            ->orderBy('id', 'DESC')
            ->get();
    }
    private function exportReport()
    {
        $records = SummaryReport::orderBy('id', 'ASC')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Revenue_Report.csv',
        ];

        return response()->stream(function () use ($records) {
            $fp = fopen('php://output', 'w');

            if ($fp) {
                $headerRow = [
                    '#',
                    'Start',
                    'End',
                    'Rent',
                    'EMF',
                    'DIA FEE',
                    'Tax',
                    'Lateness',
                    'Total Rent',
                    'Rental Revenue This Month',
                    'Past Revenue',
                    'Deferred Revenue',
                    'Total Revenue',
                    'Total Collected This Month',
                    'Rental Wallet Refund',
                    'Rental Stripe Refund',
                    'Net Collected This Month',
                    'Already Collected',
                    'Differ Collected',
                    'Total Collected',
                    'Uncollected',
                    'Total Insurance',
                    'Insu. This Month',
                    'Past Insu.',
                    'Deferred Insu.',
                    'Insu. Wallet Refund',
                    'Insu. Stripe Refund',
                    'Insu. Collected This Month',
                    'Collected Past Insu.',
                    'Collected Deferred Insu.',
                    'Total Insu. Collected',
                    'Total Insu. Calculated',
                    'Insu. Uncollected',
                    'Current Payout Owed',
                    'Past Payout Owed',
                    'Differ Payout Owed',
                    'Total Payout Owed',
                    'Paid out in Month',
                    'Stripe Fee',
                    'Net Paid out in Month',
                    'Paid out in Differ Month',
                    'Total Paid out',
                    'Dealer Owed',
                    'Wallet Refund',
                    'Stripe Refund'
                ];

                fputcsv($fp, $headerRow);

                foreach ($records as $list) {
                    $start = $list->start_datetime ? Carbon::parse($list->start_datetime)->format('Y-m-d h:i A') : '';
                    $end = $list->end_datetime ? Carbon::parse($list->end_datetime)->format('Y-m-d h:i A') : '';

                    $total = sprintf('%0.2f', (
                        $list->initial_fee
                        + $list->rent
                        + $list->extra_mileage_fee
                        + $list->dia_fee
                        + $list->tax
                        + $list->lateness_fee
                        + $list->past_m_initial_fee
                        + $list->past_m_rent
                        + $list->past_m_emf
                        + $list->past_m_dia_fee
                        + $list->past_m_tax
                        + $list->past_m_lateness_fee
                        + $list->differ_m_initial_fee
                        + $list->differ_m_rent
                        + $list->differ_m_emf
                        + $list->differ_m_dia_fee
                        + $list->differ_m_tax
                        + $list->differ_m_lateness_fee
                    ));

                    $Revtotal = sprintf('%0.2f', (
                        $list->initial_fee
                        + $list->rent
                        + $list->extra_mileage_fee
                        + $list->dia_fee
                        + $list->tax
                        + $list->lateness_fee
                    ));

                    $pasttotal = sprintf('%0.2f', (
                        $list->past_m_initial_fee
                        + $list->past_m_rent
                        + $list->past_m_emf
                        + $list->past_m_dia_fee
                        + $list->past_m_tax
                        + $list->past_m_lateness_fee
                    ));

                    $Diffetotal = sprintf('%0.2f', (
                        $list->differ_m_initial_fee
                        + $list->differ_m_rent
                        + $list->differ_m_emf
                        + $list->differ_m_dia_fee
                        + $list->differ_m_tax
                        + $list->differ_m_lateness_fee
                    ));

                    $total_collected = $list->total_collected;
                    $past_m_total_collected = $list->past_m_total_collected;
                    $differ_m_total_collected = $list->differ_m_total_collected;
                    $pastinsu_calculated = ($list->past_m_dia_insu + $list->past_m_insurance_amt);
                    $differinsu_calculated = ($list->differ_m_dia_insu + $list->differ_m_insurance_amt);

                    $collectedinsu = sprintf('%0.2f', (
                        ($list->insurance_collected + $list->past_m_insurance_collected)
                        + ($list->dia_insu_collected) + ($list->past_m_dia_insu_collected)
                    ));

                    $currentinsu_calculated = sprintf('%0.2f', (
                        $list->insurance_amt
                        + $list->dia_insu
                    ));

                    $row = [
                        '#' => $list->increment_id,
                        'Start' => $start,
                        'End' => $end,
                        'Rent' => ($list->rent + $list->initial_fee + $list->past_m_rent + $list->past_m_initial_fee + $list->differ_m_rent + $list->differ_m_initial_fee),
                        'EMF' => ($list->extra_mileage_fee + $list->past_m_emf + $list->differ_m_emf),
                        'DIA FEE' => ($list->dia_fee + $list->past_m_dia_fee + $list->differ_m_dia_fee),
                        'Tax' => ($list->tax + $list->past_m_tax + $list->differ_m_tax),
                        'Lateness' => ($list->lateness_fee + $list->past_m_lateness_fee + $list->differ_m_lateness_fee),
                        'Total Rent' => $total,
                        'Rental Revenue This Month' => $Revtotal,
                        'Past Revenue' => $pasttotal,
                        'Deferred Revenue' => $Diffetotal,
                        'Total Revenue' => sprintf('%0.2f', ($Revtotal + $Diffetotal + $pasttotal)),
                        'Total Collected This Month' => $total_collected,
                        "Rental Wallet Refund" => $list->rent_wallet_refund,
                        "Rental Stripe Refund" => $list->rent_stripe_refund,
                        "Net Collected This Month" => sprintf('%0.2f', ($total_collected - ($list->rent_wallet_refund + $list->rent_stripe_refund))),
                        'Already Collected' => $past_m_total_collected,
                        'Differ Collected' => $differ_m_total_collected,
                        'Total Collected' => sprintf('%0.2f', (($total_collected + $past_m_total_collected) - $list->rent_stripe_refund)),
                        'Uncollected' => sprintf('%0.2f', (($Revtotal + $pasttotal) - ($total_collected + $past_m_total_collected))),
                        "Total Insurance" => ($currentinsu_calculated + $pastinsu_calculated + $differinsu_calculated),
                        'Insu. This Month' => $currentinsu_calculated,
                        'Past Insu.' => $pastinsu_calculated,
                        'Deferred Insu.' => $differinsu_calculated,
                        "Insu. Wallet Refund" => $list->insu_wallet_refund,
                        "Insu. Stripe Refund" => $list->insu_stripe_refund,
                        'Insu. Collected This Month' => ($list->insurance_collected + $list->dia_insu_collected),
                        'Collected Past Insu.' => ($list->past_m_insurance_collected + $list->past_m_dia_insu_collected),
                        'Collected Deferred Insu.' => ($list->differ_m_insurance_amt + $list->differ_m_dia_insu_collected),
                        'Total Insu. Collected' => ($collectedinsu - $list->insu_stripe_refund - $list->insu_wallet_refund),
                        'Total Insu. Calculated' => ($currentinsu_calculated + $pastinsu_calculated + $differinsu_calculated),
                        'Insu. Uncollected' => sprintf('%0.2f', (($currentinsu_calculated + $pastinsu_calculated) - ($collectedinsu - $list->insu_stripe_refund - $list->insu_wallet_refund))),
                        'Current Payout Owed' => $list->dealer_payout,
                        'Past Payout Owed' => $list->past_m_payout,
                        'Differ Payout Owed' => $list->differ_m_dealer_payout,
                        'Total Payout Owed' => $list->total_payout,
                        'Paid out in Month' => $list->paid_payout,
                        'Stripe Fee' => ($list->net_paid_payout > 0 ? sprintf('%0.2f', ($list->paid_payout - $list->net_paid_payout)) : 0),
                        'Net Paid out in Month' => $list->net_paid_payout,
                        'Paid out in Differ Month' => $list->differ_paid_payout,
                        'Total Paid out' => sprintf('%0.2f', ($list->differ_paid_payout + $list->paid_payout)),
                        'Dealer Owed' => sprintf('%0.2f', ($list->differ_paid_payout + $list->paid_payout - $list->total_payout)),
                        'Wallet Refund' => sprintf('%0.2f', $list->wallet_refund),
                        'Stripe Refund' => sprintf('%0.2f', $list->stripe_refund)
                    ];

                    fputcsv($fp, array_values($row));
                }
            }

            fclose($fp);

        }, 200, $headers);
    }
}
