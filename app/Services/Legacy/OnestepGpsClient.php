<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Onestepgps.php (authenticate + device-info).
 */
class OnestepGpsClient
{
    public function baseUrl(): string
    {
        return rtrim((string)config('services.onestepgps.api', 'https://track.onestepgps.com/v3/api/public/'), '/') . '/';
    }

    public function authenticate(string $apiKey): array
    {
        $url = $this->baseUrl() . 'user/me?api-key=' . urlencode($apiKey);
        $decoded = $this->getJson($url);
        if (isset($decoded['error'])) {
            return ['status' => 0, 'message' => is_string($decoded['error']) ? $decoded['error'] : 'Error', 'data' => []];
        }

        return ['status' => 1, 'message' => '', 'data' => $decoded];
    }

    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $key = trim($vehicledata['CsSetting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $owner = trim($vehicledata['Vehicle']['user_id'] ?? '');

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $params = [
            'api-key' => $key, 'device_id_match' => $serial,
            'lat_lng' => 1, 'display_name' => 1, 'device_id' => 1,
            'odometer_mi' => 1, 'odometer_km' => 1,
        ];

        $result = $this->executeCustomCall('device-info', $params);
        if ($result['status'] && isset($result['result'][0])) {
            $r = $result['result'][0];
            $distUnit = \Illuminate\Support\Facades\DB::table('users')->where('id', $owner)->value('distance_unit') ?? 'MI';
            $miles = ($distUnit === 'KM')
                ? ($r['odometer_km'] ? sprintf('%d', $r['odometer_km']) : ($vehicledata['Vehicle']['last_mile'] ?? 0))
                : ($r['odometer_mi'] ? sprintf('%d', $r['odometer_mi']) : ($vehicledata['Vehicle']['last_mile'] ?? 0));

            return ['status' => true, 'lat' => $r['lat'], 'lng' => $r['lng'], 'miles' => $miles, 'lastLocate' => now()->toDateTimeString()];
        }

        return $return;
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $key = trim($vehicledata['CsSetting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $lastMile = $vehicledata['Vehicle']['last_mile'] ?? 0;
        $owner = trim($vehicledata['Vehicle']['user_id'] ?? '');
        $return = ['status' => false, 'miles' => $lastMile];

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $params = ['api-key' => $key, 'device_id_match' => $serial, 'display_name' => 1, 'device_id' => 1, 'odometer_mi' => 1, 'odometer_km' => 1];
        $result = $this->executeCustomCall('device-info', $params);

        if ($result['status'] && isset($result['result'][0])) {
            $r = $result['result'][0];
            $distUnit = \Illuminate\Support\Facades\DB::table('users')->where('id', $owner)->value('distance_unit') ?? 'MI';
            $miles = ($distUnit === 'KM')
                ? ($r['odometer_km'] ? sprintf('%d', $r['odometer_km']) : $lastMile)
                : ($r['odometer_mi'] ? sprintf('%d', $r['odometer_mi']) : $lastMile);
            return ['status' => true, 'miles' => $miles];
        }

        return $return;
    }

    public function getStartLastMile(array $vehicledata): array
    {
        return $this->getVehicleLastMile($vehicledata);
    }

    public function startPasstime(array $vehicleData, int $orderId): int
    {
        if (empty($orderId)) return 1;
        if (!empty($vehicleData)) {
            try {
                if (($vehicleData['Vehicle']['last_mile'] ?? 0) == 0) {
                    $resp = $this->getVehicleLastMile($vehicleData);
                    $start = $resp['miles'] ?: ($vehicleData['Vehicle']['last_mile'] ?? 1);
                } else {
                    $start = (int)$vehicleData['Vehicle']['last_mile'];
                }
                return $start ?: 1;
            } catch (\Throwable $e) {
                // fall through
            }
        }
        return 1;
    }

    public function getPasstimeMiles(array $vehicleData): array
    {
        $return = ['miles' => 0, 'allowed_miles' => 0];
        if (!empty($vehicleData)) {
            $resp = $this->getVehicleLastMile($vehicleData);
            $return['miles'] = $resp['miles'];
            $return['allowed_miles'] = $vehicleData['Vehicle']['allowed_miles'] ?? 0;
        }
        return $return;
    }

    public function deActivateVehicle(array $vehicledata): array
    {
        return $this->starterAction($vehicledata, 'starter_disable');
    }

    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->starterAction($vehicledata, 'starter_enable');
    }

    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op
    }

    private function starterAction(array $vehicledata, string $action): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $key = trim($vehicledata['CsSetting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['Vehicle']['passtime_serialno'] ?? '');

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $url = $this->baseUrl() . "device-action/{$serial}?" . http_build_query(['api-key' => $key, 'action' => $action]);
        $response = \Illuminate\Support\Facades\Http::withHeaders(['Content-Type' => 'application/json'])
            ->withoutVerifying()->timeout(30)->post($url);
        $result = $response->json();

        if (is_array($result) && !isset($result['error'])) {
            return ['status' => true];
        }

        $return['message'] = $result['error'] ?? 'Unknown error';
        return $return;
    }

    /**
     * @param  array<string, mixed>  $queryParams
     * @return array{status:bool,message?:string,result?:mixed}
     */
    public function executeCustomCall(string $api, array $queryParams = []): array
    {
        $url = $this->baseUrl() . ltrim($api, '/') . '?' . http_build_query($queryParams);
        $decoded = $this->getJson($url);
        if (isset($decoded['error'])) {
            return ['status' => false, 'message' => is_string($decoded['error']) ? $decoded['error'] : 'Error'];
        }

        return ['status' => true, 'result' => $decoded];
    }

    private function getJson(string $url): array
    {
        $response = Http::withHeaders([
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ])->timeout(60)->get($url);
        $json = $response->json();
        if (!is_array($json)) {
            return ['error' => $response->body() ?: 'Invalid response'];
        }

        return $json;
    }
}
