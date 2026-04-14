<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\SmartCarApiService;
use App\Services\Legacy\SmartCarCommonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmartCarsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private $csSettingObj;

    public function tokenredirect(Request $request)
    {
        $code = $this->getTokenRedirectCode($request);
        $requestStr = 'grant_type=authorization_code&code=' . $code['code'] . '&redirect_uri=' . url('admin/smart_car/smart_cars/tokenredirect');
        $api = new SmartCarApiService();
        $userid = base64_decode($code['state']);
        if (empty($userid)) {
            abort(400, 'Sorry, something went wrong, please try again later');
        }
        $this->csSettingObj = DB::table('cs_settings')->where('user_id', $userid)
            ->select('smartcar_client_id', 'smartcar_secret', 'passtime', 'gps_provider')->first();
        $token = $api->getAuthToken($requestStr, $this->csSettingObj->smartcar_client_id, $this->csSettingObj->smartcar_secret);

        $exists = DB::table('smart_cars')->where('user_id', $userid)->first();
        $dataToSave = [
            'user_id' => $userid,
            'expire_at' => time() + ($token['expires_in'] ?? 0),
            'token' => $token['access_token'] ?? '',
            'refresh_token' => $token['refresh_token'] ?? '',
        ];
        try {
            $this->updateDealerVehicles($userid, $token['access_token'] ?? '');
            if (!empty($exists)) {
                DB::table('smart_cars')->where('id', $exists->id)->update($dataToSave);
            } else {
                DB::table('smart_cars')->insert($dataToSave);
            }
            return view('smart_car.redirect');
        } catch (\Exception $e) {
            abort(500, 'Something went wrong');
        }
    }

    public function connect($id)
    {
        $this->csSettingObj = DB::table('cs_settings')->where('user_id', base64_decode($id))
            ->select('smartcar_client_id', 'smartcar_secret', 'passtime', 'gps_provider')->first();
        $url = 'https://connect.smartcar.com/oauth/authorize?response_type=code&client_id=' . $this->csSettingObj->smartcar_client_id
            . '&scope=read_odometer read_vehicle_info required:read_location read_vin&state=' . $id
            . '&redirect_uri=' . url('admin/smart_car/smart_cars/tokenredirect');
        return redirect($url);
    }

    public function getbattery(Request $request)
    {
        $vehicleId = base64_decode($request->input('Text.vehicle_id'));
        $res = $this->fetchBattery($vehicleId);
        return response()->json($res);
    }

    public function test($id, $token)
    {
        $this->csSettingObj = DB::table('cs_settings')->where('user_id', $id)
            ->select('smartcar_client_id', 'smartcar_secret', 'passtime', 'gps_provider')->first();
        $this->updateDealerVehicles($id, $token);
        return response('done');
    }

    private function getTokenRedirectCode(Request $request): array
    {
        if ($request->has('code') && !empty($request->query('code'))) {
            return $request->query();
        }
        abort(400, 'We received following error: ' . ($request->query('error_description') ?? 'Unknown'));
    }

    private function fetchBattery($vehicleId): array
    {
        if (empty($vehicleId)) {
            return ['status' => false, 'message' => 'Sorry, wrong request input'];
        }
        $vehicle = DB::table('vehicles as Vehicle')
            ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'Vehicle.user_id')
            ->where('Vehicle.id', $vehicleId)
            ->select('Vehicle.user_id', 'Vehicle.id', 'Vehicle.battery', 'Vehicle.gps_serialno',
                'CsSetting.gps_provider', 'CsSetting.smartcar_client_id', 'CsSetting.smartcar_secret')
            ->first();
        if (empty($vehicle) || $vehicle->gps_provider != 'smartcar') {
            return ['status' => false, 'message' => 'Sorry, vehicle dealer setting is not as smart car'];
        }
        if (empty($vehicle->gps_serialno)) {
            return ['status' => false, 'message' => 'Sorry, vehicle setting is not as smart car'];
        }
        $vehicledata = [
            'Vehicle' => (array) $vehicle,
            'CsSetting' => ['smartcar_client_id' => $vehicle->smartcar_client_id, 'smartcar_secret' => $vehicle->smartcar_secret],
        ];
        $result = SmartCarCommonService::getVehicleBattery($vehicledata);
        DB::table('vehicles')->where('id', $vehicle->id)->update(['battery' => $result['battery']]);
        return $result;
    }

    private function updateDealerVehicles($userid, $token): void
    {
        if ($this->csSettingObj->passtime != 'smartcar' && $this->csSettingObj->gps_provider != 'smartcar') {
            return;
        }
        $api = new SmartCarApiService();
        $resp = $api->getAllVehicles($token);
        if (empty($resp) || isset($resp['statusCode'])) {
            return;
        }
        foreach ($resp['vehicles'] ?? [] as $vehicle) {
            $vinObj = $api->getVinNumber($vehicle, $token);
            if (empty($vinObj) || isset($vinObj['statusCode'])) {
                continue;
            }
            $vehicleObj = DB::table('vehicles')
                ->where('vin_no', $vinObj['vin'])->where('user_id', $userid)
                ->select('id', 'passtime_serialno', 'gps_serialno')->first();
            if (empty($vehicleObj)) {
                continue;
            }
            $update = [];
            if ($this->csSettingObj->passtime == 'smartcar') {
                $update['passtime_serialno'] = $vehicle;
            }
            if ($this->csSettingObj->gps_provider == 'smartcar') {
                $update['gps_serialno'] = $vehicle;
            }
            if (!empty($update)) {
                DB::table('vehicles')->where('id', $vehicleObj->id)->update($update);
            }
        }
    }
}
