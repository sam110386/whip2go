<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\LeasesTrait;
use Illuminate\Http\Request;

class LeasesController extends LegacyAppController
{
    use LeasesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function createVehicleUnavailability($vehicleid)
    {
        return $this->processCreateVehicleUnavailability($vehicleid, 'legacy.leases.create_vehicle_unavailability');
    }

    public function load(Request $request)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }
        return $this->processLoad($request, (int)$userId);
    }

    public function remove(Request $request, $id)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }
        return $this->processRemove($request, $id, (int)$userId);
    }

    public function addunavailability(Request $request)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }
        return $this->processAddUnavailability($request, (int)$userId, false);
    }

    public function createVehicleLease(Request $request, $vehicleid)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }
        return $this->processCreateVehicleLease($request, $vehicleid, (int)$userId, 'legacy.leases.create_vehicle_lease');
    }
}
