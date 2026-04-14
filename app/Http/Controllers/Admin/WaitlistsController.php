<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitlistsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    protected function waitlistGuard(): ?RedirectResponse
    {
        return $this->ensureAdminSession();
    }

    protected function waitlistBasePath(): string
    {
        return '/admin/waitlists';
    }

    protected function waitlistViewNamespace(): string
    {
        return 'admin';
    }

    protected function waitlistLayout(): string
    {
        return 'admin.layouts.app';
    }

    protected function vehicleAjaxUrl(): string
    {
        return '/admin/bookings/getVehicle';
    }

    /**
     * Builds the base waitlist query with user/vehicle joins.
     * Admin has no user_id filter; Cloud overrides to scope by dealer.
     */
    protected function waitlistQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('waitlist as Waitlist')
            ->leftJoin('users as User', 'User.id', '=', 'Waitlist.user_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'Waitlist.vehicle_id')
            ->select([
                'Waitlist.id',
                'Waitlist.user_id',
                'Waitlist.vehicle_id',
                'Waitlist.status',
                'Waitlist.created',
                'User.first_name',
                'User.last_name',
                'User.address',
                'User.state',
                'Vehicle.vehicle_name',
            ]);
    }

    public function index(Request $request)
    {
        if ($redirect = $this->waitlistGuard()) {
            return $redirect;
        }

        $status = $request->input('Search.status', $request->input('status', ''));
        $dateFrom = $request->input('Search.date_from', $request->input('date_from', ''));
        $dateTo = $request->input('Search.date_to', $request->input('date_to', ''));
        $vehicleid = $request->input('Search.vehicle_id', $request->input('vehicle_id', ''));

        $query = $this->waitlistQuery();
        $this->applyWaitlistFilters($query, $dateFrom, $dateTo, $status, $vehicleid);

        $limit = $this->resolveLimit($request, 'waitlists_limit');

        $records = $query->orderByDesc('Waitlist.id')->paginate($limit)->withQueryString();

        $ns = $this->waitlistViewNamespace();
        $viewData = [
            'records' => $records,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $status,
            'vehicleid' => $vehicleid,
            'limit' => $limit,
            'basePath' => $this->waitlistBasePath(),
            'vehicleAjaxUrl' => $this->vehicleAjaxUrl(),
            'defaultTimezone' => session('default_timezone', 'UTC'),
        ];

        if ($request->ajax()) {
            return view("{$ns}.waitlists._index", $viewData);
        }

        return view("{$ns}.waitlists.index", $viewData);
    }

    protected function applyWaitlistFilters($query, &$dateFrom, &$dateTo, $status, $vehicleid): void
    {
        if (!empty($dateFrom)) {
            try {
                $dateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
                $query->where('Waitlist.created', '>=', $dateFrom);
            } catch (\Exception $e) {
                $dateFrom = '';
            }
        }

        if (!empty($dateTo)) {
            try {
                $dateTo = Carbon::parse($dateTo)->format('Y-m-d');
                $query->where('Waitlist.created', '<=', $dateTo . ' 23:59:59');
            } catch (\Exception $e) {
                $dateTo = '';
            }
        }

        if (!empty($status)) {
            if ($status === 'cancel') {
                $query->where('Waitlist.status', 0);
            } elseif ($status === 'active') {
                $query->where('Waitlist.status', 1);
            }
        }

        if (!empty($vehicleid)) {
            $query->where('Waitlist.vehicle_id', (int) $vehicleid);
        }
    }

    protected function resolveLimit(Request $request, string $sessionKey): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put($sessionKey, $lim);
                return $lim;
            }
        }
        $sess = (int) session()->get($sessionKey, 0);

        return in_array($sess, $allowed, true) ? $sess : 25;
    }
}
