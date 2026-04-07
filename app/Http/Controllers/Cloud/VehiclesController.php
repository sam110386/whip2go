<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\VehiclesController as AdminVehiclesController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cloud slug vehicle add/edit — same form as admin, different session guard and URLs.
 */
class VehiclesController extends AdminVehiclesController
{
    protected function ensureVehicleAddSession(): ?RedirectResponse
    {
        return $this->ensureCloudAdminSession();
    }

    protected function vehicleAddFormBasePath(): string
    {
        return '/cloud/vehicles/add';
    }

    protected function vehicleAddReturnListUrl(bool $isSuperAdmin): string
    {
        return $isSuperAdmin ? '/admin/vehicles/index' : '/cloud/linked_vehicles/index';
    }

    protected function vehicleAddLinkedListPath(): string
    {
        return '/cloud/linked_vehicles/index';
    }

    public function cloud_add(Request $request, $vehicle_id = null)
    {
        return $this->admin_add($request, $vehicle_id);
    }
}
