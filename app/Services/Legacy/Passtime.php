<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use App\Services\Legacy\IturanClient;
use App\Services\Legacy\GeotabClient;
use App\Services\Legacy\OnestepGpsClient;
use App\Services\Legacy\GeotabkeylessClient;
use App\Services\Legacy\SmartCarCommonService;
use App\Services\Legacy\AutoPiFleetClient;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;

class Passtime
{
    private $_Ituran;
    private $_Geotab;
    private $_Onestepgps;
    private $_Autopi;
    private $_logfile;

    public function __construct()
    {
        $this->_Ituran = new IturanClient();
        $this->_Geotab = new GeotabClient();
        $this->_Onestepgps = new OnestepGpsClient();
        $this->_Autopi = new AutoPiFleetClient();
        $this->_logfile = storage_path('logs/passtime_' . date('Y-m-d') . '.log');
    }

    public function getVehicleLocation(array $vehicledata)
    {
        $return = ['status' => false, 'lat' => '', 'lng' => '', 'lastLocate' => date('Y-m-d H:i:s')];
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['CsSetting']['gps_provider'] ?? null)) {
            return ['status' => false];
        }

        $provider = $vehicledata['CsSetting']['gps_provider'];

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

        $dealerId = trim($vehicledata['CsSetting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $last_mile = (int) ($vehicledata['Vehicle']['last_mile'] ?? 0);

        if (!empty($dealerId) && !empty($serialno)) {
            $token = $this->generatetoken();
            $header = [];
            $header[] = 'Content-type: application/x-www-form-urlencoded';
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept-Charset: utf-8';
            $requestBody = "DealerNumber=$dealerId&SerialNumber=$serialno&TimeZone=EST&TimeZonHasDayLightSavings=0";
            $url = 'https://softwarepartners.passtimeusa.com/api/device/GetLastLocate?' . $requestBody;
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_TIMEOUT, 10);

            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . 'getVehicleLocation=Request==' . $url . '?' . $requestBody, FILE_APPEND);
            $response = curl_exec($connection);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . '==' . 'getVehicleLocation-Response==' . $response, FILE_APPEND);

            curl_close($connection);
            $result = json_decode($response, true);
            if (isset($result[0]['lat']) && isset($result[0]['long'])) {
                if (($vehicledata['Owner']['distance_unit'] ?? null) == 'KM') {
                    $miles = isset($result[0]['totalMiles']) ? sprintf('%d', ($result[0]['totalMiles'] * 1.60934)) : $last_mile;
                    return ['status' => true, 'lat' => $result[0]['lat'], 'lng' => $result[0]['long'], 'lastLocate' => $result[0]['lastLocate'] ?? date('Y-m-d H:i:s'), "miles" => $miles];
                }
                return ['status' => true, 'lat' => $result[0]['lat'], 'lng' => $result[0]['long'], 'lastLocate' => $result[0]['lastLocate'] ?? date('Y-m-d H:i:s'), "miles" => $result[0]['totalMiles'] ?? null];
            }
        }
        return $return;
    }

    public function setVhicleLocation(array $vehicledata, $token)
    {
        $dealerId = trim($vehicledata['CsSetting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        if (!empty($dealerId) && !empty($serialno)) {
            $header = [];
            $header[] = 'Content-type: application/x-www-form-urlencoded';
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept-Charset: utf-8';
            $requestBody = "actionName=UpdateMap&DealerNumber=$dealerId&SerialNumber=$serialno";
            $url = 'https://softwarepartners.passtimeusa.com/api/device';
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection, CURLOPT_POST, 1);
            curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_TIMEOUT, 10);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . 'setVhicleLocation=Request==' . $url . '?' . $requestBody, FILE_APPEND);
            $response = curl_exec($connection);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . '==' . 'setVhicleLocation-Response==' . $response, FILE_APPEND);

            curl_close($connection);
        }
    }

    public function setVehicleLastMile(array $vehicledata, $order_id = null)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);
        if (empty($vehicledata['CsSetting']['gps_provider'] ?? null)) {
            return ['status' => false];
        }

        $provider = $vehicledata['CsSetting']['gps_provider'];

        if ($provider === 'ituran') {
            return $this->_Ituran->setVehicleLastMile($vehicledata, $order_id);
        }
        if ($provider === 'onestepgps') {
            return $this->_Onestepgps->setVehicleLastMile($vehicledata);
        }
        return null;
    }

    public function getVehicleLastMile(array $vehicledata)
    {
        $vehicledata = $this->parseVehicleSetting($vehicledata);

        if (empty($vehicledata['CsSetting']['gps_provider'] ?? null)) {
            return ['status' => false, 'miles' => 0];
        }

        $provider = $vehicledata['CsSetting']['gps_provider'];

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
        $dealerId = trim($vehicledata['CsSetting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicledata['Vehicle']['gps_serialno'] ?? '');
        $last_mile = (int) ($vehicledata['Vehicle']['last_mile'] ?? 0);

        if (!empty($dealerId) && !empty($serialno)) {
            $token = $this->generatetoken();
            $header = [];
            $header[] = 'Content-type: application/x-www-form-urlencoded';
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept-Charset: utf-8';
            $requestBody = "DealerNumber=$dealerId&SerialNumber=$serialno";
            $url = 'https://softwarepartners.passtimeusa.com/api/device/GetTotalMiles?' . $requestBody;
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_TIMEOUT, 10);

            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . 'getVehicleLastMile=Request==' . $url, FILE_APPEND);
            $response = curl_exec($connection);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . '==' . 'getVehicleLastMile-Response==' . $response, FILE_APPEND);

            curl_close($connection);
            $result = json_decode($response, true);
            if (isset($result[0]['totalMiles'])) {
                if (($vehicledata['Owner']['distance_unit'] ?? null) == 'KM') {
                    $miles = sprintf('%d', ($result[0]['totalMiles'] * 1.60934));
                    return ['status' => true, "miles" => $miles];
                }
                return ['status' => true, 'miles' => $result[0]['totalMiles']];
            }
            return ['status' => true, 'miles' => $last_mile];
        }
        return $return;
    }

    public function generatetoken()
    {
        $std = 'grant_type=password&username=adam@mindseyeny.com&password=Mindseyeisgreat1!';
        $header = [];
        $header[] = 'Content-type: application/x-www-form-urlencoded';
        $header[] = 'Accept-Charset: utf-8';
        $url = 'https://softwarepartners.passtimeusa.com/token';
        $result = $this->sendHttpRequest($url, $std, $header);
        return $result['access_token'] ?? '';
    }

    public function sendHttpRequest(string $url, string $requestBody, array $header): array
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_POST, 1);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($connection);
        curl_close($connection);
        return json_decode($response, true) ?? [];
    }

    public function startPasstime($vhicleId, $order_id)
    {
        if (!empty($vhicleId)) {
            $vehicle = Vehicle::with(['csSetting', 'vehicleSetting', 'owner'])->find($vhicleId);
            if ($vehicle) {
                $vehicleData = [
                    'Vehicle' => $vehicle->toArray(),
                    'CsSetting' => $vehicle->csSetting ? $vehicle->csSetting->toArray() : [],
                    'VehicleSetting' => $vehicle->vehicleSetting ? $vehicle->vehicleSetting->toArray() : [],
                    'Owner' => $vehicle->owner ? $vehicle->owner->toArray() : [],
                ];

                CsOrder::where('id', $order_id)->update([
                    'start_odometer' => $vehicleData['Vehicle']['last_mile'] ?? 0
                ]);

                $vehicleData = $this->parseVehicleSetting($vehicleData);
                $miles = $vehicleData['Vehicle']['last_mile'] ?? 0;
                $gpsProvider = $vehicleData['CsSetting']['gps_provider'] ?? '';

                if ($gpsProvider === 'ituran') {
                    $miles = $this->_Ituran->startPasstime($vehicleData, $order_id);
                } elseif ($gpsProvider === 'geotab') {
                    $miles = $this->_Geotab->startPasstime($vehicleData, $order_id);
                } elseif ($gpsProvider === 'onestepgps') {
                    $miles = $this->_Onestepgps->startPasstime($vehicleData, $order_id);
                } else {
                    $milesResponse = $this->getVehicleLastMile($vehicleData);
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
            $vehicle = Vehicle::with(['csSetting', 'vehicleSetting', 'owner'])->find($vhicleId);
            if ($vehicle) {
                $vehicleData = [
                    'Vehicle' => $vehicle->toArray(),
                    'CsSetting' => $vehicle->csSetting ? $vehicle->csSetting->toArray() : [],
                    'VehicleSetting' => $vehicle->vehicleSetting ? $vehicle->vehicleSetting->toArray() : [],
                    'Owner' => $vehicle->owner ? $vehicle->owner->toArray() : [],
                ];

                $vehicleData = $this->parseVehicleSetting($vehicleData);
                $gpsProvider = $vehicleData['CsSetting']['gps_provider'] ?? '';

                if ($gpsProvider === 'smartcar') {
                    $milesResponse = SmartCarCommonService::getVehicleLastMile($vehicleData);
                    $return['miles'] = $milesResponse['miles'] ?? 0;
                    $return['allowed_miles'] = $vehicleData['Vehicle']['allowed_miles'] ?? 0;
                } elseif ($gpsProvider === 'ituran') {
                    $return = $this->_Ituran->getPasstimeMiles($vehicleData);
                } elseif ($gpsProvider === 'geotab') {
                    $return = $this->_Geotab->getPasstimeMiles($vehicleData);
                } elseif ($gpsProvider === 'onestepgps') {
                    $return = $this->_Onestepgps->getPasstimeMiles($vehicleData);
                } elseif ($gpsProvider === 'passtime' || empty($gpsProvider)) {
                    $milesResponse = $this->getVehicleLastMile($vehicleData);
                    $return['miles'] = $milesResponse['miles'] ?? 0;
                    $return['allowed_miles'] = $vehicleData['Vehicle']['allowed_miles'] ?? 0;
                }

                if ($return['miles'] > 0) {
                    Vehicle::where('id', $vhicleId)->update(['last_mile' => $return['miles']]);
                }
            }
        }
        return $return;
    }

    public function deActivateVehicle(array $vehicleData)
    {
        $vehicleData = $this->parseVehicleSetting($vehicleData);
        if (empty($vehicleData['CsSetting']['passtime'] ?? null)) {
            return ['status' => false];
        }

        $passtime = $vehicleData['CsSetting']['passtime'];

        if ($passtime === 'geotabkeyless') {
            return GeotabkeylessClient::deActivateVehicle($vehicleData);
        }
        if ($passtime === 'ituran') {
            return $this->_Ituran->deActivateVehicle($vehicleData);
        }
        if ($passtime === 'geotab') {
            return $this->_Geotab->deActivateVehicle($vehicleData);
        }
        if ($passtime === 'onestepgps') {
            return $this->_Onestepgps->deActivateVehicle($vehicleData);
        }
        if ($passtime === 'smartcar') {
            return SmartCarCommonService::deActivateVehicle($vehicleData);
        }

        $return = ['status' => false, 'message' => __("Passtime dealer # or vehicle serial # not set.")];
        $dealerId = trim($vehicleData['CsSetting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicleData['Vehicle']['passtime_serialno'] ?? '');

        if (!empty($dealerId) && !empty($serialno)) {
            $token = $this->generatetoken();
            $header = [];
            $header[] = 'Content-type: application/x-www-form-urlencoded';
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept-Charset: utf-8';
            $requestBody = "actionName=EnableDisable&DealerNumber=$dealerId&SerialNumber=$serialno&EnableDisable=0";
            $url = 'https://softwarepartners.passtimeusa.com/api/device';
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection, CURLOPT_POST, 1);
            curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_TIMEOUT, 10);

            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . 'deActivateVehicle=Request==' . $url . '?' . $requestBody, FILE_APPEND);
            $response = curl_exec($connection);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . '==' . 'deActivateVehicle-Response==' . $response, FILE_APPEND);

            curl_close($connection);
            $result = json_decode($response, true);
            if (isset($result['results']['callresult'])) {
                $return = ['status' => true];
            }
        }
        return $return;
    }

    public function parseVehicleSetting(array $vehicleData)
    {
        if (!isset($vehicleData['VehicleSetting']) || empty($vehicleData['VehicleSetting']['data']) || is_null($vehicleData['VehicleSetting']['data'])) {
            return $vehicleData;
        }
        $jsonTemp = json_decode($vehicleData['VehicleSetting']['data'], true);
        if (isset($jsonTemp['gps_provider']) && !empty($jsonTemp['gps_provider']) && isset($jsonTemp['passtime']) && !empty($jsonTemp['passtime'])) {
            $vehicleData['CsSetting'] = $jsonTemp;
        }
        return $vehicleData;
    }

    public function ActivateVehicle(array $vehicleData)
    {
        $vehicleData = $this->parseVehicleSetting($vehicleData);
        if (empty($vehicleData['CsSetting']['passtime'] ?? null)) {
            return ['status' => false];
        }

        $passtime = $vehicleData['CsSetting']['passtime'];

        if ($passtime === 'geotabkeyless') {
            return GeotabkeylessClient::ActivateVehicle($vehicleData);
        }
        if ($passtime === 'geotab') {
            return $this->_Geotab->ActivateVehicle($vehicleData);
        }
        if ($passtime === 'ituran') {
            return $this->_Ituran->ActivateVehicle($vehicleData);
        }
        if ($passtime === 'onestepgps') {
            return $this->_Onestepgps->ActivateVehicle($vehicleData);
        }
        if ($passtime === 'smartcar') {
            return SmartCarCommonService::activateVehicle($vehicleData);
        }

        $return = ['status' => false, 'message' => __("Passtime dealer # or vehicle serial # not set.")];
        $dealerId = trim($vehicleData['CsSetting']['passtime_dealerid'] ?? '');
        $serialno = trim($vehicleData['Vehicle']['passtime_serialno'] ?? '');

        if (!empty($dealerId) && !empty($serialno)) {
            $token = $this->generatetoken();
            $header = [];
            $header[] = 'Content-type: application/x-www-form-urlencoded';
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept-Charset: utf-8';
            $requestBody = "actionName=EnableDisable&DealerNumber=$dealerId&SerialNumber=$serialno&EnableDisable=1";
            $url = 'https://softwarepartners.passtimeusa.com/api/device';
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection, CURLOPT_POST, 1);
            curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_TIMEOUT, 30);

            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . 'ActivateVehicle=Request==' . $url . '?' . $requestBody, FILE_APPEND);
            $response = curl_exec($connection);
            @file_put_contents($this->_logfile, "\n" . date('Y-m-d H:i:s') . '==' . 'ActivateVehicle-Response==' . $response, FILE_APPEND);

            curl_close($connection);
            $result = json_decode($response, true);
            if (isset($result['results']['callresult'])) {
                $return = ['status' => true];
            }
        }
        return $return;
    }
}