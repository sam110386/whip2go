<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Migrated from: app/Plugin/Digisure/Lib/DigisureApi.php
 *
 * Client for Digisure driver trustscore API.
 */
class DigisureApiService
{
    private string $apiUrl;
    private string $apiKey;
    private string $preFix;

    public function __construct()
    {
        $this->apiUrl = config('legacy.Digisure.url', '');
        $this->apiKey = config('legacy.Digisure.api_key', '');
        $this->preFix = config('legacy.Digisure.preFix', '');
    }

    public function getAccessToken(): array
    {
        return $this->sendHttpRequest('login', ['api_key' => $this->apiKey]);
    }

    public function addCandidateToApi(array $userdata, bool $trustScore = false): array
    {
        $checkrStatus = $this->addDriverToDigisure($userdata, '', $trustScore);

        if (isset($checkrStatus['error'])) {
            return ['status' => false, 'message' => 'sorry, some data is wrong in digisure payload', 'result' => []];
        }

        $toSave = [
            'user_id'  => $userdata['id'],
            'channel'  => 'DIG',
            'checkr_id' => $checkrStatus['id'] ?? null,
        ];

        if ($trustScore) {
            $toSave['status'] = 1;
        }

        DB::table('user_reports')->insert($toSave);

        return $checkrStatus;
    }

    public function updateCandidateToApi(array $userdata, object $userExist, bool $trustScore = false): array
    {
        $checkrStatus = $this->addDriverToDigisure($userdata, $userExist->checkr_id, $trustScore);

        if (isset($checkrStatus['error'])) {
            return ['status' => false, 'message' => 'sorry, some data is wrong in digisure payload', 'result' => []];
        }

        if ($trustScore) {
            DB::table('user_reports')->where('id', $userExist->id)->update(['status' => 1]);
        }

        return $checkrStatus;
    }

    public function addDriverToDigisure(array $user, string $digisureId = '', bool $trustScore = false): array
    {
        return ['status' => false, 'message' => 'not in use'];

        // @codeCoverageIgnoreStart
        $token = $this->getAccessToken();
        if (!isset($token['token'])) {
            return ['status' => false, 'message' => $token['error'] ?? 'Unknown error'];
        }

        $body = [
            'driver' => [
                'partner_driver_id'              => $this->preFix . $user['id'],
                'given_names'                    => $user['first_name'],
                'family_name'                    => $user['last_name'],
                'date_of_birth'                  => date('Y-m-d', strtotime($user['dob'])),
                'driver_license_number'          => $user['licence_number'],
                'driver_license_expiration_date' => !empty($user['licence_exp_date'])
                    ? date('Y-m-d', strtotime($user['licence_exp_date'])) : '',
                'email'                          => $user['email'],
                'phone'                          => '+1' . substr($user['contact_number'], -10),
                'custom_fields'                  => ['user_id' => $user['id']],
                'driver_license_address'         => [
                    'street'  => $user['address'],
                    'street2' => '',
                    'city'    => $user['city'],
                    'state'   => $user['state'],
                    'zipcode' => $user['zip'],
                    'country' => 'US',
                ],
                'trigger_trustscore'             => $trustScore,
            ],
        ];

        $method = 'POST';
        $api = 'v1/drivers';
        if (!empty($digisureId)) {
            $api .= '/' . $digisureId;
            $method = 'PUT';
        }

        return $this->sendHttpRequest($api, $body, $method, $token['token']);
        // @codeCoverageIgnoreEnd
    }

    public function pullDriverFromDigisure(string $digisureId): array
    {
        $token = $this->getAccessToken();
        if (!isset($token['token'])) {
            return ['status' => false, 'message' => $token['error'] ?? 'Unknown error'];
        }

        return $this->sendHttpRequest('v1/drivers/' . $digisureId, [], 'GET', $token['token']);
    }

    private function sendHttpRequest(string $api, array $body = [], string $method = 'POST', string $bearerToken = ''): array
    {
        $url = $this->apiUrl . $api;

        $headers = [
            'Content-Type'   => 'application/json',
            'Accept-Charset' => 'utf-8',
            'Accept'         => 'application/json',
        ];

        if (!empty($bearerToken)) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        try {
            $pending = Http::withHeaders($headers);
            $upperMethod = strtoupper($method);
            if ($upperMethod === 'GET') {
                $response = $pending->get($url, $body);
            } elseif ($upperMethod === 'PUT') {
                $response = $pending->put($url, $body);
            } else {
                $response = $pending->post($url, $body);
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('DigisureApi error', ['message' => $e->getMessage(), 'api' => $api]);
            return ['error' => $e->getMessage()];
        }
    }
}
