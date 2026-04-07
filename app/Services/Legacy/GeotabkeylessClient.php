<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Geotabkeyless.php::authenticate.
 */
class GeotabkeylessClient
{
    private const AUTH_URL = 'https://keyless.geotab.com/api/auth';

    public function authenticate(string $server, string $username, string $password, string $database): array
    {
        $payload = [
            'database' => $database,
            'userName' => $username,
            'password' => $password,
            'server' => $server,
        ];
        $response = Http::asJson()
            ->withHeaders([
                'X-version' => '1.1',
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->post(self::AUTH_URL, $payload);

        $decoded = $response->json();
        if ($response->successful() && is_array($decoded)) {
            return ['status' => 1, 'message' => '', 'data' => $decoded];
        }
        $detail = is_array($decoded) ? ($decoded['detail'] ?? $response->body()) : $response->body();

        return ['status' => 0, 'message' => (string)$detail, 'data' => []];
    }
}
