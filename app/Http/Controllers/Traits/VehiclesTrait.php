<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleLocation;
use App\Models\Legacy\DynamicFare;
use App\Models\Legacy\CsSetting;
use Illuminate\Support\Facades\Log;

trait VehiclesTrait {

    protected function handleUpload($file, $vehicleid) {
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];
        $fileformat = $file->getClientOriginalExtension();

        if (!in_array(strtolower($fileformat), $allowedExtensions)) {
            return ['error' => 'File has an invalid extension.'];
        }

        $imageCount = VehicleImage::where('vehicle_id', $vehicleid)->count() + 1;
        $filename = 'vehi_' . $vehicleid . '_' . $imageCount . '.' . $fileformat;
        
        $destinationPath = public_path('img/custom/vehicle_photo');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        if ($file->move($destinationPath, $filename)) {
            $newImage = VehicleImage::create([
                'filename' => $filename,
                'vehicle_id' => $vehicleid,
                'iorder' => $imageCount
            ]);
            return ['success' => true, "key" => $newImage->id];
        }

        return ['error' => 'Could not save uploaded file.'];
    }

    protected function _getVehicleGps($vehicle_id, $type) {
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

    protected function _getVehicleDynamicFare($params) {
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

    protected function _getVehicleInspectionDoc($vehicleid) {
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

    protected function exportToCsv($vehicles) {
        $filename = "vehicle_data_" . date('Y-m-d') . ".csv";
        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Vehicle#', 'Vehicle Name', 'Plate Number', 'VIN #', 'Stock #', 'Color', 'Make', 'Model', 'Status']);

        foreach ($vehicles as $vehicle) {
            fputcsv($handle, [
                $vehicle->vehicle_unique_id,
                $vehicle->vehicle_name,
                $vehicle->plate_number,
                $vehicle->vin_no,
                $vehicle->stock_no,
                $vehicle->color,
                $vehicle->make,
                $vehicle->model,
                $vehicle->status == 1 ? 'Active' : 'Inactive'
            ]);
        }

        fclose($handle);
        exit;
    }
}
