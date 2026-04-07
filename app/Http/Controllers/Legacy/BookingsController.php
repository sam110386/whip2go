<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\UserCcToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingsController extends LegacyAppController
{
    use BookingsTrait;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "Bookings::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * index: Frontend rental orders (usually blocked)
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        // Blocked as per legacy code
        return redirect()->back()->with('error', "Sorry, you are not allowed to access this page");
    }

    /**
     * customerautocomplete: Search for customers
     */
    public function customerautocomplete(Request $request)
    {
        $searchTerm = $request->query('term');
        $userid = Session::get('userParentId') ?: Session::get('userid');

        $userlists = User::select('id', 'first_name', 'contact_number')
            ->where('status', 1)
            ->where('is_dealer', 0)
            ->where(function($query) use ($searchTerm) {
                $query->where('contact_number', 'LIKE', "%$searchTerm%")
                      ->orWhere('first_name', 'LIKE', "%$searchTerm%")
                      ->orWhere('last_name', 'LIKE', "%$searchTerm%")
                      ->orWhere('email', 'LIKE', "%$searchTerm%");
            })
            ->limit(10)
            ->orderBy('first_name', 'ASC')
            ->get();

        $users = $userlists->map(function($user) {
            return [
                'id' => $user->id,
                'tag' => $user->first_name . ' - ' . $user->contact_number
            ];
        });

        return response()->json($users);
    }

    /**
     * create: New booking page (redirects to index)
     */
    public function create()
    {
        return redirect()->route('legacy.bookings.index');
    }

    /**
     * edit: Edit booking page
     */
    public function edit($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $id = base64_decode($id);
        if (empty($id)) return redirect()->back();

        $CsOrder = CsOrder::where('cs_orders.id', $id)
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'cs_orders.vehicle_id')
            ->select('cs_orders.*', 'Vehicle.id as vehicle_id', 'Vehicle.vehicle_name')
            ->first();

        return view('legacy.bookings.edit', compact('CsOrder'));
    }

    /**
     * editsave: Save edited booking
     */
    public function editsave(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => false, 'message' => "Session expired"]);
        return response()->json($this->_editsave($request->all()));
    }

    /**
     * overdue: List overdue bookings
     */
    public function overdue(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        // Blocked as per legacy code
        return redirect()->back()->with('error', "Sorry, you are not allowed to access this page");
    }

    public function bookingVehicleLease(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cancelBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function changeccdetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function completeBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function geotabkeylesslock(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function geotabkeylessunlock(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function getDeclarationDoc(Request $request) { return response()->json($this->_getDeclarationDoc($request->all())); }
    public function getVehicleCCMCard(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function getagreement(Request $request) { return response()->json($this->_getagreement($request->all())); }
    public function getinsurancepopup(Request $request) { return response()->json($this->getInsurancePopup($request)); }
    public function getinsurancetoken(Request $request) { return response()->json($this->getinsurancetoken_method($request)); }
    public function load_single_row(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function loadcancelBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function loadcompleteBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function loadvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function loadvehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function overdue_booking_details(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function processAdvancePayment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function processchangeccdetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function processfullpayment(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function processvehicleexpiretime(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function pullVehicleOdometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function saveBookingOdometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function startBooking(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function updateodometer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function updatevehiclegps(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function withautorenew(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    protected function _editsave($data)
    {
        return ['status' => false, 'message' => '_editsave pending migration'];
    }

    protected function _startBooking($csOrder)
    {
        return ['status' => false, 'message' => '_startBooking pending migration'];
    }

    protected function _getagreement($conditions)
    {
        return method_exists($this, '_getagreement') ? [] : [];
    }

    protected function _autocomplete($params = [])
    {
        return ['status' => false, 'message' => '_autocomplete pending migration'];
    }

    protected function _chargeLateFee($params = [])
    {
        return ['status' => false, 'message' => '_chargeLateFee pending migration'];
    }

    protected function _overdue_booking_details($params = [])
    {
        return ['status' => false, 'message' => '_overdue_booking_details pending migration'];
    }

    protected function _pullVehicleOdometer($params = [])
    {
        return ['status' => true, 'mileage' => 0, 'message' => 'Telematics pending migration'];
    }

    protected function _saveBookingOdometer($params = [])
    {
        return ['status' => false, 'message' => '_saveBookingOdometer pending migration'];
    }

    protected function _syncvehiclegps($params = [])
    {
        return ['status' => false, 'message' => '_syncvehiclegps pending migration'];
    }

    protected function _admineditsave($data)
    {
        return $this->_editsave($data);
    }

    protected function _geotabkeylesslock($params = [])
    {
        return ['status' => false, 'message' => '_geotabkeylesslock pending migration'];
    }

    protected function _geotabkeylessunlock($params = [])
    {
        return ['status' => false, 'message' => '_geotabkeylessunlock pending migration'];
    }
}
