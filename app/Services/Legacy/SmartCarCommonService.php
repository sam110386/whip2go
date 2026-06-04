<?php

namespace App\Services\Legacy;

use App\Models\Legacy\SmartCar;
use App\Services\Legacy\SmartCarApiService;

class SmartCarCommonService
{
    public static function getVehicleLastMile(array $vehicledata): array
    {
        $userid = $vehicledata['user_id'] ?? null;
        $lastMile = (int) ($vehicledata['last_mile'] ?? 0);

        if (empty($userid)) {
            return ['status' => true, 'miles' => $lastMile];
        }

        $smartCarObj = SmartCar::where('user_id', $userid)->first();

        if (empty($smartCarObj)) {
            return ['status' => true, 'miles' => $lastMile];
        }

        if (
            empty($vehicledata['cs_setting']['smartcar_client_id']) ||
            empty($vehicledata['cs_setting']['smartcar_secret'])
        ) {
            return ['status' => true, 'miles' => $lastMile];
        }

        $token = $smartCarObj->token;
        $SmartCarApi = new SmartCarApiService();

        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = "grant_type=refresh_token&refresh_token={$smartCarObj->refresh_token}";
            $refreshTokenObj = $SmartCarApi->refreshToken(
                $request,
                $vehicledata['cs_setting']['smartcar_client_id'],
                $vehicledata['cs_setting']['smartcar_secret']
            );

            if (!isset($refreshTokenObj['access_token'])) {
                return ['status' => true, 'miles' => $lastMile];
            }

            SmartCar::where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);

            $token = $refreshTokenObj['access_token'];
        }

        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $result = $SmartCarApi->getOdometer($serialno, $token);

        if (isset($result['distance']) && ($vehicledata['owner']['distance_unit'] ?? '') == 'KM') {
            return ['status' => true, 'miles' => $result['distance']];
        }

        if (isset($result['distance'])) {
            return ['status' => true, 'miles' => sprintf('%d', ($result['distance'] / 1.67))];
        }

        return ['status' => true, 'miles' => $lastMile];
    }
    public static function getVehicleLocation(array $vehicledata): array
    {
        $userid = $vehicledata['user_id'] ?? null;
        $default = ['status' => false, 'lat' => '', 'lng' => '', 'lastLocate' => date('Y-m-d H:i:s')];

        if (empty($userid)) {
            return $default;
        }

        $smartCarObj = SmartCar::where('user_id', $userid)->first();

        if (empty($smartCarObj)) {
            return $default;
        }

        if (
            empty($vehicledata['cs_setting']['smartcar_client_id']) ||
            empty($vehicledata['cs_setting']['smartcar_secret'])
        ) {
            return $default;
        }

        $token = $smartCarObj->token;
        $SmartCarApi = new SmartCarApiService();

        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = "grant_type=refresh_token&refresh_token={$smartCarObj->refresh_token}";
            $refreshTokenObj = $SmartCarApi->refreshToken(
                $request,
                $vehicledata['cs_setting']['smartcar_client_id'],
                $vehicledata['cs_setting']['smartcar_secret']
            );

            if (!isset($refreshTokenObj['access_token'])) {
                return $default;
            }

            SmartCar::where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);

            $token = $refreshTokenObj['access_token'];
        }

        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $result = $SmartCarApi->getLocation($serialno, $token);

        if (isset($result['latitude'])) {
            return [
                'status' => true,
                'lat' => $result['latitude'],
                'lng' => $result['longitude'],
                'lastLocate' => date('Y-m-d H:i:s')
            ];
        }

        return $default;
    }
    public static function deActivateVehicle(array $vehicledata): array
    {
        return self::toggleVehicleLock($vehicledata, 'lock');
    }
    public static function ActivateVehicle(array $vehicledata): array
    {
        return self::toggleVehicleLock($vehicledata, 'unlock');
    }
    private static function toggleVehicleLock(array $vehicledata, string $action): array
    {
        $userid = $vehicledata['user_id'] ?? null;
        $return = ['status' => false, 'message' => 'Passtime dealer # or vehicle serial # not set.'];

        if (empty($userid)) {
            return $return;
        }

        $smartCarObj = SmartCar::where('user_id', $userid)->first();

        if (empty($smartCarObj)) {
            return $return;
        }

        if (
            empty($vehicledata['cs_setting']['smartcar_client_id']) ||
            empty($vehicledata['cs_setting']['smartcar_secret'])
        ) {
            return $return;
        }

        $token = $smartCarObj->token;
        $SmartCarApi = new SmartCarApiService();

        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = "grant_type=refresh_token&refresh_token={$smartCarObj->refresh_token}";
            $refreshTokenObj = $SmartCarApi->refreshToken(
                $request,
                $vehicledata['cs_setting']['smartcar_client_id'],
                $vehicledata['cs_setting']['smartcar_secret']
            );

            if (!isset($refreshTokenObj['access_token'])) {
                return $return;
            }

            SmartCar::where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);

            $token = $refreshTokenObj['access_token'];
        }

        $serialno = trim($vehicledata['passtime_serialno'] ?? '');
        $result = $action === 'lock' ? $SmartCarApi->lockCar($serialno, $token) : $SmartCarApi->unlockCar($serialno, $token);

        if (isset($result['status']) && $result['status'] == 'success') {
            return ['status' => true, 'message' => $result['message']];
        }

        return ['status' => false, 'message' => 'Something went wrong with smart car api'];
    }
    public static function getOdometerBatteryAndLocation(array $vehicledata): array
    {
        $userid = $vehicledata['user_id'] ?? null;
        $errorReturn = ['status' => false, 'message' => "Passtime dealer # or vehicle serial # not set."];
        $return = ['battery' => 0, 'miles' => 0, 'lat' => '', 'lng' => ''];

        if (empty($userid)) {
            return $errorReturn;
        }

        $smartCarObj = SmartCar::where('user_id', $userid)->first();
        if (empty($smartCarObj)) {
            return $errorReturn;
        }

        if (
            empty($vehicledata['cs_setting']['smartcar_client_id']) ||
            empty($vehicledata['cs_setting']['smartcar_secret'])
        ) {
            return $errorReturn;
        }

        $token = $smartCarObj->token;
        $SmartCarApi = new SmartCarApiService();

        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = "grant_type=refresh_token&refresh_token={$smartCarObj->refresh_token}";
            $refreshTokenObj = $SmartCarApi->refreshToken(
                $request,
                $vehicledata['cs_setting']['smartcar_client_id'],
                $vehicledata['cs_setting']['smartcar_secret']
            );

            if (!isset($refreshTokenObj['access_token'])) {
                return $return;
            }

            SmartCar::where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);

            $token = $refreshTokenObj['access_token'];
        }

        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $result = $SmartCarApi->getOdometerBatteryAndLocation($serialno, $token);

        if (!isset($result['responses'])) {
            return $return;
        }

        foreach ($result['responses'] as $reslt) {

            if ($reslt['path'] === '/odometer' && $reslt['code'] == 200) {
                $return['miles'] = sprintf('%d', ($reslt['body']['distance'] / 1.67));
            }

            if ($reslt['path'] === '/location' && $reslt['code'] == 200) {
                $return['lat'] = $reslt['body']['latitude'];
                $return['lng'] = $reslt['body']['longitude'];
            }

            if ($reslt['path'] === '/battery' && $reslt['code'] == 200) {
                $return['battery'] = $reslt['body']['percentRemaining'];
            }

        }

        return $return;
    }
    public static function getVehicleBattery(array $vehicledata): array
    {
        $userid = $vehicledata['user_id'] ?? null;
        $battery = (int) ($vehicledata['battery'] ?? 0);

        if (empty($userid)) {
            return ['status' => true, 'battery' => $battery];
        }

        $smartCarObj = SmartCar::where('user_id', $userid)->first();

        if (empty($smartCarObj)) {
            return ['status' => true, 'battery' => $battery];
        }

        if (
            empty($vehicledata['cs_setting']['smartcar_client_id']) ||
            empty($vehicledata['cs_setting']['smartcar_secret'])
        ) {
            return ['status' => true, 'battery' => $battery];
        }

        $token = $smartCarObj->token;
        $SmartCarApi = new SmartCarApiService();

        if (empty($smartCarObj->expire_at) || $smartCarObj->expire_at < time()) {
            $request = "grant_type=refresh_token&refresh_token={$smartCarObj->refresh_token}";
            $refreshTokenObj = $SmartCarApi->refreshToken(
                $request,
                $vehicledata['cs_setting']['smartcar_client_id'],
                $vehicledata['cs_setting']['smartcar_secret']
            );

            if (!isset($refreshTokenObj['access_token'])) {
                return ['status' => true, 'battery' => $battery];
            }

            SmartCar::where('id', $smartCarObj->id)->update([
                'expire_at' => time() + $refreshTokenObj['expires_in'],
                'token' => $refreshTokenObj['access_token'],
                'refresh_token' => $refreshTokenObj['refresh_token'],
            ]);

            $token = $refreshTokenObj['access_token'];
        }

        $serialno = trim($vehicledata['gps_serialno'] ?? '');
        $result = $SmartCarApi->getBattery($serialno, $token);

        if (isset($result['percentRemaining'])) {
            return ['status' => true, 'battery' => $result['percentRemaining']];
        }

        return ['status' => true, 'battery' => $battery];
    }
}
