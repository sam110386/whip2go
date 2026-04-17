<?php

namespace App\Services\Legacy;

class AtomicService
{
    public static string $_identifier = 'DRIVEITAWAY_';

    public function pullConnectedAccount(int $userid): array
    {
        if (empty($userid)) {
            return ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        }
        $atomicUrl = config('legacy.Atomic.apiHost') . '/linked-account/list/' . self::$_identifier . $userid;
        return $this->sendHttpRequest($atomicUrl, []);
    }

    public function pullEmployer(string $userid): array
    {
        if (empty($userid)) {
            return ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        }
        $atomicUrl = config('legacy.Atomic.apiHost') . '/employment/' . self::$_identifier . $userid;
        return $this->sendHttpRequest($atomicUrl, []);
    }

    public function pullEmployeIdentity(string $userid): array
    {
        if (empty($userid)) {
            return ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        }
        $atomicUrl = config('legacy.Atomic.apiHost') . '/identity/' . self::$_identifier . $userid;
        return $this->sendHttpRequest($atomicUrl, []);
    }

    public function sendHttpRequest(string $url, array $requestBody): array
    {
        $header = [
            'x-api-key:' . config('legacy.Atomic.key'),
            'x-api-secret:' . config('legacy.Atomic.secret'),
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
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
