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

    public function index(Request $request)
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

    public function all(Request $request)
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

    public function singleload(Request $request)
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

    public function changeSaveStatus(Request $request): JsonResponse
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

    public function markBookingCancel(Request $request): JsonResponse
    {
        $request->merge(['status' => 2]);

        return $this->changeSaveStatus($request);
    }

    public function markBookingCompleted(Request $request): JsonResponse
    {
        $request->merge(['status' => 3]);

        return $this->changeSaveStatus($request);
    }

    public function getuserdetails(Request $request): JsonResponse
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

    public function updatelist(Request $request)
    {
        return $this->index($request);
    }

    public function updatemvr(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'MVR update queued']);
    }

    public function createBooking(Request $request): JsonResponse
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

    public function saveVehicleBooking(Request $request): JsonResponse
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

    public function changeVehicle(Request $request)
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

    public function updateReservationVehicle(Request $request): JsonResponse
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        $vehicleId = (int)$request->input('vehicle_id', 0);
        if (!$id || $vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        VehicleReservation::query()->whereKey($id)->update(['vehicle_id' => $vehicleId]);

        return response()->json(['status' => true, 'message' => 'Vehicle updated successfully']);
    }

    public function changeDatetime(Request $request)
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

    public function updateDatetime(Request $request): JsonResponse
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

    public function changeStatus(Request $request)
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

    public function loadstatuschecklist(Request $request)
    {
        $id = $this->decodeId((string)$request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._status_checklist', ['id' => $id]);
    }

    public function updatechecklist(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Checklist updated successfully']);
    }

    public function vehicleReservationLog(Request $request)
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

    public function getfarecalculations(Request $request): JsonResponse
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

    public function loadcancelblock(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._cancel_popup', ['id' => base64_encode((string)$id)]);
    }

    public function loadinsurancepopup(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._insurance_popup', ['id' => base64_encode((string)$id)]);
    }

    public function changeinsurancepopup(Request $request)
    {
        return $this->loadinsurancepopup($request);
    }

    public function changeinsurancesave(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Insurance settings updated']);
    }

    public function changeinsurancetypepopup(Request $request)
    {
        return $this->loadinsurancepopup($request);
    }

    public function saveinsurancepayer(Request $request): JsonResponse
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

    public function generateAgrement(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Agreement generation queued']);
    }

    public function capturepayment(Request $request)
    {
        $id = $this->decodeId((string)$request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._capture_payment', ['id' => base64_encode((string)$id)]);
    }

    public function processcapturepayment(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment capture processed']);
    }

    public function paymentcapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment captured']);
    }

    public function recapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment recapture queued']);
    }

    public function renderlog($filename)
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

    // ── Plaid / Financial methods (Plaid API not yet ported) ──────────

    public function getplaidrecord(Request $request): JsonResponse
    {
        $userId = (int)base64_decode((string)$request->input('userid', ''));
        if ($userId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid user id']);
        }

        $plaid = DB::table('plaid_users')->where('user_id', $userId)->first();
        if (!$plaid || empty(json_decode($plaid->metadata ?? '', true))) {
            return response()->json(['status' => false, 'message' => "Sorry, User didnt add his bank details yet"]);
        }

        return response()->json([
            'status' => true,
            'message' => '',
            'userid' => $userId,
            'plaidtoken' => $plaid->token ?? '',
            'view' => '<p>Plaid record loaded (view partial not yet ported)</p>',
        ]);
    }

    public function getplaidbalance(Request $request): JsonResponse
    {
        \Log::warning('getplaidbalance: Plaid API not yet ported to Laravel.');

        return response()->json([
            'status' => false,
            'message' => 'Plaid API not yet ported to Laravel',
            'balance' => '$0',
        ]);
    }

    public function bankstatement(Request $request): JsonResponse
    {
        \Log::warning('bankstatement: Plaid API not yet ported to Laravel.');

        return response()->json([
            'status' => false,
            'message' => 'Plaid bank statement API not yet ported to Laravel',
            'transactions' => [],
        ]);
    }

    public function provenincome(Request $request): JsonResponse
    {
        $userId = $request->input('pk');
        $value = $request->input('value');
        $name = (string)$request->input('name', '');

        if (empty($userId) || empty($value)) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, something missing']);
        }

        $existing = DB::table('user_incomes')->where('user_id', $userId)->first();

        if ($name === 'statedIncome') {
            $data = ['income' => $value, 'user_id' => $userId];
            if ($existing) {
                DB::table('user_incomes')->where('id', $existing->id)->update($data);
            } else {
                DB::table('user_incomes')->insert($data);
            }
            return response()->json(['status' => 'success', 'message' => 'Saved successfully']);
        }

        $data = ['provenincome' => $value, 'user_id' => $userId];
        if ($existing) {
            DB::table('user_incomes')->where('id', $existing->id)->update($data);
        } else {
            DB::table('user_incomes')->insert($data);
        }

        if ($existing && (float)($existing->income ?? 0) <= (float)$value) {
            VehicleReservation::query()
                ->where('renter_id', $userId)
                ->whereIn('status', [0, 1])
                ->update(['income_flag' => 4]);
        }

        return response()->json(['status' => 'success', 'message' => 'Saved successfully']);
    }

    // ── Vehicle Health methods ────────────────────────────────────────

    public function checkodometer(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        if ($vehicleId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, something missing']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as cs', 'cs.user_id', '=', 'v.user_id')
            ->where('v.id', $vehicleId)
            ->first([
                'v.id',
                'v.passtime_serialno',
                'v.autopi_unit_id',
                'cs.passtime as gps_provider',
                'cs.passtime_dealerid',
            ]);

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found']);
        }

        \Log::warning("checkodometer: GPS provider call stubbed for vehicle {$vehicleId}.");

        return response()->json([
            'status' => 'error',
            'message' => 'GPS provider call not yet ported. Check GPS provider setting & vehicle serial number.',
            'vehicle' => $vehicle,
        ]);
    }

    public function checkStarterInterrupt(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        $orderId = $request->input('orderid');

        if ($vehicleId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, something missing']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as cs', 'cs.user_id', '=', 'v.user_id')
            ->where('v.id', $vehicleId)
            ->first([
                'v.passtime_serialno',
                'cs.passtime_dealerid',
                'cs.passtime as gps_provider',
            ]);

        if (!$vehicle || empty($vehicle->passtime_serialno)) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle Passtime serial # not set']);
        }
        if (empty($vehicle->gps_provider)) {
            return response()->json(['status' => 'error', 'message' => "Vehicle Owner's GPS provider setting not set"]);
        }

        \Log::warning("checkStarterInterrupt: GPS starter check stubbed for vehicle {$vehicleId}.");

        return response()->json([
            'status' => 'success',
            'message' => 'Starter interrupt check completed (stubbed)',
            'html' => '<p>Starter interrupt confirmation (view not yet ported)</p>',
        ]);
    }

    public function disableStaterInterrupt(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        $disable = (bool)$request->input('disable', false);

        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Sorry, something missing']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as cs', 'cs.user_id', '=', 'v.user_id')
            ->leftJoin('vehicle_settings as vs', 'vs.vehicle_id', '=', 'v.id')
            ->where('v.id', $vehicleId)
            ->first([
                'v.id',
                'v.passtime_serialno',
                'v.autopi_unit_id',
                'v.passtime_status',
                'v.user_id',
                'cs.passtime as gps_provider',
                'cs.passtime_dealerid',
                'vs.vehicle_id as vs_vehicle_id',
            ]);

        if (!$vehicle || empty($vehicle->passtime_serialno)) {
            return response()->json(['status' => false, 'message' => 'Vehicle Passtime serial # not set']);
        }
        if (empty($vehicle->gps_provider)) {
            return response()->json(['status' => false, 'message' => "Vehicle Owner's GPS provider setting not set"]);
        }

        $newStatus = $disable ? 0 : 1;
        $action = $disable ? 'deactivation' : 'activation';

        \Log::warning("disableStaterInterrupt: Passtime {$action} stubbed for vehicle {$vehicleId}.");

        DB::table('vehicles')->where('id', $vehicleId)->update(['passtime_status' => $newStatus]);

        return response()->json([
            'status' => true,
            'message' => "Starter interrupt {$action} processed (stubbed). Vehicle passtime_status set to {$newStatus}.",
        ]);
    }

    public function staterInterruptWorks(Request $request): JsonResponse
    {
        $orderId = (int)$request->input('orderid', 0);
        if ($orderId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, something missing']);
        }

        \Log::warning("staterInterruptWorks: Passtime check stubbed for reservation {$orderId}.");

        VehicleReservation::query()->whereKey($orderId)->update(['gps' => 1, 'gps2' => 1]);

        return response()->json(['status' => 'success', 'message' => 'Request processed successfully']);
    }

    // ── Insurance doc method ──────────────────────────────────────────

    public function insudoc(Request $request): JsonResponse
    {
        $id = (int)base64_decode((string)$request->input('id', ''));
        if ($id <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid reservation id']);
        }

        $reservation = DB::table('vehicle_reservations as vr')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'vr.renter_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'vr.user_id')
            ->where('vr.id', $id)
            ->first([
                'vr.id',
                'vr.user_id',
                'odr.id as odr_id',
                'odr.insurance_payer',
                'v.make',
                'v.year',
                'v.model',
                'v.vin_no',
                'v.insurance_company',
                'v.insurance_policy_no',
                'v.insurance_policy_date',
                'v.insurance_policy_exp_date',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
            ]);

        if (!$reservation) {
            return response()->json(['status' => false, 'message' => "Sorry, you can't perform this action now."]);
        }

        if ((int)($reservation->insurance_payer ?? 0) === 3) {
            $payerDoc = DB::table('insurance_payers')
                ->where('order_deposit_rule_id', $reservation->odr_id)
                ->first(['insurance_card']);

            if (!$payerDoc || empty($payerDoc->insurance_card)) {
                return response()->json([
                    'status' => false,
                    'message' => "Sorry, Driver didnt upload insurance token yet. He agreed to manage it himself.",
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'result' => ['file' => '/files/reservation/' . $payerDoc->insurance_card],
            ]);
        }

        \Log::warning("insudoc: Insurance document generation stubbed for reservation {$id}.");

        return response()->json([
            'status' => false,
            'message' => 'Insurance document generation not yet ported to Laravel.',
            'result' => ['file' => null],
        ]);
    }

    // ── Goal / Matrix methods ─────────────────────────────────────────

    public function goalrecalculate(Request $request, $id = null)
    {
        $ruleId = $id ? (int)base64_decode((string)$id) : 0;
        if ($ruleId <= 0) {
            return redirect('/admin/vehicle-reservations');
        }

        $odr = DB::table('cs_order_deposit_rules')->where('id', $ruleId)->first();
        if (!$odr) {
            return redirect('/admin/vehicle-reservations');
        }

        $odrArr = (array)$odr;
        $odrArr['rent_opt'] = !empty($odrArr['rent_opt']) ? json_decode($odrArr['rent_opt'], true) : [];
        $odrArr['initial_fee_opt'] = !empty($odrArr['initial_fee_opt']) ? json_decode($odrArr['initial_fee_opt'], true) : [];
        $odrArr['deposit_opt'] = !empty($odrArr['deposit_opt']) ? json_decode($odrArr['deposit_opt'], true) : [];
        $odrArr['duration_opt'] = !empty($odrArr['duration_opt']) ? json_decode($odrArr['duration_opt'], true) : [];
        $odrArr['calculation'] = !empty($odrArr['calculation']) ? json_decode($odrArr['calculation'], true) : [];
        $odrArr['goal'] = 'custom';
        $odrArr['miles'] = floor(((float)($odrArr['miles'] ?? 0)) * 365 / 12);

        $vrId = (int)($odrArr['vehicle_reservation_id'] ?? 0);
        $vr = DB::table('vehicle_reservations')
            ->where('id', $vrId)
            ->first(['renter_id', 'vehicle_id', 'initial_discount', 'discount_desc']);

        $vehicleId = $vr ? (int)$vr->vehicle_id : 0;
        $vehicleRow = DB::table('vehicles')
            ->where('id', $vehicleId)
            ->first(['id', 'msrp', 'allowed_miles']);

        $allowedMiles = $vehicleRow->allowed_miles ?? 0;
        $k = $allowedMiles ? (int)ceil($allowedMiles * 30) : 1000;
        $milesOptions = [];
        while ($k <= 15000) {
            $milesOptions[$k] = $k;
            $k += 500;
        }

        $vehicles = [
            'id' => $vehicleRow->id ?? 0,
            'miles_options' => $milesOptions,
        ];

        return view('admin.vehicle_reservations.goalrecalculate', [
            'OrderDepositRule' => $odrArr,
            'vehicles' => $vehicles,
            'VehicleReservationObj' => $vr ? (array)$vr : [],
        ]);
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (empty($offer)) {
            return response()->json(['status' => false, 'message' => 'Missing VehicleOffer data']);
        }

        \Log::warning('getVehicleDynamicFareMatrix: fare matrix calculation stubbed.');

        return response()->json([
            'status' => false,
            'message' => 'Dynamic fare matrix calculation not yet ported to Laravel',
            'result' => [],
        ]);
    }

    public function saveGoalRecalculation(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (!empty($offer['json'])) {
            $offer = array_merge($offer, json_decode($offer['json'], true) ?: []);
        }

        if (empty($offer['id'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, required input data are missing.']);
        }

        $dataToSave = [
            'totalcost' => $offer['totalcost'] ?? 0,
            'goal' => $offer['goal'] ?? '',
            'downpayment' => $offer['downpayment'] ?? 0,
            'miles' => sprintf('%0.2f', (($offer['miles'] ?? 0) / 30)),
            'insurance' => $offer['insurance'] ?? 0,
            'emf' => $offer['emf'] ?? 0,
            'total_program_cost' => $offer['total_program_cost'] ?? 0,
            'rental' => $offer['day_rent'] ?? 0,
            'num_of_days' => $offer['days'] ?? 0,
            'tax' => $offer['tax_rate'] ?? 0,
            'total_initial_fee' => $offer['total_initial_fee'] ?? 0,
            'write_down_allocation' => $offer['write_down_allocation'] ?? 0,
            'finance_allocation' => $offer['finance_allocation'] ?? 0,
            'maintenance_allocation' => $offer['maintenance_allocation'] ?? 0,
            'financing_total' => $offer['financing_total'] ?? 0,
            'disposition_fee' => $offer['disposition_fee'] ?? 0,
            'calculation' => $offer['json'] ?? '',
        ];

        DB::table('cs_order_deposit_rules')
            ->where('id', (int)$offer['id'])
            ->update($dataToSave);

        if (!empty($offer['clear_promo']) && (int)$offer['clear_promo'] === 1) {
            $rule = DB::table('cs_order_deposit_rules')
                ->where('id', (int)$offer['id'])
                ->value('vehicle_reservation_id');

            if ($rule) {
                VehicleReservation::query()
                    ->whereKey($rule)
                    ->update(['initial_discount' => 0, 'discount_desc' => null]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Data updated successfully']);
    }

    public function savemanualcalculation(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (empty($offer['id'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, required input data are missing.']);
        }

        $calc = $offer['calculation'] ?? [];

        $dataToSave = [
            'totalcost' => $calc['totalcost'] ?? 0,
            'goal' => $calc['goal'] ?? '',
            'downpayment' => $calc['downpayment'] ?? 0,
            'miles' => sprintf('%0.2f', ($offer['miles'] ?? 0)),
            'insurance' => $offer['insurance'] ?? 0,
            'emf' => $offer['emf'] ?? 0,
            'total_program_cost' => $calc['total_program_cost'] ?? 0,
            'rental' => $calc['rental'] ?? 0,
            'base_rent' => $calc['base_dayrent'] ?? 0,
            'num_of_days' => $calc['num_of_days'] ?? 0,
            'tax' => $calc['tax_rate'] ?? 0,
            'initial_fee' => $calc['initial_fee'] ?? 0,
            'total_initial_fee' => $calc['initial_fee'] ?? 0,
            'write_down_allocation' => $calc['write_down_allocation'] ?? 0,
            'finance_allocation' => $calc['finance_allocation'] ?? 0,
            'maintenance_allocation' => $calc['maintenance_allocation'] ?? 0,
            'financing_total' => $calc['financing_total'] ?? 0,
            'disposition_fee' => $calc['disposition_fee'] ?? 0,
            'calculation' => json_encode($calc),
        ];

        DB::table('cs_order_deposit_rules')
            ->where('id', (int)$offer['id'])
            ->update($dataToSave);

        return response()->json(['status' => true, 'message' => 'Data updated successfully']);
    }

    // ── Selling / PTO methods (stubs) ─────────────────────────────────

    public function download_vehicle_images(Request $request): JsonResponse
    {
        \Log::warning('download_vehicle_images: Not yet ported to Laravel.');

        return response()->json(['status' => false, 'message' => 'Not yet ported to Laravel']);
    }

    public function vehicleSellingOpions(Request $request)
    {
        $orderId = (int)base64_decode((string)$request->input('orderid', ''));
        if ($orderId <= 0) {
            return response('Invalid reservation', 400);
        }

        $booking = DB::table('vehicle_reservations as vr')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->leftJoin('cs_deposit_rules as dr', 'dr.vehicle_id', '=', 'vr.vehicle_id')
            ->where('vr.id', $orderId)
            ->first([
                'vr.id',
                'odr.id as odr_id',
                'odr.selling_option',
                'dr.free_two_move',
            ]);

        return view('admin.vehicle_reservations.vehicle_selling_options', [
            'booking' => $booking,
            'free_two_move' => !empty($booking->free_two_move) ? json_decode($booking->free_two_move, true) : [],
            'selling_option' => !empty($booking->selling_option) ? json_decode($booking->selling_option, true) : [],
            'is_admin' => 1,
        ]);
    }

    public function vehicleSellingOpionAgreeToSell(Request $request)
    {
        $orderId = (int)$request->input('orderid', 0);
        if ($orderId <= 0) {
            return response('Invalid order', 400);
        }

        $booking = DB::table('cs_order_deposit_rules')
            ->where('id', $orderId)
            ->first(['id', 'selling_option']);

        $sellingOption = [];
        if ($booking && !empty($booking->selling_option)) {
            $sellingOption = json_decode($booking->selling_option, true) ?: [];
        }

        return view('admin.vehicle_reservations.vehicle_selling_agree', [
            'booking' => $booking,
            'selling_option' => $sellingOption,
        ]);
    }

    public function saveVehicleAgreeToSell(Request $request): JsonResponse
    {
        \Log::warning('saveVehicleAgreeToSell: File upload and email notification stubbed.');

        return response()->json([
            'status' => false,
            'message' => 'Vehicle sell agreement save not yet fully ported to Laravel',
        ]);
    }

    public function vehicleFree2moveAgreement(Request $request): JsonResponse
    {
        $reference = $request->input('reference', '');
        if (empty($reference)) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.', 'agreement' => []]);
        }

        \Log::warning("vehicleFree2moveAgreement: Free2Move API stubbed for reference {$reference}.");

        return response()->json([
            'status' => false,
            'message' => 'Free2Move agreement API not yet ported to Laravel',
            'agreement' => [],
        ]);
    }

    public function pushToDealer(Request $request, $id = null, $flag = 0): JsonResponse
    {
        $decodedId = $id ? (int)base64_decode((string)$id) : 0;
        if ($decodedId <= 0) {
            return response()->json(['status' => false, 'message' => "Sorry, you can't perform this action now"]);
        }

        $booking = DB::table('vehicle_reservations')->where('id', $decodedId)->first(['id', 'ready_for_dealer']);
        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Reservation not found']);
        }

        DB::table('vehicle_reservations')->where('id', $decodedId)->update(['ready_for_dealer' => (int)$flag]);

        \Log::warning("pushToDealer: Email notification stubbed for reservation {$decodedId}, flag={$flag}.");

        $msg = (int)$flag === 1 ? 'pushed to dealer' : 'removed from dealer list';

        return response()->json(['status' => true, 'message' => "Booking has been {$msg} successfully"]);
    }

    public function saveVehicleSellingOption(Request $request): JsonResponse
    {
        \Log::warning('saveVehicleSellingOption: Not yet ported to Laravel.');

        return response()->json(['status' => false, 'message' => 'Vehicle selling option save not yet ported to Laravel']);
    }

    // ── Protected helpers ─────────────────────────────────────────────

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
}

