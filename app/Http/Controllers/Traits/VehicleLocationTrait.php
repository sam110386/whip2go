<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/VehicleLocationTrait.php
 *
 * Saves vehicle location records, replacing all existing locations for a vehicle.
 */
trait VehicleLocationTrait
{
    protected function saveVehicleLocation($locations, $vehicleid)
    {
        $oldlocations = DB::table('vehicle_locations')
            ->where('vehicle_id', $vehicleid)
            ->pluck('id', 'id')
            ->toArray();

        $newlocations = [];
        foreach ($locations as $location) {
            if (empty($location['lat']) || empty($location['lng'])) {
                continue;
            }

            $data = [
                'vehicle_id' => $vehicleid,
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'address' => $location['address'],
                'geo' => DB::raw("POINT(" . $location['lng'] . "," . $location['lat'] . ")"),
            ];

            if (!empty($location['id'])) {
                DB::table('vehicle_locations')
                    ->where('id', $location['id'])
                    ->update($data);
                $newlocations[] = $location['id'];
            } else {
                $insertedId = DB::table('vehicle_locations')->insertGetId($data);
                $newlocations[] = $insertedId;
            }
        }

        $needToDelete = array_diff($oldlocations, $newlocations);
        if (!empty($needToDelete)) {
            DB::table('vehicle_locations')->whereIn('id', $needToDelete)->delete();
        }
    }
}
