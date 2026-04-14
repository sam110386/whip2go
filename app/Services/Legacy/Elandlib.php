<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Elandlib
{
    private array $header = [];
    private string $jwt_sub;
    private string $jwt_token;
    private string $dealer_indentifier;

    public function __construct(string $jwtsub = '', string $jwttoken = '', string $dealerindentifier = '')
    {
        $this->header[] = 'Content-type: application/json';
        $this->header[] = 'Accept-Charset: utf-8';
        $this->jwt_sub = !empty($jwtsub) ? $jwtsub : config('legacy.Eland.jwt_sub', '');
        $this->jwt_token = !empty($jwttoken) ? $jwttoken : config('legacy.Eland.jwt_token', '');
        $this->dealer_indentifier = !empty($dealerindentifier) ? $dealerindentifier : config('legacy.Eland.dealer_indentifier', '');
    }

    public function getClientIp(): string
    {
        return request()->ip() ?? 'UNKNOWN';
    }

    public function sendLead(array $data): array
    {
        $phone = preg_replace('/[^0-9]/', '', $data['Eland']['phone'] ?? '');

        $body['application'] = [
            'ID' => $data['eland_id'] ?? '',
            'Url' => config('app.url'),
            'IPAddress' => $this->getClientIp(),
            'Applicants' => [[
                'Type' => 'Primary',
                'FirstName' => $data['Eland']['fname'],
                'MiddleName' => $data['Eland']['mname'] ?? '',
                'LastName' => $data['Eland']['lname'],
                'IDs' => [
                    ['Type' => 'SSN', 'Value' => preg_replace('/[^0-9]/', '', $data['Eland']['ssn'] ?? '')],
                    ['Type' => 'DL', 'Value' => $data['Eland']['licence_number'] ?? ''],
                ],
                'PhoneNumbers' => !empty($phone) ? [['Type' => 'Cell', 'AreaCode' => substr($phone, 0, 3), 'Prefix' => substr($phone, 3, 3), 'Suffix' => substr($phone, 6, 4), 'Extension' => '']] : [],
                'Housing' => ['Type' => $data['Eland']['OwnOrRent'] ?? '', 'Payment' => $data['Eland']['rent'] ?? ''],
                'GrossMonthlyIncome' => $data['ElandResidence']['gross_income'] ?? '',
                'OtherMonthlyIncome' => $data['ElandResidence']['other_income'] ?? '',
                'OtherIncomeDescription' => $data['ElandResidence']['other_income_des'] ?? '',
                'EmailAddress' => $data['Eland']['email'] ?? '',
                'DateOfBirth' => $data['Eland']['dob'] ?? '',
                'Addresses' => [[
                    'Type' => 'Current',
                    'AddressLine1' => ($data['Eland']['houseno'] ?? '') . ' ' . ($data['Eland']['address'] ?? ''),
                    'City' => $data['Eland']['city'] ?? '',
                    'State' => $data['Eland']['state'] ?? '',
                    'PostalCode' => $data['Eland']['zip'] ?? '',
                    'YearsAtAddress' => $data['Eland']['years'] ?? '',
                    'MonthsAtAddress' => $data['Eland']['months'] ?? '',
                ]],
                'Employers' => [[
                    'Type' => 'Current',
                    'Class' => $data['ElandResidence']['emptype'] ?? '',
                    'YearsAtEmployer' => $data['ElandResidence']['emp_years'] ?? '',
                    'MonthsAtEmployer' => $data['ElandResidence']['emp_months'] ?? '',
                    'Name' => $data['ElandResidence']['employer'] ?? '',
                    'PositionTitle' => $data['ElandResidence']['occupation'] ?? '',
                    'PhoneNumber' => ($data['ElandResidence']['workphone_ext'] ?? '') . ($data['ElandResidence']['workphone'] ?? ''),
                ]],
            ]],
        ];

        $token = $this->generateAuthToken();
        if (!$token['status']) {
            return $token;
        }

        $this->header[] = 'Authorization: Bearer ' . $token['token'];
        $url = config('legacy.Eland.api_url', '') . $this->dealer_indentifier;

        $consentResp = $this->generateConsent();
        if (!$consentResp['status']) {
            return ['status' => false, 'message' => $consentResp['message']];
        }

        $body['application']['Disclosures'] = [[
            'Token' => $consentResp['token'],
            'IPAddress' => $this->getClientIp(),
            'Source' => 'web',
            'DateTimeAccepted' => gmdate('Y-m-d\TH:i:s'),
            'ApplicantType' => 'Primary',
        ]];

        $resp = $this->sendHttpRequest($this->header, $url, json_encode($body), true);

        if (($resp['result'] ?? '') == 'success' || ($resp['result'] ?? '') == 'alert') {
            return ['status' => true, 'appid' => $resp['applicationID'] ?? ''];
        }

        return ['status' => false, 'message' => $resp['alerts'][0]['text'] ?? 'Unknown error'];
    }

    public function generateConsent(): array
    {
        $url = config('legacy.Eland.disclosure_url', '') . $this->dealer_indentifier;
        $resp = $this->sendHttpRequest($this->header, $url, '', false);

        if (($resp['status'] ?? '') == 'success' && isset($resp['disclosures'][0])) {
            return ['status' => true, 'token' => $resp['disclosures'][0]['Token']];
        }

        return ['status' => false, 'message' => $resp['alerts'][0]['text'] ?? 'Consent generation failed'];
    }

    public function generateAuthToken(): array
    {
        $header = ['Content-type: application/x-www-form-urlencoded', 'Accept-Charset: utf-8'];
        $body = 'grant_type=client_credentials&client_id=' . $this->jwt_sub . '&client_secret=' . $this->jwt_token;
        $resp = $this->sendHttpRequest($header, config('legacy.Eland.auth_token_url', ''), $body, true);

        if (isset($resp['access_token'])) {
            return ['status' => true, 'token' => $resp['access_token']];
        }

        return ['status' => false, 'token' => '', 'msg' => 'Sorry, auth token generation failed. Please contact support team'];
    }

    public function sendHttpRequest(array $header, string $url, string $requestBody = '', bool $ispost = false): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if (!empty($requestBody)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        Log::channel('daily')->info('Eland API', ['url' => $url, 'request' => $requestBody, 'response' => $response]);

        return json_decode($response, true) ?? [];
    }

    public static function createJwtToken(string $sub, string $secret): string
    {
        $payload = [
            'iss' => 'www.driveitaway.com/',
            'aud' => 'https://api.elendsolutions.com',
            'sub' => !empty($sub) ? $sub : '1220',
            'iat' => time(),
            'nbt' => time(),
            'exp' => time() + 31536000,
        ];

        return \Firebase\JWT\JWT::encode($payload, base64_decode($secret), 'HS256');
    }
}
