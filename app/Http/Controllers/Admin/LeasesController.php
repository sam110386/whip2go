<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\LeasesTrait;
use Illuminate\Http\Request;

class LeasesController extends LegacyAppController
{
    use LeasesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function admin_createVehicleUnavailability($vehicleid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        return $this->processCreateVehicleUnavailability($vehicleid, 'admin.leases.create_vehicle_unavailability');
    }

    public function admin_load(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json([]);
        }
        return $this->processLoad($request, null); // Admins can load all
    }

    public function admin_remove(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 0, 'message' => "Unauthorized", 'result' => []]);
        }
        return $this->processRemove($request, $id, null);
    }

    public function admin_addunavailability(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 0, 'message' => "Unauthorized", 'result' => []]);
        }
        
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }
        
        return $this->processAddUnavailability($request, (int)$userId, true);
    }

    public function admin_createVehicleLease(Request $request, $vehicleid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }

        return $this->processCreateVehicleLease($request, $vehicleid, (int)$userId, 'admin.leases.create_vehicle_lease');
    }
}
