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
