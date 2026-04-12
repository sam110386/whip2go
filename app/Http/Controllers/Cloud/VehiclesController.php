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

    protected function vehicleBasePath(): string
    {
        return '/cloud/vehicles';
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

    public function cloud_rental_setting(Request $request, $id = null)
    {
        return $this->admin_rental_setting($request, $id);
    }

    public function cloud_duplicate(Request $request, $vehicleid = '')
    {
        return $this->admin_duplicate($request, $vehicleid);
    }

    public function cloud_lastlocation(Request $request, $vehicle_id = null)
    {
        return $this->admin_lastlocation($request, $vehicle_id);
    }

    public function cloud_saveImage(Request $request)
    {
        return $this->admin_saveImage($request);
    }

    public function cloud_deleteImage(Request $request)
    {
        return $this->admin_deleteImage($request);
    }

    public function cloud_reorderImage(Request $request)
    {
        return $this->admin_reorderImage($request);
    }

    public function cloud_getVehicleRegistration(Request $request)
    {
        return $this->admin_getVehicleRegistration($request);
    }

    public function cloud_getVehicleInspectionDoc(Request $request)
    {
        return $this->admin_getVehicleInspectionDoc($request);
    }

    public function cloud_getvehicledetails(Request $request)
    {
        return $this->admin_getvehicledetails($request);
    }

    public function cloud_updateVehicleDetails(Request $request)
    {
        return $this->admin_updateVehicleDetails($request);
    }

    public function cloud_getVehicleGps(Request $request)
    {
        return $this->admin_getVehicleGps($request);
    }

    public function cloud_gps_setting(Request $request)
    {
        return $this->admin_gps_setting($request);
    }

    public function cloud_save_gpssetting(Request $request)
    {
        return $this->admin_save_gpssetting($request);
    }

    public function cloud_delete_gpssetting(Request $request)
    {
        return $this->admin_delete_gpssetting($request);
    }

    public function cloud_changePasstimeVehicleStatus(Request $request)
    {
        return $this->admin_changePasstimeVehicleStatus($request);
    }
}
