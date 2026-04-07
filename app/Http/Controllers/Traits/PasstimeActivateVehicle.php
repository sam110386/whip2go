<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\VehicleSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait PasstimeActivateVehicle {

    public function ActivatePasstimeVehicle($vehicleId) {
        $vehicleData = Vehicle::where('vehicles.id', $vehicleId)
            ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'vehicles.user_id')
            ->leftJoin('vehicle_settings as VehicleSetting', 'VehicleSetting.vehicle_id', '=', 'vehicles.id')
            ->select(
                'vehicles.id', 'vehicles.passtime_serialno', 'vehicles.autopi_unit_id', 
                'vehicles.passtime_status', 'vehicles.user_id', 'CsSetting.*', 'VehicleSetting.*'
            )
            ->first();

        if (!$vehicleData) return false;
        if (!in_array($vehicleData->passtime_status, [0, 2])) return false;

        // Stubbed Passtime Activation Logic
        Log::info("Passtime: Activating vehicle $vehicleId with serial " . ($vehicleData->passtime_serialno ?? 'N/A'));
        
        // Simulating success for now
        $resp = ['status' => true];
        
        if ($resp['status']) {
            Vehicle::where('id', $vehicleId)->update(['passtime_status' => 1]);
            return true;
        }

        return false;
    }
}
