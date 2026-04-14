<?php

namespace App\Http\Controllers\Cloud\Report;

use App\Http\Controllers\Cloud\Report\Concerns\CloudReportAccess;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetsController extends LegacyAppController
{
    use CloudReportAccess;

    protected int $recordsPerPage = 50;

    public function index(Request $request)
    {
        if ($redirect = $this->guardCloudReportAccess()) {
            return $redirect;
        }

        [$dealers, $dealerIds] = $this->managedOwnerDealersQuery($this->cloudParentAdminId());

        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
        $vehicleid = $request->input('Search.vehicleid', $request->query('vehicleid', ''));

        $sessKey = 'fleets_limit';
        $limit = $this->getPageLimit($request, $sessKey, $this->recordsPerPage);

        $query = DB::table('report_customers as ReportCustomer')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'ReportCustomer.vehicle_id')
            ->select(
                'ReportCustomer.vehicle_id',
                'Vehicle.id',
                'Vehicle.vehicle_name',
                'Vehicle.created',
                'Vehicle.user_id',
                'Vehicle.vehicleCostInclRecon',
                DB::raw('SUM(ReportCustomer.days) as days'),
                DB::raw('SUM(ReportCustomer.miles) as miles'),
                DB::raw('SUM(ReportCustomer.total_collected - ReportCustomer.tax_collected) as total_collected'),
                DB::raw('SUM(ReportCustomer.write_down_allocation) as write_down_allocation'),
                DB::raw('(select SUM(amount) from cs_vehicle_expenses as CVE where CVE.vehicle_id=ReportCustomer.vehicle_id) as expenses')
            )
            ->groupBy(
                'ReportCustomer.vehicle_id',
                'Vehicle.id',
                'Vehicle.vehicle_name',
                'Vehicle.created',
                'Vehicle.user_id',
                'Vehicle.vehicleCostInclRecon'
            )
            ->orderByDesc('ReportCustomer.vehicle_id');

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $query->where('ReportCustomer.increment_id', 'like', $like);
        }

        if ($vehicleid !== '' && $vehicleid !== null) {
            $query->where('ReportCustomer.vehicle_id', (int) $vehicleid);
        }

        if ($dealerid !== '' && $dealerid !== null) {
            $query->where('ReportCustomer.user_id', (int) $dealerid);
        } elseif ($dealerIds !== []) {
            $query->whereIn('ReportCustomer.user_id', $dealerIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        $lists = $query->paginate($limit)->withQueryString();

        return view('cloud.report.fleets.index', [
            'title_for_layout' => 'Vehicle Report',
            'lists' => $lists,
            'keyword' => $keyword,
            'dealerid' => $dealerid,
            'vehicleid' => $vehicleid,
            'dealers' => $dealers,
            'dealerids' => $dealerIds,
            'Record' => ['limit' => $limit],
        ]);
    }
}
