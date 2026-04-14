<?php

namespace App\Services\Legacy;

class AirwallexService
{
    private string $_token = '';

    public function createCard(): array
    {
        $token = $this->getAccessToken();
        if (!isset($token['token']) || empty($token['token'])) {
            return ['status' => false, 'message' => 'Unable to get access token from Airwallex'];
        }
        $this->_token = $token['token'];

        $card = [
            'activate_on_issue' => true,
            'additional_cardholder_ids' => [
                '7f687fe6-dcf4-4462-92fa-80335301d9d2',
                'b0a1b145-4853-4456-b4b3-d690c7f3535c',
            ],
            'authorization_controls' => [
                'active_from' => '2018-10-31T00:00:00+0800',
                'active_to' => '2018-10-31T00:00:00+0800',
                'allowed_currencies' => ['USD', 'AUD'],
                'allowed_merchant_categories' => ['7531', '7534'],
                'allowed_transaction_count' => 'SINGLE',
                'blocked_transaction_usages' => [
                    ['transaction_scope' => 'MAGSTRIPE', 'usage_scope' => 'INTERNATIONAL'],
                    ['transaction_scope' => 'ONLINE_TRANSACTION', 'usage_scope' => 'ALL'],
                ],
                'transaction_limits' => [
                    'cash_withdrawal_limits' => [['amount' => 1000, 'interval' => 'PER_TRANSACTION']],
                    'currency' => 'USD',
                    'limits' => [['amount' => 1000, 'interval' => 'PER_TRANSACTION']],
                ],
            ],
            'brand' => 'VISA',
            'cardholder_id' => '7f687fe6-dcf4-4462-92fa-80335301d9d2',
            'form_factor' => 'VIRTUAL',
            'program' => [
                'interchange_percent' => '1.0',
                'purpose' => 'COMMERCIAL',
                'sub_type' => 'GOOD_FUNDS_CREDIT',
                'type' => 'PREPAID',
            ],
        ];

        return $card;
    }

    public function createCardHolder(): void
    {
        $cardholder = [
            'email' => 'john@example.com',
            'individual' => [
                'address' => [
                    'city' => 'Melbourne',
                    'country' => 'AU',
                    'line1' => '44 Gillespie St',
                    'line2' => 'Unit 2',
                    'postcode' => '3121',
                    'state' => 'VIC',
                ],
                'cardholder_agreement_terms_consent_obtained' => 'yes',
                'date_of_birth' => '1982-11-02',
                'express_consent_obtained' => 'yes',
                'name' => [
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'middle_name' => 'Fitzgerald',
                    'title' => 'Mr',
                ],
            ],
            'mobile_number' => '61432100100',
            'type' => 'INDIVIDUAL',
        ];

        $url = config('legacy.Airwallex.apiHost') . 'issuing/cardholders/create';
        $header = [
            'Authorization: Bearer ' . $this->_token,
        ];
    }

    public function getAccessToken(): array
    {
        $url = config('legacy.Airwallex.apiHost') . 'authentication/login';
        $headers = [
            'x-client-id: ' . config('legacy.Airwallex.x-client-id'),
            'x-api-key: ' . config('legacy.Airwallex.x-api-key'),
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($connection);
        curl_close($connection);

        return json_decode($response, true) ?: [];
    }

    public function sendHttpRequest(string $url, array $requestBody = [], array $header = []): array
    {
        $headers = array_merge($header, [
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ]);
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
        if (!empty($requestBody)) {
            curl_setopt($connection, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($requestBody));
        } else {
            curl_setopt($connection, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($connection);
        curl_close($connection);

        return json_decode($response, true) ?: [];
    }
}
