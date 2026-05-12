<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\OrderExtlog;

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

}
