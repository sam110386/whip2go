<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal port of CakePHP app/Lib/AutoPi.php::getDealerDevices / HTTP helper.
 */
class AutoPiFleetClient
{
    private string $apiUrl = 'https://api.autopi.io/';
    private $logger;

    public function __construct()
    {
        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/autopi.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $token = trim($vehicledata['cs_setting']['autopi_token'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');

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

        $url = $this->apiUrl . 'logbook/storage/raw/?' . http_build_query($params);
        $result = $this->sendHttpRequest($url, $token);

        if (isset($result['results'])) {
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
    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op
        return;
    }
    public function getVehicleLastMile(array $vehicledata): array
    {
        $serial = trim($vehicledata['gps_serialno'] ?? '');
        $lastMile = (int) ($vehicledata['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];
        $token = trim($vehicledata['cs_setting']['autopi_token'] ?? '');

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
        $url = $this->apiUrl . 'logbook/storage/raw/?' . http_build_query($params);
        $result = $this->sendHttpRequest($url, $token);

        if (isset($result['results'])) {
            $data = end($result['results']);
            $distUnit = $vehicledata['owner']['distance_unit'] ?? 'MI';

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
    public function startPasstime(array $vehicledata, int $orderId): int
    {
        if (empty($orderId)) {
            return 1;
        }

        if (!empty($vehicledata)) {
            try {
                if (($vehicledata['last_mile'] ?? 0) == 0) {
                    $resp = $this->getVehicleLastMile($vehicledata);
                    return $resp['miles'] ?: ($vehicledata['last_mile'] ?? 1);
                }
                return (int) $vehicledata['last_mile'];
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return 1;
    }
    public function getPasstimeMiles(array $vehicledata): array
    {
        $return = ['miles' => 0, 'allowed_miles' => 0];

        if (!empty($vehicledata)) {
            $resp = $this->getVehicleLastMile($vehicledata);
            $return['miles'] = $resp['miles'];
            $return['allowed_miles'] = $vehicledata['allowed_miles'] ?? 0;
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
    public function getDealerDevices(string $autopiToken): array
    {
        $return = [
            'status' => false,
            "message" => "Sorry, no record found matching with given criteria"
        ];

        $autopiToken = trim($autopiToken);

        if (empty($autopiToken)) {
            $return;
        }

        $param = [
            'page_size' => 100,
            'exclude_unassociated' => 'true',
        ];

        $url = $this->apiUrl . 'fleet/vehicles/?' . http_build_query($param);
        $return = $this->sendHttpRequest($url, $autopiToken);
        return $return;
    }
    public function generateAccessToken(string $devicePk, string $autopiToken): array
    {
        $return = [
            'status' => false,
            'message' => 'Sorry, no record found matching with given criteria'
        ];

        $autopiToken = trim($autopiToken);

        if (empty($devicePk)) {
            return $return;
        }

        $param = [
            'reference_id' => $devicePk,
            'valid_from_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minute')),
            'valid_to_utc' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+365 days')),
        ];

        $url = $this->apiUrl . "dongle/devices/{$devicePk}/accesstokens/";
        $result = $this->sendHttpRequest($url, $autopiToken, 'POST', $param);

        if (isset($result['id'])) {
            return [
                'status' => true,
                'message' => 'success',
                'token' => $result['token'],
                'expires_in' => $result['valid_to_utc']
            ];
        }

        return $return;
    }
    private function keyfobAction(array $vehicledata, string $action): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $unitId = trim($vehicledata['autopi_unit_id'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');
        $token = trim($vehicledata['cs_setting']['autopi_token'] ?? '');

        if (empty($unitId) || empty($serial)) {
            return $return;
        }

        $url = $this->apiUrl . "dongle/{$unitId}/execute_raw/";
        $result = $this->sendHttpRequest($url, $token, 'POST', ['command' => 'keyfob.power value=true']);
        $result = $this->sendHttpRequest($url, $token, 'POST', ['command' => "keyfob.action {$action}"]);

        return $result;
    }
    private function sendHttpRequest(string $url, string $token, string $method = 'GET', array $data = []): array
    {
        try {
            $logData = $data;
            $this->logger->info("AutoPi Request [{$method}]: {$url}", [
                'data' => $logData,
            ]);

            $pending = Http::withHeaders([
                "Content-Type" => "application/json",
                "Authorization" => "APIToken {$token}",
                "Charset" => "UTF-8",
                "Cache-Control" => "no-cache",
                "Pragma" => "no-cache",
            ])->timeout(60);

            $response = $method === 'POST'
                ? $pending->post($url, $data)
                : $pending->get($url);

            $json = $response->json();

            $this->logger->info("AutoPi Response [{$response->status()}]: {$url}", [
                'response' => is_array($json) ? $json : $response->body(),
            ]);

            if (!is_array($json)) {
                return ['error' => $response->body()];
            }

            return $json;
        } catch (\Throwable $e) {
            $this->logger->error("AutoPi Request Exception: {$e->getMessage()}", [
                'url' => $url,
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
