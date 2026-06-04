<?php

namespace App\Services\Legacy;

use App\Models\Legacy\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

/**
 * Port of CakePHP app/Lib/Onestepgps.php (authenticate + device-info).
 */
class OnestepGpsClient
{
    private string $apiUrl = '';
    private $logger;

    public function __construct()
    {
        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/onestepgps.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
    public function authenticate(string $apiKey): array
    {
        $this->apiUrl = config('legacy.OneStepGps.api') . 'user/me?api-key=' . urlencode($apiKey);
        $decoded = $this->sendHttpRequest();

        if (isset($decoded['error'])) {
            return [
                'status' => 0,
                'message' => is_string($decoded['error']) ? $decoded['error'] : 'Error',
                'data' => []
            ];
        }

        return [
            'status' => 1,
            'message' => '',
            'data' => $decoded
        ];
    }
    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $key = trim($vehicledata['cs_setting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');
        $owner = trim($vehicledata['user_id'] ?? '');

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $params = [
            'api-key' => $key,
            'device_id_match' => $serial,
            'lat_lng' => 1,
            'display_name' => 1,
            'device_id' => 1,
            'odometer_mi' => 1,
            'odometer_km' => 1,
        ];

        $this->apiUrl = config('legacy.OneStepGps.api') . 'device-info?' . http_build_query($params);
        $result = $this->sendGetHttpRequest();

        if (!isset($result['error'])) {
            $distUnit = User::where('id', $owner)->value('distance_unit') ?? 'MI';
            $miles = ($distUnit === 'KM')
                ? ($result['result'][0]['odometer_km'] ? sprintf('%d', $result['result'][0]['odometer_km']) : ($vehicledata['last_mile'] ?? 0))
                : ($result['result'][0]['odometer_mi'] ? sprintf('%d', $result['result'][0]['odometer_mi']) : ($vehicledata['last_mile'] ?? 0));

            return [
                'status' => true,
                'lat' => $result['result'][0]['lat'],
                'lng' => $result['result'][0]['lng'],
                'miles' => $miles,
                'lastLocate' => now()->toDateTimeString()
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
        $key = trim($vehicledata['cs_setting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');
        $lastMile = $vehicledata['last_mile'] ?? 0;
        $owner = trim($vehicledata['user_id'] ?? '');
        $return = ['status' => false, 'miles' => $lastMile];

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $params = [
            'api-key' => $key,
            'device_id_match' => $serial,
            'display_name' => 1,
            'device_id' => 1,
            'odometer_mi' => 1,
            'odometer_km' => 1
        ];

        $this->apiUrl = config('legacy.OneStepGps.api') . 'device-info?' . http_build_query($params);
        $result = $this->sendGetHttpRequest();

        if (!isset($result['error'])) {
            $distUnit = User::where('id', $owner)->value('distance_unit') ?? 'MI';
            $miles = ($distUnit === 'KM')
                ? ($result['result'][0]['odometer_km'] ? sprintf('%d', $result['result'][0]['odometer_km']) : $lastMile)
                : ($result['result'][0]['odometer_mi'] ? sprintf('%d', $result['result'][0]['odometer_mi']) : $lastMile);

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
                    $start = $resp['miles'] ?: ($vehicledata['last_mile'] ?? 1);
                } else {
                    $start = (int) $vehicledata['last_mile'];
                }

                return $start ?: 1;

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
        return $this->starterAction($vehicledata, 'starter_disable');
    }
    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->starterAction($vehicledata, 'starter_enable');
    }
    public function ExecuteCustomCall($api = 'device-info', $param = [])
    {
        $return = [
            'status' => false,
            'message' => "Passtime dealer # or vehicle serial # not set."
        ];

        $this->apiUrl = config('legacy.OneStepGps.api') . $api . '?' . http_build_query($param);
        $result = $this->sendGetHttpRequest();

        if (!isset($result['error'])) {
            $return = [
                'status' => true,
                "result" => $result
            ];
        }

        return $return;
    }
    private function starterAction(array $vehicledata, string $action): array
    {
        $return = [
            'status' => false,
            'message' => 'Passtime dealer # or vehicle serial # not set.'
        ];
        $key = trim($vehicledata['cs_setting']['onestepgps'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');

        if (empty($key) || empty($serial)) {
            return $return;
        }

        $param = [
            'api-key' => $key,
            'action' => $action
        ];

        $this->apiUrl = config('legacy.OneStepGps.api') . "device-action/{$serial}?" . http_build_query($param);
        $result = $this->sendHttpRequest($param);

        if (!isset($result['error'])) {
            return ['status' => true];
        }

        $return['message'] = $result['error'] ?? 'Unknown error';
        return $return;
    }
    public function sendGetHttpRequest()
    {
        try {
            $logUrl = preg_replace('/api-key=[^&]+/', 'api-key=********', $this->apiUrl);
            $this->logger->info("OneStepGps Request [GET]: {$logUrl}");

            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
                'Pragma' => 'no-cache',
                'User-Agent' => 'Thunder Client (https://www.thunderclient.com)',
            ])
                ->timeout(30)
                ->maxRedirects(10)
                ->get($this->apiUrl);

            $this->logger->info("OneStepGps Response [{$response->status()}]: {$logUrl}", [
                'response' => is_array($response->json()) ? $response->json() : $response->body(),
            ]);

            return $response->json();

        } catch (ConnectionException $e) {
            $logUrl = preg_replace('/api-key=[^&]+/', 'api-key=********', $this->apiUrl);
            $this->logger->error("OneStepGps Request Exception: {$e->getMessage()}", [
                'url' => $logUrl,
            ]);
            return ['error' => $e->getMessage()];
        }
    }
    public function sendHttpRequest(array $requestBody = [])
    {
        try {
            $logUrl = preg_replace('/api-key=[^&]+/', 'api-key=********', $this->apiUrl);
            $method = !empty($requestBody) ? 'POST' : 'GET';
            $this->logger->info("OneStepGps Request [{$method}]: {$logUrl}");

            $request = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Charset' => 'UTF-8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ])->withoutVerifying();

            $response = !empty($requestBody) ? $request->post($this->apiUrl, $requestBody) : $request->get($this->apiUrl);

            $this->logger->info("OneStepGps Response [{$response->status()}]: {$logUrl}", [
                'response' => is_array($response->json()) ? $response->json() : $response->body(),
            ]);

            if ($response->failed()) {
                return ['error' => $response->body()];
            }

            return $response->json();

        } catch (ConnectionException $e) {
            $logUrl = preg_replace('/api-key=[^&]+/', 'api-key=********', $this->apiUrl);
            $this->logger->error("OneStepGps Request Exception: {$e->getMessage()}", [
                'url' => $logUrl,
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
