<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\OrderDepositRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LinkedBookingsController extends LegacyAppController
{
    use BookingsTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "LinkedBookings::{$action} pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * cloud_index: List linked bookings for cloud user
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])
            ->pluck('user_id')
            ->toArray();

        $query = CsOrder::whereIn('cs_orders.user_id', $dealers)
            ->whereIn('cs_orders.status', [0, 1]);

        $tripLog = $query->orderBy('cs_orders.id', 'DESC')->get();

        if ($request->ajax()) {
            return view('cloud.elements.linkedbooking.cloud_booking', compact('tripLog'));
        }

        return view('cloud.linked_bookings.index', compact('tripLog'));
    }

    /**
     * cloud_startBooking
     */
    public function cloud_startBooking(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $csOrder = CsOrder::find($orderid);

        if (!$csOrder || $csOrder->status != 0) {
            return response()->json(['status' => false, 'message' => "Invalid booking status"]);
        }

        return response()->json($this->_startBooking($csOrder));
    }

    /**
     * cloud_cancelBooking
     */
    public function cloud_cancelBooking(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('Text.orderid'));
        $cancelNote = $request->input('Text.cancel_note');
        $cancellationFee = $request->input('Text.cancellation_fee');

        $csOrder = CsOrder::find($orderid);
        if (!$csOrder || !in_array($csOrder->status, [0, 2])) {
            return response()->json(['status' => false, 'message' => "Cannot cancel this booking"]);
        }

        return response()->json($this->_cancelBooking($csOrder, $cancelNote, $cancellationFee));
    }

    /**
     * cloud_completeBooking
     */
    public function cloud_completeBooking(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('Text.orderid'));
        $json = json_decode(base64_decode($request->input('Text.json')), true);
        
        $csOrder = CsOrder::find($orderid);
        if (!$csOrder) return response()->json(['status' => false, 'message' => "Booking not found"]);

        $csOrder->update([
            'status' => 3,
            'rent' => $json['rent'] ?? $csOrder->rent,
            'tax' => $json['tax'] ?? $csOrder->tax,
            'end_timing' => now()
        ]);

        Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0]);

        return response()->json(['status' => true, 'message' => "Booking completed successfully"]);
    }

    /**
     * cloud_retry...
     */
    public function cloud_retryinsurancefee(Request $request) { return response()->json($this->retryInsurance($request)); }
    public function cloud_retryinitialfee(Request $request) { return response()->json($this->retryInitialfee($request)); }
    public function cloud_retrydepositfee(Request $request) { return response()->json($this->retryDeposit($request)); }
    public function cloud_retryrentalfee(Request $request) { return response()->json($this->retryRental($request)); }
    public function cloud_retryemf(Request $request) { return response()->json($this->retryEmf($request)); }
    public function cloud_retrytollfee(Request $request) { return response()->json($this->retryToll($request)); }

    /**
     * cloud_edit: Load data for edit view
     */
    public function cloud_edit($id)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        $id = base64_decode($id);
        $csOrder = CsOrder::findOrFail($id);
        
        return view('cloud.linked_bookings.edit', compact('csOrder', 'id'));
    }

    /**
     * cloud_editsave: Save edited booking
     */
    public function cloud_editsave(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        return response()->json($this->_editsave($request->all()));
    }

    /**
     * cloud_overdue: List overdue bookings
     */
    public function cloud_overdue(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $tripLog = CsOrder::whereIn('user_id', $dealers)
            ->where('status', 1)
            ->where('end_datetime', '<', now())
            ->orderBy('id', 'DESC')
            ->get();

        return view('cloud.linked_bookings.overdue', compact('tripLog'));
    }

    public function cloud_customerautocomplete(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_getVehicle(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_getagreement(Request $request) { return response()->json($this->_getagreement($request->all())); }
    public function cloud_getinsurancepopup(Request $request) { return response()->json($this->getInsurancePopup($request)); }
    public function cloud_getinsurancetoken(Request $request) { return response()->json($this->getinsurancetoken_method($request)); }
    public function cloud_load_single_row(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_loadcancelBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_loadcompleteBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_loadvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_loadvehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_processvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_updatestartodometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_updatevehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function withautorenew(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    protected function _admineditsave(...$args)
    {
        return ['status' => false, 'message' => '_admineditsave pending migration'];
    }
}
