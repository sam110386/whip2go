<?php
namespace App\Http\Controllers\Traits;

use App\Services\Legacy\Passtime;
use Illuminate\Http\Request;
use App\Models\Legacy\CsOrderReviewImage;
use App\Models\Legacy\Vehicle;

trait BookingReviewsTrait
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];
    private $imageSize = 5242885;
    protected function handleUpload($file, $reviewId)
    {
        if (!$file->isValid()) {
            return ['error' => 'Upload Error #' . $file->getError()];
        }

        $postMax = $this->commonService->toBytes(ini_get('post_max_size'));
        $uploadMax = $this->commonService->toBytes(ini_get('upload_max_filesize'));
        $this->imageSize = min($postMax, $uploadMax);
        $fileSize = $file->getSize();

        if ($fileSize === 0) {
            return ['error' => 'File is empty.'];
        }

        if (!is_null($this->imageSize) && $this->imageSize > 0 && $fileSize > $this->imageSize) {
            return ['error' => 'File is too large.', 'preventRetry' => true];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'error' => 'File has an invalid extension, it should be one of ' . implode(', ', $this->allowedExtensions) . '.'
            ];
        }

        $imageCount = CsOrderReviewImage::where('cs_order_review_id', $reviewId)->count();
        $imageCount++;
        $fileName = "review_{$reviewId}_{$imageCount}.{$extension}";

        try {
            $targetDir = public_path('files/reviewimages');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if ($file->move($targetDir, $fileName)) {
                $reviewImage = CsOrderReviewImage::create([
                    'image' => $fileName,
                    'cs_order_review_id' => $reviewId,
                ]);

                return [
                    'success' => true,
                    'key' => $reviewImage->id,
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered.'
            ];
        }

        return ['error' => 'Could not save uploaded file.'];
    }
    protected function _pullVehicleOdometer(Request $request)
    {
        $return = [
            'status' => false,
            'message' => 'Sorry, you are not authorized user for this action.',
            'result' => [],
        ];

        $encodedVehicle = $request->input('vehicle');
        $vehicleId = $this->decodeId($encodedVehicle);

        if (empty($vehicleId)) {
            return response()->json($return);
        }

        $vehicleData = Vehicle::select([
            'id',
            'user_id',
            'passtime_serialno',
            'gps_serialno',
            'passtime_status',
            'last_mile',
        ])
            ->with([
                'csSetting',
                'vehicleSetting',
                'owner:id,distance_unit',
            ])
            ->find($vehicleId);

        if (!$vehicleData) {
            return response()->json($return);
        }

        $passtime = new Passtime();
        $response = $passtime->getVehicleLastMile($vehicleData->toArray());

        if (empty($response['status'])) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, GPS didnt responded, please check GPS provider setting again',
                'result' => [],
            ]);
        }

        return response()->json($response);
    }
}
