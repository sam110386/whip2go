<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\ReportsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsOrderPayment;
use Illuminate\Support\Facades\Cookie;

class ReportsController extends LegacyAppController
{
    use ReportsTrait;
    public function index(Request $request)
    {
        $title = 'Reports';
        $sess_limit_name = 'admin_reports_limit';
        $conditions = [];

        if ($request->has('Search.ClearFilter')) {
            Cookie::queue(Cookie::forget('report_list_search'));
            return redirect('/admin/reports/index');
        }

        $cookieData = json_decode($request->cookie('report_list_search'), true) ?? [];
        $fieldname = $request->input('Search.searchin', $request->query('searchin', $cookieData['fieldname'] ?? ''));
        $keyword = $request->input('Search.keyword', $request->query('keyword', $cookieData['keyword'] ?? ''));
        $date_from = $request->input('Search.date_from', $request->query('date_from', $cookieData['date_from'] ?? ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', $cookieData['date_to'] ?? ''));
        $status_type = $request->input('Search.status_type', $request->query('status_type', $cookieData['status_type'] ?? ''));
        $dealerid = $request->input('Search.dealer_id', $request->query('dealer_id', $cookieData['dealerid'] ?? ''));
        $renterid = $request->input('Search.renter_id', $request->query('renter_id', $cookieData['renterid'] ?? ''));

        if (!empty($date_from) && empty($date_to)) {
            $date_to = Carbon::now()->toDateString();
        }

        if (!empty($keyword)) {
            if ($fieldname == "1") {
                $conditions[] = ['pickup_address', 'LIKE', '%' . $keyword . '%'];
            } elseif ($fieldname == "2") {
                $conditions[] = ['vehicle_name', '=', $keyword];
            } elseif ($fieldname == "3") {
                $conditions[] = ['increment_id', '=', $keyword];
            }
        }

        if (!empty($date_from)) {
            $conditions[] = ['start_datetime', '>=', Carbon::parse($date_from)->toDateTimeString()];
        }
        if (!empty($date_to)) {
            $conditions[] = ['end_datetime', '<=', Carbon::parse($date_to)->toDateTimeString()];
        }

        if (!empty($status_type)) {
            if ($status_type == "cancel") {
                $conditions[] = ['status', '=', 2];
            } elseif ($status_type == "complete") {
                $conditions[] = ['status', '=', 3];
            }
        }

        if (!empty($renterid)) {
            $conditions[] = ['renter_id', '=', $renterid];
        }

        if (!empty($dealerid)) {
            $conditions[] = ['user_id', '=', $dealerid];
        }

        if (!empty($renterid)) {
            $conditions['renter_id'] = ['operator' => '=', 'value' => $renterid];
        }

        if (!empty($dealerid)) {
            $conditions['user_id'] = ['operator' => '=', 'value' => $dealerid];
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->export($conditions, $status_type);
        }

        if (!$request->ajax()) {
            $searchData = compact('keyword', 'fieldname', 'date_from', 'date_to', 'status_type', 'dealerid', 'renterid');
            Cookie::queue('report_list_search', json_encode($searchData), 43200);
        }

        $conditions[] = ['parent_id', '=', 0];

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sess_limit_name, $limit);
        } elseif ($request->session()->has($sess_limit_name)) {
            $limit = $request->session()->get($sess_limit_name);
        } else {
            $limit = $this->records_per_page ?? 50;
        }

        $query = CsOrder::query()
            ->with('user:id,first_name,last_name')
            ->where($conditions);

        if (!empty($status_type) && $status_type == "incomplete") {
            $query->whereIn('status', [0, 1]);
        }

        $allowedSorts = ['increment_id', 'start_datetime', 'end_datetime'];
        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('id');
        }

        $reportlists = $query->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($request->ajax()) {
            return response()->view('admin.reports.elements.index', compact('keyword', 'fieldname', 'date_from', 'date_to', 'status_type', 'dealerid', 'renterid', 'reportlists', 'limit'));
        }

        return view('admin.reports.index', compact('keyword', 'fieldname', 'date_from', 'date_to', 'status_type', 'dealerid', 'renterid', 'reportlists', 'limit'));
    }

    public function details($id)
    {
        return $this->_details($id);
    }

    public function loadsubbooking($orderid)
    {
        $id = $this->decodeId((string) $orderid);

        if (!$id) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $subbookinglists = CsOrder::query()
            ->with('user:id,first_name,last_name')
            ->where('id', $id)
            ->orWhere('parent_id', $id)
            ->orderByDesc('id')
            ->get();

        if (empty($subbookinglists)) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, no record found']);
        }

        $booking_id = $id;
        $html = view('admin.reports.elements.loadsubbooking', compact('subbookinglists', 'booking_id'))->render();

        return response()->json(['status' => 'success', 'booking_id' => $id, 'data' => $html]);
    }

    public function autorenewddetails($id)
    {
        return $this->_autorenewddetails($id);
    }

    public function productivity(Request $request)
    {
        $title = 'Fleet Productivity';
        $sess_limit_name = "admin_productivity_limit";
        $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->input('date_to', ''));
        $user_id = $request->input('Search.user_id', $request->input('user_id', ''));
        $conditions = [];
        $conditions[] = ['vehicles.user_id', '=', $user_id];

        if (!empty($date_from)) {
            $datefrom = Carbon::parse($date_from)->format('Y-m-d');
            $conditions[] = ['cs_orders.end_datetime', '>=', $datefrom];
        }

        if (!empty($date_to)) {
            $dateto = Carbon::parse($date_to)->format('Y-m-d');
            $conditions[] = ['cs_orders.end_datetime', '<=', $dateto];
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->exportproductivity($conditions, $date_from, $date_to);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sess_limit_name, $limit);
        } elseif ($request->session()->has($sess_limit_name)) {
            $limit = $request->session()->get($sess_limit_name);
        } else {
            $limit = $this->records_per_page ?? 10;
        }

        $query = Vehicle::query()
            ->leftJoin('cs_orders', function ($join) {
                $join->on('cs_orders.vehicle_id', '=', 'vehicles.id')
                    ->where('cs_orders.status', '=', 3);
            })
            ->where($conditions)
            ->select([
                'vehicles.vehicle_name',
                'vehicles.msrp',
                'vehicles.id',
                'vehicles.created',
                DB::raw('SUM(cs_orders.rent + cs_orders.initial_fee + cs_orders.damage_fee + cs_orders.uncleanness_fee) as totalrent'),
                DB::raw('SUM(cs_orders.end_odometer - cs_orders.start_odometer) as mileage'),
                DB::raw('SUM(DATEDIFF(cs_orders.end_datetime, cs_orders.start_datetime)) AS totaldays'),
                DB::raw('SUM(cs_orders.extra_mileage_fee) as extra_mileage_fee')
            ])
            ->groupBy('vehicles.id', 'vehicles.vehicle_name', 'vehicles.msrp', 'vehicles.created')
            ->orderBy('vehicles.id', 'DESC');
        $reportlists = $query->paginate($limit);

        return view('admin.reports.productivity', compact('title', 'reportlists', 'date_from', 'date_to', 'user_id', 'limit'));
    }
    public function paymentspopup(Request $request)
    {
        $id = $this->decodeId((string) $request->input('orderid', ''));

        if (!$id) {
            return response('Invalid booking id', 400);
        }

        $payments = CsOrderPayment::where('cs_order_id', $id)
            ->where('status', 1)->orderByDesc('id')
            ->get();

        $paymentTypeValue = $this->commonService->getPayoutTypeValue(true);
        $htmlContent = view('reports._paymentspopup', compact('payments', 'paymentTypeValue'))->render();

        return response()->json([
            'status' => 'success',
            'data' => $htmlContent
        ]);
    }

}

