<?php
namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Services\Legacy\Passtime;

trait PasstimeActivateVehicle
{
    public function ActivatePasstimeVehicle($vehicleId)
    {
        $vehicleData = Vehicle::select(['id', 'passtime_serialno', 'autopi_unit_id', 'passtime_status', 'user_id'])
            ->with(['csSetting', 'vehicleSetting'])
            ->where('vehicles.id', $vehicleId)
            ->first();

        if (!$vehicleData) {
            return false;
        }

        if ($vehicleData->passtime_status != 0 && $vehicleData->passtime_status != 2) {
            return false;
        }

        $passtimeService = new Passtime();
        $resp = $passtimeService->activateVehicle($vehicleData->toArray());

        if (!empty($resp['status'])) {
            Vehicle::where('id', $vehicleId)->update(['passtime_status' => 1]);
            return true;
        }

        return false;
    }
}
