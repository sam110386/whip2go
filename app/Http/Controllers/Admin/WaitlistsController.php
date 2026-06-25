<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\Waitlist;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class WaitlistsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Waitlist Leads';
        $sessionLimitKey = 'waitlist_limit';

        $date_from = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', ''));
        $status = $request->input('Search.status', $request->query('status', ''));
        $vehicleid = $request->input('Search.vehicle_id', $request->query('vehicle_id', ''));

        $query = Waitlist::with([
            'user:id,first_name,last_name,address,state',
            'vehicle:id,vehicle_name'
        ]);

        if (!empty($date_from)) {
            $formattedDateFrom = Carbon::parse($date_from)->toDateString();
            $query->where('created', '>=', $formattedDateFrom);
        }

        if (!empty($date_to)) {
            $formattedDateTo = Carbon::parse($date_to)->toDateString();
            $query->where('created', '<=', $formattedDateTo);
        }

        if (!empty($status)) {
            if ($status === 'cancel') {
                $query->where('status', 0);
            } elseif ($status === 'active') {
                $query->where('status', 1);
            }
        }

        if (!empty($vehicleid)) {
            $query->where('vehicle_id', $vehicleid);
        }

        $query->orderBy('id', 'desc');

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $request->merge(['Record' => ['limit' => $limit]]);
        $records = $query->paginate($limit);

        if ($request->ajax()) {
            return view('admin.waitlists.elements.index', compact('date_from', 'date_to', 'status', 'vehicleid', 'records', 'limit'));
        }

        return view('admin.waitlists.index', compact('title', 'date_from', 'date_to', 'status', 'vehicleid', 'records', 'limit'));
    }
}
