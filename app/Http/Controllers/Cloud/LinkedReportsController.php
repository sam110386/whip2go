<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\RespondsWithCustomerAutocomplete;
use App\Models\Legacy\AdminUserAssociation;
use App\Support\BookingReportDetailPresenter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkedReportsController extends LegacyAppController
{
    use RespondsWithCustomerAutocomplete;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_vehicle(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/reports/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $dealerIds = $this->dealerIds((int) ($admin['parent_id'] ?? 0));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $limit = $this->resolveLinkedReportsLimit($request);

        $dfBound = $this->dateBoundStart($dateFrom);
        $dtBound = $this->dateBoundEnd($dateTo);

        $ids = $dealerIds === [] ? [0] : $dealerIds;

        $q = DB::table('vehicles as v')
            ->whereIn('v.user_id', $ids)
            ->leftJoin('cs_orders as o', function ($join) use ($dfBound, $dtBound) {
                $join->on('o.vehicle_id', '=', 'v.id')->where('o.status', '=', 3);
                if ($dfBound !== null) {
                    $join->where('o.end_datetime', '>=', $dfBound);
                }
                if ($dtBound !== null) {
                    $join->where('o.end_datetime', '<=', $dtBound);
                }
            })
            ->groupBy('v.id', 'v.vehicle_name')
            ->selectRaw(
                'v.id, v.vehicle_name, ' .
                'SUM(o.rent) as totalrent, SUM(o.end_odometer) as mileage, ' .
                'SUM(DATEDIFF(o.end_datetime, o.start_datetime)) as totaldays'
            );

        $reportlists = $q->orderByDesc('v.id')->paginate($limit)->withQueryString();

        return view('admin.linked_reports.vehicle', [
            'reportlists' => $reportlists,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => $limit,
        ]);
    }

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/reports/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $parentId = (int) ($admin['parent_id'] ?? 0);
        $dealerIds = $this->dealerIds($parentId);
        $dealers = $this->loadDealerNameMap($parentId);

        $keyword = trim((string) $this->searchInput($request, 'keyword'));
        $fieldname = trim((string) $this->searchInput($request, 'searchin'));
        $statusType = trim((string) $this->searchInput($request, 'status_type'));
        $dealerId = trim((string) $this->searchInput($request, 'dealer_id'));
        $renterId = trim((string) $this->searchInput($request, 'renter_id'));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));

        if ($dateFrom !== '' && $dateTo === '') {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $exportQ = $this->linkedReportsOrdersBaseQuery(
            $dealerIds,
            $dealerId,
            $keyword,
            $fieldname,
            $statusType,
            $renterId,
            $dateFrom,
            $dateTo,
            false
        );

        if ($request->input('search') === 'EXPORT') {
            return $this->streamLinkedBookingExport($exportQ);
        }

        $limit = $this->resolveLinkedReportsLimit($request);
        $listQ = $this->linkedReportsOrdersBaseQuery(
            $dealerIds,
            $dealerId,
            $keyword,
            $fieldname,
            $statusType,
            $renterId,
            $dateFrom,
            $dateTo,
            true
        );

        $reportlists = $listQ->orderByDesc('o.id')->paginate($limit)->withQueryString();
        $reportlists->appends($request->query());
        $rollups = $this->loadChildRollupsForOrders($reportlists->getCollection());

        if ($request->ajax()) {
            return response()->view('admin.linked_reports._listing', [
                'reportlists' => $reportlists,
                'rollups' => $rollups,
            ]);
        }

        return view('admin.linked_reports.index', [
            'reportlists' => $reportlists,
            'rollups' => $rollups,
            'dealers' => $dealers,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'status_type' => $statusType,
            'dealer_id' => $dealerId,
            'renter_id' => $renterId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
        ]);
    }

    public function cloud_details(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIds((int) ($admin['parent_id'] ?? 0));

        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return response('<p>Invalid booking.</p>', 400);
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order || !in_array((int) $order->user_id, $dealerIds, true)) {
            return response('<p>Booking not found.</p>', 404);
        }

        $payload = BookingReportDetailPresenter::buildSingle($orderId);
        if ($payload === null) {
            return response('<p>Booking not found.</p>', 404);
        }

        return response()->view('reports._booking_details_full', $payload);
    }

    public function cloud_loadsubbooking(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIds((int) ($admin['parent_id'] ?? 0));

        $id = $this->decodeId((string) $orderid);
        if (!$id) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $ids = $dealerIds === [] ? [0] : $dealerIds;

        $rows = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->whereIn('o.user_id', $ids)
            ->where(function ($q) use ($id) {
                $q->where('o.id', $id)->orWhere('o.parent_id', $id);
            })
            ->orderByDesc('o.id')
            ->select(['o.*', 'u.first_name', 'u.last_name'])
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, no record found']);
        }

        $html = view('reports._loadsubbooking_rows', [
            'rows' => $rows,
            'bookingId' => $id,
        ])->render();

        return response()->json([
            'status' => 'success',
            'booking_id' => $id,
            'data' => $html,
        ]);
    }

    public function cloud_autorenewddetails(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIds((int) ($admin['parent_id'] ?? 0));

        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return response('<p>Invalid booking.</p>', 400);
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order || !in_array((int) $order->user_id, $dealerIds, true)) {
            return response('<p>Booking not found.</p>', 404);
        }

        $payload = BookingReportDetailPresenter::buildAutoRenew($orderId);
        if ($payload === null) {
            return response('<p>Booking not found.</p>', 404);
        }

        return response()->view('reports._booking_details_full', $payload);
    }

    public function cloud_productivity(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/reports/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $parentId = (int) ($admin['parent_id'] ?? 0);
        $dealerIds = $this->dealerIds($parentId);
        $dealers = $this->loadDealerFirstNameMap($parentId);

        $userId = trim((string) $this->searchInput($request, 'user_id'));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $limit = $this->resolveLinkedReportsLimit($request);

        $vehicleQuery = $this->linkedProductivityVehicleQuery($dealerIds, $userId, $dateFrom, $dateTo);

        if ($request->input('search') === 'EXPORT') {
            return $this->streamLinkedProductivityExport(
                (clone $vehicleQuery)->orderByDesc('v.id'),
                $dateFrom,
                $dateTo
            );
        }

        $reportlists = $vehicleQuery->orderByDesc('v.id')->paginate($limit)->withQueryString();
        $reportlists->appends($request->query());

        $dfCarbon = $dateFrom !== '' ? $this->parseLinkedReportDate($dateFrom) : null;
        $dtCarbon = $dateTo !== '' ? $this->parseLinkedReportDate($dateTo) : null;
        $vehicleDepreciation = [];
        foreach ($reportlists->getCollection() as $row) {
            $vid = (int) ($row->vehicle_id ?? 0);
            if ($vid > 0) {
                $vehicleDepreciation[$vid] = $this->vehicleDepreciationTotal($vid, $dfCarbon, $dtCarbon);
            }
        }

        return view('admin.linked_reports.productivity', [
            'reportlists' => $reportlists,
            'dealers' => $dealers,
            'user_id' => $userId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
            'vehicleDepreciation' => $vehicleDepreciation,
        ]);
    }

    public function cloud_customerautocomplete(Request $request)
    {
        return $this->respondCustomerAutocomplete($request, 'cloud');
    }

    private function dealerIds(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        return AdminUserAssociation::query()
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int)$id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function decodeId(string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }

    private function resolveLinkedReportsLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['linked_reports_limit' => $lim]);
            }
        }
        $limit = (int) session('linked_reports_limit', 50);

        return $limit > 0 ? $limit : 50;
    }

    private function resolveVehicleLimit(Request $request): int
    {
        return $this->resolveLinkedReportsLimit($request);
    }

    /**
     * @return array<int, string>
     */
    private function loadDealerNameMap(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }
        $rows = DB::table('admin_user_associations as aua')
            ->join('users as u', 'u.id', '=', 'aua.user_id')
            ->where('aua.admin_id', $parentId)
            ->where('u.is_dealer', 1)
            ->orderBy('u.first_name')
            ->select(['u.id', 'u.first_name', 'u.last_name'])
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->id] = trim((string) ($r->first_name ?? '') . ' ' . (string) ($r->last_name ?? ''));
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    private function loadDealerFirstNameMap(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }
        $rows = DB::table('admin_user_associations as aua')
            ->join('users as u', 'u.id', '=', 'aua.user_id')
            ->where('aua.admin_id', $parentId)
            ->where('u.is_dealer', 1)
            ->orderBy('u.first_name')
            ->select(['u.id', 'u.first_name'])
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->id] = (string) ($r->first_name ?? '');
        }

        return $map;
    }

    private function parseLinkedReportDate(string $value): ?Carbon
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

    private function linkedReportsOrdersBaseQuery(
        array $dealerIds,
        string $dealerId,
        string $keyword,
        string $fieldname,
        string $statusType,
        string $renterId,
        string $dateFrom,
        string $dateTo,
        bool $parentRootsOnly
    ): Builder {
        $ids = $dealerIds === [] ? [0] : $dealerIds;

        $q = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->select([
                'o.*',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'owner.business_name as owner_business_name',
            ]);

        if ($dealerId !== '' && ctype_digit($dealerId)) {
            $did = (int) $dealerId;
            if (in_array($did, $ids, true)) {
                $q->where('o.user_id', $did);
            } else {
                $q->whereRaw('1 = 0');
            }
        } else {
            $q->whereIn('o.user_id', $ids);
        }

        if ($parentRootsOnly) {
            $q->where('o.parent_id', 0);
        }

        if ($keyword !== '' && $fieldname !== '') {
            if ($fieldname === '1') {
                $q->where('o.pickup_address', 'like', '%' . $keyword . '%');
            } elseif ($fieldname === '2') {
                $q->where('o.vehicle_name', $keyword);
            } elseif ($fieldname === '3') {
                $q->where('o.increment_id', $keyword);
            }
        }

        $df = $dateFrom !== '' ? $this->parseLinkedReportDate($dateFrom) : null;
        $dt = $dateTo !== '' ? $this->parseLinkedReportDate($dateTo) : null;
        if ($df) {
            $q->where('o.start_datetime', '>=', $df->copy()->startOfDay()->toDateTimeString());
        }
        if ($dt) {
            $q->where('o.end_datetime', '<=', $dt->copy()->endOfDay()->toDateTimeString());
        }

        if ($statusType === 'cancel') {
            $q->where('o.status', 2);
        } elseif ($statusType === 'complete') {
            $q->where('o.status', 3);
        } elseif ($statusType === 'incomplete') {
            $q->whereIn('o.status', [0, 1]);
        }

        if ($renterId !== '' && ctype_digit($renterId)) {
            $q->where('o.renter_id', (int) $renterId);
        }

        return $q;
    }

    private function streamLinkedBookingExport(Builder $baseQ): StreamedResponse
    {
        $rows = (clone $baseQ)
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
                'renter.first_name',
                'renter.last_name',
                'v.make',
                'v.model',
                'v.year',
                'v.vin_no',
                'owner.business_name',
            ]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Booking No', 'Duration', 'Total Rental', 'Mileage', 'Insurance', 'Type', 'Car Info', 'VIN Number', 'Start Date', 'End Date', 'Owner Name', 'Driver Name', 'DIA Commission', 'Late Fee Share', 'Late Insurance', 'City Tax', 'Tourism Surcharge', 'City', 'Amount To Owner']);
            $i = 1;
            foreach ($rows as $r) {
                $driver = trim((string) (($r->first_name ?? '') . ' ' . ($r->last_name ?? '')));
                $car = trim(implode(' ', array_filter([$r->make ?? '', $r->model ?? '', $r->year ?? ''])));
                $rent = (float) ($r->rent ?? 0);
                $tz = $r->timezone ?? null;
                $start = $this->formatUserDate($r->start_datetime ?? null, $tz);
                $end = $this->formatUserDate($r->end_datetime ?? null, $tz);
                $days = $this->daysBetween($r->start_datetime ?? null, $r->end_datetime ?? null);
                fputcsv($out, [
                    $i++,
                    $r->increment_id ?? '',
                    $days,
                    $r->rent ?? '',
                    $r->end_odometer ?? '',
                    $r->insurance_amt ?? '',
                    ($r->parent_id ?? 0) ? 'Extended' : '',
                    $car,
                    $r->vin_no ?? '',
                    $start,
                    $end,
                    $r->business_name ?? '',
                    $driver,
                    sprintf('%0.2f', $rent * 15 / 100),
                    '0.0',
                    '0.0',
                    '3.43',
                    '2',
                    $r->pickup_address ?? '',
                    sprintf('%0.2f', $rent * 85 / 100),
                ]);
            }
            fclose($out);
        }, 'Booking_Report.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $orders
     * @return array<int, object>
     */
    private function loadChildRollupsForOrders($orders): array
    {
        $out = [];
        foreach ($orders as $o) {
            $id = (int) ($o->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (!((int) ($o->auto_renew ?? 0)) && !((int) ($o->pto ?? 0))) {
                continue;
            }
            if (isset($out[$id])) {
                continue;
            }
            $row = $this->loadCompletedSubtreeRollup($id);
            if ($row) {
                $out[$id] = $row;
            }
        }

        return $out;
    }

    private function loadCompletedSubtreeRollup(int $bookingId): ?object
    {
        return DB::table('cs_orders')
            ->where('status', 3)
            ->where(function ($q) use ($bookingId) {
                $q->where('parent_id', $bookingId)->orWhere('id', $bookingId);
            })
            ->selectRaw(
                'MAX(end_datetime) as end_datetime, ' .
                'SUM(rent+initial_fee+extra_mileage_fee+damage_fee+uncleanness_fee) as paid_amount, ' .
                'SUM(insurance_amt+dia_insu) as insurance, ' .
                'SUM(toll+pending_toll) as toll, ' .
                'SUM(end_odometer-start_odometer) as mileage, ' .
                'SUM(dia_fee) as dia_fee, ' .
                'SUM(extra_mileage_fee) as extra_mileage_fee, ' .
                'SUM(damage_fee) as damage_fee, ' .
                'SUM(lateness_fee) as lateness_fee, ' .
                'SUM(uncleanness_fee) as uncleanness_fee'
            )
            ->first();
    }

    private function linkedProductivityVehicleQuery(array $dealerIds, string $userId, string $dateFrom, string $dateTo): Builder
    {
        $ids = $dealerIds === [] ? [0] : $dealerIds;

        $q = DB::table('vehicles as v')
            ->leftJoin('cs_orders as o', function ($join) {
                $join->on('o.vehicle_id', '=', 'v.id')->where('o.status', '=', 3);
            });

        $df = $dateFrom !== '' ? $this->parseLinkedReportDate($dateFrom) : null;
        $dt = $dateTo !== '' ? $this->parseLinkedReportDate($dateTo) : null;
        if ($df !== null) {
            $q->where('o.end_datetime', '>=', $df->format('Y-m-d 00:00:00'));
        }
        if ($dt !== null) {
            $q->where('o.end_datetime', '<=', $dt->format('Y-m-d 23:59:59'));
        }

        if ($userId !== '' && ctype_digit($userId)) {
            $uid = (int) $userId;
            if (in_array($uid, $ids, true)) {
                $q->where('v.user_id', $uid);
            } else {
                $q->whereRaw('1 = 0');
            }
        } else {
            $q->whereIn('v.user_id', $ids);
        }

        return $q->groupBy('v.id', 'v.vehicle_name', 'v.msrp')
            ->selectRaw(
                'v.id as vehicle_id, v.vehicle_name, v.msrp, ' .
                'SUM(o.rent+o.tax+o.initial_fee+o.extra_mileage_fee+o.damage_fee+o.lateness_fee+o.uncleanness_fee) as totalrent, ' .
                'SUM(o.end_odometer - o.start_odometer) as mileage, ' .
                'SUM(DATEDIFF(o.end_datetime, o.start_datetime)) as totaldays, ' .
                'SUM(o.extra_mileage_fee) as extra_mileage_fee'
            );
    }

    private function streamLinkedProductivityExport(Builder $vehicleQuery, string $dateFrom, string $dateTo): StreamedResponse
    {
        $df = $dateFrom !== '' ? $this->parseLinkedReportDate($dateFrom) : null;
        $dt = $dateTo !== '' ? $this->parseLinkedReportDate($dateTo) : null;
        $rows = (clone $vehicleQuery)->limit(5000)->get();

        return response()->streamDownload(function () use ($rows, $df, $dt) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Vhicle', 'Vehicle Cost', 'Depreciation', 'Base Rent ($)', 'Mileage Fee', 'Total Mileage', 'Total Days']);
            foreach ($rows as $r) {
                $vid = (int) ($r->vehicle_id ?? 0);
                $dep = $this->vehicleDepreciationTotal($vid, $df, $dt);
                fputcsv($out, [
                    $r->vehicle_name ?? '',
                    $r->msrp ?? '',
                    $dep,
                    $r->totalrent ?? 0,
                    $r->extra_mileage_fee ?? 0,
                    $r->mileage ?? 0,
                    $r->totaldays ?? 0,
                ]);
            }
            fclose($out);
        }, 'Productivity_Report.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    private function vehicleDepreciationTotal(int $vehicleId, ?Carbon $dateFrom, ?Carbon $dateTo): float
    {
        if ($vehicleId <= 0 || !Schema::hasTable('cs_vehicle_expenses')) {
            return 0.0;
        }
        $q = DB::table('cs_vehicle_expenses')
            ->where('vehicle_id', $vehicleId)
            ->where('type', 1);
        if ($dateFrom !== null) {
            $q->where('created', '>=', $dateFrom->format('Y-m-d 00:00:00'));
        }
        if ($dateTo !== null) {
            $q->where('created', '<=', $dateTo->format('Y-m-d 23:59:59'));
        }

        return (float) $q->sum('amount');
    }

    private function formatUserDate(?string $datetime, ?string $timezone): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        try {
            $tz = $timezone ?: config('app.timezone');

            return Carbon::parse($datetime)->timezone($tz)->format('m/d/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function daysBetween(?string $start, ?string $end): int
    {
        if ($start === null || $end === null || $start === '' || $end === '') {
            return 0;
        }
        try {
            $a = Carbon::parse($start)->startOfDay();
            $b = Carbon::parse($end)->startOfDay();

            return (int) abs($a->diffInDays($b));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function dateBoundStart(string $dateFrom): ?string
    {
        if (trim($dateFrom) === '') {
            return null;
        }
        try {
            return Carbon::parse($dateFrom)->format('Y-m-d 00:00:00');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function dateBoundEnd(string $dateTo): ?string
    {
        if (trim($dateTo) === '') {
            return null;
        }
        try {
            return Carbon::parse($dateTo)->format('Y-m-d 23:59:59');
        } catch (\Throwable $e) {
            return null;
        }
    }
}

