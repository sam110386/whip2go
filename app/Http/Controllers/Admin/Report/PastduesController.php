<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PastduesController extends LegacyAppController
{
    use UsesReportPageLimit;

    protected Common $common;

    public function __construct(Common $common)
    {
        parent::__construct();
        $this->common = $common;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Past Due Report';
        $dealerid = '';

        $query = DB::table('cs_orders')
            ->select('cs_orders.increment_id', 'cs_orders.id', 'cs_orders.parent_id')
            ->whereNotIn('cs_orders.status', [2, 3])
            ->where(function ($q) {
                $q->whereRaw('cs_orders.end_datetime < ?', [date('Y-m-d H:i:s')])
                    ->orWhere('cs_orders.payment_status', 2)
                    ->orWhere('cs_orders.insu_status', 2)
                    ->orWhere('cs_orders.dpa_status', 2)
                    ->orWhere('cs_orders.infee_status', 2)
                    ->orWhere('cs_orders.dia_insu_status', 2);
            })
            ->groupBy('cs_orders.id', 'cs_orders.increment_id', 'cs_orders.parent_id')
            ->orderByDesc('cs_orders.id');

        if ($request->filled('Search') || $request->query->has('dealerid')) {
            $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
            if ($dealerid !== '') {
                $query->where('cs_orders.user_id', $dealerid);
            }
        }

        $limit = $this->getPageLimit($request, 'pastdues_limit', 50);
        $lists = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements.admin_pastdue', compact('lists', 'dealerid', 'title'));
        }

        return view('admin.report.pastdues.index', compact('title', 'lists', 'dealerid'));
    }

    public function logs(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $order = $request->input('order');
        $all = $request->boolean('all');

        $query = DB::table('cs_order_extlogs as OrderExtlog')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'OrderExtlog.owner')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'OrderExtlog.cs_order_id')
            ->select('OrderExtlog.*', 'Owner.first_name as __owner_fn', 'Owner.last_name as __owner_ln', 'CsOrder.increment_id as __increment_id')
            ->orderByDesc('OrderExtlog.id');

        if ($all) {
            $orders = DB::table('cs_orders')
                ->where('id', $order)
                ->orWhere('parent_id', $order)
                ->pluck('id');
            $query->whereIn('OrderExtlog.cs_order_id', $orders);
        } else {
            $query->where('OrderExtlog.cs_order_id', $order);
        }

        $lists = $query->get()->map(function ($r) {
            $a = (array) $r;
            $incrementId = $a['__increment_id'] ?? '';
            $ownerFn = $a['__owner_fn'] ?? '';
            $ownerLn = $a['__owner_ln'] ?? '';
            unset($a['__increment_id'], $a['__owner_fn'], $a['__owner_ln']);

            return [
                'OrderExtlog' => $a,
                'Owner' => ['first_name' => $ownerFn, 'last_name' => $ownerLn],
                'CsOrder' => ['increment_id' => $incrementId],
            ];
        })->all();

        return view('admin.report.pastdues.logs', compact('lists'));
    }

    public function details(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $request->input('order');
        $csorder = [];
        $subOrders = [[]];
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

        if (! empty($id)) {
            $orderRow = DB::table('cs_orders')->where('id', $id)->first();
            if ($orderRow) {
                $userRow = DB::table('users')->where('id', $orderRow->renter_id)->first();
                $csorder = [
                    'CsOrder' => (array) $orderRow,
                    'User' => [
                        'first_name' => $userRow->first_name ?? '',
                        'last_name' => $userRow->last_name ?? '',
                        'contact_number' => $userRow->contact_number ?? '',
                    ],
                ];

                $agg = DB::table('cs_orders')
                    ->where(function ($q) use ($id) {
                        $q->where('parent_id', $id)->orWhere('id', $id);
                    })
                    ->selectRaw('SUM(rent) as rent')
                    ->selectRaw('SUM(dia_fee) as dia_fee')
                    ->selectRaw('SUM((tax + emf_tax)) as tax')
                    ->selectRaw('SUM(initial_fee) as initial_fee')
                    ->selectRaw('SUM(initial_fee_tax) as initial_fee_tax')
                    ->selectRaw('SUM(extra_mileage_fee) as extra_mileage_fee')
                    ->selectRaw('SUM(lateness_fee) as lateness_fee')
                    ->selectRaw('SUM(damage_fee) as damage_fee')
                    ->selectRaw('SUM(uncleanness_fee) as uncleanness_fee')
                    ->selectRaw('SUM(insurance_amt) as insurance_amt')
                    ->selectRaw('SUM(dia_insu) as dia_insu')
                    ->selectRaw('SUM(toll) as toll')
                    ->selectRaw('SUM(pending_toll) as pending_toll')
                    ->selectRaw('SUM(end_odometer) as end_odometer')
                    ->selectRaw('SUM(initial_discount) as initial_discount')
                    ->selectRaw('SUM(discount) as discount')
                    ->first();

                $subOrders = [array_merge(array_fill_keys([
                    'rent', 'dia_fee', 'tax', 'initial_fee', 'initial_fee_tax', 'extra_mileage_fee',
                    'lateness_fee', 'damage_fee', 'uncleanness_fee', 'insurance_amt', 'dia_insu',
                    'toll', 'pending_toll', 'end_odometer', 'initial_discount', 'discount',
                ], 0), (array) $agg)];

                $lastOrder = $csorder;
                if ((int) ($csorder['CsOrder']['status'] ?? 0) === 3) {
                    $lo = DB::table('cs_orders')->where('parent_id', $id)->orderByDesc('id')->first();
                    if ($lo) {
                        $lastOrder = ['CsOrder' => (array) $lo];
                    }
                }

                $realBookingId = (int) $csorder['CsOrder']['id'];
                if (! empty($csorder['CsOrder']['parent_id'])) {
                    $realBookingId = (int) $csorder['CsOrder']['parent_id'];
                }

                $deposit = DB::table('cs_order_deposit_rules')->where('cs_order_id', $realBookingId)->orderByDesc('id')->first();
                $OrderDepositRule = $deposit ? ['OrderDepositRule' => (array) $deposit] : [];

                $Siblingbooking = DB::table('cs_orders')
                    ->where(function ($q) use ($csorder) {
                        $q->where('parent_id', $csorder['CsOrder']['id'])
                            ->orWhere('id', $csorder['CsOrder']['id']);
                    })
                    ->pluck('increment_id', 'id')
                    ->toArray();

                $Siblingbookings = array_keys($Siblingbooking);

                $revRow = DB::table('rev_settings')->where('user_id', $lastOrder['CsOrder']['user_id'] ?? 0)->first();
                $revshare = ($revRow && ! empty($revRow->rental_rev)) ? $revRow->rental_rev : config('legacy.OWNER_PART', 85);
                $diAFee = (100 - (float) $revshare * 1);

                $payments = DB::table('cs_order_payments')
                    ->whereIn('cs_order_id', $Siblingbookings)
                    ->where('status', 1)
                    ->select('id', 'rent', 'amount', 'tax', 'dia_fee', 'type', 'charged_at')
                    ->get()
                    ->map(fn ($p) => ['CsOrderPayment' => (array) $p])
                    ->all();

                $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = 0;
                foreach ($payments as $payment) {
                    $p = $payment['CsOrderPayment'];
                    $totalGrandPaid += (float) ($p['amount'] ?? 0);
                    if (in_array((int) ($p['type'] ?? 0), [2, 19, 16], true)) {
                        $totalPaid += ((float) ($p['amount'] ?? 0) - (float) ($p['tax'] ?? 0) - (float) ($p['dia_fee'] ?? 0));
                        $totalDiaFee += (((float) ($p['amount'] ?? 0) - (float) ($p['tax'] ?? 0) - (float) ($p['dia_fee'] ?? 0)) * $diAFee / 100);
                    }
                    if (in_array((int) ($p['type'] ?? 0), [3], true)) {
                        $paidInitialFee += ((float) ($p['amount'] ?? 0) - (float) ($p['tax'] ?? 0));
                        $totalDiaFee += (((float) ($p['amount'] ?? 0) - (float) ($p['tax'] ?? 0) - (float) ($p['dia_fee'] ?? 0)) * $diAFee / 100);
                    }
                }
                $downpaymentPaid = $totalPaid + $paidInitialFee;

                $extlogs = $this->getExtLogs($Siblingbookings);
                $calculation = ! empty($OrderDepositRule['OrderDepositRule']['calculation'])
                    ? json_decode((string) $OrderDepositRule['OrderDepositRule']['calculation'], true) : [];
                if (isset($OrderDepositRule['OrderDepositRule']['insurance_payer'])) {
                    $insurance_payer = $this->common->getInsurancePayer((int) $OrderDepositRule['OrderDepositRule']['insurance_payer']);
                } else {
                    $insurance_payer = '';
                }
            }
        }

        return view('admin.report.pastdues.details', compact(
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

    /**
     * @param  array<int|string>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function getExtLogs(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('cs_order_extlogs as OrderExtlog')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'OrderExtlog.owner')
            ->whereIn('OrderExtlog.cs_order_id', $ids)
            ->select('OrderExtlog.*', 'Owner.first_name as __owner_fn', 'Owner.last_name as __owner_ln')
            ->orderByDesc('OrderExtlog.id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $a = (array) $r;
            $ownerFn = $a['__owner_fn'] ?? '';
            $ownerLn = $a['__owner_ln'] ?? '';
            unset($a['__owner_fn'], $a['__owner_ln']);
            $out[] = [
                'OrderExtlog' => $a,
                'Owner' => ['first_name' => $ownerFn, 'last_name' => $ownerLn],
            ];
        }

        return $out;
    }
}
