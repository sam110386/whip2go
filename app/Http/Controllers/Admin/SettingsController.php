<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsSetting as LegacyCsSetting;
use App\Models\Legacy\DepositTemplate as LegacyDepositTemplate;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use App\Models\Legacy\VehicleLocation as LegacyVehicleLocation;
use App\Services\Legacy\AutoPiFleetClient;
use App\Services\Legacy\GeotabClient;
use App\Services\Legacy\GeotabkeylessClient;
use App\Services\Legacy\OnestepGpsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request, $userId)
    {
        $decodedUserId = $this->decodeId($userId);
        if (!$decodedUserId) {
            return redirect('/admin/users/index');
        }

        if ($request->isMethod('POST')) {
            $payload = $request->input('CsSetting', []);
            unset($payload['encode_user_id']);
            $locations = $request->input('VehicleLocation', []);
            $depositTemplate = $request->input('DepositTemplate', []);

            $settingData = $payload;
            $settingData['user_id'] = $decodedUserId;
            $bvRaw = $settingData['booking_validation'] ?? [];
            $settingData['booking_validation'] = json_encode([
                'registration' => !empty($bvRaw['registration']),
                'inspection' => !empty($bvRaw['inspection']),
                'income_threshold' => !empty($bvRaw['income_threshold']),
                'residency_proof' => !empty($bvRaw['residency_proof']),
            ]);
            $settingData['locations'] = json_encode($locations);

            $existing = LegacyCsSetting::query()->where('user_id', $decodedUserId)->first();
            if ($existing) {
                LegacyCsSetting::query()->whereKey((int)$existing->id)->update($settingData);
            } else {
                $defaults = [
                    'vh_mileage_threshold' => 0,
                    'passtime' => 'passtime',
                    'gps_provider' => 'geotab',
                    'vehicle_financing' => 1,
                    'vehicle_program' => 2,
                    'marketplace_auth_require' => 0,
                    'delivery' => 0,
                    'allowed_miles' => 150,
                    'subscription_allowed_miles' => 150,
                    'min_rental_period' => 24,
                    'max_rental_period' => 1000,
                    'max_stripe_balance' => 1000,
                    'preparation_time' => 0,
                    'driver_checker' => 'DIG',
                    'multi_location' => 0,
                    'tdk_setting' => 0,
                    'rental_threshold' => 0,
                    'created' => now(),
                ];
                LegacyCsSetting::query()->create(array_merge($defaults, $settingData));
            }

            if (!empty($depositTemplate['id'])) {
                LegacyDepositTemplate::query()->whereKey((int)$depositTemplate['id'])->update([
                    'roadside_assistance_included' => $depositTemplate['roadside_assistance_included'] ?? null,
                    'maintenance_included_fee' => $depositTemplate['maintenance_included_fee'] ?? null,
                ]);
            }

            return redirect()->to($request->headers->get('referer') ?: '/admin/settings/index/' . base64_encode((string)$decodedUserId));
        }

        $setting = LegacyCsSetting::query()->where('user_id', $decodedUserId)->first();
        if ($setting && !empty($setting->booking_validation)) {
            $setting->booking_validation = json_decode((string)$setting->booking_validation, true);
        }
        $locations = [];
        if ($setting && !empty($setting->locations)) {
            $locations = json_decode((string)$setting->locations, true) ?: [];
        }
        $locationRows = [];
        if ($locations !== []) {
            $i = 1;
            foreach ($locations as $loc) {
                if (is_array($loc)) {
                    $locationRows[$i++] = $loc;
                }
            }
        }
        if ($locationRows === []) {
            $locationRows[1] = ['address' => '', 'lat' => '', 'lng' => ''];
        }
        $depositTemplate = LegacyDepositTemplate::query()->where('user_id', $decodedUserId)->first();

        return view('admin.settings.index', [
            'userId' => $decodedUserId,
            'setting' => $setting,
            'locations' => $locations,
            'locationRows' => $locationRows,
            'depositTemplate' => $depositTemplate,
            'googleMapsKey' => config('services.google.maps_api_key'),
        ]);
    }

    public function syncVehicleAllowedMiles(Request $request): JsonResponse
    {
        $userId = (int)$request->input('userid');
        $allowedMiles = (float)$request->input('allowed_miles', 0);
        LegacyVehicle::query()->where('user_id', $userId)->update(['allowed_miles' => $allowedMiles]);

        return response()->json(['status' => 1, 'message' => 'Vehicle updated successfully']);
    }

    public function syncVehicleProgram(Request $request): JsonResponse
    {
        $userId = (int)$request->input('userid');
        $program = (string)$request->input('program', '');
        LegacyVehicle::query()->where('user_id', $userId)->update(['program' => $program]);

        return response()->json(['status' => 1, 'message' => 'Vehicle updated successfully']);
    }

    public function syncVehicleFinancing(Request $request): JsonResponse
    {
        $userId = (int)$request->input('userid');
        $finance = (string)$request->input('finance', '');
        LegacyVehicle::query()->where('user_id', $userId)->update(['financing' => $finance]);

        return response()->json(['status' => 1, 'message' => 'Vehicle updated successfully']);
    }

    public function syncVehicleAddress(Request $request): JsonResponse
    {
        $userId = (int)$request->input('CsSetting.user_id');
        $multiLocation = (int)$request->input('CsSetting.multi_location', 0);
        $locationsInput = $request->input('VehicleLocation', []);
        if (!is_array($locationsInput)) {
            $locationsInput = [];
        }
        $locations = [];
        foreach ($locationsInput as $location) {
            if (!is_array($location)) {
                continue;
            }
            $lat = $location['lat'] ?? null;
            $lng = $location['lng'] ?? null;
            if ($lat === null || $lat === '' || $lng === null || $lng === '') {
                continue;
            }
            $locations[] = [
                'lat' => $lat,
                'lng' => $lng,
                'address' => (string)($location['address'] ?? ''),
            ];
        }
        if ($userId <= 0 || empty($locations)) {
            return response()->json(['status' => 0, 'message' => 'Sorry location data was not correct']);
        }

        try {
            $vehicleIds = LegacyVehicle::query()->where('user_id', $userId)->pluck('id')->all();
            LegacyVehicleLocation::query()->whereIn('vehicle_id', $vehicleIds)->delete();

            if ($multiLocation === 0) {
                foreach ($vehicleIds as $vid) {
                    LegacyVehicle::query()->whereKey((int)$vid)->update(['multi_location' => 0]);
                }
            } else {
                $locTable = (new LegacyVehicleLocation())->getTable();
                $hasGeoCol = Schema::hasColumn($locTable, 'geo');
                $geoType = $hasGeoCol ? Schema::getColumnType($locTable, 'geo') : null;
                foreach ($vehicleIds as $vid) {
                    foreach ($locations as $location) {
                        $lat = (float)$location['lat'];
                        $lng = (float)$location['lng'];
                        $row = [
                            'vehicle_id' => (int)$vid,
                            'lat' => $lat,
                            'lng' => $lng,
                            'address' => $location['address'],
                        ];
                        if ($hasGeoCol) {
                            if (in_array($geoType, ['integer', 'bigint', 'smallint', 'tinyint'], true)) {
                                $row['geo'] = 0;
                            } else {
                                $row['geo'] = DB::raw('POINT(' . $lng . ',' . $lat . ')');
                            }
                        }
                        LegacyVehicleLocation::query()->create($row);
                    }
                    LegacyVehicle::query()->whereKey((int)$vid)->update(['multi_location' => 1]);
                }
            }
            LegacyCsSetting::query()->where('user_id', $userId)->update(['multi_location' => $multiLocation]);

            return response()->json(['status' => 1, 'message' => 'Vehicle updated successfully']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function syncVehicleDefaultAddress(Request $request): JsonResponse
    {
        $userId = (int)$request->input('CsSetting.user_id');
        $address = (string)$request->input('CsSetting.address', '');
        $lat = $request->input('CsSetting.address_lat');
        $lng = $request->input('CsSetting.address_lng');
        LegacyVehicle::query()->where('user_id', $userId)->update([
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        return response()->json(['status' => 1, 'message' => 'Vehicle updated successfully']);
    }

    public function validateGeotab(Request $request): JsonResponse
    {
        $server = (string)$request->input('server', '');
        $username = (string)$request->input('username', '');
        $pwd = (string)$request->input('pwd', '');
        $database = (string)$request->input('database', '');
        $geotab = (string)$request->input('geotab', '');
        if ($geotab === 'geotabkeyless') {
            $out = (new GeotabkeylessClient())->authenticate($server, $username, $pwd, $database);
        } else {
            $out = (new GeotabClient())->authenticate($server, $username, $pwd, $database);
        }

        return response()->json($out);
    }

    public function validateOneStepGPSKey(Request $request): JsonResponse
    {
        $key = (string)$request->input('key', '');
        $out = (new OnestepGpsClient())->authenticate($key);

        return response()->json($out);
    }

    public function syncDeviceWithGeotab(Request $request): JsonResponse
    {
        $server = (string)$request->input('server', '');
        $username = (string)$request->input('username', '');
        $pwd = (string)$request->input('pwd', '');
        $database = (string)$request->input('database', '');
        $type = (string)$request->input('type', '');
        $userId = (int)$request->input('userid', 0);
        if (!in_array($type, ['starter', 'both', 'gps'], true)) {
            return response()->json(['status' => false, 'message' => 'sorry, something went wrong. Please try again later']);
        }
        $client = new GeotabClient();
        $return = $client->getDealerDevices([
            'geotab_server' => $server,
            'geotab_user' => $username,
            'geotab_pwd' => $pwd,
            'geotab_db' => $database,
        ]);
        if (empty($return['status'])) {
            return response()->json(['status' => false, 'message' => $return['message'] ?? 'Geotab error']);
        }
        $devices = $return['result'] ?? [];
        $vinToId = [];
        foreach ($devices as $d) {
            if (!is_array($d)) {
                continue;
            }
            $vin = $d['vehicleIdentificationNumber'] ?? $d['vin'] ?? null;
            $id = $d['id'] ?? null;
            if ($vin && $id !== null) {
                $vinToId[strtoupper((string)$vin)] = $id;
            }
        }
        $vehicles = LegacyVehicle::query()
            ->where('user_id', $userId)
            ->get(['id', 'vin_no', 'passtime_serialno', 'gps_serialno']);
        foreach ($vehicles as $vehicle) {
            $vin = strtoupper(trim((string)$vehicle->vin_no));
            $geoId = $vinToId[$vin] ?? null;
            if ($geoId === null) {
                continue;
            }
            $updates = [];
            if ($type === 'starter' || $type === 'both') {
                $updates['passtime_serialno'] = $geoId;
            }
            if ($type === 'gps' || $type === 'both') {
                $updates['gps_serialno'] = $geoId;
            }
            if (!empty($updates)) {
                LegacyVehicle::query()->whereKey((int)$vehicle->id)->update($updates);
            }
        }

        return response()->json(['status' => true, 'result' => $devices]);
    }

    public function syncVehicleWithOnestep(Request $request): JsonResponse
    {
        $apikey = (string)$request->input('apikey', '');
        $userId = (int)$request->input('userid', 0);
        $setting = LegacyCsSetting::query()->where('user_id', $userId)->first(['gps_provider', 'passtime', 'onestepgps']);
        if (!$setting) {
            return response()->json(['status' => false, 'message' => 'sorry, something went wrong. Please try again later']);
        }
        if ($setting->gps_provider !== 'onestepgps' && $setting->passtime !== 'onestepgps') {
            return response()->json(['status' => false, 'message' => 'sorry, something went wrong. Please try again later']);
        }
        $client = new OnestepGpsClient();
        $return = $client->executeCustomCall('device-info', ['api-key' => $apikey, 'device_id' => 1, 'vin' => 1]);
        if (empty($return['status'])) {
            return response()->json(['status' => false, 'message' => $return['message'] ?? 'OneStep error']);
        }
        $rows = $return['result'] ?? [];
        if (!is_array($rows)) {
            return response()->json(['status' => false, 'message' => 'Invalid device-info response']);
        }
        $vinToDevice = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $vin = $row['vin'] ?? null;
            $deviceId = $row['device_id'] ?? null;
            if ($vin && $deviceId !== null) {
                $vinToDevice[strtoupper((string)$vin)] = $deviceId;
            }
        }
        $vehicles = LegacyVehicle::query()->where('user_id', $userId)->get(['id', 'vin_no']);
        foreach ($vehicles as $vehicle) {
            $vin = strtoupper(trim((string)$vehicle->vin_no));
            $did = $vinToDevice[$vin] ?? null;
            if ($did === null) {
                continue;
            }
            $updates = [];
            if ($setting->passtime === 'onestepgps') {
                $updates['passtime_serialno'] = $did;
            }
            if ($setting->gps_provider === 'onestepgps') {
                $updates['gps_serialno'] = $did;
            }
            if (!empty($updates)) {
                LegacyVehicle::query()->whereKey((int)$vehicle->id)->update($updates);
            }
        }

        return response()->json(['status' => true, 'result' => $rows]);
    }

    public function pullDevicesFromAutoPi(Request $request): JsonResponse
    {
        $autopiToken = (string)$request->input('autopi_token', '');
        $type = (string)$request->input('type', '');
        $userId = (int)$request->input('userid', 0);
        if (!in_array($type, ['starter', 'both', 'gps'], true)) {
            return response()->json(['status' => false, 'message' => 'sorry, something went wrong. Please try again later']);
        }
        $client = new AutoPiFleetClient();
        $raw = $client->getDealerDevices($autopiToken);
        if (empty($raw['status']) || empty($raw['result'])) {
            return response()->json(['status' => false, 'message' => $raw['message'] ?? 'AutoPi error']);
        }
        $result = [];
        foreach ($raw['result'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $vin = $item['vin'] ?? null;
            $connections = $item['connections'] ?? [];
            if ($vin && is_array($connections) && $connections !== []) {
                $first = reset($connections);
                if (is_array($first)) {
                    $result[strtoupper((string)$vin)] = $first;
                }
            }
        }
        $vehicles = LegacyVehicle::query()
            ->where('user_id', $userId)
            ->get(['id', 'vin_no', 'passtime_serialno', 'gps_serialno', 'autopi_unit_id']);
        foreach ($vehicles as $vehicle) {
            $vin = strtoupper(trim((string)$vehicle->vin_no));
            $conn = $result[$vin] ?? null;
            if (!is_array($conn)) {
                continue;
            }
            $unitId = $conn['unit_id'] ?? '';
            $connId = $conn['id'] ?? '';
            $updates = ['autopi_unit_id' => $unitId];
            if ($type === 'starter' || $type === 'both') {
                $updates['passtime_serialno'] = $connId;
            }
            if ($type === 'gps' || $type === 'both') {
                $updates['gps_serialno'] = $connId;
            }
            LegacyVehicle::query()->whereKey((int)$vehicle->id)->update($updates);
        }

        return response()->json(['status' => true, 'result' => $raw['result']]);
    }
}
