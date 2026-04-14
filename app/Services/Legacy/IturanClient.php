<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Ituran.php
 * Ituran GPS device API: location, mileage, starter inhibit/enable.
 */
class IturanClient
{
    private string $apiUrl;
    private string $networkId;
    private string $senderUsername;
    private string $senderPassword;

    public function __construct()
    {
        $this->apiUrl = config('services.ituran.url', 'https://ws1.ituranusa.com/v2/ws_v2.3.2.asp');
        $this->networkId = config('services.ituran.network_id', 'oHSeJxBLDqMb8FcNYGJps35nfw3uRztt');
        $this->senderUsername = config('services.ituran.sender_username', 'Driveitaway');
        $this->senderPassword = config('services.ituran.sender_password', 'PThK0j');
    }

    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $usr = trim($vehicledata['CsSetting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');

        if (empty($usr) || empty($pwd) || empty($serial)) {
            return $return;
        }

        $xml = $this->buildDeviceDetailsXml($usr, $pwd, $serial);
        $result = $this->sendXmlRequest($xml);

        if (!empty($result) && empty($result['RequestHead']['Errors']) && isset($result['RequestBody']['GetDeviceDetailsResponse']['Location'])) {
            $loc = $result['RequestBody']['GetDeviceDetailsResponse']['Location'];
            $miles = $loc['Odometer'] ?? 0;
            if (($loc['Quality'] ?? '') === 'MEMORY') {
                $miles = sprintf('%0.2f', $miles * 0.621371);
            }
            $return = [
                'status' => true,
                'lat' => $loc['Lat'] ?? '',
                'lng' => $loc['Lon'] ?? '',
                'miles' => $miles,
                'lastLocate' => now()->toDateTimeString(),
            ];
        }

        return $return;
    }

    public function setVhicleLocation(array $vehicledata, $token): void
    {
        // Not applicable for Ituran
    }

    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op for Ituran
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $lastMile = (int)($vehicledata['Vehicle']['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];

        $usr = trim($vehicledata['CsSetting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');

        if (empty($usr) || empty($pwd) || empty($serial)) {
            return $return;
        }

        $xml = $this->buildDeviceDetailsXml($usr, $pwd, $serial);
        $result = $this->sendXmlRequest($xml);

        if (isset($result['RequestBody']['GetDeviceDetailsResponse']['Location'])) {
            $loc = $result['RequestBody']['GetDeviceDetailsResponse']['Location'];
            $miles = $loc['Odometer'] ?? $lastMile;
            if (($loc['Quality'] ?? '') === 'MEMORY') {
                $miles = sprintf('%d', $miles * 0.621371);
            }
            if (($vehicledata['Owner']['distance_unit'] ?? '') === 'KM') {
                return ['status' => true, 'miles' => sprintf('%d', $miles * 1.60934)];
            }
            $return = ['status' => true, 'miles' => $miles];
        }

        return $return;
    }

    public function getStartLastMile(array $vehicledata): array
    {
        return $this->getVehicleLastMile($vehicledata);
    }

    public function startPasstime(array $vehicleData, int $orderId): int
    {
        if (empty($orderId)) return 1;

        if (!empty($vehicleData)) {
            $resp = $this->getStartLastMile($vehicleData);
            return $resp['miles'] ?: ($vehicleData['Vehicle']['last_mile'] ?? 1);
        }
        return 1;
    }

    public function getPasstimeMiles(array $vehicleData): array
    {
        $return = ['miles' => 0, 'allowed_miles' => 0];
        if (!empty($vehicleData)) {
            $resp = $this->getVehicleLastMile($vehicleData);
            $return['miles'] = $resp['miles'];
            $return['allowed_miles'] = $vehicleData['Vehicle']['allowed_miles'] ?? 0;
        }
        return $return;
    }

    public function deActivateVehicle(array $vehicledata): array
    {
        return $this->sendCommand($vehicledata, 'SKON');
    }

    public function ActivateVehicle(array $vehicledata): array
    {
        return $this->sendCommand($vehicledata, 'SKOFF');
    }

    private function sendCommand(array $vehicledata, string $command): array
    {
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];
        $usr = trim($vehicledata['CsSetting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['CsSetting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['Vehicle']['passtime_serialno'] ?? '');

        if (empty($usr) || empty($pwd) || empty($serial)) {
            return $return;
        }

        $xml = "<Root>
            <RequestHead>
                <NetworkID>{$this->networkId}</NetworkID>
                <SenderUserName>{$this->senderUsername}</SenderUserName>
                <SenderPassword>{$this->senderPassword}</SenderPassword>
            </RequestHead>
            <RequestBody>
                <SendCommand>
                    <CusUsername>{$usr}</CusUsername>
                    <CusPassword>{$pwd}</CusPassword>
                    <IP>{$serial}</IP>
                    <Command>{$command}</Command>
                </SendCommand>
            </RequestBody>
        </Root>";

        $result = $this->sendXmlRequest($xml);

        if (!empty($result) && empty($result['RequestBody']['SendCommandResponse']['Errors'])) {
            return ['status' => true];
        }

        return $return;
    }

    private function buildDeviceDetailsXml(string $usr, string $pwd, string $serial): string
    {
        return "<Root>
            <RequestHead>
                <NetworkID>{$this->networkId}</NetworkID>
                <SenderUserName>{$this->senderUsername}</SenderUserName>
                <SenderPassword>{$this->senderPassword}</SenderPassword>
            </RequestHead>
            <RequestBody>
                <GetDeviceDetails>
                    <CusUsername>{$usr}</CusUsername>
                    <CusPassword>{$pwd}</CusPassword>
                    <IP>{$serial}</IP>
                    <DeviceFields>
                        <CollectionStatus>False</CollectionStatus>
                        <Commands>False</Commands>
                        <Location>True</Location>
                        <StarterStatus>False</StarterStatus>
                    </DeviceFields>
                </GetDeviceDetails>
            </RequestBody>
        </Root>";
    }

    private function sendXmlRequest(string $xml): ?array
    {
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/xml'])
                ->timeout(30)
                ->withBody($xml, 'application/xml')
                ->post($this->apiUrl);

            $body = $response->body();
            $obj = simplexml_load_string($body);
            return json_decode(json_encode($obj), true);
        } catch (\Throwable $e) {
            Log::warning("IturanClient: request failed – {$e->getMessage()}");
            return null;
        }
    }
}
