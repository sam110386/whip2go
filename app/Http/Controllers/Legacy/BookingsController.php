<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\RespondsWithCustomerAutocomplete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingsController extends LegacyAppController
{
    use RespondsWithCustomerAutocomplete;

    protected bool $shouldLoadLegacyModules = true;

    private function ownerUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent !== 0 ? $parent : (int) session()->get('userid', 0);
    }

    private function ownerUserIds(): array
    {
        $uid = (int) session()->get('userid', 0);
        $parent = (int) session()->get('userParentId', 0);
        $ids = array_unique(array_filter([$uid, $parent]));

        return $ids !== [] ? $ids : [0];
    }

    /**
     * Verify that a cs_orders row belongs to the current session user (or parent).
     */
    private function verifyOrderOwnership(int $orderId): ?\stdClass
    {
        return DB::table('cs_orders')
            ->where('id', $orderId)
            ->whereIn('user_id', $this->ownerUserIds())
            ->first();
    }

    // ─── Core CRUD ───────────────────────────────────────────────

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orders = DB::table('cs_orders')
            ->leftJoin('vehicles', 'cs_orders.vehicle_id', '=', 'vehicles.id')
            ->whereIn('cs_orders.user_id', $this->ownerUserIds())
            ->whereNotIn('cs_orders.status', [2, 3])
            ->select(
                'cs_orders.*',
                'vehicles.year as vehicle_year',
                'vehicles.make as vehicle_make',
                'vehicles.model as vehicle_model',
                'vehicles.vin_number as vehicle_vin',
            )
            ->orderByDesc('cs_orders.id')
            ->paginate(20);

        return view('bookings.index', [
            'title_for_layout' => 'My Bookings',
            'orders' => $orders,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->ownerUserId();
        $vehicles = DB::table('vehicles')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->get(['id', 'year', 'make', 'model', 'vin_number']);

        return view('bookings.create', [
            'title_for_layout' => 'Create Booking',
            'vehicles' => $vehicles,
        ]);
    }

    public function edit(Request $request, $id = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/bookings/index');
        }

        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return redirect('/bookings/index');
        }

        return view('bookings.edit', [
            'title_for_layout' => 'Edit Booking',
            'order' => $order,
        ]);
    }

    public function editsave(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = (int) $request->input('order_id', 0);
        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return redirect('/bookings/index')->with('error', 'Order not found or access denied.');
        }

        $updatable = $request->only([
            'start_datetime', 'end_datetime', 'pickup_address',
            'dropoff_address', 'notes',
        ]);
        $updatable['modified'] = now();

        DB::table('cs_orders')->where('id', $orderId)->update($updatable);

        return redirect('/bookings/index')->with('success', 'Booking updated successfully.');
    }

    public function bookingVehicleLease(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return redirect('/bookings/index');
    }

    // ─── Booking Lifecycle ───────────────────────────────────────

    public function overdue(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orders = DB::table('cs_orders')
            ->leftJoin('vehicles', 'cs_orders.vehicle_id', '=', 'vehicles.id')
            ->whereIn('cs_orders.user_id', $this->ownerUserIds())
            ->where('cs_orders.status', 1)
            ->where('cs_orders.end_datetime', '<', now())
            ->select(
                'cs_orders.*',
                'vehicles.year as vehicle_year',
                'vehicles.make as vehicle_make',
                'vehicles.model as vehicle_model',
                'vehicles.vin_number as vehicle_vin',
            )
            ->orderByDesc('cs_orders.id')
            ->paginate(20);

        return view('bookings.index', [
            'title_for_layout' => 'Overdue Bookings',
            'orders' => $orders,
        ]);
    }

    public function startBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order ID']);
        }

        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or access denied']);
        }

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status' => 1,
            'start_timing' => now(),
            'modified' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Booking started successfully.']);
    }

    public function loadcancelBooking(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.loadcancel', [
            'order' => $order,
        ]);
    }

    public function load_single_row(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = null;
        if ($orderId) {
            $order = DB::table('cs_orders')
                ->leftJoin('vehicles', 'cs_orders.vehicle_id', '=', 'vehicles.id')
                ->where('cs_orders.id', $orderId)
                ->whereIn('cs_orders.user_id', $this->ownerUserIds())
                ->select(
                    'cs_orders.*',
                    'vehicles.year as vehicle_year',
                    'vehicles.make as vehicle_make',
                    'vehicles.model as vehicle_model',
                    'vehicles.vin_number as vehicle_vin',
                )
                ->first();
        }

        return view('bookings.single_row', [
            'order' => $order,
        ]);
    }

    public function cancelBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order ID']);
        }

        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or access denied']);
        }

        DB::transaction(function () use ($orderId, $order) {
            DB::table('cs_orders')->where('id', $orderId)->update([
                'status' => 2,
                'modified' => now(),
            ]);

            if (!empty($order->vehicle_id)) {
                DB::table('vehicles')->where('id', $order->vehicle_id)->update([
                    'is_available' => 1,
                    'modified' => now(),
                ]);
            }
        });

        \Log::warning('BookingsController::cancelBooking - notification/refund not ported', ['order_id' => $orderId]);

        return response()->json(['status' => true, 'message' => 'Booking cancelled successfully.']);
    }

    public function loadcompleteBooking(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.loadcomplete', [
            'order' => $order,
        ]);
    }

    public function completeBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order ID']);
        }

        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or access denied']);
        }

        $extraFees = (float) $request->input('extra_fees', 0);
        $extraNotes = (string) $request->input('extra_notes', '');

        DB::transaction(function () use ($orderId, $order, $extraFees, $extraNotes) {
            $update = [
                'status' => 3,
                'end_timing' => now(),
                'modified' => now(),
            ];
            if ($extraFees > 0) {
                $update['extra_fees'] = $extraFees;
                $update['extra_notes'] = $extraNotes;
            }

            DB::table('cs_orders')->where('id', $orderId)->update($update);

            if (!empty($order->vehicle_id)) {
                DB::table('vehicles')->where('id', $order->vehicle_id)->update([
                    'is_available' => 1,
                    'modified' => now(),
                ]);
            }
        });

        \Log::warning('BookingsController::completeBooking - payment processing not ported', ['order_id' => $orderId]);

        return response()->json(['status' => true, 'message' => 'Booking completed successfully.']);
    }

    // ─── Popup / Utility Methods ─────────────────────────────────

    public function getinsurancepopup(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.insurance_popup', [
            'order' => $order,
        ]);
    }

    public function getinsurancetoken(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'status' => true,
            'token' => sha1(microtime(true) . random_int(1000, 9999)),
        ]);
    }

    public function getagreement(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::getagreement - agreement flow not ported');

        return response()->json([
            'status' => false,
            'message' => 'Agreement flow is not migrated yet.',
        ]);
    }

    public function changeccdetails(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.changeccdetails', [
            'order' => $order,
        ]);
    }

    public function processchangeccdetails(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::processchangeccdetails - CC change not ported');

        return response()->json([
            'status' => false,
            'message' => 'CC change flow is not migrated yet.',
        ]);
    }

    public function loadvehicleexpiretime(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.vehicleexpiretime', [
            'order' => $order,
        ]);
    }

    public function processvehicleexpiretime(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::processvehicleexpiretime - vehicle expire time processing not ported');

        return response()->json([
            'status' => false,
            'message' => 'Vehicle expire time processing is not migrated yet.',
        ]);
    }

    public function loadvehiclegps(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;
        $vehicle = null;

        if ($order && !empty($order->vehicle_id)) {
            $vehicle = DB::table('vehicles')
                ->where('id', $order->vehicle_id)
                ->first(['id', 'gps_serial_no', 'gps_provider', 'year', 'make', 'model']);
        }

        return view('bookings.vehiclegps', [
            'order' => $order,
            'vehicle' => $vehicle,
        ]);
    }

    public function updatevehiclegps(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $vehicleId = (int) $request->input('vehicle_id', 0);
        $gpsSerial = trim((string) $request->input('gps_serial_no', ''));

        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle ID']);
        }

        $vehicle = DB::table('vehicles')
            ->where('id', $vehicleId)
            ->whereIn('user_id', $this->ownerUserIds())
            ->first(['id']);

        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Vehicle not found or access denied']);
        }

        DB::table('vehicles')->where('id', $vehicleId)->update([
            'gps_serial_no' => $gpsSerial,
            'modified' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'GPS serial number updated.']);
    }

    public function updateodometer(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $order = $orderId ? $this->verifyOrderOwnership($orderId) : null;

        return view('bookings.updateodometer', [
            'order' => $order,
        ]);
    }

    public function saveBookingOdometer(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order ID']);
        }

        $order = $this->verifyOrderOwnership($orderId);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or access denied']);
        }

        $startOdo = $request->input('start_odometer');
        $endOdo = $request->input('end_odometer');

        $update = ['modified' => now()];
        if ($startOdo !== null) {
            $update['start_odometer'] = (float) $startOdo;
        }
        if ($endOdo !== null) {
            $update['end_odometer'] = (float) $endOdo;
        }

        DB::table('cs_orders')->where('id', $orderId)->update($update);

        return response()->json(['status' => true, 'message' => 'Odometer saved successfully.']);
    }

    public function pullVehicleOdometer(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::pullVehicleOdometer - GPS provider odometer pull not ported');

        return response()->json([
            'status' => false,
            'message' => 'GPS provider odometer pull is not migrated yet.',
        ]);
    }

    public function geotabkeylesslock(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::geotabkeylesslock - Geotab keyless lock not ported');

        return response()->json([
            'status' => false,
            'message' => 'Geotab keyless lock is not migrated yet.',
        ]);
    }

    public function geotabkeylessunlock(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::geotabkeylessunlock - Geotab keyless unlock not ported');

        return response()->json([
            'status' => false,
            'message' => 'Geotab keyless unlock is not migrated yet.',
        ]);
    }

    public function getDeclarationDoc(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::getDeclarationDoc - declaration doc not ported');

        return response()->json([
            'status' => false,
            'message' => 'Declaration document flow is not migrated yet.',
        ]);
    }

    public function overdue_booking_details(Request $request): View|RedirectResponse
    {
        return $this->overdue($request);
    }

    public function getVehicleCCMCard(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        \Log::warning('BookingsController::getVehicleCCMCard - CCM card not ported');

        return response()->json([
            'status' => false,
            'message' => 'Vehicle CCM card flow is not migrated yet.',
        ]);
    }

    public function customerautocomplete(Request $request): JsonResponse
    {
        return $this->respondCustomerAutocomplete($request, 'frontend');
    }
}
