<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\DepositTemplatesTrait;
use Illuminate\Http\Request;

class DepositTemplatesController extends LegacyAppController
{
    use DepositTemplatesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_index(Request $request, $userId)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $decodedUser = base64_decode($userId);
        $cleanUserId = (int)$decodedUser > 0 ? (int)$decodedUser : (int)$userId;

        return $this->processDepositTemplateIndex(
            $request,
            $cleanUserId,
            'Update Rental Fee Template',
            'cloud.deposit_templates.index',
            null
        );
    }

    public function cloud_syncToVehicle(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }
        return $this->processSyncToVehicle($request);
    }
}
