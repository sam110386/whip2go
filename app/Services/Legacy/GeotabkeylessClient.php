<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Geotabkeyless.php
 * Geotab Keyless API: auth, device commands (lock/unlock/inhibit/enable), virtual keys.
 */
class GeotabkeylessClient
{
    private const BASE_URL = 'https://keyless.geotab.com/api';

    public function authenticate(string $server, string $username, string $password, string $database): array
    {
        $payload = [
            'database' => $database,
            'userName' => $username,
            'password' => $password,
            'server' => $server,
        ];
        $result = $this->httpRequest(self::BASE_URL . '/auth', $payload);

        if (($result['status'] ?? 0) == 200) {
            return ['status' => 1, 'message' => '', 'data' => $result['response']];
        }
        return ['status' => 0, 'message' => $result['response']['detail'] ?? 'Auth failed', 'data' => []];
    }

    public function getVehicleLocation(array $vehicledata): array
    {
        return ['status' => false, 'lat' => '', 'lng' => ''];
    }

    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $lastMile = (int)($vehicledata['Vehicle']['last_mile'] ?? 0);
        return ['status' => false, 'miles' => $lastMile];
    }

    public function getStartLastMile(array $vehicledata): array
    {
        return $this->getVehicleLastMile($vehicledata);
    }

    public function startPasstime(array $vehicleData, int $orderId): void
    {
        // No-op for keyless
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
            'serviceAccount' => ['username' => $username, 'password' => $pwd],
            'isNotificationEnabled' => false,
        ];

        $result = $this->httpRequest(self::BASE_URL . '/tenants', $param, $token);
        if (($result['status'] ?? 0) == 200) {
            return ['status' => 1, 'message' => '', 'data' => $result['response']];
        }
        return ['status' => 0, 'message' => $result['response']['detail'] ?? 'Tenant setup failed', 'data' => []];
    }

    public function generateVirtualKeys(array $vehicledata): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $settings = $this->parseVehicleSetting($vehicledata);

        $server = trim($settings['CsSetting']['geotab_server'] ?? '');
        $usr = trim($settings['CsSetting']['geotab_user'] ?? '');
        $pwd = trim($settings['CsSetting']['geotab_pwd'] ?? '');
        $database = trim($settings['CsSetting']['geotab_db'] ?? '');
        $serial = trim($settings['Vehicle']['passtime_serialno'] ?? '');

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

        $url = self::BASE_URL . "/tenants/{$database}/devices/{$serial}/virtual-keys";
        $result = $this->httpRequest($url, $param, $token);

        if (($result['status'] ?? 0) == 200) {
            return ['status' => true, 'result' => $result['response']];
        }

        return $return;
    }

    public static function deActivateVehicle(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'IgnitionInhibit');
    }

    public static function ActivateVehicle(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'IgnitionEnable');
    }

    public static function lock(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'LOCK');
    }

    public static function unlock(array $vehicledata): array
    {
        return (new self)->command($vehicledata, 'UNLOCK');
    }

    private function command(array $vehicledata, string $cmd): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        $server = trim($vehicledata['CsSetting']['geotab_server'] ?? '');
        $usr = trim($vehicledata['CsSetting']['geotab_user'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['geotab_pwd'] ?? '');
        $database = trim($vehicledata['CsSetting']['geotab_db'] ?? '');
        $serial = trim($vehicledata['Vehicle']['passtime_serialno'] ?? '');

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

        $url = self::BASE_URL . "/tenants/{$database}/devices/{$serial}/commands";
        $result = $this->httpRequest($url, $param, $token);

        if (($result['status'] ?? 0) == 200) {
            return ['status' => true, 'message' => 'Your request is processed successfully'];
        }
        if (($result['status'] ?? 0) == 202) {
            return ['status' => false, 'message' => 'Device seems not connected or not in range, your request is added into queue.'];
        }

        return $return;
    }

    private function parseVehicleSetting(array $vehicleData): array
    {
        if (!isset($vehicleData['VehicleSetting']) || empty($vehicleData['VehicleSetting']['data'] ?? null)) {
            return $vehicleData;
        }
        $json = json_decode($vehicleData['VehicleSetting']['data'], true);
        if (isset($json['gps_provider']) && !empty($json['gps_provider']) && isset($json['passtime']) && !empty($json['passtime'])) {
            $vehicleData['CsSetting'] = $json;
        }
        return $vehicleData;
    }

    private function httpRequest(string $url, array $body, ?string $token = null): array
    {
        $headers = ['X-version' => '1.1', 'Content-Type' => 'application/json'];
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
            $headers['accept'] = 'application/json';
        }

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->post($url, $body);

            return [
                'status' => $response->status(),
                'response' => $response->json() ?? [],
            ];
        } catch (\Throwable $e) {
            Log::warning("GeotabkeylessClient: request to {$url} failed – {$e->getMessage()}");
            return ['status' => 0, 'response' => ['detail' => $e->getMessage()]];
        }
    }
}
