<?php

namespace App\Http\Controllers\Cloud\Report;

use App\Http\Controllers\Cloud\Report\Concerns\CloudReportAccess;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PastduesController extends LegacyAppController
{
    use CloudReportAccess;

    protected int $recordsPerPage = 50;

    public function index(Request $request)
    {
        if ($redirect = $this->guardCloudReportAccess()) {
            return $redirect;
        }

        [$dealers, $dealerIds] = $this->managedOwnerDealersQuery($this->cloudParentAdminId());

        $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));

        $sessKey = 'pastdues_limit';
        $limit = $this->getPageLimit($request, $sessKey, $this->recordsPerPage);

        $now = date('Y-m-d H:i:s');

        $latestExtSub = DB::table('cs_order_extlogs as oel')
            ->select('oel.*')
            ->joinSub(
                DB::table('cs_order_extlogs')
                    ->selectRaw('cs_order_id, MAX(id) as max_id')
                    ->groupBy('cs_order_id'),
                'mx',
                function ($join) {
                    $join->on('mx.cs_order_id', '=', 'oel.cs_order_id')
                        ->on('mx.max_id', '=', 'oel.id');
                }
            );

        $query = DB::table('cs_orders as CsOrder')
            ->leftJoinSub($latestExtSub, 'OrderExtlog', 'OrderExtlog.cs_order_id', '=', 'CsOrder.id')
            ->select(
                'CsOrder.id',
                'CsOrder.increment_id',
                'CsOrder.parent_id',
                'OrderExtlog.ext_date',
                'OrderExtlog.note'
            )
            ->whereNotIn('CsOrder.status', [2, 3])
            ->where(function ($w) use ($now) {
                $w->where('CsOrder.end_datetime', '<', $now)
                    ->orWhere('CsOrder.payment_status', 2)
                    ->orWhere('CsOrder.insu_status', 2)
                    ->orWhere('CsOrder.dpa_status', 2)
                    ->orWhere('CsOrder.infee_status', 2)
                    ->orWhere('CsOrder.dia_insu_status', 2);
            })
            ->orderByDesc('CsOrder.id')
            ->groupBy('CsOrder.id', 'CsOrder.increment_id', 'CsOrder.parent_id', 'OrderExtlog.ext_date', 'OrderExtlog.note');

        if ($dealerid !== '' && $dealerid !== null) {
            $query->where('CsOrder.user_id', (int) $dealerid);
        } elseif ($dealerIds !== []) {
            $query->whereIn('CsOrder.user_id', $dealerIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        $paginator = $query->paginate($limit)->withQueryString();

        $paginator->getCollection()->transform(function ($row) {
            $r = (array) $row;
            $orderExtlog = [];
            if (array_key_exists('ext_date', $r) && $r['ext_date'] !== null) {
                $orderExtlog[] = [
                    'ext_date' => $r['ext_date'],
                    'note' => $r['note'] ?? null,
                ];
            }

            return [
                'CsOrder' => [
                    'id' => $r['id'],
                    'increment_id' => $r['increment_id'],
                    'parent_id' => $r['parent_id'],
                ],
                'OrderExtlog' => $orderExtlog,
            ];
        });

        return view('cloud.report.pastdues.index', [
            'title_for_layout' => 'Past Due Report',
            'lists' => $paginator,
            'dealerid' => $dealerid,
            'dealers' => $dealers,
            'Record' => ['limit' => $limit],
        ]);
    }

    /**
     * @return View|RedirectResponse
     */
    public function logs(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $order = $request->input('order');
        $lists = DB::table('cs_order_extlogs as OrderExtlog')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'OrderExtlog.owner')
            ->where('OrderExtlog.cs_order_id', $order)
            ->select(
                'OrderExtlog.*',
                'Owner.first_name as owner_first_name',
                'Owner.last_name as owner_last_name'
            )
            ->orderByDesc('OrderExtlog.id')
            ->get()
            ->map(function ($row) {
                $r = (array) $row;
                $ownerFirst = $r['owner_first_name'] ?? null;
                $ownerLast = $r['owner_last_name'] ?? null;
                unset($r['owner_first_name'], $r['owner_last_name']);

                return [
                    'OrderExtlog' => $r,
                    'Owner' => [
                        'first_name' => $ownerFirst,
                        'last_name' => $ownerLast,
                    ],
                ];
            })
            ->all();

        return view('cloud.report.pastdues.logs', ['lists' => $lists]);
    }

    /**
     * @return View|RedirectResponse
     */
    public function details(Request $request, Common $common)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $id = $request->input('order');
        $viewData = $this->buildPastdueDetailsViewData($id, $common);

        return view('cloud.report.pastdues.details', $viewData);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPastdueDetailsViewData(mixed $id, Common $common): array
    {
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

        if (! empty($id)) {
            $id = (int) $id;

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
                if (! empty($orderRow->parent_id)) {
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
                $revshare = ($revSetting && ! empty($revSetting->rental_rev))
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
                if (! empty($OrderDepositRule['OrderDepositRule']['calculation'])) {
                    $decoded = json_decode($OrderDepositRule['OrderDepositRule']['calculation'], true);
                    $calculation = is_array($decoded) ? $decoded : [];
                }

                if ($rule !== null && isset($rule->insurance_payer)) {
                    $insurance_payer = $common->getInsurancePayer((int) $rule->insurance_payer);
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
