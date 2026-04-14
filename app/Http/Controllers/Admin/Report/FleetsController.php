<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetsController extends LegacyAppController
{
    use UsesReportPageLimit;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Report';
        $keyword = '';
        $dealerid = '';
        $vehicleid = '';

        if ($request->filled('Search') || $request->query->count() > 0) {
            $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
            $vehicleid = $request->input('Search.vehicleid', $request->query('vehicleid', ''));
            $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        }

        $limit = $this->getPageLimit($request, 'fleets_limit', 50);

        $query = DB::table('report_customers as rc')
            ->leftJoin('vehicles as v', 'v.id', '=', 'rc.vehicle_id')
            ->select(
                'rc.vehicle_id',
                'v.id as vehicle_table_id',
                'v.vehicle_name',
                'v.created as vehicle_created',
                'v.user_id as vehicle_user_id',
                'v.vehicleCostInclRecon',
                DB::raw('SUM(rc.days) as days'),
                DB::raw('SUM(rc.miles) as miles'),
                DB::raw('SUM(rc.total_collected - rc.tax_collected) as total_collected'),
                DB::raw('SUM(rc.write_down_allocation) as write_down_allocation'),
                DB::raw('(select SUM(amount) from cs_vehicle_expenses as CVE where CVE.vehicle_id=rc.vehicle_id) as expenses')
            )
            ->groupBy('rc.vehicle_id', 'v.id', 'v.vehicle_name', 'v.created', 'v.user_id', 'v.vehicleCostInclRecon')
            ->orderByDesc('rc.vehicle_id');

        if ($keyword !== '') {
            $query->where('rc.increment_id', 'like', '%'.$keyword.'%');
        }
        if ($dealerid !== '') {
            $query->where('rc.user_id', $dealerid);
        }
        if ($vehicleid !== '') {
            $query->where('rc.vehicle_id', $vehicleid);
        }

        $lists = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements.admin_fleet', compact('lists', 'keyword', 'dealerid', 'vehicleid', 'title'));
        }

        return view('admin.report.fleets.index', compact('title', 'lists', 'keyword', 'dealerid', 'vehicleid'));
    }
}
