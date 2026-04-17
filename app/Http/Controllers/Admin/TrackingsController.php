<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'trackings_limit';

    protected function basePath(): string
    {
        return '/admin/trackings';
    }

    /**
     * Cake `admin_index`: paginated tracking rows with vehicle + user; AJAX returns listing fragment.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $trackings = DB::table('trackings')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'trackings.vehicle_id')
            ->leftJoin('users', 'users.id', '=', 'trackings.user_id')
            ->select(
                'trackings.*',
                'vehicles.vehicle_name',
                'users.first_name',
                'users.last_name'
            )
            ->orderByDesc('trackings.id')
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'title_for_layout' => 'Tracking Data',
            'trackings' => $trackings,
            'limit' => $limit,
            'basePath' => $this->basePath(),
        ];

        if ($request->ajax()) {
            return response()->view('admin.trackings.partials.index_listing', $viewData);
        }

        return view('admin.trackings.index', $viewData);
    }

    /**
     * Cake `admin_view`: counts per vehicle with vehicle name; paginated; AJAX returns listing fragment.
     */
    public function view(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $trackings = DB::table('trackings')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'trackings.vehicle_id')
            ->selectRaw('trackings.vehicle_id, vehicles.vehicle_name, COUNT(trackings.vehicle_id) AS views')
            ->groupBy('trackings.vehicle_id', 'vehicles.vehicle_name')
            ->orderByDesc(DB::raw('views'))
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'title_for_layout' => 'Vehicle Views',
            'trackings' => $trackings,
            'limit' => $limit,
            'basePath' => $this->basePath(),
        ];

        if ($request->ajax()) {
            return response()->view('admin.trackings.partials.view_listing', $viewData);
        }

        return view('admin.trackings.view', $viewData);
    }

    protected function resolveLimit(Request $request): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);

                return $lim;
            }
        }
        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }
}
