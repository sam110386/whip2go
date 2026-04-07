<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsReservationPayment;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\UserIncome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "VehicleReservations::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userid = Session::get('userParentId') ?: Session::get('userid');
        $limit = $request->input('limit', Session::get('VehicleReservations_limit', 25));
        Session::put('VehicleReservations_limit', $limit);

        $query = VehicleReservation::where('vehicle_reservations.user_id', $userid)
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->select(
                'vehicle_reservations.*', 
                'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as rule_id', 'OrderDepositRule.financing',
                'Renter.first_name', 'Renter.last_name'
            );

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.vehiclereservation.index', [
            'bookings' => $bookings,
            'readyForDealerStatus' => $this->readyForDealerStatus
        ]);
    }

    public function all(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userid = Session::get('userParentId') ?: Session::get('userid');
        $limit = $request->input('limit', Session::get('VehicleReservations_limit', 25));

        $query = VehicleReservation::where('vehicle_reservations.user_id', $userid)
            ->where('vehicle_reservations.buy', 0)
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->select('vehicle_reservations.*', 'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name');

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.vehiclereservation.all', compact('bookings'));
    }

    public function createBooking(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $lease_id = base64_decode($request->input('lease_id', ''));
        if (empty($lease_id)) return redirect()->route('legacy.vehiclereservations.index');

        $reservedata = VehicleReservation::with(['Vehicle', 'User'])->find($lease_id);
        if (!$reservedata) return redirect()->route('legacy.vehiclereservations.index');

        $OrderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $lease_id)->first();
        $priceRulesAmt = $this->_getfarecalculations($lease_id);

        return view('legacy.vehiclereservation.create_booking', compact('reservedata', 'OrderDepositRule', 'priceRulesAmt'));
    }

    public function markBookingCompleted(Request $request)
    {
        $lease_id = base64_decode($request->input('lease_id', ''));
        if ($lease_id) {
            VehicleReservation::where('id', $lease_id)->update(['status' => 1]);
            return response()->json(['status' => true, 'message' => "Request processed successfully"]);
        }
        return response()->json(['status' => false, 'message' => "Invalid Request"]);
    }

    public function markBookingCancel(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => false, 'message' => "Session expired"]);
        
        $lease_id = base64_decode($request->input('lease_id', ''));
        $cancel_note = $request->input('cancel_note', '');
        $userid = Session::get('userid');

        $reservation = VehicleReservation::where('id', $lease_id)->where('user_id', $userid)->first();
        return response()->json($this->_markBookingCancel($reservation, $cancel_note));
    }

    public function saveVehicleBooking(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => false, 'message' => "Session expired"]);
        return response()->json($this->_saveVehicleBooking($request->all()));
    }

    public function getuserdetails(Request $request)
    {
        $userid = base64_decode($request->input('userid', ''));
        $booking_id = $request->input('booking') ? base64_decode($request->input('booking')) : null;
        
        $user = User::find($userid);
        $userIncome = UserIncome::where('user_id', $userid)->first();
        $plaidObj = PlaidUser::where('user_id', $userid)->first();

        return view('legacy.vehiclereservation.user_details_modal', compact('user', 'userIncome', 'plaidObj', 'booking_id'));
    }

    public function provenincome(Request $request)
    {
        $userid = $request->input('pk');
        $value = $request->input('value');
        
        if ($userid && $value) {
            UserIncome::updateOrCreate(['user_id' => $userid], ['provenincome' => $value]);
            // Update reservation threshold if needed
            return response()->json(['status' => 'success', 'message' => "Saved successfully"]);
        }
        return response()->json(['status' => 'error', 'message' => "Missing data"]);
    }

    public function checkodometer(Request $request)
    {
        $vehicleid = $request->input('vehicleid');
        // Placeholder for Passtime logic
        Log::info("Passtime: Checking odometer for vehicle $vehicleid");
        return response()->json(['status' => 'error', 'message' => "GPS integration will be migrated later."]);
    }

    public function bankstatement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function buy(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function changeDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function changeSaveStatus(Request $request) { return response()->json(['status' => $this->_changeSaveStatus($request->input('id'), $request->input('save_status'))]); }
    public function changeStatus(Request $request) { return response()->json(['status' => $this->_changeStatus($request->input('id'), $request->input('status'))]); }
    public function changeVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function checkStarterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function disableStaterInterrupt(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function getfarecalculations(Request $request) { return response()->json($this->_getfarecalculations($request->input('lease_id'))); }
    public function getplaidbalance(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function getplaidrecord(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function renderlog(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function saveVehicleAgreeToSell(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function saveVehicleSellingOption(Request $request) { return response()->json($this->_saveVehicleSellingOption($request->all())); }
    public function singleload(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function staterInterruptWorks(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function updateDatetime(Request $request) { return response()->json($this->_updateDatetime($request->all())); }
    public function updateReservationVehicle(Request $request) { return response()->json($this->_updateReservationVehicle($request->all())); }
    public function updatelist(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function vehicleReservationLog(Request $request) { return response()->json($this->_vehicleReservationLog($request->all())); }
    public function vehicleSellingOpionAgreeToSell(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function vehicleSellingOpionFindReplacement(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function vehicleSellingOpions(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    protected function _changeInsuranceTypePopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _changeSaveStatus(...$args)
    {
        $reservationId = $args[0] ?? null;
        $saveStatus = $args[1] ?? null;
        $reservation = VehicleReservation::find($reservationId);
        if ($reservation) {
            $reservation->update(['save_status' => $saveStatus]);
            return true;
        }
        return false;
    }
    protected function _changeinsurancepopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _changeinsurancesave(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _createOutstanidngIssues(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _getfarecalculations(...$args)
    {
        $reservationId = $args[0] ?? null;
        $reservation = VehicleReservation::with('OrderDepositRule')->find($reservationId);
        if (!$reservation) {
            return ['status' => false, 'message' => 'Reservation not found'];
        }
        return ['status' => true, 'message' => 'Calculation pending migration', 'result' => []];
    }
    protected function _insudoc(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _loadcancelblock(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _loadinsurancepopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _markBookingCancel(...$args)
    {
        $exists = $args[0] ?? null;
        $cancel_note = $args[1] ?? '';
        if ($exists) {
            $exists->update(['status' => 2, 'cancel_note' => $cancel_note, 'cancel_date' => now()]);
            Vehicle::where('id', $exists->vehicle_id)->update(['booked' => 0]);
            return ['status' => true, 'message' => 'Booking cancelled successfully'];
        }
        return ['status' => false, 'message' => 'Invalid Request'];
    }

    protected function _saveVehicleBooking(...$args)
    {
        return ['status' => false, 'message' => '_saveVehicleBooking pending migration'];
    }
    protected function _saveVehicleSellingOption(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _saveinsurancepayer(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _updateDatetime(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _updateReservationVehicle(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _vehicleReservationLog(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
}
