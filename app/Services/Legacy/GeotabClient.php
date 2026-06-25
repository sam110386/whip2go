<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Geotab.php (authenticate + device list).
 */
class GeotabClient
{
    private string $apiurl = 'geotab.com/apiv1';
    private string $_apiurl = '';
    private $logger;

    public function __construct()
    {
        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/geotab.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
    public function authenticate(string $server, string $username, string $password, string $database = ''): array
    {
        $server = trim(str_replace('.geotab.com', '', $server));
        $this->_apiurl = "https://{$server}.{$this->apiurl}";
        $body = [
            'method' => 'Authenticate',
            'params' => [
                'database' => $database,
                'userName' => $username,
                'password' => $password,
            ],
        ];

        $decoded = $this->sendHttpRequest($body);

        if (isset($decoded['result'])) {
            return ['status' => 1, 'message' => '', 'data' => $decoded['result']];
        }

        $msg = $decoded['error']['message'] ?? (is_string($decoded['error'] ?? null) ? $decoded['error'] : 'Authentication failed');

        return ['status' => 0, 'message' => $msg, 'data' => []];
    }
    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $server = trim(str_replace('.geotab.com', '', $vehicledata['cs_setting']['geotab_server'] ?? ''));
        $usr = trim($vehicledata['cs_setting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['cs_setting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $this->_apiurl = "https://{$server}.{$this->apiurl}";
        $body = [
            'method' => 'Get',
            'params' => [
                'typeName' => 'DeviceStatusInfo',
                'credentials' => [
                    'database' => $database,
                    'userName' => $usr,
                    'password' => $pwd
                ],
                'search' => [
                    'deviceSearch' => [
                        'id' => $serial
                    ]
                ],
            ],
        ];

        $result = $this->sendHttpRequest($body);

        if (isset($result['result'][0])) {
            $return = [
                'status' => true,
                'lat' => $result['result'][0]['latitude'],
                'lng' => $result['result'][0]['longitude'],
            ];
        }

        return $return;
    }
    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op for Geotab
        return;
    }
    public function getVehicleLastMile(array $vehicledata): array
    {
        $server = trim(str_replace('.geotab.com', '', $vehicledata['cs_setting']['geotab_server'] ?? ''));
        $usr = trim($vehicledata['cs_setting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['cs_setting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');
        $lastMile = (int) ($vehicledata['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $this->_apiurl = "https://{$server}.{$this->apiurl}";
        $body = [
            'method' => 'Get',
            'params' => [
                'typeName' => 'StatusData',
                'credentials' => [
                    'database' => $database,
                    'userName' => $usr,
                    'password' => $pwd
                ],
                'search' => [
                    'deviceSearch' => [
                        'id' => $serial
                    ],
                    'diagnosticSearch' => [
                        'id' => 'DiagnosticOdometerAdjustmentId'
                    ],
                    'fromDate' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-10 minute')),
                    'toDate' => gmdate('Y-m-d\TH:i:s\Z'),
                ],
            ],
        ];

        $result = $this->sendHttpRequest($body);

        if (isset($result['result']) && !empty($result['result'])) {
            $data = end($result['result']);
            $distanceUnit = $vehicledata['owner']['distance_unit'] ?? 'MI';

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
        return $this->sendIoxCommand($vehicledata, true);
    }
    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->sendIoxCommand($vehicledata, false);
    }
    public function ExecuteMultiCall(string $server, array $callData, array $credentials): ?array
    {
        $server = trim(str_replace('.geotab.com', '', $server));
        $this->_apiurl = "https://{$server}.{$this->apiurl}";
        $body = [
            'method' => 'ExecuteMultiCall',
            'params' => [
                'calls' => $callData,
                'credentials' => $credentials
            ]
        ];

        return $this->sendHttpRequest($body);
    }
    public function getDealerDevices(array $settingdata, array $search = []): array
    {
        $server = trim((string) ($settingdata['geotab_server'] ?? ''));
        $usr = trim((string) ($settingdata['geotab_user'] ?? ''));
        $pwd = trim((string) ($settingdata['geotab_pwd'] ?? ''));
        $database = trim((string) ($settingdata['geotab_db'] ?? ''));

        if (empty($server) || empty($usr) || empty($pwd) || empty($database)) {
            return ['status' => false, 'message' => 'Sorry, no record found matching with given criteria'];
        }

        $host = trim(str_replace('.geotab.com', '', $server));
        $this->_apiurl = "https://{$host}.{$this->apiurl}";
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
        $decoded = $this->sendHttpRequest($body);

        if (isset($decoded['result'])) {
            return ['status' => true, 'result' => $decoded['result']];
        }

        $msg = $decoded['error']['message'] ?? 'Geotab request failed';

        return ['status' => false, 'message' => $msg];
    }
    private function sendIoxCommand(array $vehicledata, bool $isRelayOn): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $server = trim(str_replace('.geotab.com', '', $vehicledata['cs_setting']['geotab_server'] ?? ''));
        $usr = trim($vehicledata['cs_setting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['cs_setting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $this->_apiurl = "https://{$server}.{$this->apiurl}";
        $body = [
            'method' => 'Add',
            'params' => [
                'typeName' => 'TextMessage',
                'credentials' => [
                    'database' => $database,
                    'userName' => $usr,
                    'password' => $pwd
                ],
                'entity' => [
                    'device' => [
                        'id' => $serial
                    ],
                    'messageContent' => [
                        'isRelayOn' => $isRelayOn,
                        'contentType' => 'IoxOutput'
                    ],
                    'isDirectionToVehicle' => true,
                ],
            ],
        ];

        $result = $this->sendHttpRequest($body);

        if (isset($result['result'])) {
            return ['status' => true];
        }

        return $return;
    }
    private function sendHttpRequest(array $body): array
    {
        $url = $this->_apiurl;

        try {
            $logBody = $body;

            if (isset($logBody['params']['password'])) {
                $logBody['params']['password'] = '********';
            }

            if (isset($logBody['params']['credentials']['password'])) {
                $logBody['params']['credentials']['password'] = '********';
            }

            $this->logger->info("Geotab Request", [
                'url' => $url,
                'body' => $logBody,
            ]);

            $response = Http::asJson()
                ->withHeaders([
                    'User-Agent' => 'mygeotab-php/1.0',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Charset' => 'UTF-8',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache'
                ])
                ->timeout(60)
                ->post($url, $body);

            $json = $response->json();

            $this->logger->info("Geotab Response [{$response->status()}]", [
                'url' => $url,
                'response' => is_array($json) ? $json : $response->body(),
            ]);

            if (!is_array($json)) {
                return ['error' => ['message' => $response->body() ?: 'Invalid JSON response']];
            }

            return $json;

        } catch (\Throwable $e) {
            $this->logger->error("Geotab Request Exception: {$e->getMessage()}", [
                'url' => $url,
            ]);
            return ['error' => ['message' => $e->getMessage()]];
        }
    }
}
