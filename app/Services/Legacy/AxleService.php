<?php

namespace App\Services\Legacy;

class AxleService
{
    private array $header = [];

    public static array $PolicyStatus = [
        "0" => "Not Connected", "1" => "Connected", "2" => "Active",
        "3" => "Inactive", "4" => "Insufficient Coverage",
        "5" => "Manually Accepted", "6" => "Processing",
    ];

    public static array $rules = [
        "lienholder" => ["label" => "Policy Lien Holder", "accepted" => false, "policy_text" => ""],
        'lessor' => ["label" => "Policy Lessor", "accepted" => false, "policy_text" => ""],
        'compreshensive' => ["label" => "Compreshensive Deductible", "accepted" => false, "policy_text" => ""],
        'collision' => ["label" => "Collision Deductible", "accepted" => false, "policy_text" => ""],
        'vin' => ["label" => "VIN", "accepted" => false, "policy_text" => ""],
        'insurance_old' => ["label" => "Insurance Old", "insurance_payer" => "", "insurance_rate" => ""],
        'insurance_new' => ["label" => "Insurance New", "insurance_payer" => "", "insurance_rate" => ""],
        'emfinsurance_old' => ["label" => "EMF Insurance Old", "insurance_payer" => "", "insurance_rate" => ""],
        'emfinsurance_new' => ["label" => "EMF Insurance New", "insurance_payer" => "", "insurance_rate" => ""],
    ];

    public function startIgnition(array $data = []): array
    {
        $requestBody = [
            "redirectUri" => config('app.url') . "axle/axle_webhooks/return",
            "webhookUri" => config('app.url') . "axle/axle_webhooks/index",
            "metadata" => ["order_id" => $data['order_id']],
            'user' => [
                "id" => $data['renter_id'],
                "firstName" => $data['first_name'],
                "lastName" => $data['last_name'],
            ],
        ];
        $this->header = [
            "x-client-id:" . config('legacy.Axle.x-client_id'),
            "x-client-secret:" . config('legacy.Axle.x-client_secret'),
            "Content-Type: application/json",
            "Charset=UTF-8",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "x-access-token:" . ($data['x-access-token'] ?? ''),
        ];
        return $this->sendHttpRequest('ignition', 'POST', $requestBody);
    }

    public function fetchPolicyDetails(array $obj, string $policy): array
    {
        $this->buildAuthHeader();
        $this->header[] = 'x-access-token:' . $obj['access_token'];
        return $this->sendHttpRequest('policies/' . $policy, 'GET', []);
    }

    public function fetchAccountDetails(array $obj): array
    {
        $this->buildAuthHeader();
        $this->header[] = 'x-access-token:' . $obj['access_token'];
        $accountId = $obj['account_id'];
        return $this->sendHttpRequest('accounts/' . $accountId, 'GET', []);
    }

    public function fetchAccountAndPolicyDetails(array $obj): array
    {
        $this->buildAuthHeader();
        $tokenObj = $this->tokenExchange($obj['axle_authCode']);
        if (!($tokenObj['success'] ?? false)) {
            return $tokenObj;
        }
        $this->header[] = 'x-access-token:' . $tokenObj['data']['accessToken'];
        $tokenArray = ['access_token' => $tokenObj['data']['accessToken'], "accountId" => $tokenObj['data']['account']];
        $accountId = $tokenObj['data']['account'];
        $AccountObj = $this->sendHttpRequest('accounts/' . $accountId, 'GET', []);
        if (!($AccountObj['success'] ?? false)) {
            return array_merge($tokenArray, $AccountObj);
        }
        $policy = current($tokenObj['data']['policies']);
        $return = $this->sendHttpRequest('policies/' . $policy, 'GET', []);
        return array_merge($tokenArray, $return);
    }

    public function closeAxleConnection(int $orderId): array
    {
        $axleStatusObj = \Illuminate\Support\Facades\DB::table('axle_status')
            ->where('order_id', $orderId)->first();
        if (empty($axleStatusObj) || empty($axleStatusObj->access_token) || $axleStatusObj->type != 'axle') {
            return ['success' => true, 'message' => 'No Axle connection found for this order'];
        }
        $terminate = $this->terminateToken((array) $axleStatusObj);
        if (!($terminate['success'] ?? false)) {
            return $terminate;
        }
        \Illuminate\Support\Facades\DB::table('axle_status')->where('id', $axleStatusObj->id)->update([
            'axle_status' => 0, 'expired_on' => null,
            'calculated_insurance' => 0, 'policy' => null, 'access_token' => null,
        ]);
        return ['success' => true, 'message' => 'Disconnected'];
    }

    public function terminateToken(array $obj): array
    {
        $this->buildAuthHeader();
        $this->header[] = 'x-access-token:' . $obj['access_token'];
        return $this->sendHttpRequest('token/descope', 'POST', ["scope" => "monitoring"]);
    }

    private function tokenExchange(string $authCode): array
    {
        return $this->sendHttpRequest('token/exchange', 'POST', ["authCode" => $authCode]);
    }

    private function buildAuthHeader(): void
    {
        $this->header = [
            "x-client-id:" . config('legacy.Axle.x-client_id'),
            "x-client-secret:" . config('legacy.Axle.x-client_secret'),
            "Content-Type: application/json",
            "Charset=UTF-8",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        ];
    }

    private function sendHttpRequest(string $api, string $request = 'GET', array $requestBody = []): array
    {
        $url = config('legacy.Axle.apiHost') . '/' . $api;
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, $request);
        if (!empty($requestBody)) {
            curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($requestBody));
        }
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($connection);
        curl_close($connection);
        return json_decode($response, true) ?: [];
    }
}
