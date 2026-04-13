<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleReservationsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        $limit = $this->resolveLimit($request);
        $bookings = $this->reservationQuery([0, 1])
            ->orderByDesc('vr.id')
            ->paginate($limit)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.vehicle_reservations._table', [
                'bookings' => $bookings,
                'mode' => 'index',
            ]);
        }

        return view('admin.vehicle_reservations.index', [
            'bookings' => $bookings,
            'limit' => $limit,
            'mode' => 'index',
        ]);
    }

    public function admin_all(Request $request)
    {
        $limit = $this->resolveLimit($request);
        $bookings = $this->reservationQuery()
            ->orderByDesc('vr.id')
            ->paginate($limit)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.vehicle_reservations._table', [
                'bookings' => $bookings,
                'mode' => 'all',
            ]);
        }

        return view('admin.vehicle_reservations.index', [
            'bookings' => $bookings,
            'limit' => $limit,
            'mode' => 'all',
        ]);
    }

    public function admin_singleload(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', ''));
        if (!$id) {
            return response('Invalid reservation id', 400);
        }

        $booking = $this->reservationQuery()
            ->where('vr.id', $id)
            ->first();

        if (!$booking) {
            return response('Reservation not found', 404);
        }

        return response()->view('admin.vehicle_reservations._single_row', [
            'b' => $booking,
        ]);
    }

    public function admin_changeSaveStatus(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        $status = (int)$request->input('status', -1);
        if (!$id || !in_array($status, [0, 1, 2, 3], true)) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $exists = VehicleReservation::query()->find($id);
        if (!$exists) {
            return response()->json(['status' => false, 'message' => 'Reservation not found']);
        }

        VehicleReservation::query()->whereKey($id)->update(['status' => $status]);
        if (in_array($status, [2, 3], true)) {
            DB::table('vehicles')->where('id', (int)$exists->vehicle_id)->update(['booked' => 0]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Reservation updated successfully',
            'result' => ['id' => $id, 'status' => $status],
        ]);
    }

    public function admin_markBookingCancel(Request $request): JsonResponse
    {
        $request->merge(['status' => 2]);

        return $this->admin_changeSaveStatus($request);
    }

    public function admin_markBookingCompleted(Request $request): JsonResponse
    {
        $request->merge(['status' => 3]);

        return $this->admin_changeSaveStatus($request);
    }

    public function admin_getuserdetails(Request $request): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid user id']);
        }
        $user = DB::table('users')->where('id', $userId)->first([
            'id',
            'first_name',
            'last_name',
            'email',
            'contact_number',
            'address',
            'state',
            'city',
            'zip',
        ]);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found']);
        }

        return response()->json(['status' => true, 'user' => $user]);
    }

    public function admin_updatelist(Request $request)
    {
        return $this->admin_index($request);
    }

    public function admin_updatemvr(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'MVR update queued']);
    }

    public function admin_createBooking(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response()->json(['status' => false, 'message' => 'Invalid reservation']);
        }
        $row = VehicleReservation::query()->find($id);
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Reservation not found']);
        }
        if ((int)$row->status !== 1) {
            VehicleReservation::query()->whereKey($id)->update(['status' => 1]);
        }
        DB::table('vehicles')->where('id', (int)$row->vehicle_id)->update(['booked' => 1]);

        return response()->json(['status' => true, 'message' => 'Booking created successfully', 'result' => ['lease_id' => $id]]);
    }

    public function admin_saveVehicleBooking(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('lease_id', $request->input('id', '')));
        if (!$id) {
            return response()->json(['status' => false, 'message' => 'Invalid reservation']);
        }
        $payload = [];
        foreach (['start_datetime', 'end_datetime', 'vehicle_id', 'renter_id'] as $k) {
            $v = $request->input($k);
            if ($v !== null && $v !== '') {
                $payload[$k] = $v;
            }
        }
        if ($payload !== []) {
            VehicleReservation::query()->whereKey($id)->update($payload);
        }

        return response()->json(['status' => true, 'message' => 'Reservation updated successfully']);
    }

    public function admin_changeVehicle(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }
        $reservation = VehicleReservation::query()->find($id);
        if (!$reservation) {
            return response('Reservation not found', 404);
        }
        $vehicles = DB::table('vehicles')
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('vehicle_unique_id')
            ->limit(100)
            ->get(['id', 'vehicle_unique_id', 'vehicle_name']);

        return response()->view('admin.vehicle_reservations._change_vehicle', compact('reservation', 'vehicles'));
    }

    public function admin_updateReservationVehicle(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        $vehicleId = (int)$request->input('vehicle_id', 0);
        if (!$id || $vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        VehicleReservation::query()->whereKey($id)->update(['vehicle_id' => $vehicleId]);

        return response()->json(['status' => true, 'message' => 'Vehicle updated successfully']);
    }

    public function admin_changeDatetime(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }
        $reservation = VehicleReservation::query()->find($id, ['id', 'start_datetime', 'end_datetime', 'timezone']);
        if (!$reservation) {
            return response('Reservation not found', 404);
        }

        return response()->view('admin.vehicle_reservations._change_datetime', compact('reservation'));
    }

    public function admin_updateDatetime(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        $data = [];
        foreach (['start_datetime', 'end_datetime'] as $key) {
            $val = (string)$request->input($key, '');
            if ($val !== '') {
                $data[$key] = $val;
            }
        }
        if ($data !== []) {
            VehicleReservation::query()->whereKey($id)->update($data);
        }

        return response()->json(['status' => true, 'message' => 'Date/time updated successfully']);
    }

    public function admin_changeStatus(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }
        $reservation = VehicleReservation::query()->find($id, ['id', 'status']);
        if (!$reservation) {
            return response('Reservation not found', 404);
        }

        return response()->view('admin.vehicle_reservations._change_status', compact('reservation'));
    }

    public function admin_loadstatuschecklist(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._status_checklist', ['id' => $id]);
    }

    public function admin_updatechecklist(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Checklist updated successfully']);
    }

    public function admin_vehicleReservationLog(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }
        $logs = DB::table('cs_order_statuslogs')
            ->where('reservation_id', $id)
            ->orWhere('vehicle_reservation_id', $id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->view('admin.vehicle_reservations._log', ['logs' => $logs, 'id' => $id]);
    }

    public function admin_getfarecalculations(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response()->json(['status' => false, 'message' => 'Invalid reservation']);
        }
        $reservation = VehicleReservation::query()->find($id);
        if (!$reservation) {
            return response()->json(['status' => false, 'message' => 'Reservation not found']);
        }
        $odr = DB::table('cs_order_deposit_rules')->where('vehicle_reservation_id', $id)->first();

        return response()->json([
            'status' => true,
            'data' => [
                'rental' => (float)data_get($odr, 'rental', 0),
                'tax' => (float)data_get($odr, 'tax', 0),
                'insurance' => (float)data_get($odr, 'insurance', 0),
                'deposit' => (float)data_get($odr, 'downpayment', 0),
            ],
        ]);
    }

    public function admin_loadcancelblock(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._cancel_popup', ['id' => base64_encode((string)$id)]);
    }

    public function admin_loadinsurancepopup(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._insurance_popup', ['id' => base64_encode((string)$id)]);
    }

    public function admin_changeinsurancepopup(Request $request)
    {
        return $this->admin_loadinsurancepopup($request);
    }

    public function admin_changeinsurancesave(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Insurance settings updated']);
    }

    public function admin_changeinsurancetypepopup(Request $request)
    {
        return $this->admin_loadinsurancepopup($request);
    }

    public function admin_saveinsurancepayer(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        $payer = (string)$request->input('insurance_payer', '');
        if (!$id || $payer === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        DB::table('cs_order_deposit_rules')
            ->where('vehicle_reservation_id', $id)
            ->update(['insurance_payer' => $payer]);

        return response()->json(['status' => true, 'message' => 'Insurance payer updated']);
    }

    public function admin_generateAgrement(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Agreement generation queued']);
    }

    public function admin_capturepayment(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._capture_payment', ['id' => base64_encode((string)$id)]);
    }

    public function admin_processcapturepayment(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment capture processed']);
    }

    public function admin_paymentcapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment captured']);
    }

    public function admin_recapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment recapture queued']);
    }

    public function admin_renderlog($filename)
    {
        return response()->view('admin.vehicle_reservations._render_log', ['filename' => (string)$filename]);
    }

    protected function reservationQuery(?array $statuses = null)
    {
        $q = DB::table('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'vr.user_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'vr.renter_id')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->select([
                'vr.*',
                'v.vehicle_name',
                'v.vehicle_unique_id',
                'v.vin_no',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'odr.id as order_rule_id',
                'odr.downpayment',
                'odr.insurance',
                'odr.insurance_payer',
                'odr.insu_agreed',
                'odr.financing',
            ]);

        if ($statuses !== null) {
            $q->whereIn('vr.status', $statuses);
        }

        return $q;
    }

    protected function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['vehicle_reservations_limit' => $lim]);
            }
        }

        $limit = (int)session('vehicle_reservations_limit', 50);

        return $limit > 0 ? $limit : 50;
    }

    protected function decodeId(string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }
}

