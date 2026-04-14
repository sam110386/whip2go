<?php

namespace App\Http\Controllers\Legacy;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CakePHP `LeasesController` — user-facing vehicle lease / unavailability.
 */
class LeasesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const STATUS_FAIL = 0;

    private const STATUS_SUCCESS = 1;

    private function legacyOwnerUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent !== 0 ? $parent : (int) session()->get('userid', 0);
    }

    /**
     * Parse calendar `start` / `end` query (unix seconds or date string) to Y-m-d in app timezone.
     */
    private function calendarQueryToDateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value, config('app.timezone'))->format('Y-m-d');
        }
        try {
            return Carbon::parse((string) $value, config('app.timezone'))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cake `Time::format('m/d/Y')` display for lease dates (DB stores Y-m-d).
     */
    private function formatLeaseDateForInput(?string $ymd): string
    {
        if ($ymd === null || $ymd === '') {
            return '';
        }
        try {
            return Carbon::parse($ymd)->format('m/d/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Normalize posted lease dates (m/d/Y from legacy picker or Y-m-d).
     */
    private function parseLeaseDateToServer(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        foreach (['m/d/Y', 'n/j/Y', 'Y-m-d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function csLeaseInput(Request $request): array
    {
        $nested = $request->input('data.CsLease', []);
        if (is_array($nested) && $nested !== []) {
            return $nested;
        }
        $flat = $request->input('CsLease', []);

        return is_array($flat) ? $flat : [];
    }

    public function createVehicleUnavailability(Request $request, ?string $vehicleid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $id = $this->decodeId($vehicleid ?? '');
        if ($id === null || $id <= 0) {
            return redirect('/vehicles/index');
        }

        return view('leases.create_vehicle_unavailability', [
            'vehicleid' => $id,
        ]);
    }

    /** Cake default URL inflection: `create_vehicle_unavailability`. */
    public function create_vehicle_unavailability(Request $request, ?string $vehicleid = null): View|RedirectResponse
    {
        return $this->createVehicleUnavailability($request, $vehicleid);
    }

    public function load(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json([], 401);
        }
        $userid = $this->legacyOwnerUserId();
        $startDate = $this->calendarQueryToDateString($request->query('start'));
        $endDate = $this->calendarQueryToDateString($request->query('end'));
        $vehicleid = (int) $request->query('vehicle_id', 0);
        if ($startDate === null || $endDate === null || $vehicleid <= 0) {
            return response()->json([]);
        }

        $rows = DB::table('cs_lease_unavailabilities')
            ->where('vehicle_id', $vehicleid)
            ->where('user_id', $userid)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get(['id', 'date']);

        $responseData = [];
        foreach ($rows as $row) {
            $dt = Carbon::parse($row->date)->startOfDay()->timezone(config('app.timezone'));
            $responseData[] = [
                'id' => (int) $row->id,
                'allDay' => false,
                'start' => $dt->toIso8601String(),
                'end' => $dt->toIso8601String(),
            ];
        }

        return response()->json($responseData);
    }

    public function remove(Request $request, ?string $id = null): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => self::STATUS_FAIL, 'message' => 'Unauthorized', 'result' => []], 401);
        }
        $return = ['status' => self::STATUS_FAIL, 'message' => 'Sorry, you are not authorize owner of selected Vehicle', 'result' => []];
        if ($id !== null && $id !== '') {
            DB::table('cs_lease_unavailabilities')->where('id', $id)->delete();
            $return = ['status' => self::STATUS_SUCCESS, 'message' => 'Vehicle Unavailability deleted for selected date', 'result' => []];
        }

        return response()->json($return);
    }

    public function addunavailability(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => self::STATUS_FAIL, 'message' => 'Unauthorized', 'result' => []], 401);
        }
        $userid = $this->legacyOwnerUserId();
        $vehicleId = (int) $request->input('vehicle_id', 0);
        $return = ['status' => self::STATUS_FAIL, 'message' => 'Invalid Json', 'result' => []];

        if ($vehicleId <= 0) {
            return response()->json($return);
        }

        $vehicle = DB::table('vehicles')
            ->where('id', $vehicleId)
            ->where('user_id', $userid)
            ->first(['id']);
        $return = ['status' => self::STATUS_FAIL, 'message' => 'Sorry, you are not authorize owner of selected Vehicle', 'result' => []];
        if ($vehicle === null) {
            return response()->json($return);
        }

        $startRaw = $request->input('start');
        $endRaw = $request->input('end');
        $return = ['status' => self::STATUS_FAIL, 'message' => 'Sorry, please select correct date range', 'result' => []];
        if ($startRaw === null || $startRaw === '' || $endRaw === null || $endRaw === '') {
            return response()->json($return);
        }
        try {
            $startDate = Carbon::parse((string) $startRaw)->format('Y-m-d');
            $endDate = Carbon::parse((string) $endRaw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return response()->json($return);
        }

        for ($d = $startDate; strtotime($d) <= strtotime($endDate); $d = date('Y-m-d', strtotime('+1 day', strtotime($d)))) {
            $existing = DB::table('cs_lease_unavailabilities')
                ->where('vehicle_id', $vehicleId)
                ->where('date', $d)
                ->first(['id']);
            $payload = [
                'user_id' => $userid,
                'vehicle_id' => $vehicleId,
                'date' => $d,
                'modified' => now(),
            ];
            if ($existing) {
                DB::table('cs_lease_unavailabilities')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created'] = now();
                DB::table('cs_lease_unavailabilities')->insert($payload);
            }
        }

        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'message' => 'Vehicle Unavailability data is saved successfully.',
            'result' => [],
        ]);
    }

    public function createVehicleLease(Request $request, ?string $vehicleid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $decodedVehicleId = $this->decodeId($vehicleid ?? '');
        if ($decodedVehicleId === null || $decodedVehicleId <= 0) {
            return redirect('/vehicles/index');
        }
        $userid = $this->legacyOwnerUserId();

        $leaseInput = $this->csLeaseInput($request);
        if ($request->isMethod('post') && $leaseInput !== []) {
            $start = $this->parseLeaseDateToServer($leaseInput['start_date'] ?? null);
            $end = $this->parseLeaseDateToServer($leaseInput['end_date'] ?? null);
            $postedVehicleId = (int) ($leaseInput['vehicle_id'] ?? 0);
            if ($start !== null && $end !== null && $postedVehicleId > 0 && $postedVehicleId === $decodedVehicleId) {
                $existing = DB::table('cs_leases')->where('vehicle_id', $decodedVehicleId)->first(['id']);
                $now = now();
                $row = [
                    'vehicle_id' => $decodedVehicleId,
                    'user_id' => $userid,
                    'pickup_address' => $leaseInput['pickup_address'] ?? null,
                    'lat' => $leaseInput['lat'] ?? null,
                    'lng' => $leaseInput['lng'] ?? null,
                    'start_date' => $start,
                    'end_date' => $end,
                    'details' => $leaseInput['details'] ?? null,
                    'modified' => $now,
                ];
                if ($existing) {
                    DB::table('cs_leases')->where('id', $existing->id)->update($row);
                } else {
                    $row['created'] = $now;
                    DB::table('cs_leases')->insert($row);
                }
                session()->flash('success', 'Records data saved successfully');

                return redirect('/vehicles/index');
            }
        }

        $vehicle = DB::table('vehicles')->where('id', $decodedVehicleId)->first();
        if ($vehicle === null) {
            return redirect('/vehicles/index');
        }
        $lease = DB::table('cs_leases')->where('vehicle_id', $decodedVehicleId)->first();

        $vehicleArr = (array) $vehicle;
        $leaseArr = $lease ? (array) $lease : [];
        if ($leaseArr !== []) {
            $leaseArr['start_date'] = $this->formatLeaseDateForInput($leaseArr['start_date'] ?? null);
            $leaseArr['end_date'] = $this->formatLeaseDateForInput($leaseArr['end_date'] ?? null);
        }

        return view('leases.create_vehicle_lease', [
            'title_for_layout' => 'Vehicle Lease',
            'data' => [
                'Vehicle' => $vehicleArr,
                'CsLease' => $leaseArr,
            ],
            'vehicleIdEncoded' => $this->encodeId($decodedVehicleId),
            'googleMapsKey' => (string) config('services.google.maps_api_key', ''),
        ]);
    }

    /** Cake default URL inflection: `create_vehicle_lease`. */
    public function create_vehicle_lease(Request $request, ?string $vehicleid = null): View|RedirectResponse
    {
        return $this->createVehicleLease($request, $vehicleid);
    }
}
