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

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "Reports::{$action} pending migration.",
            'result' => [],
        ]);
    }

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

    public function autorenewddetails(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = $id ? base64_decode($id) : base64_decode((string) $request->input('id', ''));
        $data = $this->_autorenewddetails($id);
        return view('legacy.reports.autorenew_details', $data);
    }

    public function export(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $userid = Session::get('userParentId') ?: Session::get('userid');
        $query = $this->_getBookingReportsQuery($request, ['CsOrder.user_id' => $userid, 'CsOrder.parent_id' => 0]);
        return $this->_exportReportsCsv($query->get());
    }

    public function exportproductivity(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }

    public function loadsubbooking(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        $orderid = $request->input('orderid');
        $subLog = $this->_loadSubBookings($orderid);
        return view('legacy.elements.reports.subbooking_list', compact('subLog'));
    }

    public function paymentspopup(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        $orderid = base64_decode((string) $request->input('orderid'));
        $payments = $this->_getPaymentsData($orderid);
        return view('legacy.elements.reports.payment_popup', compact('payments'));
    }

    protected function _autorenewddetails($id)
    {
        return $this->_getAutoRenewDetails($id);
    }

    protected function _details($id)
    {
        return $this->_getReportDetails($id);
    }

    protected function _getExtLogs($id)
    {
        return $this->_loadSubBookings($id);
    }
}
