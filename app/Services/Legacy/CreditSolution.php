<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Creditsolution.php
 * 700CreditSolution API client for pre-qualification credit checks.
 */
class CreditSolution
{
    private string $apiUrl;
    private string $username;
    private string $password;

    private array $candidateTemplate = [
        'ACCOUNT' => '',
        'PASSWD' => '',
        'pass' => 2,
        'PROCESS' => 'PCCREDIT',
        'NAME' => '',
        'ADDRESS' => '',
        'CITY' => '',
        'STATE' => '',
        'ZIP' => '',
        'PRODUCT' => 'PREQUALIFY',
        'email' => 'adam@driveitaway.com',
        'BUREAU' => 'XPN',
    ];

    public function __construct()
    {
        $this->apiUrl = config('services.creditsolution.url', '');
        $this->username = config('services.creditsolution.username', '');
        $this->password = config('services.creditsolution.password', '');
    }

    public function addCandidateToApi(array $user, string $bureau = 'XPN'): array
    {
        $candidate = $this->candidateTemplate;
        $candidate['ACCOUNT'] = $this->username;
        $candidate['PASSWD'] = $this->password;
        $candidate['NAME'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
        $candidate['ADDRESS'] = $user['address'] ?? '';
        $candidate['CITY'] = $user['city'] ?? '';
        $candidate['STATE'] = $user['state'] ?? '';
        $candidate['ZIP'] = $user['zip'] ?? '';
        $candidate['email'] = $user['email'] ?? '';
        $candidate['BUREAU'] = $bureau;

        $result = $this->sendHttpRequest($candidate);

        if (empty($result)) {
            return ['status' => false, 'message' => '700 credit API is down'];
        }

        if (isset($result['Creditsystem_Error'])) {
            $msg = $result['Creditsystem_Error']['@attributes']['message'] ?? 'Unknown credit API error';
            (new Emailnotify())->sendCustomEmail($msg, config('mail.admin_email', 'admin@driveitaway.com'), 'Creditsolution error');
            return ['status' => false, 'message' => $msg];
        }

        $logPath = storage_path("app/CreditLogs/{$user['id']}_{$bureau}.json");
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($logPath, json_encode($result));

        $score = 0;
        if (
            isset($result['XML_Report']['Prescreen_Report']['Score']) &&
            ($result['XML_Report']['Prescreen_Report']['ResultCode'] ?? '') === 'A'
        ) {
            $score = $result['XML_Report']['Prescreen_Report']['Score'];
        }

        return [
            'status' => true,
            'message' => 'Success',
            'score' => $score,
            'repossession' => $this->filterRepossession($result['Data_Points']['Trade'] ?? []),
        ];
    }

    private function filterRepossession(array $trades): int
    {
        if (!isset($trades[0])) {
            return isset($trades['repossession']) ? (int)$trades['repossession'] : 0;
        }
        foreach ($trades as $trade) {
            if (isset($trade['repossession']) && $trade['repossession'] == 1) {
                return 1;
            }
        }
        return 0;
    }

    private function sendHttpRequest(array $body): ?array
    {
        if (empty($this->apiUrl)) {
            Log::warning('CreditSolution: API URL not configured');
            return null;
        }

        $response = Http::asForm()->timeout(160)->post($this->apiUrl, $body);
        $xml = $response->body();

        try {
            $obj = simplexml_load_string($xml);
            return json_decode(json_encode($obj), true);
        } catch (\Throwable $e) {
            Log::warning("CreditSolution: XML parse error – {$e->getMessage()}");
            return null;
        }
    }
}
