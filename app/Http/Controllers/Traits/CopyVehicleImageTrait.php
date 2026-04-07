<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\VehicleImage;
use Illuminate\Support\Facades\Storage;

trait CopyVehicleImageTrait
{
    private function _CopyVehicleImageFromRemote($vehicleid)
    {
        $images = VehicleImage::where('vehicle_id', $vehicleid)->where('remote', 1)->get();
        $imageCount = 1;

        foreach ($images as $image) {
            $url = $image->filename;
            $fileformat = pathinfo($url, PATHINFO_EXTENSION);
            $newFilename = 'vehi_' . $vehicleid . '_1_' . $imageCount . '.' . $fileformat;
            $savePath = public_path('img/custom/vehicle_photo/' . $newFilename);

            try {
                // Download from remote URL
                $content = file_get_contents($url);
                if ($content !== false) {
                    file_put_contents($savePath, $content);
                    $image->filename = $newFilename;
                    $image->remote = 0;
                    $image->save();
                    $imageCount++;
                }
            } catch (\Exception $e) {
                // Skip if download fails
                continue;
            }
        }
    }
}
