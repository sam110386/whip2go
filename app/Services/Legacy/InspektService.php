<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Legacy\VehicleScanInspection;

class InspektService
{
    private string $_identifier = 'DRIVEITAWAY_';

    public function statusFlags(string $key = '')
    {
        $status = [
            '0' => "Not Uploaded",
            "1" => "Uploaded",
            "2" => "Approved",
            "3" => "Rejected"
        ];

        if (empty($key)) {
            return $status;
        }

        return $status[$key] ?? "N/A";
    }
    public function generateToken(array $dataObj = []): array
    {
        $caseId = $this->_identifier . $dataObj['vehicle_id'] . "_" . $dataObj['rand'];
        $url = config('legacy.Inspektlabs.url');
        $auth_url = config('legacy.Inspektlabs.auth_url');
        $clientId = config('legacy.Inspektlabs.clientId');
        $secret_key = config('legacy.Inspektlabs.secret_key');

        $request = [
            "apiKey" => $secret_key,
            "clientId" => $clientId,
            "caseId" => $caseId,
            "appType" => "custom",
            "personaId" => "1",
            "userDetails" => [
                "phone" => "",
                "Email" => ""
            ],
            "inputMetaData" => [
                "licensePlate" => "",
                "infoCarCode" => "",
                "vin" => $dataObj['vin_no'],
            ],
        ];

        $token = $this->sendHttpRequest($auth_url, $request);

        if (($token['status'] ?? '') != 'true') {
            return [
                "status" => false,
                "message" => $token['message'] ?? 'Unknown error',
                "result" => []
            ];
        }

        $webviewUrl = "{$url}#{$token['token']}";

        return [
            "status" => true,
            "message" => $token['message'] ?? '',
            "result" => [
                "webview_url" => $webviewUrl,
                "caseId" => $caseId,
                "token" => $token['token']
            ],
        ];
    }
    private function sendHttpRequest(string $url, array $requestBody = [])
    {
        $client = Http::withHeaders([
            'Charset' => 'UTF-8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ])->withoutVerifying();

        try {
            $response = !empty($requestBody) ? $client->post($url, $requestBody) : $client->get($url);
            return $response->json();
        } catch (\Exception $e) {
            Log::error("HTTP Request Failed: " . $e->getMessage());
            return null;
        }

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
            return [
                "status" => false,
                "message" => $tokenObj['message']
            ];
        }

        if (!empty($data['renter_id'])) {
            $msg = "You are requested to scan vehicle and upload scan report. Please click <a href='" . $tokenObj['result']['webview_url'] . "'>here</a> to start scan";
            Notifier::notifyByIntercomWithTagAsRenter($data['renter_id'], $msg, 'vehicle_scan_alert');
        }

        VehicleScanInspection::create([
            'case_id' => $tokenObj['result']['caseId'],
            'token' => $tokenObj['result']['token'],
            'vehicle_id' => $data['vehicle_id'],
            'order_id' => $data['id'],
            'parent_order_id' => !empty($data['parent_id']) ? $data['parent_id'] : $data['id'],
        ]);

        return $tokenObj;
    }
}
