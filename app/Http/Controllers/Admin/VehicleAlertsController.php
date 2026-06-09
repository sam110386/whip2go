<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\VehicleAlert;
use Illuminate\Http\Request;
/**
 * Migrated from: app/Plugin/VehicleAlert/Controller/VehicleAlertsController.php
 */
class VehicleAlertsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Alerts';
        $sessionLimitName = 'vehicle_alerts_limit';
        $vehicleId = '';
        $conditions = [];

        if ($request->has('Search') || $request->has('vehicle_id')) {
            $vehicleId = $request->input('Search.vehicle_id') ?? $request->input('vehicle_id');
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitName => $limit]);
        } else {
            $limit = session($sessionLimitName, $this->recordsPerPage);
        }

        $query = VehicleAlert::with('vehicle:id,vehicle_name');

        if (!empty($vehicleId)) {
            $query->where('vehicle_id', $vehicleId);
        }

        $vehicleAlerts = $query->orderBy('id', 'desc')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.vehicle_alerts.elements.index', compact('vehicleAlerts', 'vehicleId', 'limit'));
        }

        return view('admin.vehicle_alerts.index', compact('title', 'vehicleAlerts', 'vehicleId', 'limit'));
    }
    public function delete(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$request->ajax()) {
            return response()->json([
                'status' => false,
                'message' => 'wrong attempt'
            ], 400);
        }

        $return = [
            'status' => false,
            'message' => 'Sorry, something went wrong'
        ];

        $recordId = $request->input('recordid');

        if (empty($recordId)) {
            return response()->json($return);
        }

        VehicleAlert::destroy($recordId);

        return response()->json([
            'status' => true,
            'message' => 'Record deleted successfully'
        ]);
    }
}
