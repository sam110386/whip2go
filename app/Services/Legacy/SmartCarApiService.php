<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartCarApiService
{
    private string $authUrl = 'https://auth.smartcar.com/oauth/token';
    private string $apiUrl = 'https://api.smartcar.com/v2.0';
    private int $timeout = 160;
    private $logger;
    private array $header = [];

    public function __construct()
    {
        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/smartcar.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
    public function getAuthToken(string $request, string $clientId, string $secret): array
    {
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$secret}"),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return $this->sendHttpRequest($this->authUrl, $request);
    }
    public function refreshToken(string $request, string $clientId, string $secret): array
    {
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$secret}"),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return $this->sendHttpRequest($this->authUrl, $request);
    }
    public function getAllVehicles(string $token): array
    {
        $url = "{$this->apiUrl}/vehicles?limit=40";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
        ];
        return $this->sendHttpRequest($url);
    }
    public function getVinNumber(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/vin";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
        ];

        return $this->sendHttpRequest($url);
    }
    public function getOdometer(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/odometer";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
        ];

        return $this->sendHttpRequest($url);
    }
    public function getLocation(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/location";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
        ];

        return $this->sendHttpRequest($url);
    }
    public function getBattery(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/battery";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
        ];

        return $this->sendHttpRequest($url);
    }
    public function lockCar(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/security";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ];

        return $this->sendHttpRequest($url, '{"action": "LOCK"}');
    }
    public function unlockCar(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/security";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ];

        return $this->sendHttpRequest($url, '{"action": "UNLOCK"}');
    }
    public function getOdometerBatteryAndLocation(string $id, string $token): array
    {
        $url = "{$this->apiUrl}/vehicles/{$id}/batch";
        $this->header = [
            'Accept-Charset' => 'utf-8',
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ];

        $request = '{"requests": [{ "path" : "/odometer" }, { "path" : "/location" },{ "path" : "/battery" }]}';
        return $this->sendHttpRequest($url, $request);
    }
    private function sendHttpRequest(string $url, string $requestBody = ''): array
    {
        $method = empty($requestBody) ? 'GET' : 'POST';

        try {
            $this->logger->info("SmartCar Request [{$method}]: {$url}", [
                'body' => $requestBody,
            ]);

            $pending = Http::withHeaders($this->header)->timeout($this->timeout);

            if ($method === 'POST') {
                $contentType = $this->header['Content-Type'] ?? 'application/json';
                $response = $pending->withBody($requestBody, $contentType)->post($url);
            } else {
                $response = $pending->get($url);
            }

            $body = $response->body();

            $this->logger->info("SmartCar Response [{$response->status()}]: {$url}", [
                'body' => $body,
            ]);

            return $response->json() ?? [];

        } catch (\Throwable $e) {
            $this->logger->error("SmartCar Request Exception: {$e->getMessage()}", [
                'method' => $method,
                'url' => $url,
                'body' => $requestBody,
            ]);
            return [];
        }
    }
}
