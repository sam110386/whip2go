<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MeasureOneService
{
    public static string $_identifier = 'DIA_';
    private string $type = 'measureOne';

    public function createDataRequest(array $user): array
    {
        $token = $this->getToken();
        $userObj = DB::table('measureone_users')->where('user_id', $user['id'])->first();
        if (!empty($userObj)) {
            $individualId = $userObj->individual_id;
        } else {
            $individualObj = $this->createIndividual($user, $token);
            if (!$individualObj['status']) {
                return $individualObj;
            }
            $individualId = $individualObj['result']['id'];
            DB::table('measureone_users')->insert(['user_id' => $user['id'], 'individual_id' => $individualId]);
        }
        $isActivePaystubReport = DB::table('measureone_users')->where('user_id', $user['id'])->where('paystub', 1)->where('status', 1)->count();
        $header = $this->buildHeader($token);
        $requestBody = [
            'individual_id' => $individualId,
            'type' => 'INCOME_EMPLOYMENT_DETAILS',
            'delivery_details' => $this->buildDeliveryDetails(config('app.url') . 'measureone/webhooks/index'),
            'refresh_policy' => null,
        ];
        if ($isActivePaystubReport) {
            $requestBody['request_details'] = ['enable_manual_upload' => false];
        }
        $response = $this->sendHttpRequest('datarequests/new', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => array_merge($response, $token)];
    }

    public function createInsuranceDataRequest(array $user, string $orderRuleId = ''): array
    {
        $token = $this->getToken();
        $userObj = DB::table('measureone_users')->where('user_id', $user['id'])->first();
        if (!empty($userObj)) {
            $individualId = $userObj->individual_id;
        } else {
            $individualObj = $this->createIndividual($user, $token);
            if (!$individualObj['status']) {
                return $individualObj;
            }
            $individualId = $individualObj['result']['id'];
            DB::table('measureone_users')->insert(['user_id' => $user['id'], 'individual_id' => $individualId]);
        }
        $header = $this->buildHeader($token);
        $orderRuleId = base64_decode($orderRuleId);
        $requestBody = [
            'individual_id' => $individualId,
            'type' => 'AUTO_INSURANCE_DETAILS',
            'delivery_details' => $this->buildDeliveryDetails(config('app.url') . 'measureone/insu_webhooks/index', true),
            'refresh_policy' => ['enabled' => true, 'schedule' => ['frequency' => 'DAILY']],
            'enable_manual_upload' => false,
        ];
        $response = $this->sendHttpRequest('datarequests/new', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => array_merge($response, $token)];
    }

    public function createIndividual(array $user, $token = ''): array
    {
        if (empty($token)) {
            $token = $this->getToken();
        }
        $header = $this->buildHeader($token);
        $country = 'United States';
        $requestBody = [
            'external_id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'date_of_birth' => date('m/d/Y', strtotime($user['dob'] ?? '')),
            'address' => [
                'addr1' => $user['address'] ?? '',
                'addr2' => '',
                'city' => $user['city'] ?? '',
                'state_name' => $user['state'] ?? '',
                'country' => ['name' => $country],
            ],
        ];
        $response = $this->sendHttpRequest('individuals/new', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => $response];
    }

    public function getIncomeEmploymentDetails(array $record): array
    {
        $token = $this->getToken();
        $header = $this->buildHeader($token);
        if (empty($record['transaction_id'])) {
            return ['status' => false, 'message' => 'Sorry, report is not available now', 'result' => []];
        }
        $requestBody = ['transaction_id' => $record['transaction_id']];
        $response = $this->sendHttpRequest('services/get_income_employment_details', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => $response];
    }

    public function searchEmployer(string $datasourceId): array
    {
        $token = $this->getToken();
        $header = $this->buildHeader($token);
        $requestBody = ['filters' => ['id' => $datasourceId, 'countries' => ['CA', 'US']]];
        $response = $this->sendHttpRequest('datasources/get', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => $response];
    }

    public function getInsuranceDetails(array $requestBody = []): array
    {
        $token = $this->getToken();
        $header = $this->buildHeader($token);
        if (empty($requestBody)) {
            return ['status' => false, 'message' => 'Sorry, report is not available now', 'result' => []];
        }
        $response = $this->sendHttpRequest('services/get_insurance_details', $header, 'POST', $requestBody);
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => $response];
    }

    public function getToken(): array
    {
        $data = Cache::get($this->type);
        if (!isset($data['expires_at']) || time() > $data['expires_at']) {
            $result = $this->generateAccessToken();
            if (!$result['status']) {
                return $result;
            }
            $result = json_decode(json_encode($result['result']), true);
            $result['expires_at'] = (time() + $result['expires_in'] - 3600);
            Cache::put($this->type, $result, 365 * 24 * 60 * 60);
            return $result;
        }
        return $data;
    }

    public function generateAccessToken(): array
    {
        $header = [
            'Authorization: Basic ' . base64_encode(config('legacy.MeasureOne.client_id') . ':' . config('legacy.MeasureOne.secret')),
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
        $response = $this->sendHttpRequest('auth/generate_access_token', $header, 'POST');
        if (isset($response['error_code'])) {
            return ['status' => false, 'message' => $response['error_message'], 'result' => []];
        }
        return ['status' => true, 'message' => '', 'result' => $response];
    }

    public function sendHttpRequest(string $apiurl, array $header, string $method = 'GET', array $requestBody = []): array
    {
        $connection = curl_init();
        $url = config('legacy.MeasureOne.url') . $apiurl;
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($requestBody)) {
            curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($requestBody));
        }
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($connection);
        curl_close($connection);

        return json_decode($response, true) ?: [];
    }

    private function buildHeader(array $token): array
    {
        return [
            'version:' . config('legacy.MeasureOne.version'),
            'Authorization: Bearer ' . ($token['access_token'] ?? ''),
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
    }

    private function buildDeliveryDetails(string $webhookUrl, bool $includeRefreshFailed = false): array
    {
        $events = ['datasource.connected', 'datarequest.report_error', 'transaction.processed', 'datarequest.report_available', 'item.created', 'session.rejected'];
        if ($includeRefreshFailed) {
            $events[] = 'datarequest.refresh_failed';
        }
        $details = [];
        foreach ($events as $event) {
            $details[] = [
                'event_type' => $event,
                'url' => $webhookUrl,
                'headers' => [
                    'content-type' => 'application/json',
                    'Authorization' => 'Basic ' . config('legacy.MeasureOne.webhook_token'),
                ],
            ];
        }
        return $details;
    }
}
