<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class SmartCarCommonService
{
    public static function getVehicleLastMile(array $vehicledata): array
    {
        $userid = $vehicledata['Vehicle']['user_id'] ?? null;
        $lastMile = (int) ($vehicledata['Vehicle']['last_mile'] ?? 0);
        if (empty($userid)) {
            return ['status' => true, 'miles' => $lastMile];
        }
        $smartCarObj = DB::table('smart_cars')->where('user_id', $userid)->first();
        if (empty($smartCarObj)) {
            return ['status' => true, 'miles' => $lastMile];
        }
        if (empty($vehicledata['CsSetting']['smartcar_client_id']) || empty($vehicledata['CsSetting']['smartcar_secret'])) {
            return ['status' => true, 'miles' => $lastMile];
        }
        $token = $smartCarObj->token;
        $api = new SmartCarApiService();
        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = 'grant_type=refresh_token&refresh_token=' . $smartCarObj->refresh_token;
            $refreshTokenObj = $api->refreshToken($request, $vehicledata['CsSetting']['smartcar_client_id'], $vehicledata['CsSetting']['smartcar_secret']);
            if (!isset($refreshTokenObj['access_token'])) {
                return ['status' => true, 'miles' => $lastMile];
            }
            DB::table('smart_cars')->where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);
            $token = $refreshTokenObj['access_token'];
        }
        $serialno = trim($vehicledata['Vehicle']['gps_serialno']);
        $result = $api->getOdometer($serialno, $token);
        if (isset($result['distance']) && ($vehicledata['Owner']['distance_unit'] ?? '') == 'KM') {
            return ['status' => true, 'miles' => $result['distance']];
        }
        if (isset($result['distance'])) {
            return ['status' => true, 'miles' => sprintf('%d', ($result['distance'] / 1.67))];
        }
        return ['status' => true, 'miles' => $lastMile];
    }

    public static function getVehicleLocation(array $vehicledata): array
    {
        $userid = $vehicledata['Vehicle']['user_id'] ?? null;
        $default = ['status' => false, 'lat' => '', 'lng' => '', 'lastLocate' => date('Y-m-d H:i:s')];
        if (empty($userid)) return $default;
        $smartCarObj = DB::table('smart_cars')->where('user_id', $userid)->first();
        if (empty($smartCarObj)) return $default;
        if (empty($vehicledata['CsSetting']['smartcar_client_id']) || empty($vehicledata['CsSetting']['smartcar_secret'])) return $default;

        $token = $smartCarObj->token;
        $api = new SmartCarApiService();
        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = 'grant_type=refresh_token&refresh_token=' . $smartCarObj->refresh_token;
            $refreshTokenObj = $api->refreshToken($request, $vehicledata['CsSetting']['smartcar_client_id'], $vehicledata['CsSetting']['smartcar_secret']);
            if (!isset($refreshTokenObj['access_token'])) return $default;
            DB::table('smart_cars')->where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);
            $token = $refreshTokenObj['access_token'];
        }
        $serialno = trim($vehicledata['Vehicle']['gps_serialno']);
        $result = $api->getLocation($serialno, $token);
        if (isset($result['latitude'])) {
            return ['status' => true, 'lat' => $result['latitude'], 'lng' => $result['longitude'], 'lastLocate' => date('Y-m-d H:i:s')];
        }
        return $default;
    }

    public static function deActivateVehicle(array $vehicledata): array
    {
        return self::toggleVehicleLock($vehicledata, 'lock');
    }

    public static function activateVehicle(array $vehicledata): array
    {
        return self::toggleVehicleLock($vehicledata, 'unlock');
    }

    private static function toggleVehicleLock(array $vehicledata, string $action): array
    {
        $userid = $vehicledata['Vehicle']['user_id'] ?? null;
        $errMsg = 'Passtime dealer # or vehicle serial # not set.';
        if (empty($userid)) return ['status' => false, 'message' => $errMsg];
        $smartCarObj = DB::table('smart_cars')->where('user_id', $userid)->first();
        if (empty($smartCarObj)) return ['status' => false, 'message' => $errMsg];
        if (empty($vehicledata['CsSetting']['smartcar_client_id']) || empty($vehicledata['CsSetting']['smartcar_secret'])) {
            return ['status' => false, 'message' => 'SmartCar client # or secret # is not set.'];
        }
        $token = $smartCarObj->token;
        $api = new SmartCarApiService();
        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = 'grant_type=refresh_token&refresh_token=' . $smartCarObj->refresh_token;
            $refreshTokenObj = $api->refreshToken($request, $vehicledata['CsSetting']['smartcar_client_id'], $vehicledata['CsSetting']['smartcar_secret']);
            if (!isset($refreshTokenObj['access_token'])) return ['status' => false, 'message' => 'Token is not refresh'];
            DB::table('smart_cars')->where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);
            $token = $refreshTokenObj['access_token'];
        }
        $serialno = trim($vehicledata['Vehicle']['passtime_serialno']);
        $result = $action === 'lock' ? $api->lockCar($serialno, $token) : $api->unlockCar($serialno, $token);
        if (isset($result['status']) && $result['status'] == 'success') {
            return ['status' => true, 'message' => $result['message']];
        }
        return ['status' => false, 'message' => 'Something went wrong with smart car api'];
    }

    public static function getVehicleBattery(array $vehicledata): array
    {
        $userid = $vehicledata['Vehicle']['user_id'] ?? null;
        $battery = (int) ($vehicledata['Vehicle']['battery'] ?? 0);
        if (empty($userid)) return ['status' => true, 'battery' => $battery];
        $smartCarObj = DB::table('smart_cars')->where('user_id', $userid)->first();
        if (empty($smartCarObj)) return ['status' => true, 'battery' => $battery];
        if (empty($vehicledata['CsSetting']['smartcar_client_id']) || empty($vehicledata['CsSetting']['smartcar_secret'])) {
            return ['status' => true, 'battery' => $battery];
        }
        $token = $smartCarObj->token;
        $api = new SmartCarApiService();
        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = 'grant_type=refresh_token&refresh_token=' . $smartCarObj->refresh_token;
            $refreshTokenObj = $api->refreshToken($request, $vehicledata['CsSetting']['smartcar_client_id'], $vehicledata['CsSetting']['smartcar_secret']);
            if (!isset($refreshTokenObj['access_token'])) return ['status' => true, 'battery' => $battery];
            DB::table('smart_cars')->where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);
            $token = $refreshTokenObj['access_token'];
        }
        $serialno = trim($vehicledata['Vehicle']['gps_serialno']);
        $result = $api->getBattery($serialno, $token);
        if (isset($result['percentRemaining'])) {
            return ['status' => true, 'battery' => $result['percentRemaining']];
        }
        return ['status' => true, 'battery' => $battery];
    }
}
