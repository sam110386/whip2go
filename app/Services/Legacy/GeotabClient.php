<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Geotab.php (authenticate + device list).
 */
class GeotabClient
{
    private const API_SUFFIX = 'geotab.com/apiv1';

    public function authenticate(string $server, string $username, string $password, string $database): array
    {
        $host = $this->normalizeServerHost($server);
        $url = "https://{$host}." . self::API_SUFFIX;
        $body = [
            'method' => 'Authenticate',
            'params' => [
                'database' => $database,
                'userName' => $username,
                'password' => $password,
            ],
        ];
        $decoded = $this->postJson($url, $body);
        if (isset($decoded['result'])) {
            return ['status' => 1, 'message' => '', 'data' => $decoded['result']];
        }
        $msg = $decoded['error']['message'] ?? (is_string($decoded['error'] ?? null) ? $decoded['error'] : 'Authentication failed');

        return ['status' => 0, 'message' => $msg, 'data' => []];
    }

    /**
     * @return array{status:bool,message?:string,result?:array}
     */
    public function getDealerDevices(array $settingdata, array $search = []): array
    {
        $server = trim((string)($settingdata['geotab_server'] ?? ''));
        $usr = trim((string)($settingdata['geotab_user'] ?? ''));
        $pwd = trim((string)($settingdata['geotab_pwd'] ?? ''));
        $database = trim((string)($settingdata['geotab_db'] ?? ''));
        if ($server === '' || $usr === '' || $pwd === '' || $database === '') {
            return ['status' => false, 'message' => 'Sorry, no record found matching with given criteria'];
        }
        $host = $this->normalizeServerHost($server);
        $url = "https://{$host}." . self::API_SUFFIX;
        $params = [
            'typeName' => 'Device',
            'credentials' => [
                'database' => $database,
                'userName' => $usr,
                'password' => $pwd,
            ],
            'resultsLimit' => 500,
        ];
        if (!empty($search)) {
            $params['search'] = $search;
        }
        $body = ['method' => 'Get', 'params' => $params];
        $decoded = $this->postJson($url, $body);
        if (isset($decoded['result'])) {
            return ['status' => true, 'result' => $decoded['result']];
        }
        $msg = $decoded['error']['message'] ?? 'Geotab request failed';

        return ['status' => false, 'message' => $msg];
    }

    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $server = $this->normalizeServerHost($vehicledata['CsSetting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['CsSetting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['CsSetting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $url = "https://{$server}." . self::API_SUFFIX;
        $body = [
            'method' => 'Get',
            'params' => [
                'typeName' => 'DeviceStatusInfo',
                'credentials' => compact('database') + ['userName' => $usr, 'password' => $pwd],
                'search' => ['deviceSearch' => ['id' => $serial]],
            ],
        ];

        $result = $this->postJson($url, $body);
        if (isset($result['result'][0])) {
            $return = [
                'status' => true,
                'lat' => $result['result'][0]['latitude'],
                'lng' => $result['result'][0]['longitude'],
            ];
        }

        return $return;
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $server = $this->normalizeServerHost($vehicledata['CsSetting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['CsSetting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['CsSetting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $lastMile = (int)($vehicledata['Vehicle']['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $url = "https://{$server}." . self::API_SUFFIX;
        $body = [
            'method' => 'Get',
            'params' => [
                'typeName' => 'StatusData',
                'credentials' => compact('database') + ['userName' => $usr, 'password' => $pwd],
                'search' => [
                    'deviceSearch' => ['id' => $serial],
                    'diagnosticSearch' => ['id' => 'DiagnosticOdometerAdjustmentId'],
                    'fromDate' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-10 minute')),
                    'toDate' => gmdate('Y-m-d\TH:i:s\Z'),
                ],
            ],
        ];

        $result = $this->postJson($url, $body);
        if (isset($result['result']) && !empty($result['result'])) {
            $data = end($result['result']);
            $distanceUnit = $vehicledata['Owner']['distance_unit'] ?? 'MI';
            if ($distanceUnit === 'KM') {
                $miles = $data['data'] ? sprintf('%d', $data['data'] / 1000) : $lastMile;
            } else {
                $miles = $data['data'] ? sprintf('%d', $data['data'] / (1000 * 1.60934)) : $lastMile;
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
        return $this->sendIoxCommand($vehicledata, true);
    }

    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->sendIoxCommand($vehicledata, false);
    }

    public function ExecuteMultiCall(string $server, array $callData, array $credentials): ?array
    {
        $server = $this->normalizeServerHost($server);
        $url = "https://{$server}." . self::API_SUFFIX;
        $body = ['method' => 'ExecuteMultiCall', 'params' => ['calls' => $callData, 'credentials' => $credentials]];
        return $this->postJson($url, $body);
    }

    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op for Geotab
    }

    private function sendIoxCommand(array $vehicledata, bool $isRelayOn): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $server = $this->normalizeServerHost($vehicledata['CsSetting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['CsSetting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['CsSetting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['Vehicle']['passtime_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $url = "https://{$server}." . self::API_SUFFIX;
        $body = [
            'method' => 'Add',
            'params' => [
                'typeName' => 'TextMessage',
                'credentials' => compact('database') + ['userName' => $usr, 'password' => $pwd],
                'entity' => [
                    'device' => ['id' => $serial],
                    'messageContent' => ['isRelayOn' => $isRelayOn, 'contentType' => 'IoxOutput'],
                    'isDirectionToVehicle' => true,
                ],
            ],
        ];

        $result = $this->postJson($url, $body);
        if (isset($result['result'])) {
            return ['status' => true];
        }

        return $return;
    }

    private function normalizeServerHost(string $server): string
    {
        $s = trim(str_replace('.geotab.com', '', $server));

        return $s;
    }

    private function postJson(string $url, array $body): array
    {
        $response = Http::asJson()
            ->withHeaders([
                'User-Agent' => 'mygeotab-php/1.0',
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->post($url, $body);

        $json = $response->json();
        if (!is_array($json)) {
            return ['error' => ['message' => $response->body() ?: 'Invalid JSON response']];
        }

        return $json;
    }
}
