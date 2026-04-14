<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/CopyVehicleImageTrait.php
 *
 * Copies remote vehicle images to local storage.
 */
trait CopyVehicleImageTrait
{
    private function _CopyVehicleImageFromRemote($vehicleid)
    {
        $images = DB::table('vehicle_images')
            ->where('vehicle_id', $vehicleid)
            ->where('remote', 1)
            ->get();

        $imageCount = 1;
        foreach ($images as $image) {
            $url = $image->filename;
            $fileformat = pathinfo($url, PATHINFO_EXTENSION);
            $filename = public_path('img/custom/vehicle_photo/vehi_' . $vehicleid . '_1_' . $imageCount . '.' . $fileformat);

            if (file_put_contents($filename, file_get_contents($url))) {
                DB::table('vehicle_images')
                    ->where('id', $image->id)
                    ->update([
                        'filename' => 'vehi_' . $vehicleid . '_1_' . $imageCount . '.' . $fileformat,
                        'remote' => 0,
                    ]);
                $imageCount++;
            }
        }
    }
}
