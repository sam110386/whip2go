<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Concerns\BuildsLinkedVehicleIndex;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use App\Support\VehicleListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LinkedVehiclesController extends LegacyAppController
{
    use BuildsLinkedVehicleIndex;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * Cake LinkedVehiclesController::cloud_index
     */
    public function index(Request $request)
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
    public function add(Request $request, $vehicle_id = null): RedirectResponse
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

    /**
     * Cake LinkedVehiclesController::cloud_loadVehicleStatus
     */
    public function loadVehicleStatus(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return response('Unauthorized', 403);
        }

        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response('Invalid vehicle id', 400);
        }

        $vehcile = LegacyVehicle::query()
            ->select(['id', 'status'])
            ->find($vehicleId);

        if (!$vehcile) {
            return response('Vehicle not found', 404);
        }

        return view('cloud.linked_vehicles.load_vehicle_status', [
            'vehcile' => $vehcile,
            'statusOptions' => VehicleListing::adminStatusLabels(),
        ]);
    }

    /**
     * Cake LinkedVehiclesController::cloud_changeVehicleStatus
     */
    public function changeVehicleStatus(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return response()->json(['status' => false, 'vehicleid' => 0], 401);
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return response()->json(['status' => false, 'vehicleid' => 0], 403);
        }

        $payload = (array)$request->input('Vehicle', []);
        $vehicleId = isset($payload['id']) ? (int)$payload['id'] : 0;
        $status = isset($payload['status']) ? (int)$payload['status'] : null;

        if ($vehicleId <= 0 || $status === null) {
            return response()->json(['status' => false, 'vehicleid' => $vehicleId]);
        }

        LegacyVehicle::query()->whereKey($vehicleId)->update(['status' => $status]);

        return response()->json(['status' => true, 'vehicleid' => $vehicleId]);
    }

    /**
     * Cake LinkedVehiclesController::cloud_loadSingleRow
     */
    public function loadSingleRow(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return response('Unauthorized', 403);
        }

        $vehicleId = (int)$request->input('vehicleid', 0);
        if ($vehicleId <= 0) {
            return response('Invalid vehicle id', 400);
        }

        $vehcile = LegacyVehicle::query()
            ->with('owner')
            ->find($vehicleId);

        if (!$vehcile) {
            return response('Vehicle not found', 404);
        }

        return view('cloud.linked_vehicles.load_single_row', [
            'vehcile' => $vehcile,
            'vehicleStatuses' => VehicleListing::adminStatusLabels(),
        ]);
    }

}
