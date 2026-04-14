<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;

class SmartCarApiService
{
    private array $header = [];
    private string $_logfile = '';

    public function __construct()
    {
        $this->_logfile = storage_path('logs/smartcar_' . date('Y-m-d') . '.log');
    }

    public function getAuthToken(string $request, string $clientId, string $secret): array
    {
        $url = 'https://auth.smartcar.com/oauth/token';
        $this->header[] = 'Accept-Charset: utf-8';
        $this->header[] = 'Authorization: Basic ' . base64_encode($clientId . ':' . $secret);
        $this->header[] = 'Content-Type: application/x-www-form-urlencoded';
        return $this->sendHttpRequest($url, $request);
    }

    public function refreshToken(string $request, string $clientId, string $secret): array
    {
        $url = 'https://auth.smartcar.com/oauth/token';
        $this->header[] = 'Accept-Charset: utf-8';
        $this->header[] = 'Authorization: Basic ' . base64_encode($clientId . ':' . $secret);
        $this->header[] = 'Content-Type: application/x-www-form-urlencoded';
        return $this->sendHttpRequest($url, $request);
    }

    public function getAllVehicles(string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles?limit=40';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token];
        return $this->sendHttpRequest($url);
    }

    public function getVinNumber(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/vin';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token];
        return $this->sendHttpRequest($url);
    }

    public function getOdometer(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/odometer';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token];
        return $this->sendHttpRequest($url);
    }

    public function getLocation(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/location';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token];
        return $this->sendHttpRequest($url);
    }

    public function getBattery(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/battery';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token];
        return $this->sendHttpRequest($url);
    }

    public function lockCar(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/security';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token, 'Content-Type: application/json'];
        return $this->sendHttpRequest($url, '{"action": "LOCK"}');
    }

    public function unlockCar(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/security';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token, 'Content-Type: application/json'];
        return $this->sendHttpRequest($url, '{"action": "UNLOCK"}');
    }

    public function getOdometerBatteryAndLocation(string $id, string $token): array
    {
        $url = 'https://api.smartcar.com/v2.0/vehicles/' . $id . '/batch';
        $this->header = ['Accept-Charset: utf-8', 'Authorization: Bearer ' . $token, 'Content-Type: application/json'];
        $request = '{"requests": [{ "path" : "/odometer" }, { "path" : "/location" },{ "path" : "/battery" }]}';
        return $this->sendHttpRequest($url, $request);
    }

    public function sendHttpRequest(string $url, string $requestBody = ''): array
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->header);
        if (!empty($requestBody)) {
            curl_setopt($connection, CURLOPT_POST, 1);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        }
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_TIMEOUT, 160);
        $response = curl_exec($connection);
        curl_close($connection);

        Log::channel('daily')->debug('SmartCar API', ['url' => $url, 'request' => $requestBody]);

        return json_decode($response, true) ?: [];
    }
}
