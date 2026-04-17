<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Support\BookingReportDetailPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $limit = $this->resolveLimit($request, 'admin_reports_limit');
        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));
        $status = trim((string)$this->searchInput($request, 'status'));

        $q = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->select([
                'o.*',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
            ]);

        if ($dateFrom !== '') {
            $q->whereDate('o.start_datetime', '>=', Carbon::parse($dateFrom)->toDateString());
        }
        if ($dateTo !== '') {
            $q->whereDate('o.end_datetime', '<=', Carbon::parse($dateTo)->toDateString());
        }
        if ($status !== '' && is_numeric($status)) {
            $q->where('o.status', (int)$status);
        }

        $reportlists = $q->orderByDesc('o.id')->paginate($limit)->withQueryString();
        if ($request->ajax()) {
            return response()->view('admin.reports._listing', compact('reportlists'));
        }

        return view('admin.reports.index', compact('reportlists', 'dateFrom', 'dateTo', 'status', 'limit'));
    }

    public function details($id)
    {
        $orderId = $this->decodeId((string)$id);
        if (!$orderId) {
            return redirect('/admin/reports/index');
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return redirect('/admin/reports/index');
        }

        $payload = BookingReportDetailPresenter::buildSingle($orderId);
        if ($payload === null) {
            return redirect('/admin/reports/index');
        }

        return view('admin.reports.details', $payload);
    }

    public function loadsubbooking($orderid)
    {
        $id = $this->decodeId((string)$orderid);
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $subs = DB::table('cs_orders')->where('parent_id', $id)->orderBy('id')->get();

        return response()->view('admin.reports._subbookings', compact('subs', 'id'));
    }

    public function autorenewddetails($id)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/reports/index');
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return redirect('/admin/reports/index');
        }

        $payload = BookingReportDetailPresenter::buildAutoRenew($orderId);
        if ($payload === null) {
            return redirect('/admin/reports/index');
        }

        return view('admin.reports.details', $payload);
    }

    public function productivity(Request $request)
    {
        $from = trim((string)$this->searchInput($request, 'date_from'));
        $to = trim((string)$this->searchInput($request, 'date_to'));

        $q = DB::table('cs_orders')
            ->selectRaw('user_id, COUNT(*) as total_orders, SUM(rent + tax + dia_fee) as gross')
            ->groupBy('user_id');
        if ($from !== '') {
            $q->whereDate('start_datetime', '>=', Carbon::parse($from)->toDateString());
        }
        if ($to !== '') {
            $q->whereDate('end_datetime', '<=', Carbon::parse($to)->toDateString());
        }
        $rows = $q->orderByDesc('total_orders')->limit(500)->get();

        return view('admin.reports.productivity', ['rows' => $rows, 'dateFrom' => $from, 'dateTo' => $to]);
    }

    public function paymentspopup(Request $request)
    {
        $id = $this->decodeId((string)$request->input('orderid', ''));
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $rows = DB::table('cs_order_payments')->where('cs_order_id', $id)->orderByDesc('id')->get();

        return response()->view('admin.reports._payments_popup', compact('rows', 'id'));
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session([$sessionKey => $lim]);
            }
        }
        $limit = (int)session($sessionKey, 50);

        return $limit > 0 ? $limit : 50;
    }
}

