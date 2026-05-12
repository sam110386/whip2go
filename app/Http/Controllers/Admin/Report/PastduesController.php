<?php

namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\ReportTrait;
use App\Models\Legacy\CsOrder;

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

        return $this->_details($request);
    }

}
