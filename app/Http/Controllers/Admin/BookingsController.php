<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingsController extends LegacyAppController
{
    use BookingsTrait;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "AdminBookings::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * admin_index: Main admin booking listing
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $adminUser = session('SESSION_ADMIN');
        // Permission check...
        
        $query = CsOrder::whereNotIn('cs_orders.status', [2, 3])
            ->leftJoin('users as Owner', 'Owner.id', '=', 'cs_orders.user_id')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'cs_orders.renter_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', function($join) {
                $join->on('OrderDepositRule.cs_order_id', '=', 'cs_orders.id')
                     ->orOn('OrderDepositRule.cs_order_id', '=', 'cs_orders.parent_id');
            })
            ->select(
                'cs_orders.*', 
                'Owner.first_name as owner_first', 'Owner.last_name as owner_last', 
                'Driver.first_name as driver_first', 'Driver.last_name as driver_last', 
                'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as rule_id', 'OrderDepositRule.vehicle_reservation_id'
            );

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Driver.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Driver.email', 'LIKE', "%$keyword%")
                  ->orWhere('cs_orders.id', 'LIKE', "%$keyword%");
            });
        }

        $tripLog = $query->orderBy('cs_orders.id', 'DESC')->paginate(100)->withQueryString();

        return view('admin.booking.index', compact('tripLog'));
    }

    /**
     * admin_startBooking
     */
    public function admin_startBooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $CsOrder = CsOrder::find($orderid);
        
        if (!$CsOrder || $CsOrder->status != 0) {
            return response()->json(['status' => false, 'message' => "Invalid booking status"]);
        }

        return response()->json($this->_startBooking($CsOrder));
    }

    /**
     * admin_cancelBooking
     */
    public function admin_cancelBooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('Text.orderid'));
        $cancelNote = $request->input('Text.cancel_note');
        $cancellationFee = $request->input('Text.cancellation_fee');

        $CsOrder = CsOrder::find($orderid);
        return response()->json($this->_cancelBooking($CsOrder, $cancelNote, $cancellationFee));
    }

    /**
     * admin_loadcompleteBooking: Popup data for completion
     */
    public function admin_loadcompleteBooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $CsOrder = CsOrder::with('Vehicle')->find($orderid);
        
        // Use ActiveBookingTotalPending trait logic
        $all_fee = $this->getNextScheduleFee(null, $CsOrder); // Simplified

        return view('admin.booking.complete_modal', compact('CsOrder', 'all_fee'));
    }

    /**
     * admin_completeBooking
     */
    public function admin_completeBooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('Text.orderid'));
        $json = json_decode(base64_decode($request->input('Text.json')), true);
        
        $CsOrder = CsOrder::find($orderid);
        if (!$CsOrder) return response()->json(['status' => false, 'message' => "Booking not found"]);

        $CsOrder->update([
            'status' => 3,
            'rent' => $json['rent'] ?? $CsOrder->rent,
            'tax' => $json['tax'] ?? $CsOrder->tax,
            'end_timing' => now()
        ]);

        Vehicle::where('id', $CsOrder->vehicle_id)->update(['booked' => 0]);

        return response()->json(['status' => true, 'message' => "Booking completed successfully"]);
    }

    /**
     * admin_retryrentalfee
     */
    public function admin_retryrentalfee(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        return response()->json($this->retryRental($request));
    }

    // Mapping other retries...
    public function admin_retryinsurancefee(Request $request) { return response()->json($this->retryInsurance($request)); }
    public function admin_retryinitialfee(Request $request) { return response()->json($this->retryInitialfee($request)); }
    public function admin_retrydepositfee(Request $request) { return response()->json($this->retryDeposit($request)); }
    public function admin_retrytollfee(Request $request) { return response()->json($this->retryToll($request)); }

    /**
     * admin_getagreement
     */
    public function admin_getagreement(Request $request)
    {
        $id = base64_decode($request->input('orderid'));
        return response()->json($this->_getagreement(['CsOrder.id' => $id]));
    }

    /**
     * admin_getDeclarationDoc
     */
    public function admin_getDeclarationDoc(Request $request)
    {
        $id = base64_decode($request->input('orderid'));
        return response()->json($this->_getDeclarationDoc(['id' => $id]));
    }

    public function admin_autocomplete(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_changeExtendTime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_checkrapprove(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_checkrdisapprove(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_customerautocomplete(Request $request) { return $this->customerautocomplete($request); }
    public function admin_diabletempvehicle(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_edit(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_editsave(Request $request) { return response()->json($this->_editsave($request->all())); }
    public function admin_geotabkeylesslock(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_geotabkeylessunlock(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getVehicle(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getVehicleCCMCard(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getVehicleDynamicFareMatrix(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getinsurancepopup(Request $request) { return response()->json($this->getInsurancePopup($request)); }
    public function admin_getinsurancetoken(Request $request) { return response()->json($this->getinsurancetoken_method($request)); }
    public function admin_goalrecalculate(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_insurancepopup(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_load_single_row(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_loadcancelBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_loadextendtime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_loadvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_loadvehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_overdue(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_overdue_booking_details(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_partial_payment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_process_partial_payment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_processvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_pullVehicleOdometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_retrydiainsurancefee(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_retryemf(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_retrylatefee(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveBookingOdometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveGoalRecalculation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_savemanualcalculation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_sendAxleShareDetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_sendDirectAxleLink(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateodometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatevehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
