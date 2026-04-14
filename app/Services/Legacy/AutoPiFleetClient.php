<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Minimal port of CakePHP app/Lib/AutoPi.php::getDealerDevices / HTTP helper.
 */
class AutoPiFleetClient
{
    private const BASE = 'https://api.autopi.io/';

    /**
     * @return array{status:bool,message?:string,result?:array}
     */
    public function getDealerDevices(string $autopiToken): array
    {
        $autopiToken = trim($autopiToken);
        if ($autopiToken === '') {
            return ['status' => false, 'message' => 'Sorry, no record found matching with given criteria'];
        }
        $url = self::BASE . 'fleet/vehicles/?' . http_build_query([
            'page_size' => 100,
            'exclude_unassociated' => 'true',
        ]);
        $decoded = $this->request($url, $autopiToken, 'GET', []);
        if (isset($decoded['results']) && is_array($decoded['results'])) {
            return ['status' => true, 'result' => $decoded['results']];
        }

        return ['status' => false, 'message' => $decoded['detail'] ?? ($decoded['error'] ?? 'AutoPi request failed')];
    }

    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $token = trim($vehicledata['CsSetting']['autopi_token'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');

        if (empty($token) || empty($serial)) {
            return $return;
        }

        $params = [
            'device_id' => $serial,
            'data_type' => 'track.pos',
            'start_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 hour')),
            'end_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'page_size' => 1,
        ];
        $url = self::BASE . 'logbook/storage/raw/?' . http_build_query($params);
        $result = $this->request($url, $token, 'GET', []);

        if (isset($result['results']) && !empty($result['results'])) {
            $res = reset($result['results']);
            return [
                'status' => true,
                'lat' => $res['data']['loc']['lat'] ?? '',
                'lng' => $res['data']['loc']['lon'] ?? '',
                'lastLocate' => $res['rec'] ?? '',
                'miles' => null,
            ];
        }

        return $return;
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $lastMile = (int)($vehicledata['Vehicle']['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];
        $token = trim($vehicledata['CsSetting']['autopi_token'] ?? '');

        if (empty($token) || empty($serial)) {
            return $return;
        }

        $params = [
            'device_id' => $serial,
            'data_type' => 'obd.odometer',
            'start_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-2 hour')),
            'end_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'page_size' => 1,
        ];
        $url = self::BASE . 'logbook/storage/raw/?' . http_build_query($params);
        $result = $this->request($url, $token, 'GET', []);

        if (isset($result['results']) && !empty($result['results'])) {
            $data = end($result['results']);
            $distUnit = $vehicledata['Owner']['distance_unit'] ?? 'MI';
            if ($distUnit === 'KM') {
                $miles = isset($data['data']['value']) ? sprintf('%d', $data['data']['value']) : $lastMile;
            } else {
                $miles = isset($data['data']['value']) ? sprintf('%d', $data['data']['value'] / 1.60934) : $lastMile;
            }
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
                    return $resp['miles'] ?: ($vehicleData['Vehicle']['last_mile'] ?? 1);
                }
                return (int)$vehicleData['Vehicle']['last_mile'];
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
        return $this->keyfobAction($vehicledata, 'lock');
    }

    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->keyfobAction($vehicledata, 'unlock');
    }

    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op
    }

    public function generateAccessToken(string $devicePk, string $autopiToken): array
    {
        $return = ['status' => false, 'message' => 'Sorry, no record found matching with given criteria'];
        if (empty($devicePk)) return $return;

        $param = [
            'reference_id' => $devicePk,
            'valid_from_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minute')),
            'valid_to_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+365 days')),
        ];
        $url = self::BASE . "dongle/devices/{$devicePk}/accesstokens/";
        $result = $this->request($url, trim($autopiToken), 'POST', $param);

        if (isset($result['id'])) {
            return ['status' => true, 'message' => 'success', 'token' => $result['token'], 'expires_in' => $result['valid_to_utc']];
        }
        return $return;
    }

    private function keyfobAction(array $vehicledata, string $action): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $unitId = trim($vehicledata['Vehicle']['autopi_unit_id'] ?? '');
        $serial = trim($vehicledata['Vehicle']['passtime_serialno'] ?? '');
        $token = trim($vehicledata['CsSetting']['autopi_token'] ?? '');

        if (empty($unitId) || empty($serial)) {
            return $return;
        }

        $url = self::BASE . "dongle/{$unitId}/execute_raw/";
        $this->request($url, $token, 'POST', ['command' => 'keyfob.power value=true']);
        $result = $this->request($url, $token, 'POST', ['command' => "keyfob.action {$action}"]);

        if (isset($result['jid'])) {
            return ['status' => true];
        }
        return $result ?? $return;
    }

    private function request(string $url, string $token, string $method, array $data): array
    {
        $pending = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'APIToken ' . $token,
        ])->timeout(60);
        $response = $method === 'POST'
            ? $pending->post($url, $data)
            : $pending->get($url);
        $json = $response->json();
        if (!is_array($json)) {
            return ['error' => $response->body()];
        }

        return $json;
    }
}
