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
    private string $apiUrl = '';
    private string $networkId = '';
    private string $senderUsername = '';
    private string $senderPassword = '';
    private $logger;

    public function __construct()
    {
        $this->apiUrl = config('legacy.Ituran.url', '');
        $this->networkId = config('legacy.Ituran.networkId', '');
        $this->senderUsername = config('legacy.Ituran.senderUserName', '');
        $this->senderPassword = config('legacy.Ituran.senderPassword', '');

        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/lturan.log'),
            'level' => 'debug',
            'days' => 14,
        ]);

    }
    public function getVehicleLocation(array $vehicledata): array
    {
        $return = ['status' => false, 'lat' => '', 'lng' => ''];
        $usr = trim($vehicledata['cs_setting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');

        if (empty($usr) || empty($pwd) || empty($serial)) {
            return $return;
        }

        $xml = $this->buildDeviceDetailsXml($usr, $pwd, $serial);
        $result = $this->sendHttpRequest($xml);

        if (
            !empty($result) &&
            empty($result['RequestHead']['Errors']) &&
            isset($result['RequestBody']['GetDeviceDetailsResponse']['Location'])
        ) {
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
        return;
    }
    public function setVehicleLastMile(array $vehicledata): void
    {
        // No-op for Ituran
        return;
    }

    public function getVehicleLastMile(array $vehicledata): array
    {
        $lastMile = (int) ($vehicledata['last_mile'] ?? 0);
        $return = ['status' => false, 'miles' => $lastMile];
        $usr = trim($vehicledata['cs_setting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['gps_serialno'] ?? '');

        if (empty($usr) || empty($pwd) || empty($serial)) {
            return $return;
        }

        $xml = $this->buildDeviceDetailsXml($usr, $pwd, $serial);
        $result = $this->sendHttpRequest($xml);

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

    public function startPasstime(array $vehicledata, int $orderId): int
    {
        if (empty($orderId)) {
            return 1;
        }

        if (!empty($vehicledata)) {
            $resp = $this->getStartLastMile($vehicledata);
            return $resp['miles'] ?: ($vehicledata['last_mile'] ?? 1);
        }

        return 1;
    }

    public function getPasstimeMiles(array $vehicledata): array
    {
        $return = ['miles' => 0, 'allowed_miles' => 0];

        if (!empty($vehicledata)) {
            $resp = $this->getVehicleLastMile($vehicledata);
            $return['miles'] = $resp['miles'];
            $return['allowed_miles'] = $vehicledata['allowed_miles'] ?? 0;
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
        $usr = trim($vehicledata['cs_setting']['ituran_usr'] ?? '');
        $pwd = trim($vehicledata['cs_setting']['ituran_pwd'] ?? '');
        $serial = trim($vehicledata['passtime_serialno'] ?? '');

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

        $result = $this->sendHttpRequest($xml);

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
    private function sendHttpRequest(string $xml): ?array
    {
        try {
            $this->logger->info('Ituran Request', [
                'url' => $this->apiUrl,
                'xml' => $xml,
            ]);

            $response = Http::withHeaders(['Content-Type' => 'application/xml'])
                ->timeout(30)
                ->withBody($xml, 'application/xml')
                ->post($this->apiUrl);

            $body = $response->body();
            $obj = simplexml_load_string($body);
            $result = json_decode(json_encode($obj), true);

            $this->logger->info("Ituran Response [{$response->status()}]", [
                'url' => $this->apiUrl,
                'body' => $body,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("Ituran Request Exception: {$e->getMessage()}", [
                'url' => $this->apiUrl,
            ]);
            return null;
        }
    }
}
