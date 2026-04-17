<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Argyle.php
 * Argyle API client for income/activity data.
 */
class ArgyleClient
{
    public function getUserIncome(): ?array
    {
        return $this->sendHttpRequest('webhooks', 'GET');
    }

    public function createWebhook(): ?array
    {
        return $this->sendHttpRequest('webhooks', 'GET', []);
    }

    public function deleteWebhook(): ?array
    {
        return $this->sendHttpRequest('webhooks/004c5eed-c8d4-4e5c-9de0-8bacd8ba3b06', 'DELETE', []);
    }

    public function getUserActivity(): ?array
    {
        return $this->sendHttpRequest('activities?user=3549c47d-8416-4101-9e61-868c1b72f804', 'GET', []);
    }

    public function getActivity(string $id): ?array
    {
        return $this->sendHttpRequest("activities/{$id}", 'GET', []);
    }

    private function sendHttpRequest(string $api, string $method = 'GET', array $body = []): ?array
    {
        $clientId = config('services.argyle.client_id');
        $clientSecret = config('services.argyle.client_secret');
        $apiHost = config('services.argyle.api_host', 'https://api-sandbox.argyle.com/v1');
        $url = rtrim($apiHost, '/') . '/' . $api;

        $pending = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$clientSecret}"),
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
        ])->withoutVerifying()->timeout(30);

        $response = match (strtoupper($method)) {
            'POST' => $pending->post($url, $body),
            'DELETE' => $pending->delete($url, $body),
            default => $pending->get($url),
        };

        return $response->json();
    }
}
