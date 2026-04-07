<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    /**
     * Cake DepositTemplatesController::admin_updateFareType (subset: settings sync buttons).
     */
    public function admin_updateFareType(Request $request): JsonResponse
    {
        $userId = (int)$request->input('user_id');
        $field = (string)$request->input('field');
        if ($userId <= 0 || $field === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $allowedScalar = ['roadside_assistance_included', 'maintenance_included_fee'];

        $vehicles = LegacyVehicle::query()
            ->where('user_id', $userId)
            ->get(['id', 'fare_type', 'day_rent']);

        foreach ($vehicles as $v) {
            if ($field === 'fare_type') {
                $fare = (string)$request->input('fare_type', '');
                $updates = ['fare_type' => $fare];
                if ($fare === 'L') {
                    $updates['day_rent'] = 0;
                }
                LegacyVehicle::query()->whereKey((int)$v->id)->update($updates);
            } elseif (in_array($field, $allowedScalar, true)) {
                $val = $request->input($field);
                LegacyVehicle::query()->whereKey((int)$v->id)->update([$field => $val]);
            } else {
                return response()->json(['status' => false, 'message' => 'Invalid field']);
            }
        }

        return response()->json(['status' => true, 'message' => 'Vehicle synched successfully']);
    }
}
