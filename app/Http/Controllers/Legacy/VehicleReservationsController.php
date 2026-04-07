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
use Carbon\Carbon;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

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
}
