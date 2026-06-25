<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Legacy\VehicleImage;

/**
 * Ported from CakePHP app/Controller/Traits/CopyVehicleImageTrait.php
 *
 * Copies remote vehicle images to local storage.
 */
trait CopyVehicleImageTrait
{
    private function _CopyVehicleImageFromRemote($vehicleId)
    {
        $images = VehicleImage::where('vehicle_id', $vehicleId)
            ->where('remote', 1)
            ->get();

        $imageCount = 1;

        foreach ($images as $image) {
            $url = $image->filename;
            $fileFormat = pathinfo($url, PATHINFO_EXTENSION);
            $newFileName = "vehi_{$vehicleId}_1_{$imageCount}.{$fileFormat}";
            $storagePath = "custom/vehicle_photo/{$newFileName}";

            try {
                $response = Http::get($url);

                if ($response->successful()) {
                    Storage::disk('public')->put($storagePath, $response->body());
                    $image->update([
                        'filename' => $newFileName,
                        'remote' => 0
                    ]);

                    $imageCount++;
                }
            } catch (\Exception $e) {
                logger()->error("Failed to copy remote image from {$url}: " . $e->getMessage());
            }
        }
    }
}
