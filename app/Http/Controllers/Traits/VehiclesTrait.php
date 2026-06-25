<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Services\Legacy\Free2MoveService;
use App\Services\Legacy\DynamicFare;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\GeotabClient;
use App\Services\Legacy\OnestepGpsClient;
use App\Services\Legacy\AutoPiFleetClient;


trait VehiclesTrait
{

    private function handleUpload($file, $vehicleId)
    {
        if (!$file->isValid()) {
            return ['error' => 'Upload Error: ' . $file->getErrorMessage()];
        }

        $size = $file->getSize();

        if ($size === 0) {
            return ['error' => 'File is empty.'];
        }

        $maxServerSize = $file->getMaxFilesize();

        if ($size > $maxServerSize) {
            return ['error' => 'File is too large for the server configuration.', 'preventRetry' => true];
        }

        $fileFormat = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];

        if (!in_array($fileFormat, $allowedExtensions)) {
            return ['error' => 'File has an invalid extension, it should be one of ' . implode(', ', $allowedExtensions) . '.'];
        }

        $imageCount = VehicleImage::where('vehicle_id', $vehicleId)->count();
        $imageCount++;
        $newFileName = "vehi_{$vehicleId}_{$imageCount}.{$fileFormat}";
        $targetDir = 'img/custom/vehicle_photo';
        $uploadSuccess = $file->move(public_path($targetDir), $newFileName);

        if ($uploadSuccess) {
            $vehicleImage = VehicleImage::create([
                'vehicle_id' => $vehicleId,
                'filename' => $newFileName,
                'iorder' => $imageCount,
            ]);

            return [
                'success' => true,
                'key' => $vehicleImage->id
            ];
        }

        return ['error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered'];
    }

    private function _getVehicleGps($vehicleId, $type)
    {
        $vehicle = Vehicle::select([
            'id',
            'gps_serialno',
            'passtime_serialno',
            'vin_no',
            'user_id',
        ])->with(['csSetting', 'vehicleSetting'])->find($vehicleId);

        if (!$vehicle || !$vehicle->csSetting) {
            return [
                'status' => false,
                "message" => "sorry, seems your setting is not saved for GPS provider. Please contact to Administrator support."
            ];
        }

        if (!in_array($type, ['gps_serialno', 'passtime_serialno'])) {
            return [
                'status' => false,
                "message" => "sorry, you didn't pass valid inputs. Please refresh your page"
            ];
        }

        $parsedSettings = (new Passtime())->parseVehicleSetting($vehicle->toArray() ?: []);
        $server = $parsedSettings['geotab_server'] ?? null;
        $username = $parsedSettings['geotab_user'] ?? null;
        $pwd = $parsedSettings['geotab_pwd'] ?? null;
        $database = $parsedSettings['geotab_db'] ?? null;
        $onestepgps = $parsedSettings['onestepgps'] ?? null;
        $gpsProvider = $parsedSettings['gps_provider'] ?? null;

        if (
            ($gpsProvider == 'geotab' && (empty($server) || empty($username) || empty($pwd) || empty($database))) ||
            ($gpsProvider == 'onestepgps' && empty($onestepgps))
        ) {
            return [
                'status' => false,
                "message" => "sorry, seems your setting is not saved for GPS provider. Please contact to Administrator support."
            ];
        }

        // --- GEOTAB ---
        if ($gpsProvider == 'geotab') {
            $geotab = new GeotabClient();
            $return = $geotab->getDealerDevices([
                "geotab_server" => $server,
                "geotab_user" => $username,
                "geotab_pwd" => $pwd,
                "geotab_db" => $database
            ]);

            if (!$return['status']) {
                return $return;
            }

            $result = collect($return['result'])->pluck('id', 'vehicleIdentificationNumber')->all();
            $gpsSerialNo = $result[$vehicle->vin_no] ?? "";

            if (!empty($gpsSerialNo)) {
                $vehicle->update([$type => $gpsSerialNo]);
            }

            return [
                'status' => !empty($gpsSerialNo),
                "message" => "Sorry, vehicle VIN not found on GPS portal",
                "gps_serialno" => $gpsSerialNo
            ];
        }

        // --- ONE STEP GPS ---
        if ($gpsProvider == 'onestepgps') {
            $params = ["api-key" => $onestepgps, "device_id" => 1, "vin" => 1];
            $oneStepGpsService = new OnestepGpsClient();
            $return = $oneStepGpsService->ExecuteCustomCall('device-info', $params);

            if (!$return['status']) {
                return $return;
            }

            $result = collect($return['result'])->pluck('device_id', 'vin')->all();
            $gpsSerialNo = $result[$vehicle->vin_no] ?? "";

            if (!empty($gpsSerialNo)) {
                $vehicle->update([$type => $gpsSerialNo]);
            }

            return [
                'status' => !empty($gpsSerialNo),
                "message" => "Sorry, vehicle VIN not found on GPS portal",
                "gps_serialno" => $gpsSerialNo
            ];
        }

        // --- AUTO PI ---
        if ($gpsProvider == 'autopi') {
            $return = (new AutoPiFleetClient())->getDealerDevices($parsedSettings['autopi_token']);

            if (!$return['status']) {
                return $return;
            }

            $result = collect($return['result'])->pluck('connections', 'vin')->all();
            $vinData = $result[$vehicle->vin_no] ?? null;
            $firstConnection = !empty($vinData) ? reset($vinData) : null;

            $gpsSerialNo = $firstConnection['id'] ?? "";
            $autoPiUnitId = $firstConnection['unit_id'] ?? "";

            if (!empty($gpsSerialNo)) {
                $vehicle->update([
                    $type => $gpsSerialNo,
                    'autopi_unit_id' => $autoPiUnitId
                ]);
            }

            return [
                'status' => !empty($gpsSerialNo),
                "message" => "Sorry, vehicle VIN not found on GPS portal",
                "gps_serialno" => $gpsSerialNo
            ];
        }

    }

    private function _getVehicleDynamicFare($request)
    {
        $vehicleId = $request->input('vehicleid');
        $tag = $request->input('tag', 'D');
        $defaultError = [
            "status" => "error",
            "msg" => "Sorry, something went wrong. Please try again"
        ];

        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return response()->json($defaultError);
        }

        $responseData = $defaultError;

        if ($tag === 'D') {
            $fareResponse = DynamicFare::calculateDynamicFare($vehicle, 1);

            if ($fareResponse) {
                $responseData['data'] = $fareResponse;
                $responseData['status'] = 'success';
            }
        }

        if ($tag === 'L') {
            $fareResponse = Free2MoveService::fetchDynamicFare($vehicleId, 1);

            $responseData['data'] = $fareResponse;
            $responseData['status'] = isset($fareResponse['error']) ? 'error' : 'success';
            $responseData['msg'] = $fareResponse['error'] ?? $defaultError['msg'];
        }

        return response()->json($responseData);
    }

    private function _getVehicleInspectionDoc(Request $request)
    {
        $return = ['status' => false, 'message' => "Invalid Vehicle ID", 'result' => []];
        $vehicleId = $this->decodeId($request->input('vehicleid'));

        if (empty($vehicleId)) {
            return response()->json($return);
        }

        $vehicle = Vehicle::select('inspection_image')->find($vehicleId);

        if ($vehicle && !empty($vehicle->inspection_image)) {
            $filePath = "custom/vehicle_photo/{$vehicle->inspection_image}";

            if (Storage::disk('public')->exists($filePath)) {
                $fileUrl = Storage::disk('public')->url($filePath);
                $return = [
                    'status' => true,
                    'message' => "Success",
                    'result' => ['file' => $fileUrl]
                ];
            } else {
                $return = ['status' => false, 'message' => "sorry, document not exists", 'result' => []];
            }
        } else {
            $return = ['status' => false, 'message' => "sorry, document not added yet by owner", 'result' => []];
        }

        return response()->json($return);
    }

    private function exportToCsv($vehicles)
    {
        $vehicleStatus = $this->commonService->getVehicleStatus();
        $fileName = 'vehicle_data_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($vehicles, $vehicleStatus) {
            $fp = fopen('php://output', 'w');
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $columns = ['Vehicle#', 'Vehicle Name', 'Plate Number', 'VIN #', 'Stock #', 'Color', 'Make', 'Model', 'Status'];
            fputcsv($fp, $columns);

            foreach ($vehicles as $vehicle) {

                if (in_array($vehicle->passtime_status, [0, 2])) {
                    $status = "Starter Disabled";
                } elseif ($vehicle->passtime_status == 1 && $vehicle->booked == 1) {
                    $status = "Booked";
                } else {
                    $status = $vehicleStatus[$vehicle->status] ?? "Active";
                }

                fputcsv($fp, [
                    $vehicle->vehicle_unique_id,
                    $vehicle->vehicle_name,
                    $vehicle->plate_number,
                    $vehicle->vin_no,
                    $vehicle->stock_no,
                    $vehicle->color,
                    $vehicle->make,
                    $vehicle->model,
                    $status,
                ]);
            }

            fclose($fp);
        }, 200, $headers);
    }
}
