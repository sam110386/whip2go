<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/PasstimeActivateVehicle.php
 *
 * Activates a vehicle via the Passtime/AutoPi tracking service.
 */
trait PasstimeActivateVehicleTrait
{
    protected function ActivatePasstimeVehicle($vehicle_id)
    {
        $vehicleData = DB::table('vehicles')
            ->leftJoin('cs_settings', 'cs_settings.user_id', '=', 'vehicles.user_id')
            ->leftJoin('vehicle_settings', 'vehicle_settings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.id', $vehicle_id)
            ->select(
                'vehicles.id',
                'vehicles.passtime_serialno',
                'vehicles.autopi_unit_id',
                'vehicles.passtime_status',
                'vehicles.user_id',
                'cs_settings.*',
                'vehicle_settings.*'
            )
            ->first();

        if (empty($vehicleData)) {
            return false;
        }
        if ($vehicleData->passtime_status != 0 && $vehicleData->passtime_status != 2) {
            return false;
        }

        // TODO: Replace with injected Passtime service when migrated
        // Legacy: $this->Passtime->ActivateVehicle($vehicleData)
        $resp = $this->Passtime->ActivateVehicle($vehicleData);

        if ($resp['status']) {
            DB::table('vehicles')
                ->where('id', $vehicle_id)
                ->update(['passtime_status' => 1]);
            return true;
        }
        return false;
    }
}
