<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PayoutsTrait;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LinkedPayoutsController extends LegacyAppController
{
    use PayoutsTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * cloud_index: List payouts for linked dealers
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $query = CsPayout::whereIn('user_id', $dealers);

        if ($request->filled('Search.date_from')) {
            $query->where('processed_on', '>=', $request->input('Search.date_from'));
        }
        if ($request->filled('Search.date_to')) {
            $query->where('processed_on', '<=', $request->input('Search.date_to'));
        }

        $reportlists = $query->orderBy('id', 'DESC')->paginate(25);

        return view('cloud.linked_payouts.index', compact('reportlists'));
    }

    /**
     * cloud_transactions: List transactions for a specific payout or dealer
     */
    public function cloud_transactions(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $searchParams = $this->resolvePayoutSearch($request);
        
        // Ensure cloud user only sees their dealers
        if (!$searchParams['userId'] || !in_array($searchParams['userId'], $dealers)) {
            $searchParams['userId'] = $dealers; // Multiselect or array filter
        }

        $query = CsPayoutTransaction::query()
            ->from('cs_payout_transactions as CsPayoutTransaction')
            ->whereIn('CsPayoutTransaction.user_id', (array)$searchParams['userId'])
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->select('CsPayoutTransaction.*', 'CsOrder.increment_id', 'Vehicle.vehicle_name');

        if ($searchParams['payoutId']) {
            $query->where('CsPayoutTransaction.cs_payout_id', $searchParams['payoutId']);
        }

        if ($request->has('export')) {
            return $this->streamPayoutCsv($query->get(), $searchParams['userId']);
        }

        $reportlists = $query->orderBy('CsPayoutTransaction.id', 'DESC')->paginate(50);

        return view('cloud.linked_payouts.transactions', compact('reportlists', 'searchParams'));
    }

    public function cloudexport(Request $request)
    {
        $request->merge(['export' => 1]);
        return $this->cloud_transactions($request);
    }
}
