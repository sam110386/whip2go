<?php
namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\ReportCustomer;
use App\Http\Controllers\Legacy\LegacyAppController;

class FleetsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Report';
        $sessionLimitKey = "report_customer_limit";
        $keyword = $request->input('Search.keyword', $request->query('Search.keyword', ''));
        $dealerid = $request->input('Search.dealerid', $request->query('Search.dealerid', ''));
        $vehicleid = $request->input('Search.vehicleid', $request->query('Search.vehicleid', ''));

        if ($request->filled('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            Session::put($sessionLimitKey, $limit);
        } elseif ($request->filled('limit')) {
            $limit = (int) $request->input('limit');
            Session::put($sessionLimitKey, $limit);
        } else {
            $limit = Session::get($sessionLimitKey, 50);
        }

        $query = ReportCustomer::query()
            ->select([
                'report_customers.vehicle_id',
                'vehicles.id as vehicles_id',
                'vehicles.vehicle_name',
                'vehicles.created',
                'vehicles.user_id',
                'vehicles.vehicleCostInclRecon',
                DB::raw('SUM(report_customers.days) as days'),
                DB::raw('SUM(report_customers.miles) as miles'),
                DB::raw('SUM(report_customers.total_collected - report_customers.tax_collected) as total_collected'),
                DB::raw('SUM(report_customers.write_down_allocation) as write_down_allocation'),
                DB::raw('(SELECT SUM(amount) FROM cs_vehicle_expenses WHERE cs_vehicle_expenses.vehicle_id = report_customers.vehicle_id) as expenses')
            ])
            ->leftJoin('vehicles', 'vehicles.id', '=', 'report_customers.vehicle_id')
            ->groupBy('report_customers.vehicle_id', 'vehicles.id', 'vehicles.vehicle_name', 'vehicles.created', 'vehicles.user_id', 'vehicles.vehicleCostInclRecon');

        if (!empty($keyword)) {
            $query->where('report_customers.increment_id', 'LIKE', "%{$keyword}%");
        }

        if (!empty($dealerid)) {
            $query->where('report_customers.user_id', $dealerid);
        }

        if (!empty($vehicleid)) {
            $query->where('report_customers.vehicle_id', $vehicleid);
        }

        $lists = $query->orderBy('report_customers.vehicle_id', 'DESC')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.report.fleets.elements.fleet', compact('title', 'lists', 'keyword', 'dealerid', 'vehicleid', 'limit'));
        }

        return view('admin.report.fleets.index', compact('title', 'lists', 'keyword', 'dealerid', 'vehicleid', 'limit'));
    }
}
