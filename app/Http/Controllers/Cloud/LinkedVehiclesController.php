<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Concerns\BuildsLinkedVehicleIndex;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LinkedVehiclesController extends LegacyAppController
{
    use BuildsLinkedVehicleIndex;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * Cake LinkedVehiclesController::cloud_index
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $parentId = (int)($admin['parent_id'] ?? 0);

        return $this->linkedVehiclesIndexResponse($request, $parentId, '/cloud/linked_vehicles/index');
    }

    /**
     * Dealer-linked add/edit — delegates to Cloud\VehiclesController::cloud_add.
     */
    public function cloud_add(Request $request, $vehicle_id = null): RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $suffix = $vehicle_id !== null && $vehicle_id !== '' ? '/' . ltrim((string)$vehicle_id, '/') : '';

        return redirect('/cloud/vehicles/add' . $suffix);
    }
}
