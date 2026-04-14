<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Migrated from: app/Plugin/VehicleAlert/Controller/VehicleAlertsController.php
 *
 * Admin CRUD for vehicle alert records.
 * Views: resources/views/admin/vehicle_alerts/
 */
class VehicleAlertsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    /**
     * admin_index → index
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $vehicleid = '';
        $conditions = [];

        if ($request->has('Search') || $request->route('vehicle_id')) {
            $vehicleid = $request->route('vehicle_id')
                ?? $request->input('Search.vehicle_id', '');

            if (!empty($vehicleid)) {
                $conditions[] = ['VehicleAlert.vehicle_id', '=', $vehicleid];
            }
        }

        $sessKey = 'vehicle_alerts_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessKey, $this->recordsPerPage);

        if ($request->input('Record.limit')) {
            session([$sessKey => $limit]);
        }

        $query = DB::table('vehicle_alerts as VehicleAlert')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleAlert.vehicle_id')
            ->select('VehicleAlert.*', 'Vehicle.vehicle_name');

        foreach ($conditions as $cond) {
            $query->where(...$cond);
        }

        $vehiclealrets = $query->orderByDesc('VehicleAlert.id')->paginate($limit);

        $data = compact('vehiclealrets', 'vehicleid');

        if ($request->ajax()) {
            return view('admin.vehicle_alerts._index_table', $data);
        }

        return view('admin.vehicle_alerts.index', $data)->with('title_for_layout', 'Vehicle Alerts');
    }

    /**
     * admin_delete → delete
     */
    public function delete(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'wrong attempt'], 400);
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong'];

        $recordId = $request->input('recordid');
        if (empty($recordId)) {
            return response()->json($return);
        }

        DB::table('vehicle_alerts')->where('id', $recordId)->delete();

        return response()->json(['status' => true, 'message' => 'Record deleted successfully']);
    }
}
