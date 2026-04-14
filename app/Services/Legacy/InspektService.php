<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class InspektService
{
    private string $_identifier = 'DRIVEITAWAY_';

    public function statusFlags(string $key = '')
    {
        $status = ['0' => "Not Uploaded", "1" => "Uploaded", "2" => "Approved", "3" => "Rejected"];
        if (empty($key)) {
            return $status;
        }
        return $status[$key] ?? "N/A";
    }

    public function generateToken(array $dataObj = []): array
    {
        $caseId = $this->_identifier . $dataObj['vehicle_id'] . "_" . $dataObj['rand'];
        $request = [
            "apiKey" => config('legacy.Inspektlabs.secret_key'),
            "clientId" => config('legacy.Inspektlabs.clientId'),
            "caseId" => $caseId,
            "appType" => "custom",
            "personaId" => "1",
            "userDetails" => ["phone" => "", "Email" => ""],
            "inputMetaData" => [
                "licensePlate" => "",
                "infoCarCode" => "",
                "vin" => $dataObj['vin_no'],
            ],
        ];
        $token = $this->sendHttpRequest(config('legacy.Inspektlabs.auth_url'), $request);
        if (($token['status'] ?? '') != 'true') {
            return ["status" => false, "message" => $token['message'] ?? 'Unknown error', "result" => []];
        }
        $webviewUrl = config('legacy.Inspektlabs.url') . '#' . $token['token'];
        return [
            "status" => true,
            "message" => $token['message'] ?? '',
            "result" => ["webview_url" => $webviewUrl, "caseId" => $caseId, "token" => $token['token']],
        ];
    }

    public function createTokenAndSave(array $data = []): array
    {
        $reqObj = [
            'vehicle_id' => $data['vehicle_id'],
            'vin_no' => $data['vin_no'],
            'rand' => $data['id'],
        ];
        $tokenObj = $this->generateToken($reqObj);
        if (!$tokenObj['status']) {
            return ["status" => false, "message" => $tokenObj['message']];
        }
        DB::table('vehicle_scan_inspections')->insert([
            'case_id' => $tokenObj['result']['caseId'],
            'token' => $tokenObj['result']['token'],
            'vehicle_id' => $data['vehicle_id'],
            'order_id' => $data['id'],
            'parent_order_id' => !empty($data['parent_id']) ? $data['parent_id'] : $data['id'],
        ]);
        return $tokenObj;
    }

    private function sendHttpRequest(string $url, array $requestBody = []): array
    {
        $header = [
            "Content-Type: application/json",
            "Charset=UTF-8",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        ];
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
        if (!empty($requestBody)) {
            curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($requestBody));
        } else {
            curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "GET");
        }
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($connection);
        curl_close($connection);
        return json_decode($response, true) ?: [];
    }
}
