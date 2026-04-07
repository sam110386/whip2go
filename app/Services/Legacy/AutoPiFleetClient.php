<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Minimal port of CakePHP app/Lib/AutoPi.php::getDealerDevices / HTTP helper.
 */
class AutoPiFleetClient
{
    private const BASE = 'https://api.autopi.io/';

    /**
     * @return array{status:bool,message?:string,result?:array}
     */
    public function getDealerDevices(string $autopiToken): array
    {
        $autopiToken = trim($autopiToken);
        if ($autopiToken === '') {
            return ['status' => false, 'message' => 'Sorry, no record found matching with given criteria'];
        }
        $url = self::BASE . 'fleet/vehicles/?' . http_build_query([
            'page_size' => 100,
            'exclude_unassociated' => 'true',
        ]);
        $decoded = $this->request($url, $autopiToken, 'GET', []);
        if (isset($decoded['results']) && is_array($decoded['results'])) {
            return ['status' => true, 'result' => $decoded['results']];
        }

        return ['status' => false, 'message' => $decoded['detail'] ?? ($decoded['error'] ?? 'AutoPi request failed')];
    }

    private function request(string $url, string $token, string $method, array $data): array
    {
        $pending = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'APIToken ' . $token,
        ])->timeout(60);
        $response = $method === 'POST'
            ? $pending->post($url, $data)
            : $pending->get($url);
        $json = $response->json();
        if (!is_array($json)) {
            return ['error' => $response->body()];
        }

        return $json;
    }
}
