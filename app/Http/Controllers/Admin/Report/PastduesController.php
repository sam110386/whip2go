<?php

namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\ReportTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderExtlog;


class PastduesController extends LegacyAppController
{
    use ReportTrait;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Past Due Report';
        $sessLimitName = "cs_orders_limit";
        $dealerId = $request->input('Search.dealerid') ?? $request->query('dealerid') ?? '';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessLimitName, $limit);
        } else {
            $limit = Session::get($sessLimitName, 20);
        }

        $query = CsOrder::query()
            ->select('id', 'increment_id', 'parent_id')
            ->with([
                'orderExtlogs' => function ($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->whereNotIn('status', [2, 3])
            ->where(function ($q) {
                $q->where('end_datetime', '<', now())
                    ->orWhere('payment_status', 2)
                    ->orWhere('insu_status', 2)
                    ->orWhere('dpa_status', 2)
                    ->orWhere('infee_status', 2)
                    ->orWhere('dia_insu_status', 2);
            });

        if (!empty($dealerId)) {
            $query->where('user_id', $dealerId);
        }

        $lists = $query->orderBy('id', 'DESC')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.report.pastdues.elements.pastdue', compact('lists', 'dealerId', 'limit'));
        }

        return view('admin.report.pastdues.index', compact('title', 'lists', 'dealerId', 'limit'));
    }

    public function logs(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderId = $request->input('order');
        $all = $request->has('all') ? $request->input('all') : false;
        $orderIds = [$orderId];

        if ($all) {
            $orderIds = CsOrder::where('id', $orderId)
                ->orWhere('parent_id', $orderId)
                ->pluck('id')
                ->toArray();
        }

        $lists = OrderExtlog::with(['owner', 'csOrder'])
            ->whereIn('cs_order_id', $orderIds)
            ->orderBy('id', 'DESC')
            ->get();

        return view('report.pastdues.logs', compact('lists'));
    }

    public function details(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->_details($request);
    }

}
