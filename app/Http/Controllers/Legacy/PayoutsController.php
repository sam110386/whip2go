<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\PayoutsTrait;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use Illuminate\Http\Request;

class PayoutsController extends LegacyAppController
{
    use PayoutsTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function resolveUserId(): int
    {
        $userId = (int)session('userParentId', 0);
        return $userId === 0 ? (int)session('userid', 0) : $userId;
    }

    // ─── index ────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->resolveUserId();

        // Export shortcut
        if ($request->input('search') === 'EXPORT') {
            return $this->export($request);
        }

        $search   = $this->resolvePayoutSearch($request);
        $listtype = $search['listtype'];

        $sessionLimitKey  = 'Payouts_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        if (empty($listtype)) {
            $query = CsPayout::where('user_id', $userId);
            if (!empty($search['dateFrom'])) {
                $query->where('processed_on', '>=', $search['dateFrom']);
            }
            if (!empty($search['dateTo'])) {
                $query->where('processed_on', '<=', $search['dateTo']);
            }
            if (!empty($search['payoutId'])) {
                $query->where('id', $search['payoutId']);
            }
            $payoutlists = $query->orderBy('processed_on', 'DESC')->paginate($limit)->withQueryString();
        } else {
            $query = CsPayoutTransaction::query()
                ->from('cs_payout_transactions as CsPayoutTransaction')
                ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
                ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
                ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
                ->select('CsPayoutTransaction.*', 'CsOrder.id as order_id', 'CsOrder.increment_id',
                         'Renter.first_name', 'Renter.last_name', 'Vehicle.vehicle_name')
                ->where('CsPayoutTransaction.user_id', $userId)
                ->where('CsPayoutTransaction.status', 1)
                ->orderBy('CsPayoutTransaction.id', 'DESC');
            $payoutlists = $query->paginate($limit)->withQueryString();
        }

        return view('legacy.payouts.index', array_merge($search, [
            'title_for_layout' => 'Payouts',
            'payoutlists'      => $payoutlists,
        ]));
    }

    // ─── export (CSV download) ─────────────────────────────────────────────────
    public function export(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->resolveUserId();
        $search = $this->resolvePayoutSearch($request);

        $conditions = [
            'user_id'   => $userId,
            'date_from' => $search['dateFrom'],
            'date_to'   => $search['dateTo'],
            'payout_id' => $search['payoutId'],
        ];

        $ordersData = $this->buildExportQuery($conditions);

        if ($ordersData->isEmpty()) {
            return redirect()->back()->with('error', 'Sorry, No record found for selected criteria.');
        }

        return $this->streamPayoutCsv($ordersData, $userId);
    }

    // ─── transactions (AJAX payout detail) ───────────────────────────────────
    public function transactions(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId    = $this->resolveUserId();
        $payoutId  = $request->input('payoutid');

        $transactions = CsPayoutTransaction::query()
            ->from('cs_payout_transactions as CsPayoutTransaction')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
            ->select('CsPayoutTransaction.*', 'CsOrder.id as order_id', 'CsOrder.increment_id',
                     'CsOrder.start_datetime', 'Renter.first_name', 'Renter.last_name', 'Vehicle.vehicle_name')
            ->where('CsPayoutTransaction.user_id', $userId)
            ->where('CsPayoutTransaction.cs_payout_id', $payoutId)
            ->orderBy('CsPayoutTransaction.id', 'DESC')
            ->get();

        return view('legacy.payouts.transactions', [
            'transactions' => $transactions,
        ]);
    }
}
