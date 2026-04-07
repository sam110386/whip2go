<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\ReportsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LinkedReportsController extends LegacyAppController
{
    use ReportsTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * cloud_index: List reports for linked dealers
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $query = $this->_getBookingReportsQuery($request, ['CsOrder.parent_id' => 0])
            ->whereIn('CsOrder.user_id', $dealers);
            
        if ($request->input('search') == 'EXPORT') {
            return $this->_exportReportsCsv($query->get());
        }

        $reportlists = $query->orderBy('CsOrder.id', 'DESC')->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('cloud.elements.reports.index', compact('reportlists'));
        }

        return view('cloud.linked_reports.index', compact('reportlists'));
    }

    /**
     * cloud_details: Detailed report view
     */
    public function cloud_details(Request $request, $id)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $data = $this->_getReportDetails($id);
        
        if (!$data) return response()->json(['error' => 'No record found'], 404);

        return view('cloud.linked_reports.details', $data);
    }

    /**
     * cloud_vehicle: Vehicle fleet report for linked dealers
     */
    public function cloud_vehicle(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $query = $this->_getVehicleReportsQuery($request)
            ->whereIn('Vehicle.user_id', $dealers);

        $reportlists = $query->orderBy('Vehicle.id', 'DESC')->paginate(25)->withQueryString();

        return view('cloud.linked_reports.vehicle', compact('reportlists'));
    }

    /**
     * cloud_productivity: Historical productivity for linked dealers
     */
    public function cloud_productivity(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $query = $this->_getVehicleReportsQuery($request)
            ->whereIn('Vehicle.user_id', $dealers);

        $reportlists = $query->orderBy('Vehicle.id', 'DESC')->paginate(25)->withQueryString();

        return view('cloud.linked_reports.productivity', compact('reportlists'));
    }
}
