<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Port of CakePHP app/Lib/Geotabkeyless.php
 * Geotab Keyless API: auth, device commands (lock/unlock/inhibit/enable), virtual keys.
 */
class GeotabkeylessClient
{
    private string $apiUrl = 'https://keyless.geotab.com/api';
    private $logger;

    public function __construct()
    {
        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/geotabkeyless.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
    public function authenticate(string $server, string $username, string $password, string $database): array
    {
        $payload = [
            'database' => $database,
            'userName' => $username,
            'password' => $password,
            'server' => $server,
        ];

        $this->apiUrl = "{$this->apiUrl}/auth";
        $result = $this->HttpRequest($payload);

        if ($result['status'] == 200) {
            return [
                'status' => 1,
                'message' => '',
                'data' => $result['response']
            ];
        }

        return [
            'status' => 0,
            'message' => $result['response']['detail'] ?? 'Auth failed',
            'data' => []
        ];
    }
    public function getVehicleLocation(array $vehicledata): array
    {
        return ['status' => false, 'lat' => '', 'lng' => ''];
    }
    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op
        return;
    }
    public function getVehicleLastMile(array $vehicledata): array
    {
        $lastMile = (int) ($vehicledata['last_mile'] ?? 0);
        return ['status' => false, 'miles' => $lastMile];
    }
    public function getStartLastMile(array $vehicledata): array
    {
        return $this->getVehicleLastMile($vehicledata);
    }
    public function startPasstime(array $vehicledata, int $orderId): void
    {
        // No-op for keyless
        return;
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
    public function setupTentant(string $server, string $username, string $pwd, string $database = ''): array
    {
        $tokenResp = $this->authenticate($server, $username, $pwd, $database);

        if (!$tokenResp['status']) {
            return $tokenResp;
        }

        $token = $tokenResp['data']['accessToken'];
        $param = [
            'database' => $database,
            'server' => $server,
            'serviceAccount' => [
                'username' => $username,
                'password' => $pwd
            ],
            'isNotificationEnabled' => false,
        ];

        $this->apiUrl = "{$this->apiUrl}/tenants";
        $result = $this->HttpRequest($param, $token);

        if (($result['status']) == 200) {
            return [
                'status' => 1,
                'message' => '',
                'data' => $result['response']
            ];
        }

        return [
            'status' => 0,
            'message' => $result['response']['detail'] ?? 'Tenant setup failed',
            'data' => []
        ];
    }
    private function parseVehicleSetting(array $vehicledata): array
    {
        if (
            !isset($vehicledata['vehicle_setting']) ||
            empty($vehicledata['vehicle_setting']['data'] ?? null)
        ) {
            return $vehicledata;
        }

        $toArrayFormat = fn($val) => is_array($val) ? $val : (json_decode($val ?? '', true) ?? []);
        $json = $toArrayFormat($vehicledata['vehicle_setting']['data']);

        if (
            isset($json['gps_provider']) &&
            !empty($json['gps_provider']) &&
            isset($json['passtime']) &&
            !empty($json['passtime'])
        ) {
            $vehicledata['cs_setting'] = $json;
        }

        return $vehicledata;
    }
    private function command(array $vehicledata, string $cmd): array
    {
        $return = [
            'status' => false,
            'message' => 'Passtime dealer # or vehicle serial # not set.'
        ];
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        $server = trim($vehicledata['cs_setting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['cs_setting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['cs_setting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $tokenResp = $this->authenticate($server, $usr, $pwd, $database);

        if (!$tokenResp['status']) {
            return $tokenResp;
        }

        $token = $tokenResp['data']['accessToken'];
        $param = [
            'commands' => [$cmd],
            'virtualKeyId' => '',
            'virtualKeyRequest' => null,
        ];

        $this->apiUrl = "{$this->apiUrl}/tenants/{$database}/devices/{$serial}/commands";
        $result = $this->HttpRequest($param, $token);

        if (($result['status']) == 200) {
            return [
                'status' => true,
                'message' => 'Your request is processed successfully'
            ];
        }

        if (($result['status']) == 202) {
            return [
                'status' => false,
                'message' => 'Device seems not connected or not in range, your request is added into queue.'
            ];
        }

        return $return;
    }
    public function generateVirtualKeys(array $vehicledata): array
    {
        $return = [
            'status' => false,
            'message' => 'Passtime dealer # or vehicle serial # not set.'
        ];

        $server = trim($vehicledata['cs_setting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['cs_setting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['cs_setting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');

        if (empty($server) || empty($usr) || empty($pwd) || empty($database) || empty($serial)) {
            return $return;
        }

        $tokenResp = $this->authenticate($server, $usr, $pwd, $database);

        if (!$tokenResp['status']) {
            return $tokenResp;
        }

        $token = $tokenResp['data']['accessToken'];
        $param = [
            'isStoredVirtualKey' => false,
            'tapCardSerialNumbers' => [$serial],
            'userReference' => 'support@driveitaway.com',
            'beginningTimestamp' => time() * 1000,
            'endingTimestamp' => (time() + 180 * 3600) * 1000,
            'permissions' => [],
            'privileges' => ['ResetAtEndOfBooking'],
            'endBookConditions' => ['IgnitionOff'],
        ];

        $this->apiUrl = "{$this->apiUrl}/tenants/{$database}/devices/{$serial}/virtual-keys";
        $result = $this->HttpRequest($param, $token);

        if (($result['status']) == 200) {
            return ['status' => true, 'result' => $result['response']];
        }

        return $return;
    }
    public function deActivateVehicle(array $vehicledata): array
    {
        return $this->command($vehicledata, 'IgnitionInhibit');
    }
    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->command($vehicledata, 'IgnitionEnable');
    }
    public function lock(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'LOCK');
    }
    public function unlock(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'UNLOCK');
    }
    public function getDealerDevices(array $settingdata, array $search = []): array
    {
        $return = [
            'status' => false,
            'message' => 'Sorry, no record found matching with given criteria'
        ];

        $server = trim($settingdata['geotab_server'] ?? '');
        $usr = trim($settingdata['geotab_user'] ?? '');
        $pwd = trim($settingdata['geotab_pwd'] ?? '');
        $database = trim($settingdata['geotab_db'] ?? '');

        if (!empty($server) && !empty($usr) && !empty($pwd) && !empty($database)) {
            $param = [
                'method' => 'Get',
                'params' => [
                    'typeName' => 'StatusData',
                    'credentials' => [
                        'database' => $database,
                        'userName' => $usr,
                        'password' => $pwd
                    ],
                    'resultsLimit' => 500
                ]
            ];

            if (!empty($search)) {
                $param['params']['search'] = $search;
            }

            $this->apiUrl = "https://{$server}/apiv1";
            $result = $this->sendHttpRequest($param);

            if (isset($result['result'])) {
                $return = [
                    'status' => true,
                    'result' => $result['result']
                ];
            } else {
                $return['message'] = $result['error']['message'] ?? 'Unknown error';
            }
        }

        return $return;
    }
    public function sendHttpRequest(array $requestBody = [])
    {
        try {

            if (isset($requestBody['params']['credentials']['password'])) {
                $requestBody['params']['credentials']['password'] = '********';
            }

            $this->logger->info("GeotabKeyless MyGeotab Request [POST]", [
                'url' => $this->apiUrl,
                'body' => $requestBody,
            ]);

            $response = Http::withHeaders([
                'User-Agent' => 'mygeotab-php/1.0',
                'Content-Type' => 'application/json',
                'Charset' => 'UTF-8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache'
            ])
                ->withoutVerifying()
                ->timeout(30)
                ->post($this->apiUrl, $requestBody);

            $this->logger->info("GeotabKeyless MyGeotab Response [{$response->status()}]", [
                'url' => $this->apiUrl,
                'response' => is_array($response->json()) ? $response->json() : $response->body(),
            ]);

            if ($response->failed()) {
                return ['error' => ['message' => $response->body()]];
            }

            return $response->json();

        } catch (ConnectionException $e) {
            $this->logger->error("GeotabKeyless MyGeotab Request Exception: {$e->getMessage()}", [
                'url' => $this->apiUrl,
            ]);
            return ['error' => ['message' => $e->getMessage()]];
        }
    }
    private function HttpRequest(array $body, ?string $token = null): array
    {
        $headers = [
            'X-version' => '1.1',
            'Content-Type' => 'application/json',
            'Charset' => 'UTF-8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ];

        if ($token) {
            $headers = [
                'X-version' => '1.1',
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ];
        }

        try {

            if (isset($body['password'])) {
                $body['password'] = '********';
            }

            if (isset($body['serviceAccount']['password'])) {
                $body['serviceAccount']['password'] = '********';
            }

            $this->logger->info("GeotabKeyless Request", [
                'url' => $this->apiUrl,
                'body' => $body,
            ]);

            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->post($this->apiUrl, $body);

            $json = $response->json();

            $this->logger->info("GeotabKeyless Response [{$response->status()}]", [
                'url' => $this->apiUrl,
                'response' => is_array($json) ? $json : $response->body(),
            ]);

            return [
                'status' => $response->status(),
                'response' => $json ?? [],
            ];

        } catch (\Throwable $e) {
            $this->logger->error("GeotabKeyless Request Exception: {$e->getMessage()}", [
                'url' => $this->apiUrl,
            ]);

            return ['status' => 0, 'response' => ['detail' => $e->getMessage()]];
        }
    }
}
