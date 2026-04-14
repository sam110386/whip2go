<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickupsController extends LegacyAppController
{
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
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessLimitName = 'admin_pickups_limit';
        $limit = $request->input('Record.limit') ?: session($sessLimitName, 25);
        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $bookings = DB::table('vehicle_reservations')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->whereIn('vehicle_reservations.status', $this->getAllowedStatuses())
            ->where('vehicle_reservations.buy', 0)
            ->where('vehicle_reservations.ready_for_dealer', 1)
            ->select(
                'vehicle_reservations.*',
                'Vehicle.id as vehicle_table_id', 'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name',
                'OrderDepositRule.id as deposit_rule_id',
                'Renter.first_name as renter_first_name', 'Renter.last_name as renter_last_name'
            )
            ->orderByDesc('vehicle_reservations.id')
            ->paginate($limit);

        $checklists = $this->checklist;
        $readyForDealerStatus = $this->readyForDealerStatus;

        if ($request->ajax()) {
            return view('admin.reservation._admin_index', compact('bookings', 'checklists', 'readyForDealerStatus'));
        }

        return view('admin.reservation.index', compact('bookings', 'checklists', 'readyForDealerStatus'));
    }

    public function loadstatuschecklist(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderid = $this->decodeId($request->input('orderid'));
        $return = ['status' => false, 'message' => 'Sorry, something missing'];

        if (empty($orderid)) {
            return response()->json($return);
        }

        $bookingData = DB::table('vehicle_reservations')
            ->where('id', $orderid)
            ->select('checklists')
            ->first();

        if (empty($bookingData)) {
            return response()->json(['status' => false, 'message' => 'Sorry, booking not found']);
        }

        $bookingchecks = !empty($bookingData->checklists) ? json_decode($bookingData->checklists, true) : [];
        $checklist = $this->checklist;
        $dataUrl = config('app.url') . 'admin/reservation/pickups/updatechecklist';

        $html = view('admin.reservation._loadstatuschecklist', compact('bookingchecks', 'orderid', 'checklist', 'dataUrl'))->render();

        return response()->json(['status' => true, 'message' => 'loaded successfully', 'html' => $html]);
    }

    public function updatechecklist(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
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
            ->where('id', $orderid)
            ->select('checklists', 'id')
            ->first();

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

    public function openLicenseScanRequestPopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
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

        $isAdmin = 1;

        return view('admin.reservation._open_license_scan_request_popup', compact('booking', 'pickupData', 'isAdmin'));
    }

    public function pickUpUploadPhoto(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $booking = base64_decode($request->input('orderid'));
        $rule = DB::table('cs_order_deposit_rules')
            ->where('vehicle_reservation_id', $booking)
            ->select('id', 'pickup_data')
            ->first();

        $pickupData = json_decode($rule->pickup_data ?? '{}', true);
        $orderid = $booking;
        $isAdmin = 1;

        return view('admin.reservation.pick_up_upload_photo_popup', compact('pickupData', 'orderid', 'isAdmin'));
    }

    private function getAllowedStatuses(): array
    {
        return [0, 1, 2, 3, 4, 5, 6];
    }
}
