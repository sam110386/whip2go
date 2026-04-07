<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = (int) $request->input('Record.limit', session('Trackings_limit', 20));
        $limit = $limit > 0 ? $limit : 20;
        session(['Trackings_limit' => $limit]);

        $trackings = DB::table('trackings as Tracking')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'Tracking.vehicle_id')
            ->leftJoin('users as User', 'User.id', '=', 'Tracking.user_id')
            ->select('Tracking.*', 'Vehicle.vehicle_name', 'User.first_name', 'User.last_name')
            ->orderByDesc('Tracking.id')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.trackings.admin_index', [
            'trackings' => $trackings,
        ]);
    }

    public function admin_view(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = (int) $request->input('Record.limit', session('Trackings_limit', 20));
        $limit = $limit > 0 ? $limit : 20;
        session(['Trackings_limit' => $limit]);

        $trackings = DB::table('trackings as Tracking')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'Tracking.vehicle_id')
            ->selectRaw('COUNT(Tracking.vehicle_id) as views, Vehicle.vehicle_name')
            ->groupBy('Tracking.vehicle_id', 'Vehicle.vehicle_name')
            ->orderByDesc('Tracking.id')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.trackings.admin_view', [
            'trackings' => $trackings,
        ]);
    }
}
