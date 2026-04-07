<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportRentersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (Paginated order reports grouped by renter) ─────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->set('title_for_layout', 'Renter Reports');
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $fieldname  = $request->input('Search.searchin', $request->query('searchin', ''));
        $keyword    = $request->input('Search.keyword', $request->query('keyword', ''));
        $date_from  = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to    = $request->input('Search.date_to', $request->query('date_to', ''));
        $status_type = $request->input('Search.status_type', $request->query('status_type', ''));

        if (!empty($date_from) && empty($date_to)) {
            $date_to = Carbon::now()->format('Y-m-d');
        }

        $query = CsOrder::query()
            ->from('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->where('CsOrder.user_id', $userid)
            ->where('CsOrder.status', 3) // Defaults to complete
            ->selectRaw('count(CsOrder.id) as totalbooking, CsOrder.id, User.id as renter_id, User.first_name, User.last_name, User.contact_number')
            ->groupBy('CsOrder.renter_id');

        if (!empty($keyword)) {
            $v = strip_tags($keyword);
            if ($fieldname == '1') {
                $query->where(fn($q) => $q->where('User.first_name', 'LIKE', "%$v%")->orWhere('User.last_name', 'LIKE', "%$v%"));
            } elseif ($fieldname == '2') {
                $query->where('CsOrder.vehicle_name', $v);
            } elseif ($fieldname == '3') {
                $query->where('CsOrder.id', $v);
            } elseif ($fieldname == '4') {
                $query->where('User.contact_number', 'LIKE', "%$v%");
            }
        }

        if (!empty($date_from)) {
            $query->where('CsOrder.start_datetime', '>=', $date_from);
        }
        if (!empty($date_to)) {
            $query->where('CsOrder.end_datetime', '<=', $date_to);
        }

        if (!empty($status_type)) {
            if ($status_type == 'cancel')     $query->where('CsOrder.status', 2);
            if ($status_type == 'complete')   $query->where('CsOrder.status', 3);
            if ($status_type == 'incomplete') $query->where('CsOrder.status', '!=', 3);
        }

        $sessionLimitKey  = 'ReportRenters_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $reportlists = $query->orderBy('CsOrder.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.report_renters.index', [
            'keyword'     => $keyword,
            'fieldname'   => $fieldname,
            'date_from'   => $date_from,
            'date_to'     => $date_to,
            'status_type' => $status_type,
            'reportlists' => $reportlists,
        ]);
    }

    // ─── details (Basic renter/order details) ──────────────────────────────────
    public function details(Request $request, $id)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $id = base64_decode($id);
        $csorder = null;

        if (!empty($id)) {
            $csorder = CsOrder::from('cs_orders as CsOrder')
                ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
                ->where('CsOrder.id', $id)
                ->select('CsOrder.*', 'User.first_name', 'User.last_name', 'User.contact_number')
                ->first();
        }

        return view('legacy.report_renters.details', compact('csorder'));
    }

    // ─── history (AJAX rental history for a specific renter) ──────────────────────
    public function history(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $renterid = base64_decode($request->input('renterid', ''));
        $bookings = collect();

        if (!empty($renterid)) {
            $bookings = CsOrder::from('cs_orders as CsOrder')
                ->leftJoin('vehicles as Vehicle', 'CsOrder.vehicle_id', '=', 'Vehicle.id')
                ->where('CsOrder.user_id', $userid)
                ->where('CsOrder.status', '!=', 1)
                ->where('CsOrder.renter_id', $renterid)
                ->select('CsOrder.*', 'Vehicle.vehicle_unique_id')
                ->orderBy('CsOrder.id', 'DESC')
                ->limit(200)
                ->get();
        }

        return view('legacy.report_renters.history', compact('bookings'));
    }
}
