<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\PromoTerm;
use App\Models\Legacy\PromotionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait ReportTrait
{

    protected function _details(Request $request)
    {
        $request = new Request();

        $id = $request->input('order');
        $csorder = [];

        // $subOrders = [];
        // $lastOrder = [];
        // $payments = [];
        // $downpaymentPaid = 0;
        // $Siblingbooking = [];
        // $extlogs = [];
        // $calculation = [];
        // $insurance_payer = '';
        // $totalGrandPaid = 0;
        // $OrderDepositRule = [];
        // $totalDiaFee = 0;

        if (!empty($id)) {
            $orderRow = DB::table('cs_orders')->where('id', $id)->first();
            $userRow = $orderRow
                ? DB::table('users')->where('id', $orderRow->renter_id)->first()
                : null;

            if ($orderRow !== null) {
                $csorder = [
                    'CsOrder' => (array) $orderRow,
                    'User' => $userRow ? [
                        'first_name' => $userRow->first_name,
                        'last_name' => $userRow->last_name,
                        'contact_number' => $userRow->contact_number ?? null,
                    ] : [],
                ];

                $agg = DB::table('cs_orders')
                    ->where(function ($q) use ($id) {
                        $q->where('parent_id', $id)->orWhere('id', $id);
                    })
                    ->selectRaw(
                        'SUM(rent) as rent, SUM(dia_fee) as dia_fee, SUM((tax + emf_tax)) as tax, SUM(initial_fee) as initial_fee, '
                        . 'SUM(initial_fee_tax) as initial_fee_tax, SUM(extra_mileage_fee) as extra_mileage_fee, SUM(lateness_fee) as lateness_fee, '
                        . 'SUM(damage_fee) as damage_fee, SUM(uncleanness_fee) as uncleanness_fee, SUM(insurance_amt) as insurance_amt, '
                        . 'SUM(dia_insu) as dia_insu, SUM(toll) as toll, SUM(pending_toll) as pending_toll, SUM(end_odometer) as end_odometer, '
                        . 'SUM(initial_discount) as initial_discount, SUM(discount) as discount'
                    )
                    ->first();

                $subOrders = [0 => (array) $agg];

                $lastOrder = ['CsOrder' => (array) $orderRow];
                if ((int) $orderRow->status === 3) {
                    $child = DB::table('cs_orders')
                        ->where('parent_id', $id)
                        ->orderByDesc('id')
                        ->first();
                    if ($child !== null) {
                        $lastOrder = ['CsOrder' => (array) $child];
                    }
                }

                $realBookingId = (int) $orderRow->id;
                if (!empty($orderRow->parent_id)) {
                    $realBookingId = (int) $orderRow->parent_id;
                }

                $rule = DB::table('cs_order_deposit_rules')
                    ->where('cs_order_id', $realBookingId)
                    ->orderByDesc('id')
                    ->first();

                $OrderDepositRule = $rule !== null ? ['OrderDepositRule' => (array) $rule] : ['OrderDepositRule' => []];

                $Siblingbooking = DB::table('cs_orders')
                    ->where(function ($q) use ($orderRow, $id) {
                        $q->where('parent_id', $orderRow->id)->orWhere('id', $orderRow->id);
                    })
                    ->pluck('increment_id', 'id')
                    ->toArray();

                $Siblingbookings = array_map('intval', array_keys($Siblingbooking));

                $lastUserId = $lastOrder['CsOrder']['user_id'] ?? $orderRow->user_id;
                $revSetting = DB::table('rev_settings')->where('user_id', $lastUserId)->first();
                $revshare = ($revSetting && !empty($revSetting->rental_rev))
                    ? (float) $revSetting->rental_rev
                    : (float) config('legacy.OWNER_PART', 85);
                $diAFee = 100 - $revshare;

                $paymentRows = DB::table('cs_order_payments')
                    ->whereIn('cs_order_id', $Siblingbookings !== [] ? $Siblingbookings : [-1])
                    ->where('status', 1)
                    ->select('id', 'rent', 'amount', 'tax', 'dia_fee', 'type', 'charged_at')
                    ->get();

                $totalPaid = 0;
                $paidInitialFee = 0;
                foreach ($paymentRows as $payment) {
                    $p = (array) $payment;
                    $totalGrandPaid += (float) $p['amount'];
                    if (in_array((int) $p['type'], [2, 19, 16], true)) {
                        $totalPaid += ((float) $p['amount'] - (float) $p['tax'] - (float) $p['dia_fee']);
                        $totalDiaFee += (((float) $p['amount'] - (float) $p['tax'] - (float) $p['dia_fee']) * $diAFee / 100);
                    }
                    if (in_array((int) $p['type'], [3], true)) {
                        $paidInitialFee += ((float) $p['amount'] - (float) $p['tax']);
                        $totalDiaFee += (((float) $p['amount'] - (float) $p['tax'] - (float) $p['dia_fee']) * $diAFee / 100);
                    }
                    $payments[] = ['CsOrderPayment' => $p];
                }

                $downpaymentPaid = $totalPaid + $paidInitialFee;

                $extRows = DB::table('cs_order_extlogs as OrderExtlog')
                    ->leftJoin('users as Owner', 'Owner.id', '=', 'OrderExtlog.owner')
                    ->whereIn('OrderExtlog.cs_order_id', $Siblingbookings !== [] ? $Siblingbookings : [-1])
                    ->select(
                        'OrderExtlog.*',
                        'Owner.first_name as owner_first_name',
                        'Owner.last_name as owner_last_name'
                    )
                    ->orderByDesc('OrderExtlog.id')
                    ->get();

                foreach ($extRows as $er) {
                    $e = (array) $er;
                    $ownerFirst = $e['owner_first_name'] ?? null;
                    $ownerLast = $e['owner_last_name'] ?? null;
                    unset($e['owner_first_name'], $e['owner_last_name']);
                    $extlogs[] = [
                        'OrderExtlog' => $e,
                        'Owner' => [
                            'first_name' => $ownerFirst,
                            'last_name' => $ownerLast,
                        ],
                    ];
                }

                $calculation = [];
                if (!empty($OrderDepositRule['OrderDepositRule']['calculation'])) {
                    $decoded = json_decode($OrderDepositRule['OrderDepositRule']['calculation'], true);
                    $calculation = is_array($decoded) ? $decoded : [];
                }

                if ($rule !== null && isset($rule->insurance_payer)) {
                    $insurance_payer = $this->commonService->getInsurancePayer((int) $rule->insurance_payer);
                }
            }
        }

        return compact(
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
        );
    }

}
