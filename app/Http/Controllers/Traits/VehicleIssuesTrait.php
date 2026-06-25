<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsVehicleIssueImage;

trait VehicleIssuesTrait
{
    protected function handleUpload($file, int $issueid, int $ftype = 0): array
    {
        $post_max_size = $this->commonService->toBytes(ini_get('post_max_size'));
        $upload_max_filesize = $this->commonService->toBytes(ini_get('upload_max_filesize'));
        $this->imageSize = min($post_max_size, $upload_max_filesize);

        if ($file->getError()) {
            return ['error' => 'Upload Error #' . $file->getError()];
        }

        if ($file->getSize() == 0) {
            return ['error' => 'File is empty.'];
        }

        if ($file->getSize() > $this->imageSize) {
            return ['error' => 'File is too large.', 'preventRetry' => true];
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $this->allowedExtensions)) {
            return ['error' => 'File has an invalid extension, it should be one of ' . implode(', ', $this->allowedExtensions) . '.'];
        }

        $imageCount = (CsVehicleIssueImage::where('cs_vehicle_issue_id', $issueid)->count()) + 1;
        $uploadDir = public_path('img/custom/vehicle_issue');

        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = "issue_{$issueid}_{$imageCount}.{$ext}";
        $file->move($uploadDir, $filename);

        $imageId = CsVehicleIssueImage::insertGetId([
            'image' => $filename,
            'type' => $ftype,
            'cs_vehicle_issue_id' => $issueid,
        ]);

        return ['success' => true, 'key' => $imageId];
    }
}