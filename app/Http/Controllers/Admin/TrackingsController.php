<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Tracking;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessLimitName = "trackings_limit";
        $title = 'Tracking Data';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $request->merge(['Record' => ['limit' => $limit]]);

        $trackings = Tracking::with([
            'vehicle:id,vehicle_name',
            'user:id,first_name,last_name'
        ])
            ->paginate($limit);

        if ($request->ajax()) {
            return view('admin.trackings.elements.index', compact('trackings', 'limit'));
        }

        return view('admin.trackings.index', compact('title', 'trackings', 'limit'));
    }
    public function view(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessLimitName = "trackings_limit";
        $title = 'Vehicle Views';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $request->merge(['Record' => ['limit' => $limit]]);

        $trackings = DB::table('trackings')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'trackings.vehicle_id')
            ->selectRaw('trackings.vehicle_id, vehicles.vehicle_name, COUNT(trackings.vehicle_id) AS views')
            ->groupBy('trackings.vehicle_id', 'vehicles.vehicle_name')
            ->orderByDesc(DB::raw('views'))
            ->paginate($limit)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.trackings.elements.view', compact('trackings', 'limit'));
        }

        return view('admin.trackings.view', compact('title', 'trackings', 'limit'));
    }
}
