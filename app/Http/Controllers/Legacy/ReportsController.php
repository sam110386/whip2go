<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\ReportsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ReportsController extends LegacyAppController
{
    use ReportsTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * index: Main owner report listing
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');

        $query = $this->_getBookingReportsQuery($request, [
            'CsOrder.user_id' => $userid,
            'CsOrder.parent_id' => 0
        ]);
        
        if ($request->input('search') == 'EXPORT') {
            return $this->_exportReportsCsv($query->get());
        }

        $reportlists = $query->orderBy('CsOrder.id', 'DESC')->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('legacy.elements.reports.index', compact('reportlists'));
        }

        return view('legacy.reports.index', compact('reportlists'));
    }

    /**
     * details: Report details view
     */
    public function details(Request $request, $id)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $data = $this->_getReportDetails($id);
        
        if (!$data) return response()->json(['error' => 'No record found'], 404);

        return view('legacy.reports.details', $data);
    }

    /**
     * vehicle: Fleet productivity report for owner
     */
    public function vehicle(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');

        $query = $this->_getVehicleReportsQuery($request, ['Vehicle.user_id' => $userid]);

        $reportlists = $query->orderBy('Vehicle.id', 'DESC')->paginate(25)->withQueryString();

        return view('legacy.reports.vehicle', compact('reportlists'));
    }
}
