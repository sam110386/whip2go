<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "CloudVehicleReservations::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * cloud_index: Super Admin Reservation View
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return $redirect;

        $query = VehicleReservation::leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'vehicle_reservations.user_id')
            ->select(
                'vehicle_reservations.*', 
                'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.financing',
                'Renter.first_name as renter_first_name', 'Renter.last_name as renter_last_name',
                'Owner.company_name as owner_company'
            );

        // Global search for cloud admin
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Renter.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Renter.email', 'LIKE', "%$keyword%")
                  ->orWhere('Owner.company_name', 'LIKE', "%$keyword%")
                  ->orWhere('Vehicle.vin_no', 'LIKE', "%$keyword%");
            });
        }

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate(100)->withQueryString();

        return view('cloud.vehiclereservation.index', [
            'bookings' => $bookings,
            'readyForDealerStatus' => $this->readyForDealerStatus
        ]);
    }

    /**
     * cloud_view: Detailed view for cloud admin
     */
    public function cloud_view(Request $request, $id)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return $redirect;

        $id = base64_decode($id);
        $reservation = VehicleReservation::with(['Vehicle', 'User', 'Renter', 'OrderDepositRule'])->findOrFail($id);

        return view('cloud.vehiclereservation.view', compact('reservation'));
    }

    /**
     * cloud_updatestatus: Global status update
     */
    public function cloud_updatestatus(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $status = $request->input('status');

        if ($this->_changeStatus($id, $status)) {
            return response()->json(['status' => true, 'message' => "Global status updated"]);
        }
        return response()->json(['status' => false, 'message' => "Failed to update status"]);
    }

    public function cloud_all(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_bankstatement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_changeDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function cloud_changeSaveStatus(Request $request) { return response()->json(['status' => $this->_changeSaveStatus($request->input('id'), $request->input('save_status'))]); }
    public function cloud_changeStatus(Request $request) { return $this->cloud_updatestatus($request); }
    public function cloud_changeVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function cloud_checkStarterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_checkodometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_createBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_disableStaterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_getfarecalculations(Request $request) { return response()->json($this->_getfarecalculations($request->input('lease_id'))); }
    public function cloud_getplaidbalance(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_getplaidrecord(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_getuserdetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_markBookingCancel(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_markBookingCompleted(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_provenincome(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_renderlog(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_saveVehicleBooking(Request $request) { return response()->json($this->_saveVehicleBooking($request->all())); }
    public function cloud_singleload(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_staterInterruptWorks(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_updateDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function cloud_updateReservationVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function cloud_updatelist(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_vehicleReservationLog(Request $request) { return response()->json($this->_vehicleReservationLog($request->all())); }
}
