<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\VehicleLocation;
use Illuminate\Support\Facades\DB;

trait VehicleLocationTrait
{
    protected function saveVehicleLocation($locations, $vehicleid)
    {
        $oldLocationIds = VehicleLocation::where('vehicle_id', $vehicleid)->pluck('id')->toArray();
        $newLocationIds = [];

        if (empty($locations)) {
            VehicleLocation::where('vehicle_id', $vehicleid)->delete();
            return;
        }

        foreach ($locations as $location) {
            if (empty($location['lat']) || empty($location['lng'])) {
                continue;
            }

            $data = [
                'vehicle_id' => $vehicleid,
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'address' => $location['address'] ?? '',
                // In Laravel, specifically for MySQL spatial fields:
                'geo' => DB::raw("POINT(" . $location['lng'] . ", " . $location['lat'] . ")")
            ];

            if (!empty($location['id'])) {
                $loc = VehicleLocation::find($location['id']);
                if ($loc) {
                    $loc->update($data);
                    $newLocationIds[] = $loc->id;
                }
            } else {
                $loc = VehicleLocation::create($data);
                $newLocationIds[] = $loc->id;
            }
        }

        $toDelete = array_diff($oldLocationIds, $newLocationIds);
        if (!empty($toDelete)) {
            VehicleLocation::whereIn('id', $toDelete)->delete();
        }
    }
}
