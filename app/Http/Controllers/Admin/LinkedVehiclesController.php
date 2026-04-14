<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsLinkedVehicleIndex;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Dealer-scoped vehicle list (Cake LinkedVehiclesController under admin URL).
 */
class LinkedVehiclesController extends LegacyAppController
{
    use BuildsLinkedVehicleIndex;

    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $parentId = (int)($admin['parent_id'] ?? 0);

        return $this->linkedVehiclesIndexResponse($request, $parentId, '/admin/linked_vehicles/index');
    }

    /**
     * Dealer-linked add/edit — delegates to VehiclesController::admin_add (dealer id locked from session).
     */
    public function add(Request $request, $vehicle_id = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $suffix = $vehicle_id !== null && $vehicle_id !== '' ? '/' . ltrim((string)$vehicle_id, '/') : '';

        return redirect('/admin/vehicles/add' . $suffix);
    }
}
