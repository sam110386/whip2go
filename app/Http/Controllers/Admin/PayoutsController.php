<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;

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

        if (empty($listType)) {

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = Carbon::now()->format('Y-m-d');
            }

            $payoutLists = CsPayout::query()
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
                })
                ->orderBy('processed_on', 'desc')
                ->paginate($limit);

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
            return view('admin.payouts.partials.payout_table', $viewData);
        }

        return view('admin.payouts.index', $viewData);
    }


    public function transactions(Request $request)
    {
        $payoutId = (int) $request->input('payoutid');
        if ($payoutId <= 0) {
            return response('Invalid payout', 400);
        }

        $transactions = DB::table('cs_payout_transactions as pt')
            ->where('pt.cs_payout_id', $payoutId)
            ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->select([
                'pt.*',
                'o.id as order_table_id',
                'o.increment_id',
                'o.start_datetime',
                'v.vehicle_name',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
            ])
            ->orderByDesc('pt.id')
            ->get();

        return view('admin.payouts.batch_transactions', [
            'transactions' => $transactions,
            'paymentTypeValue' => self::payoutTypeLabels(),
        ]);
    }

    private function payoutSearch(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string) $v;
        }

        $q = $request->query($key);

        return $q === null ? '' : (string) $q;
    }
}
