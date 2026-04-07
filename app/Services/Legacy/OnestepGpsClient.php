<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Onestepgps.php (authenticate + device-info).
 */
class OnestepGpsClient
{
    public function baseUrl(): string
    {
        return rtrim((string)config('services.onestepgps.api', 'https://track.onestepgps.com/v3/api/public/'), '/') . '/';
    }

    public function authenticate(string $apiKey): array
    {
        $url = $this->baseUrl() . 'user/me?api-key=' . urlencode($apiKey);
        $decoded = $this->getJson($url);
        if (isset($decoded['error'])) {
            return ['status' => 0, 'message' => is_string($decoded['error']) ? $decoded['error'] : 'Error', 'data' => []];
        }

        return ['status' => 1, 'message' => '', 'data' => $decoded];
    }

    /**
     * @param  array<string, mixed>  $queryParams
     * @return array{status:bool,message?:string,result?:mixed}
     */
    public function executeCustomCall(string $api, array $queryParams = []): array
    {
        $url = $this->baseUrl() . ltrim($api, '/') . '?' . http_build_query($queryParams);
        $decoded = $this->getJson($url);
        if (isset($decoded['error'])) {
            return ['status' => false, 'message' => is_string($decoded['error']) ? $decoded['error'] : 'Error'];
        }

        return ['status' => true, 'result' => $decoded];
    }

    private function getJson(string $url): array
    {
        $response = Http::withHeaders([
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ])->timeout(60)->get($url);
        $json = $response->json();
        if (!is_array($json)) {
            return ['error' => $response->body() ?: 'Invalid response'];
        }

        return $json;
    }
}
