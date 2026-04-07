<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
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
     * admin_index: Main admin report listing
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $query = $this->_getBookingReportsQuery($request, ['CsOrder.parent_id' => 0]);
        
        if ($request->input('search') == 'EXPORT') {
            return $this->_exportReportsCsv($query->get());
        }

        $limit = $request->input('Record.limit') ?: Session::get('reports_limit', 50);
        if ($request->has('Record.limit')) Session::put('reports_limit', $limit);

        $reportlists = $query->orderBy('CsOrder.id', 'DESC')->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.elements.reports.index', compact('reportlists'));
        }

        return view('admin.reports.index', compact('reportlists'));
    }

    /**
     * admin_details: Report details view
     */
    public function admin_details(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $data = $this->_getReportDetails($id);
        
        if (!$data) return response()->json(['error' => 'No record found'], 404);

        return view('admin.reports.details', $data);
    }

    /**
     * admin_productivity: Fleet productivity report
     */
    public function admin_productivity(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $extraConditions = [];
        if ($request->filled('Search.user_id')) {
            $extraConditions['Vehicle.user_id'] = $request->input('Search.user_id');
        }

        $query = $this->_getVehicleReportsQuery($request, $extraConditions);

        if ($request->input('search') == 'EXPORT') {
            // Fleet productivity CSV logic could be added here
            return response()->json(['message' => 'Export in progress']);
        }

        $reportlists = $query->orderBy('Vehicle.id', 'DESC')->paginate(25)->withQueryString();

        return view('admin.reports.productivity', compact('reportlists'));
    }

    /**
     * admin_autorenewddetails: Auto-renew detailed view
     */
    public function admin_autorenewddetails(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $data = $this->_getAutoRenewDetails($id);
        
        return view('admin.reports.autorenew_details', $data);
    }

    /**
     * admin_loadsubbooking: AJAX load sub-bookings
     */
    public function admin_loadsubbooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $orderid = $request->input('orderid');
        $subLog = $this->_loadSubBookings($orderid);
        
        return view('admin.elements.reports.subbooking_list', compact('subLog'));
    }

    /**
     * admin_paymentspopup: AJAX load payments data
     */
    public function admin_paymentspopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $payments = $this->_getPaymentsData($orderid);
        
        return view('admin.elements.reports.payment_popup', compact('payments'));
    }
}
