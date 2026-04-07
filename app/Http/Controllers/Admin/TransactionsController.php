<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrderPayment as LegacyCsOrderPayment;
use App\Models\Legacy\CsWallet as LegacyCsWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /**
     * Cake TransactionsController::admin_index — completed/canceled orders (status 2,3) with filters.
     */
    public function admin_index(Request $request)
    {
        $keyword = trim((string)$this->searchInput($request, 'keyword'));
        $fieldname = trim((string)$this->searchInput($request, 'searchin'));
        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));
        $statusType = trim((string)$this->searchInput($request, 'status_type'));
        $transactionId = trim((string)$this->searchInput($request, 'transaction_id'));

        if ($request->isMethod('POST') && $request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['admin_transactions_limit' => $lim]);
            }
        }
        $limit = (int)session('admin_transactions_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $query = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->select(['o.*', 'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name'])
            ->whereIn('o.status', [2, 3]);

        $hasSearch = $request->isMethod('POST')
            || $request->anyFilled([
                'keyword', 'searchin', 'date_from', 'date_to', 'status_type', 'transaction_id',
                'Search.keyword', 'Search.searchin', 'Search.date_from', 'Search.date_to', 'Search.status_type', 'Search.transaction_id',
            ]);

        if ($hasSearch) {
            if ($keyword !== '' && $fieldname === '2') {
                $query->where('o.vehicle_name', $keyword);
            }
            if ($keyword !== '' && $fieldname === '3') {
                $query->where('o.increment_id', $keyword);
            }
            if ($dateFrom !== '') {
                try {
                    $df = Carbon::parse($dateFrom)->startOfDay();
                    $query->where('o.start_datetime', '>=', $df->toDateTimeString());
                } catch (\Throwable $e) {
                }
            }
            if ($dateTo !== '') {
                try {
                    $dt = Carbon::parse($dateTo)->endOfDay();
                    $query->where('o.end_datetime', '<=', $dt->toDateTimeString());
                } catch (\Throwable $e) {
                }
            }
            if ($statusType === 'cancel') {
                $query->where('o.status', 2);
            } elseif ($statusType === 'complete') {
                $query->where('o.status', 3);
            } elseif ($statusType === 'incomplete') {
                $query->whereIn('o.status', [0, 1]);
            }
        }

        if ($transactionId !== '') {
            $query->whereExists(function ($q) use ($transactionId) {
                $q->selectRaw('1')
                    ->from('cs_order_payments as op')
                    ->whereColumn('op.cs_order_id', 'o.id')
                    ->where('op.transaction_id', $transactionId);
            });
        }

        $reportlists = $query->orderByDesc('o.id')->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.transactions.listing', [
                'reportlists' => $reportlists,
            ]);
        }

        return view('admin.transactions.index', [
            'reportlists' => $reportlists,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_type' => $statusType,
            'transaction_id' => $transactionId,
            'limit' => $limit,
        ]);
    }

    /**
     * Cake TransactionsController::admin_usertransactions — driver payment lines (modal + partial refresh).
     *
     * @param  mixed  $partial  Path segment "1" = return table partial only for #transsactionlisting
     */
    public function admin_usertransactions(Request $request, $userid = null, $time = '1 day', $partial = null)
    {
        $uid = (int)($userid ?? $request->input('userid') ?? 0);
        if ($uid <= 0) {
            return response('Invalid user', 400);
        }

        $timeStr = trim((string)($time ?: '1 day'));
        if ($timeStr === '') {
            $timeStr = '1 day';
        }

        $bookingid = (string)$request->input('bookingid', '');
        $currency = (string)$request->input('currency', 'USD');

        $dateFrom = Carbon::now()->modify('-' . $timeStr)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $lim = (int)session('admin_transactions_limit', 50);
        if ($lim < 1) {
            $lim = 50;
        }

        $basePayments = DB::table('cs_order_payments as p')
            ->join('cs_orders as o', 'o.id', '=', 'p.cs_order_id')
            ->where('o.renter_id', $uid)
            ->where('p.status', 1)
            ->whereDate('p.created', '>=', $dateFrom)
            ->whereDate('p.created', '<=', $dateTo);

        $total = (float)(clone $basePayments)->sum('p.amount');
        $reportlists = (clone $basePayments)
            ->select([
                'p.*',
                'o.increment_id',
                'o.start_datetime',
                'o.end_datetime',
                'o.timezone',
            ])
            ->orderByDesc('p.id')
            ->limit(min($lim, 500))
            ->get();
        $walletBalance = LegacyCsWallet::query()->where('user_id', $uid)->value('balance') ?? 0;

        $listVars = [
            'rows' => $reportlists,
            'total' => $total,
            'userid' => $uid,
        ];

        $partialOnly = $partial !== null && $partial !== '' && (string)$partial === '1';

        if ($partialOnly) {
            return view('admin.transactions.usertransactions_list', $listVars);
        }

        return view('admin.transactions.usertransactions', [
            'rows' => $reportlists,
            'total' => $total,
            'wallet_balance' => $walletBalance,
            'userid' => $uid,
            'time' => $timeStr,
            'bookingid' => $bookingid,
            'currency' => $currency,
        ]);
    }

    /**
     * Read-only order + successful payments (Cake admin_updatetransaction subset).
     */
    /**
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function admin_updatetransaction(Request $request, $id = null)
    {
        $orderId = $this->decodeId((string)$id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->where('o.id', $orderId)
            ->select(['o.*', 'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name'])
            ->first();

        if (!$order) {
            return redirect('/admin/transactions/index');
        }

        $payments = LegacyCsOrderPayment::query()
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        return view('admin.transactions.updatetransaction', [
            'order' => $order,
            'payments' => $payments,
        ]);
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function decodeId(string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }
}
