<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderStatuslog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "AdminVehicleReservations::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * admin_index: Manage Reservations
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $loggedId = session('SESSION_ADMIN.id');
        $query = VehicleReservation::leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->select(
                'vehicle_reservations.*', 
                'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name', 'Vehicle.plate_number',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as rule_id', 'OrderDepositRule.financing',
                'Renter.first_name', 'Renter.last_name', 'Renter.email', 'Renter.contact_number'
            );

        if (session('adminRoleId') != 1) {
            $query->where('vehicle_reservations.user_id', $loggedId);
        }

        // Filtering logic...
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Renter.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Renter.email', 'LIKE', "%$keyword%")
                  ->orWhere('Vehicle.vin_no', 'LIKE', "%$keyword%");
            });
        }

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate(50)->withQueryString();

        return view('admin.vehiclereservation.index', [
            'bookings' => $bookings,
            'readyForDealerStatus' => $this->readyForDealerStatus,
            'checklist' => $this->checklist
        ]);
    }

    /**
     * admin_edit: View/Edit Reservation details
     */
    public function admin_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        $reservation = VehicleReservation::with(['Vehicle', 'User', 'Renter', 'OrderDepositRule'])->findOrFail($id);
        
        $statusLogs = CsOrderStatuslog::where('reservation_id', $id)->orderBy('id', 'DESC')->get();

        return view('admin.vehiclereservation.edit', compact('reservation', 'statusLogs'));
    }

    /**
     * admin_updatestatus: Update Reservation Status
     */
    public function admin_updatestatus(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $status = $request->input('status');

        if ($this->_changeStatus($id, $status)) {
            return response()->json(['status' => true, 'message' => "Status updated successfully"]);
        }
        return response()->json(['status' => false, 'message' => "Failed to update status"]);
    }

    /**
     * admin_updatechecklist: Update individual checklist items
     */
    public function admin_updatechecklist(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $key = $request->input('key');
        $value = $request->input('value');

        $reservation = VehicleReservation::find($id);
        if ($reservation) {
            $checklists = json_decode($reservation->checklists, true) ?: [];
            $checklists[$key] = $value;
            $reservation->update(['checklists' => json_encode($checklists)]);
            return response()->json(['status' => true, 'message' => "Checklist updated"]);
        }
        return response()->json(['status' => false, 'message' => "Reservation not found"]);
    }

    /**
     * admin_delete: Delete reservation
     */
    public function admin_delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        VehicleReservation::where('id', $id)->delete();
        
        return redirect()->back()->with('success', 'Reservation deleted successfully');
    }

    public function admin_all(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_bankstatement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_capturepayment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_changeDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function admin_changeSaveStatus(Request $request) { return response()->json(['status' => $this->_changeSaveStatus($request->input('id'), $request->input('save_status'))]); }
    public function admin_changeStatus(Request $request) { return $this->admin_updatestatus($request); }
    public function admin_changeVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function admin_changeinsurancepopup(Request $request) { return response()->json($this->_changeinsurancepopup($request->all())); }
    public function admin_changeinsurancesave(Request $request) { return response()->json($this->_changeinsurancesave($request->all())); }
    public function admin_changeinsurancetypepopup(Request $request) { return response()->json($this->_changeInsuranceTypePopup($request->all())); }
    public function admin_checkStarterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_checkodometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_createBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_disableStaterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_download_vehicle_images(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_generateAgrement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getVehicleDynamicFareMatrix(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getfarecalculations(Request $request) { return response()->json($this->_getfarecalculations($request->input('lease_id'))); }
    public function admin_getplaidbalance(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getplaidrecord(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getuserdetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_goalrecalculate(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_insudoc(Request $request) { return response()->json($this->_insudoc($request->all())); }
    public function admin_loadcancelblock(Request $request) { return response()->json($this->_loadcancelblock($request->all())); }
    public function admin_loadinsurancepopup(Request $request) { return response()->json($this->_loadinsurancepopup($request->all())); }
    public function admin_loadstatuschecklist(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_markBookingCancel(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_markBookingCompleted(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_paymentcapturevehiclereservation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_processcapturepayment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_provenincome(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_pushToDealer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_recapturevehiclereservation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_renderlog(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveGoalRecalculation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveVehicleAgreeToSell(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveVehicleBooking(Request $request) { return response()->json($this->_saveVehicleBooking($request->all())); }
    public function admin_saveVehicleSellingOption(Request $request) { return response()->json($this->_saveVehicleSellingOption($request->all())); }
    public function admin_saveinsurancepayer(Request $request) { return response()->json($this->_saveinsurancepayer($request->all())); }
    public function admin_savemanualcalculation(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_singleload(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_staterInterruptWorks(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function admin_updateReservationVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function admin_updatelist(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatemvr(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_vehicleFree2moveAgreement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_vehicleReservationLog(Request $request) { return response()->json($this->_vehicleReservationLog($request->all())); }
    public function admin_vehicleSellingOpionAgreeToSell(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_vehicleSellingOpions(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
