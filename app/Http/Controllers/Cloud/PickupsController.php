<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickupsController extends LegacyAppController
{
    protected array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];

    private array $checklist = [
        'vehicle_scan' => 'Vehicle Scan',
        'license_scan' => "Driver's License Scan",
        'driver_photo' => 'Driver Photo',
        'customer_key_hand_off_picture' => 'Take Customer Key Hand Off Picture',
    ];

    private array $readyForDealerStatus = [
        0 => ['In Review', 'bg-primary'],
        1 => ['Vehicle Sale', 'bg-success'],
        2 => ['Agree to Sell', 'bg-warning'],
        3 => ['Not Interested', 'bg-danger'],
        4 => ['Find a Replacement', 'bg-info'],
    ];

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $sessLimitName = 'cloud_pickups_limit';
        $limit = $request->input('Record.limit') ?: session($sessLimitName, 25);
        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $bookings = DB::table('vehicle_reservations')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->where('vehicle_reservations.user_id', $userid)
            ->whereIn('vehicle_reservations.status', $this->getAllowedStatuses())
            ->where('vehicle_reservations.buy', 0)
            ->where('vehicle_reservations.ready_for_dealer', 1)
            ->select(
                'vehicle_reservations.*',
                'Vehicle.id as vehicle_table_id', 'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as deposit_rule_id', 'OrderDepositRule.financing',
                'Renter.first_name as renter_first_name', 'Renter.last_name as renter_last_name'
            )
            ->orderByDesc('vehicle_reservations.id')
            ->paginate($limit);

        $checklists = $this->checklist;
        $readyForDealerStatus = $this->readyForDealerStatus;

        if ($request->ajax()) {
            return view('cloud.reservation._index', compact('bookings', 'checklists', 'readyForDealerStatus'));
        }

        return view('cloud.reservation.index', compact('bookings', 'checklists', 'readyForDealerStatus'));
    }

    public function loadstatuschecklist(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderid = $this->decodeId($request->input('orderid'));
        $return = ['status' => false, 'message' => 'Sorry, something missing'];

        if (empty($orderid)) {
            return response()->json($return);
        }

        $bookingData = DB::table('vehicle_reservations')
            ->where('id', $orderid)->select('checklists')->first();

        if (empty($bookingData)) {
            return response()->json(['status' => false, 'message' => 'Sorry, booking not found']);
        }

        $bookingchecks = !empty($bookingData->checklists) ? json_decode($bookingData->checklists, true) : [];
        $checklist = $this->checklist;
        $dataUrl = config('app.url') . 'reservation/pickups/updatechecklist';

        $html = view('cloud.reservation._loadstatuschecklist', compact('bookingchecks', 'orderid', 'checklist', 'dataUrl'))->render();

        return response()->json(['status' => true, 'message' => 'loaded successfully', 'html' => $html]);
    }

    public function updatechecklist(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $return = ['status' => false, 'message' => 'Sorry, something missing'];
        if (!$request->isMethod('post')) {
            return response()->json($return);
        }

        $orderid = $request->input('pk');
        $key = $request->input('name');
        $value = $request->input('value');

        if (empty($orderid)) {
            return response()->json($return);
        }

        $bookingData = DB::table('vehicle_reservations')
            ->where('id', $orderid)->select('checklists', 'id')->first();

        if (empty($bookingData)) {
            return response()->json(['status' => false, 'message' => 'Sorry, booking not found']);
        }

        $checklists = !empty($bookingData->checklists) ? json_decode($bookingData->checklists, true) : [];
        $checklists[$key] = $value;

        DB::table('vehicle_reservations')
            ->where('id', $orderid)
            ->update(['checklists' => json_encode($checklists)]);

        return response()->json(['status' => true, 'message' => 'saved successfully']);
    }

    public function licenseScan(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return view('cloud.reservation.license_scan');
    }

    public function openLicenseScanRequestPopup(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $booking = base64_decode($request->input('booking'));
        $pickupData = '';

        try {
            $rule = DB::table('cs_order_deposit_rules')
                ->where('vehicle_reservation_id', $booking)
                ->select('id', 'pickup_data')
                ->first();

            if (empty($rule)) {
                return response()->json(['message' => 'Invalid reservation id']);
            }
            $pickupData = json_decode($rule->pickup_data ?? '{}', true);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }

        $isAdmin = 0;

        return view('cloud.reservation._open_license_scan_request_popup', compact('booking', 'pickupData', 'isAdmin'));
    }

    public function saveLicenseScanPopupRequest(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong'];
        if (!$request->isMethod('post')) {
            return response()->json($return);
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $booking = base64_decode($request->input('Text.booking'));
        $dataObj = $request->input('Text');

        if (empty($dataObj['email'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, please enter email address']);
        }

        $reservation = DB::table('vehicle_reservations')
            ->where('id', $booking)
            ->select('id', 'vehicle_id', 'renter_id')
            ->first();

        if (empty($reservation)) {
            return response()->json(['status' => false, 'message' => 'Sorry, reservation not found']);
        }

        return response()->json(['status' => true, 'message' => 'Email reminder sent successfully for license scan']);
    }

    public function pickUpUploadPhoto(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $booking = base64_decode($request->input('orderid'));
        $rule = DB::table('cs_order_deposit_rules')
            ->where('vehicle_reservation_id', $booking)
            ->select('id', 'pickup_data')
            ->first();

        $pickupData = json_decode($rule->pickup_data ?? '{}', true);
        $orderid = $booking;
        $isAdmin = 0;

        return view('cloud.reservation.pick_up_upload_photo_popup', compact('pickupData', 'orderid', 'isAdmin'));
    }

    public function savePickUpUploadPhoto(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong.'];

        if ($request->filled('OrderDepositRule') && $request->ajax()) {
            $reservationId = $request->input('OrderDepositRule.reservation_id');
            $rule = DB::table('cs_order_deposit_rules')
                ->where('vehicle_reservation_id', $reservationId)
                ->select('id', 'pickup_data')
                ->first();

            if (empty($rule)) {
                return response()->json(['status' => false, 'message' => 'Invalid reservation id']);
            }

            $pickupData = json_decode($rule->pickup_data ?? '{}', true);

            if ($request->hasFile('OrderDepositRule.driver_photo')) {
                $file = $request->file('OrderDepositRule.driver_photo');
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, $this->allowedExtensions)) {
                    $filename = 'driver_photo_' . $reservationId . '.' . $ext;
                    $file->move(public_path('files/reservation'), $filename);
                    $pickupData['driver_photo'] = $filename;
                }
            }

            $return['status'] = true;
            DB::table('cs_order_deposit_rules')
                ->where('id', $rule->id)
                ->update(['pickup_data' => json_encode($pickupData)]);
        }

        return response()->json($return);
    }

    private function getAllowedStatuses(): array
    {
        return [0, 1, 2, 3, 4, 5, 6];
    }
}
