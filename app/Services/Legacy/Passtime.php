<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Legacy\IturanClient;
use App\Services\Legacy\GeotabClient;
use App\Services\Legacy\OnestepGpsClient;
use App\Services\Legacy\AutoPiFleetClient;
use App\Services\Legacy\GeotabkeylessClient;
use App\Services\Legacy\SmartCarCommonService;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;

class Passtime
{
    private $_Ituran;
    private $_Geotab;
    private $_Onestepgps;
    private $_Autopi;
    private $logger;

    public function __construct()
    {
        $this->_Ituran = new IturanClient();
        $this->_Geotab = new GeotabClient();
        $this->_Onestepgps = new OnestepGpsClient();
        $this->_Autopi = new AutoPiFleetClient();

        $this->logger = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/passtime.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }

    public function getVehicleLocation(array $vehicledata)
    {
        $return = ['status' => false, 'lat' => '', 'lng' => '', 'lastLocate' => date('Y-m-d H:i:s')];
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['cs_setting']['gps_provider'] ?? null)) {
            return ['status' => false];
        }

        $provider = $vehicledata['cs_setting']['gps_provider'];

        if ($provider === 'ituran') {
            return $this->_Ituran->getVehicleLocation($vehicledata);
        }

        if ($provider === 'geotab') {
            return $this->_Geotab->getVehicleLocation($vehicledata);
        }

        if ($provider === 'onestepgps') {
            return $this->_Onestepgps->getVehicleLocation($vehicledata);
        }

        if ($provider === 'smartcar') {
            return SmartCarCommonService::getVehicleLocation($vehicledata);
        }

        if ($provider === 'autopi') {
            return $this->_Autopi->getVehicleLocation($vehicledata);
        }

        $dealerId = trim($vehicledata['cs_setting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $last_mile = (int) ($vehicledata['last_mile'] ?? 0);

        if (!empty($dealerId) && !empty($serialno)) {

            $url = config('legacy.Passtime.url') . '/api/device/GetLastLocate';
            $params = [
                'DealerNumber' => $dealerId,
                'SerialNumber' => $serialno,
                'TimeZone' => 'EST',
                'TimeZonHasDayLightSavings' => 0,
            ];

            $token = $this->generatetoken();
            $result = $this->sendHttpRequest('GET', $url, $params, 10, $token);

            if (($vehicledata['owner']['distance_unit'] ?? null) == 'KM') {
                $miles = isset($result[0]['totalMiles']) ?
                    sprintf('%d', ($result[0]['totalMiles'] * 1.60934)) :
                    $last_mile;

                return [
                    'status' => true,
                    'lat' => $result[0]['lat'],
                    'lng' => $result[0]['long'],
                    'lastLocate' => $result[0]['lastLocate'] ?? date('Y-m-d H:i:s'),
                    "miles" => $miles
                ];
            }

            return [
                'status' => true,
                'lat' => $result[0]['lat'],
                'lng' => $result[0]['long'],
                'lastLocate' => $result[0]['lastLocate'] ?? date('Y-m-d H:i:s'),
                "miles" => $result[0]['totalMiles'] ?? null
            ];
        }

        return $return;
    }
    public function setVhicleLocation(array $vehicledata, $token): void
    {
        $dealerId = trim($vehicledata['cs_setting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['gps_serialno'] ?? '');

        if (!empty($dealerId) && !empty($serialno)) {
            $url = config('legacy.Passtime.url') . '/api/device';
            $params = [
                'actionName' => 'UpdateMap',
                'DealerNumber' => $dealerId,
                'SerialNumber' => $serialno,
            ];

            $this->sendHttpRequest('POST', $url, $params, 10, $token);
        }

        return;
    }
    public function setVehicleLastMile(array $vehicledata, $order_id = null)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['cs_setting']['gps_provider'] ?? null)) {
            return ['status' => false];
        }

        $provider = $vehicledata['cs_setting']['gps_provider'];

        if ($provider === 'ituran') {
            return $this->_Ituran->setVehicleLastMile($vehicledata);
        }

        if ($provider === 'onestepgps') {
            return $this->_Onestepgps->setVehicleLastMile($vehicledata);
        }

        return null;
    }
    public function getVehicleLastMile(array $vehicledata)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['cs_setting']['gps_provider'] ?? null)) {
            return ['status' => false, 'miles' => 0];
        }

        $provider = $vehicledata['cs_setting']['gps_provider'];

        if ($provider === 'ituran') {
            return $this->_Ituran->getVehicleLastMile($vehicledata);
        }

        if ($provider === 'geotab') {
            return $this->_Geotab->getVehicleLastMile($vehicledata);
        }

        if ($provider === 'onestepgps') {
            return $this->_Onestepgps->getVehicleLastMile($vehicledata);
        }

        if ($provider === 'smartcar') {
            return SmartCarCommonService::getVehicleLastMile($vehicledata);
        }

        if ($provider === 'autopi') {
            return $this->_Autopi->getVehicleLastMile($vehicledata);
        }

        $return = ['status' => false, 'miles' => 0];
        $dealerId = trim($vehicledata['cs_setting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $last_mile = (int) ($vehicledata['last_mile'] ?? 0);

        if (!empty($dealerId) && !empty($serialno)) {
            $url = config('legacy.Passtime.url') . '/api/device/GetTotalMiles';
            $params = [
                'DealerNumber' => $dealerId,
                'SerialNumber' => $serialno,
            ];

            $token = $this->generatetoken();
            $result = $this->sendHttpRequest('GET', $url, $params, 10, $token);

            if (isset($result[0]['totalMiles'])) {

                if (($vehicledata['owner']['distance_unit'] ?? null) == 'KM') {
                    $miles = sprintf('%d', ($result[0]['totalMiles'] * 1.60934));
                    return ['status' => true, "miles" => $miles];
                }

                return ['status' => true, 'miles' => $result[0]['totalMiles']];
            }

            return ['status' => true, 'miles' => $last_mile];
        }

        return $return;
    }
    public function generatetoken(): string
    {
        $url = config('legacy.Passtime.url') . '/token';
        $params = [
            'grant_type' => 'password',
            'username' => config('legacy.Passtime.username'),
            'password' => config('legacy.Passtime.password'),
        ];

        $result = $this->sendHttpRequest('POST', $url, $params, 10);
        return $result['access_token'] ?? '';
    }
    private function sendHttpRequest(string $method, string $url, array $params, int $timeout = 10, ?string $token = null): ?array
    {
        $headers = [
            'Accept-Charset' => 'utf-8',
        ];

        if (!empty($token)) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            $this->logger->info("Passtime Request [{$method}]: {$url}", [
                'params' => $params,
            ]);

            $pending = Http::withHeaders($headers)->timeout($timeout);

            $response = strtoupper($method) === 'POST' ?
                $pending->asForm()->post($url, $params) :
                $pending->get($url, $params);


            $body = $response->body();

            $logParams = $params;
            if (isset($logParams['password'])) {
                $logParams['password'] = '********';
            }

            $this->logger->info("Passtime Response [{$response->status()}]: {$url}", [
                'params' => $logParams,
                'body' => $body,
            ]);

            return $response->json();

        } catch (\Throwable $e) {
            $this->logger->error("Passtime Request Exception: {$e->getMessage()}", [
                'method' => $method,
                'url' => $url,
                'params' => $params,
            ]);
            return null;
        }
    }
    public function startPasstime($vhicleId, $order_id)
    {
        if (!empty($vhicleId)) {
            $vehicledata = Vehicle::with(['csSetting', 'vehicleSetting', 'owner'])->find($vhicleId)->toArray();

            if (!empty($vehicleData)) {

                CsOrder::where('id', $order_id)->update([
                    'start_odometer' => $vehicledata['last_mile'] ?? 0
                ]);

                $vehicledata = $this->parseVehicleSetting($vehicledata);
                $miles = $vehicledata['last_mile'] ?? 0;
                $gpsProvider = $vehicledata['cs_setting']['gps_provider'] ?? '';

                if ($gpsProvider === 'ituran') {
                    $miles = $this->_Ituran->startPasstime($vehicledata, $order_id);
                } elseif ($gpsProvider === 'geotab') {
                    $miles = $this->_Geotab->startPasstime($vehicledata, $order_id);
                } elseif ($gpsProvider === 'onestepgps') {
                    $miles = $this->_Onestepgps->startPasstime($vehicledata, $order_id);
                } else {
                    $milesResponse = $this->getVehicleLastMile($vehicledata);
                    $miles = ($milesResponse['miles'] ?? 0) ?: 1;
                }

                CsOrder::where('id', $order_id)->update(['start_odometer' => $miles]);

                $totalMileage = $miles + config('legacy.MaintenanceMonitoring.miles', 5000);
                Vehicle::where('id', $vhicleId)->update(['total_mileage' => $totalMileage]);
            }
        }
    }
    public function getPasstimeMiles($vhicleId)
    {
        $return = ['miles' => 0, 'allowed_miles' => 0];

        if (!empty($vhicleId)) {
            $vehicledata = Vehicle::with(['csSetting', 'vehicleSetting', 'owner'])->find($vhicleId)->toArray();

            if (!empty($vehicledata)) {
                $vehicledata = $this->parseVehicleSetting($vehicledata);
                $gpsProvider = $vehicledata['cs_setting']['gps_provider'] ?? '';

                if ($gpsProvider === 'smartcar') {
                    $milesResponse = SmartCarCommonService::getVehicleLastMile($vehicledata);
                    $return['miles'] = $milesResponse['miles'] ?? 0;
                    $return['allowed_miles'] = $vehicledata['allowed_miles'] ?? 0;
                } elseif ($gpsProvider === 'ituran') {
                    $return = $this->_Ituran->getPasstimeMiles($vehicledata);
                } elseif ($gpsProvider === 'geotab') {
                    $return = $this->_Geotab->getPasstimeMiles($vehicledata);
                } elseif ($gpsProvider === 'onestepgps') {
                    $return = $this->_Onestepgps->getPasstimeMiles($vehicledata);
                } elseif ($gpsProvider === 'passtime' || empty($gpsProvider)) {
                    $milesResponse = $this->getVehicleLastMile($vehicledata);
                    $return['miles'] = $milesResponse['miles'] ?? 0;
                    $return['allowed_miles'] = $vehicledata['allowed_miles'] ?? 0;
                }

                if ($return['miles'] > 0) {
                    Vehicle::where('id', $vhicleId)->update(['last_mile' => $return['miles']]);
                }

            }
        }

        return $return;
    }
    public function deActivateVehicle(array $vehicledata)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['cs_setting']['passtime'] ?? null)) {
            return ['status' => false];
        }

        $passtime = $vehicledata['cs_setting']['passtime'];

        if ($passtime === 'geotabkeyless') {
            return GeotabkeylessClient::deActivateVehicle($vehicledata);
        }

        if ($passtime === 'ituran') {
            return $this->_Ituran->deActivateVehicle($vehicledata);
        }

        if ($passtime === 'geotab') {
            return $this->_Geotab->deActivateVehicle($vehicledata);
        }

        if ($passtime === 'onestepgps') {
            return $this->_Onestepgps->deActivateVehicle($vehicledata);
        }

        if ($passtime === 'smartcar') {
            return SmartCarCommonService::deActivateVehicle($vehicledata);
        }

        $return = ['status' => false, 'message' => __("Passtime dealer # or vehicle serial # not set.")];
        $dealerId = trim($vehicledata['cs_setting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['passtime_serialno'] ?? '');

        if (!empty($dealerId) && !empty($serialno)) {
            $url = config('legacy.Passtime.url') . '/api/device';
            $params = [
                'actionName' => 'EnableDisable',
                'DealerNumber' => $dealerId,
                'SerialNumber' => $serialno,
                'EnableDisable' => 0,
            ];

            $token = $this->generatetoken();
            $result = $this->sendHttpRequest('POST', $url, $params, 10, $token);

            if (isset($result['results']['callresult'])) {
                $return = ['status' => true];
            }
        }

        return $return;
    }
    public function parseVehicleSetting(array $vehicledata): array
    {
        if (
            !isset($vehicledata['vehicle_setting']) ||
            empty($vehicledata['vehicle_setting']['data']) ||
            is_null($vehicledata['vehicle_setting']['data'])
        ) {
            return $vehicledata;
        }

        $toArrayFormat = fn($val) => is_array($val) ? $val : (json_decode($val ?? '', true) ?? []);
        $jsonTemp = $toArrayFormat($vehicledata['vehicle_setting']['data']);

        if (
            isset($jsonTemp['gps_provider']) &&
            !empty($jsonTemp['gps_provider']) &&
            isset($jsonTemp['passtime']) &&
            !empty($jsonTemp['passtime'])
        ) {
            $vehicledata['cs_setting'] = $jsonTemp;
        }

        return $vehicledata;
    }
    public function ActivateVehicle(array $vehicledata)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['cs_setting']['passtime'] ?? null)) {
            return ['status' => false];
        }

        $passtime = $vehicledata['cs_setting']['passtime'];

        if ($passtime === 'geotabkeyless') {
            return GeotabkeylessClient::ActivateVehicle($vehicledata);
        }

        if ($passtime === 'geotab') {
            return $this->_Geotab->ActivateVehicle($vehicledata);
        }

        if ($passtime === 'ituran') {
            return $this->_Ituran->ActivateVehicle($vehicledata);
        }

        if ($passtime === 'onestepgps') {
            return $this->_Onestepgps->ActivateVehicle($vehicledata);
        }

        if ($passtime === 'smartcar') {
            return SmartCarCommonService::ActivateVehicle($vehicledata);
        }

        $return = ['status' => false, 'message' => __("Passtime dealer # or vehicle serial # not set.")];
        $dealerId = trim($vehicledata['cs_setting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['passtime_serialno'] ?? '');

        if (!empty($dealerId) && !empty($serialno)) {
            $url = config('legacy.Passtime.url') . '/api/device';
            $params = [
                'actionName' => 'EnableDisable',
                'DealerNumber' => $dealerId,
                'SerialNumber' => $serialno,
                'EnableDisable' => 1,
            ];

            $token = $this->generatetoken();
            $result = $this->sendHttpRequest('POST', $url, $params, 30, $token);

            if (isset($result['results']['callresult'])) {
                $return = ['status' => true];
            }
        }

        return $return;
    }
}