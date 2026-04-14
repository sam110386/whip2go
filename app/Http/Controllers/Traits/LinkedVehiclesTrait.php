<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use Illuminate\Http\Request;
use Exception;

trait LinkedVehiclesTrait
{
    private $allowedExtensions = ['jpeg', 'jpg', 'png'];

    protected function handleUpload(Request $request, $vehicleId, $fileKey = 'vehicleimage')
    {
        if (!$request->hasFile($fileKey)) {
            return ['error' => 'No files were uploaded.'];
        }

        $file = $request->file($fileKey);

        if (!$file->isValid()) {
            return ['error' => 'Upload Error #' . $file->getError()];
        }

        $size = $file->getSize();
        if ($size == 0) {
            return ['error' => 'File is empty.'];
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $these = implode(', ', $this->allowedExtensions);
            return ['error' => 'File has an invalid extension, it should be one of ' . $these . '.'];
        }

        $imageCount = VehicleImage::where('vehicle_id', $vehicleId)->count();
        $imageCount++;
        
        $filename = 'vehi_' . $vehicleId . '_' . $imageCount . '.' . $extension;

        try {
            $file->move(public_path('img/custom/vehicle_photo/'), $filename);

            $vehicleImage = new VehicleImage();
            $vehicleImage->filename = $filename;
            $vehicleImage->vehicle_id = $vehicleId;
            $vehicleImage->iorder = $imageCount;
            $vehicleImage->save();

            return ['success' => true, 'key' => $vehicleImage->id];
        } catch (Exception $e) {
            return ['error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered'];
        }
    }

    protected function _getVehicleGps($vehicleId, $type)
    {
        $vehicleData = Vehicle::query()
            ->from('vehicles as Vehicle')
            ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'Vehicle.user_id')
            ->leftJoin('vehicle_settings as VehicleSetting', 'VehicleSetting.vehicle_id', '=', 'Vehicle.id')
            ->select('Vehicle.id', 'Vehicle.gps_serialno', 'Vehicle.vin_no', 'CsSetting.*', 'VehicleSetting.*')
            ->where('Vehicle.id', $vehicleId)
            ->first();

        if (empty($vehicleData)) {
            return ['status' => false, "message" => "sorry, seems your setting is not saved for GPS provider. Please contact to Administrator support."];
        }

        // Equivalent of legacy passtime->parseVehicleSetting
        $passtimeClass = '\\App\\Models\\Legacy\\Passtime';
        if (static::class_exists($passtimeClass)) {
            $passtime = new $passtimeClass();
            $vehicleDataArray = $passtime->parseVehicleSetting(['CsSetting' => $vehicleData->toArray()]); 
            // In modern Laravel this logic might natively rest on model accessors
        } else {
            $vehicleDataArray = ['CsSetting' => $vehicleData->toArray()];
        }

        $CsSetting = $vehicleDataArray['CsSetting'];

        $server = $CsSetting["geotab_server"] ?? null;
        $username = $CsSetting["geotab_user"] ?? null;
        $pwd = $CsSetting["geotab_pwd"] ?? null;
        $database = $CsSetting["geotab_db"] ?? null;
        $onestepgps = $CsSetting["onestepgps"] ?? null;
        $gps_provider = $CsSetting["gps_provider"] ?? null;

        if (($gps_provider == 'geotab' && (empty($server) || empty($username) || empty($pwd) || empty($database))) || ($gps_provider == 'onestepgps' && empty($onestepgps))) {
            return ['status' => false, "message" => "sorry, seems your setting is not saved for GPS provider. Please contact to Administrator support."];
        }

        if (!in_array($type, ['gps_serialno', 'passtime_serialno'])) {
            return ['status' => false, "message" => "sorry, you didnt pass valid inputs. Please refresh your page"];
        }

        $gps_serialno = "";

        if ($gps_provider == 'geotab') {
            $geotabClass = '\\App\\Lib\\Legacy\\Geotab';
            if (static::class_exists($geotabClass)) {
                $Geotab = new $geotabClass();
                $return = $Geotab->getDealerDevices([
                    "geotab_server" => $server, "geotab_user" => $username, "geotab_pwd" => $pwd, "geotab_db" => $database
                ]);

                if (!$return['status']) {
                    return $return;
                }

                $result = collect($return['result'])->keyBy('vehicleIdentificationNumber')->toArray();
                $gps_serialno = $result[$vehicleData->vin_no]['id'] ?? "";
            }
        } elseif ($gps_provider == 'onestepgps') {
            $onestepgpsClass = '\\App\\Lib\\Legacy\\Onestepgps';
            if (static::class_exists($onestepgpsClass)) {
                $params = ["api-key" => $onestepgps, "device_id" => 1, "vin" => 1];
                $Onestepgps = new $onestepgpsClass();
                $return = $Onestepgps->ExecuteCustomCall('device-info', $params);

                if (!$return['status']) {
                    return $return;
                }

                $result = collect($return['result'])->keyBy('vin')->toArray();
                $gps_serialno = $result[$vehicleData->vin_no]['device_id'] ?? "";
            }
        } elseif ($gps_provider == 'autopi') {
            $autopiClass = '\\App\\Lib\\Legacy\\AutoPi';
            if (static::class_exists($autopiClass)) {
                $params = ["autopi_token" => $CsSetting['autopi_token'] ?? null];
                $AutoPi = new $autopiClass();
                $return = $AutoPi->getDealerDevices($params);

                if (!$return['status']) {
                    return $return;
                }

                $result = collect($return['result'])->mapWithKeys(function ($item) {
                     return [$item['vin'] => $item['connections']];
                })->toArray();
                $connections = $result[$vehicleData->vin_no] ?? [];
                $gps_serialno = !empty($connections) ? array_values($connections)[0]['id'] : "";
            }
        }

        if (!empty($gps_serialno)) {
            Vehicle::where('id', $vehicleData->id)->update([$type => $gps_serialno]);
        }

        return [
            'status' => !empty($gps_serialno), 
            "message" => "Sorry, vehicle VIN not found on GPS portal", 
            "gps_serialno" => $gps_serialno
        ];
    }
}
