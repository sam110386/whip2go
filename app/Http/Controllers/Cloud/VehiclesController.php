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

    public function add(Request $request, $vehicle_id = null)
    {
        return parent::add($request, $vehicle_id);
    }

    public function rental_setting(Request $request, $id = null)
    {
        return parent::rental_setting($request, $id);
    }

    public function duplicate(Request $request, $vehicleid = '')
    {
        return parent::duplicate($request, $vehicleid);
    }

    public function lastlocation(Request $request, $vehicle_id = null)
    {
        return parent::lastlocation($request, $vehicle_id);
    }

    public function saveImage(Request $request)
    {
        return parent::saveImage($request);
    }

    public function deleteImage(Request $request)
    {
        return parent::deleteImage($request);
    }

    public function reorderImage(Request $request)
    {
        return parent::reorderImage($request);
    }

    public function getVehicleRegistration(Request $request)
    {
        return parent::getVehicleRegistration($request);
    }

    public function getVehicleInspectionDoc(Request $request)
    {
        return parent::getVehicleInspectionDoc($request);
    }

    public function getvehicledetails(Request $request)
    {
        return parent::getvehicledetails($request);
    }

    public function updateVehicleDetails(Request $request)
    {
        return parent::updateVehicleDetails($request);
    }

    public function getVehicleGps(Request $request)
    {
        return parent::getVehicleGps($request);
    }

    public function gps_setting(Request $request)
    {
        return parent::gps_setting($request);
    }

    public function save_gpssetting(Request $request)
    {
        return parent::save_gpssetting($request);
    }

    public function delete_gpssetting(Request $request)
    {
        return parent::delete_gpssetting($request);
    }

    public function changePasstimeVehicleStatus(Request $request)
    {
        return parent::changePasstimeVehicleStatus($request);
    }
}
