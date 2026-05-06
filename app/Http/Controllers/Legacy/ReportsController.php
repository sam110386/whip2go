<?php

namespace App\Http\Controllers\Legacy;

use App\Support\BookingReportDetailPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CakePHP `ReportsController` — dealer session (`/reports/...`).
 */
class ReportsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $limit = $this->resolveLimit($request, 'reports_limit');
        $keyword = trim((string) $this->searchInput($request, 'keyword'));
        $fieldname = trim((string) $this->searchInput($request, 'searchin'));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $statusType = trim((string) $this->searchInput($request, 'status_type'));

        if ($dateFrom !== '' && $dateTo === '') {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $q = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.user_id', $userId)
            ->where('o.parent_id', 0)
            ->select([
                'o.*',
                'u.first_name',
                'u.last_name',
            ]);

        if ($keyword !== '' && $fieldname !== '') {
            if ($fieldname === '1') {
                $q->where('o.pickup_address', 'like', '%' . $keyword . '%');
            } elseif ($fieldname === '2') {
                $q->where('o.vehicle_name', $keyword);
            } elseif ($fieldname === '3') {
                $q->where('o.increment_id', $keyword);
            }
        }

        if ($dateFrom !== '') {
            $df = $this->parseReportDate($dateFrom);
            if ($df) {
                $q->where('o.start_datetime', '>=', $df->startOfDay()->toDateTimeString());
            }
        }
        if ($dateTo !== '') {
            $dt = $this->parseReportDate($dateTo);
            if ($dt) {
                $q->where('o.end_datetime', '<=', $dt->endOfDay()->toDateTimeString());
            }
        }

        if ($statusType === 'cancel') {
            $q->where('o.status', 2);
        } elseif ($statusType === 'complete') {
            $q->where('o.status', 3);
        } elseif ($statusType === 'incomplete') {
            $q->whereIn('o.status', [0, 1]);
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->exportBookingsCsv($q, $userId);
        }

        $allowedSorts = ['increment_id', 'start_datetime', 'end_datetime'];
        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedSorts)) {
            $q->orderBy('o.' . $sort, $direction);
        } else {
            $q->orderByDesc('o.id');
        }

        $reportlists = $q->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return response()->view('reports.elements.index', [
                'reportlists' => $reportlists,
                'prefix' => 'reports',
            ]);
        }

        return view('reports.index', [
            'title_for_layout' => 'Reports',
            'reportlists' => $reportlists,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_type' => $statusType,
            'limit' => $limit,
        ]);
    }

    public function vehicle(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $limit = $this->resolveLimit($request, 'reports_limit');
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));

        if ($request->input('search') === 'EXPORT') {
            return $this->exportVehicleCsv($userId, $dateFrom, $dateTo);
        }

        $q = $this->vehicleProductivityQuery($userId, $dateFrom, $dateTo);

        $allowedSorts = ['vehicle_name', 'msrp', 'totalrent', 'mileage', 'totaldays', 'extra_mileage_fee'];
        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedSorts)) {
            if (in_array($sort, ['vehicle_name', 'msrp'])) {
                $q->orderBy('v.' . $sort, $direction);
            } else {
                $q->orderBy($sort, $direction);
            }
        } else {
            $q->orderByDesc('v.id');
        }

        $reportlists = $q->paginate($limit)->withQueryString();

        return view('reports.vehicle', [
            'title_for_layout' => 'Fleet Productivity',
            'reportlists' => $reportlists,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
        ]);
    }

    public function details(Request $request, ?string $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeTripId($id ?? '');
        if (!$orderId) {
            return response('<p>Invalid booking.</p>', 400);
        }

        $userId = $this->effectiveUserId();
        $owns = DB::table('cs_orders')->where('id', $orderId)->where('user_id', $userId)->exists();
        if (!$owns) {
            return response('<p>Booking not found.</p>', 404);
        }

        return $this->_details($orderId);
    }

    public function autorenewddetails(Request $request, ?string $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeTripId($id ?? '');
        if (!$orderId) {
            return response('<p>Invalid booking.</p>', 400);
        }

        $userId = $this->effectiveUserId();
        $owns = DB::table('cs_orders')->where('id', $orderId)->where('user_id', $userId)->exists();
        if (!$owns) {
            return response('<p>Booking not found.</p>', 404);
        }

        return $this->_autorenewddetails($orderId);
    }

    public function loadsubbooking(Request $request, ?string $orderid = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $oid = (int) $orderid;
        if ($oid <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $subbookinglists = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.user_id', $userId)
            ->where(function ($q) use ($oid) {
                $q->where('o.id', $oid)->orWhere('o.parent_id', $oid);
            })
            ->orderByDesc('o.id')
            ->select(['o.*', 'u.first_name', 'u.last_name'])
            ->get()
            ->map(function ($item) {
                return ['CsOrder' => (array) $item, 'User' => ['first_name' => $item->first_name, 'last_name' => $item->last_name]];
            })->toArray();

        if (empty($subbookinglists)) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, no record found']);
        }

        $booking_id = $oid;
        $html = view('reports.elements.loadsubbooking', compact('subbookinglists', 'booking_id'))->render();

        return response()->json([
            'status' => 'success',
            'booking_id' => $oid,
            'data' => $html,
        ]);
    }

    public function paymentspopup(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $raw = (string) $request->input('orderid', '');
        $orderId = $this->decodeTripId($raw);
        if (!$orderId) {
            return response()->json(['status' => 'error', 'data' => '<p>Invalid booking.</p>']);
        }

        $userId = $this->effectiveUserId();
        $owns = DB::table('cs_orders')->where('id', $orderId)->where('user_id', $userId)->exists();
        if (!$owns) {
            return response()->json(['status' => 'error', 'data' => '<p>Not authorized.</p>']);
        }

        $payments = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $paymentTypeValue = app(\App\Services\Legacy\Common::class)->getPayoutTypeValue(true);
        $html = view('reports._paymentspopup', compact('payments', 'paymentTypeValue'))->render();

        return response()->json(['status' => 'success', 'data' => $html]);
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent > 0 ? $parent : (int) session()->get('userid', 0);
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string) $v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session([$sessionKey => $lim]);
            }
        }
        $limit = (int) session($sessionKey, 50);

        return $limit > 0 ? $limit : 50;
    }

    private function decodeTripId(string $id): ?int
    {
        if ($id === '') {
            return null;
        }
        if (ctype_digit($id)) {
            return (int) $id;
        }
        $decoded = base64_decode($id, true);
        if ($decoded !== false && ctype_digit($decoded)) {
            return (int) $decoded;
        }

        return null;
    }

    private function parseReportDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->startOfDay();
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q  already filtered query
     */
    private function exportBookingsCsv($q, int $userId): StreamedResponse
    {
        $rows = (clone $q)
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->orderBy('o.id')
            ->limit(5000)
            ->get([
                'o.id',
                'o.increment_id',
                'o.parent_id',
                'o.rent',
                'o.timezone',
                'o.start_datetime',
                'o.end_datetime',
                'o.pickup_address',
                'o.end_odometer',
                'o.insurance_amt',
                'u.first_name',
                'u.last_name',
                'v.make',
                'v.model',
                'v.year',
                'v.vin_no',
                'owner.business_name',
            ]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Booking No', 'Duration', 'Total Rental', 'Mileage', 'Insurance', 'Type', 'Car Info', 'VIN', 'Start', 'End', 'Owner', 'Driver']);
            $i = 1;
            foreach ($rows as $r) {
                $driver = trim((string) (($r->first_name ?? '') . ' ' . ($r->last_name ?? '')));
                $car = trim(implode(' ', array_filter([$r->make ?? '', $r->model ?? '', $r->year ?? ''])));
                fputcsv($out, [
                    $i++,
                    $r->increment_id ?? '',
                    '',
                    $r->rent ?? '',
                    $r->end_odometer ?? '',
                    $r->insurance_amt ?? '',
                    ($r->parent_id ?? 0) ? 'Extended' : '',
                    $car,
                    $r->vin_no ?? '',
                    $r->start_datetime ?? '',
                    $r->end_datetime ?? '',
                    $r->business_name ?? '',
                    $driver,
                ]);
            }
            fclose($out);
        }, 'Booking_Report.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    private function vehicleProductivityQuery(int $userId, string $dateFrom, string $dateTo)
    {
        $df = $dateFrom !== '' ? $this->parseReportDate($dateFrom) : null;
        $dt = $dateTo !== '' ? $this->parseReportDate($dateTo) : null;
        $dfBound = $df ? $df->format('Y-m-d 00:00:00') : null;
        $dtBound = $dt ? $dt->format('Y-m-d 23:59:59') : null;

        $q = DB::table('vehicles as v')
            ->leftJoin('cs_orders as o', function ($join) use ($dfBound, $dtBound) {
                $join->on('o.vehicle_id', '=', 'v.id')->where('o.status', '=', 3);
                if ($dfBound !== null) {
                    $join->where('o.end_datetime', '>=', $dfBound);
                }
                if ($dtBound !== null) {
                    $join->where('o.end_datetime', '<=', $dtBound);
                }
            })
            ->where('v.user_id', $userId);

        return $q->groupBy('v.id', 'v.vehicle_name', 'v.msrp')
            ->selectRaw(
                'v.id, v.vehicle_name, v.msrp, ' .
                'SUM(o.rent + o.initial_fee + o.damage_fee + o.uncleanness_fee) as totalrent, ' .
                'SUM(o.end_odometer - o.start_odometer) as mileage, ' .
                'SUM(DATEDIFF(o.end_datetime, o.start_datetime)) as totaldays, ' .
                'SUM(o.extra_mileage_fee) as extra_mileage_fee'
            );
    }

    private function exportVehicleCsv(int $userId, string $dateFrom, string $dateTo): StreamedResponse
    {
        $rows = $this->vehicleProductivityQuery($userId, $dateFrom, $dateTo)->orderByDesc('v.id')->limit(5000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Vehicle', 'MSRP', 'Base usage', 'Extra usage', 'Total usage', 'Mileage', 'Days']);
            foreach ($rows as $r) {
                $base = (float) ($r->totalrent ?? 0);
                $extra = (float) ($r->extra_mileage_fee ?? 0);
                fputcsv($out, [
                    $r->vehicle_name ?? '',
                    $r->msrp ?? '',
                    number_format($base, 2, '.', ''),
                    $extra,
                    number_format($base + $extra, 2, '.', ''),
                    $r->mileage ?? 0,
                    $r->totaldays ?? 0,
                ]);
            }
            fclose($out);
        }, 'Fleet_Productivity.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
