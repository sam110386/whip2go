<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleLocation;
use App\Models\Legacy\DynamicFare;
use App\Models\Legacy\CsSetting;
use Illuminate\Support\Facades\Log;

trait VehiclesTrait
{

    protected function handleUpload($file, $vehicleId)
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

    protected function _getVehicleGps($vehicle_id, $type)
    {
        $vehicle = Vehicle::with(['CsSetting', 'VehicleSetting'])->find($vehicle_id);
        if (!$vehicle || !$vehicle->CsSetting) {
            return ['status' => false, "message" => "sorry, seems your setting is not saved for GPS provider."];
        }

        $gps_provider = $vehicle->CsSetting->gps_provider;
        $vin = $vehicle->vin_no;

        // Placeholder for GPS logic
        Log::info("GPS: getDealerDevices for provider $gps_provider, vin $vin");

        // Simulation of GPS search success
        $gps_serialno = 'simulated_' . $vin;

        if (!empty($gps_serialno)) {
            $vehicle->update([$type => $gps_serialno]);
            return ['status' => true, "message" => "Vehicle found on GPS portal", "gps_serialno" => $gps_serialno];
        }

        return ['status' => false, "message" => "Sorry, vehicle VIN not found on GPS portal"];
    }

    protected function _getVehicleDynamicFare($params)
    {
        $vehicleid = $params['vehicleid'];
        $tag = $params['tag'] ?? 'D';
        $vehicle = Vehicle::find($vehicleid);

        if (!$vehicle) {
            return ["status" => "error", "msg" => "Vehicle not found"];
        }

        if ($tag == 'D') {
            // Placeholder for DynamicFare::calculateDynamicFare
            Log::info("DynamicFare: calculateDynamicFare for vehicle $vehicleid");
            return [
                'status' => 'success',
                'data' => ['simulated' => 'dynamic_fare_data']
            ];
        }

        if ($tag == 'L') {
            // Placeholder for Free2Move::fetchDynamicFare
            Log::info("Free2Move: fetchDynamicFare for vehicle $vehicleid");
            return [
                'status' => 'success',
                'data' => ['simulated' => 'free2move_fare_data']
            ];
        }

        return ["status" => "error", "msg" => "Invalid tag"];
    }

    protected function _getVehicleInspectionDoc($vehicleid)
    {
        $vehicle = Vehicle::find($vehicleid);
        if ($vehicle && !empty($vehicle->inspection_image)) {
            $filePath = public_path('img/custom/vehicle_photo/' . $vehicle->inspection_image);
            if (file_exists($filePath)) {
                return [
                    'status' => true,
                    'message' => "Success",
                    'result' => ['file' => asset('img/custom/vehicle_photo/' . $vehicle->inspection_image)]
                ];
            }
        }
        return ['status' => false, 'message' => "Document not found"];
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
