<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PayoutsTrait;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use Illuminate\Http\Request;

class PayoutsController extends LegacyAppController
{
    use PayoutsTrait;

    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index ──────────────────────────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->adminexport($request);
        }

        $search   = $this->resolvePayoutSearch($request);
        $listtype = $search['listtype'];
        $userId   = $search['userId'];

        $sessionLimitKey  = 'Payouts_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        if (empty($listtype)) {
            $query = CsPayout::query();
            if (!empty($search['dateFrom'])) { $query->where('processed_on', '>=', $search['dateFrom']); }
            if (!empty($search['dateTo']))   { $query->where('processed_on', '<=', $search['dateTo']); }
            if (!empty($search['payoutId'])) { $query->where('id', $search['payoutId']); }
            if (!empty($userId))             { $query->where('user_id', $userId); }
            $payoutlists = $query->orderBy('processed_on', 'DESC')->paginate($limit)->withQueryString();
        } else {
            $query = CsPayoutTransaction::query()
                ->from('cs_payout_transactions as CsPayoutTransaction')
                ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
                ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
                ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
                ->select('CsPayoutTransaction.*', 'CsOrder.id as order_id', 'CsOrder.increment_id',
                         'Renter.first_name', 'Renter.last_name', 'Vehicle.vehicle_name')
                ->where('CsPayoutTransaction.status', 1)
                ->orderBy('CsPayoutTransaction.id', 'DESC');
            if (!empty($userId)) { $query->where('CsPayoutTransaction.user_id', $userId); }
            $payoutlists = $query->paginate($limit)->withQueryString();
        }

        $viewData = array_merge($search, [
            'title_for_layout' => 'Payouts',
            'payoutlists'      => $payoutlists,
        ]);

        return $request->ajax()
            ? view('admin.payouts.index_ajax', $viewData)
            : view('admin.payouts.index', $viewData);
    }

    // ─── admin_transactions ───────────────────────────────────────────────────
    public function admin_transactions(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $payoutId = $request->input('payoutid');

        $transactions = CsPayoutTransaction::query()
            ->from('cs_payout_transactions as CsPayoutTransaction')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
            ->select('CsPayoutTransaction.*', 'CsOrder.id as order_id', 'CsOrder.increment_id',
                     'CsOrder.start_datetime', 'Renter.first_name', 'Renter.last_name', 'Vehicle.vehicle_name')
            ->where('CsPayoutTransaction.cs_payout_id', $payoutId)
            ->orderBy('CsPayoutTransaction.id', 'DESC')
            ->get();

        return view('admin.payouts.transactions', compact('transactions'));
    }

    // ─── adminexport ──────────────────────────────────────────────────────────
    public function adminexport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $search = $this->resolvePayoutSearch($request);
        $userId = $search['userId'];

        if (empty($userId)) {
            return redirect()->back()->with('error', 'Sorry, please choose dealer first.');
        }

        $ordersData = $this->buildExportQuery([
            'user_id'   => $userId,
            'date_from' => $search['dateFrom'],
            'date_to'   => $search['dateTo'],
            'payout_id' => $search['payoutId'],
        ]);

        if ($ordersData->isEmpty()) {
            return redirect()->back()->with('error', 'Sorry, No record found for selected criteria.');
        }

        return $this->streamPayoutCsv($ordersData, $userId);
    }
}
