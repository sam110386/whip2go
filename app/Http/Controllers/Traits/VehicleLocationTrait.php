<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Legacy\VehicleLocation;

/**
 * Ported from CakePHP app/Controller/Traits/VehicleLocationTrait.php
 * Saves vehicle location records, replacing all existing locations for a vehicle.
 */
trait VehicleLocationTrait
{
    protected function saveVehicleLocation($locations, $vehicleid)
    {

        $oldLocations = VehicleLocation::where('vehicle_id', $vehicleid)->pluck('id')->toArray();
        $newLocations = [];

        foreach ($locations as $location) {

            if (empty($location['lat']) || empty($location['lng'])) {
                continue;
            }

            $attributes = ['id' => $location['id'] ?? null];

            $values = [
                'vehicle_id' => $vehicleid,
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'address' => $location['address'] ?? null,
                'geo' => DB::raw("POINT(" . $location['lng'] . ", " . $location['lat'] . ")")
            ];

            $savedLocation = VehicleLocation::updateOrCreate($attributes, $values);
            $newLocations[] = $savedLocation->id;
        }

        $needToDelete = array_diff($oldLocations, $newLocations);

        if (!empty($needToDelete)) {
            VehicleLocation::whereIn('id', $needToDelete)->delete();
        }
    }
}
