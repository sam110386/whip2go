<?php

namespace App\Http\Controllers\Legacy;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * CakePHP `LeaseHistoriesController` — dealer session (`/lease_histories/...`).
 *
 * Listing uses `cs_lease_availabilities`; lease status/detail/edit use `cs_leases` (Cake `Lease` / `CsLease`).
 */
class LeaseHistoriesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $limit = $this->resolveLimit($request, 'lease_histories_limit');

        $keyword = trim((string) $this->searchInput($request, 'keyword'));
        $fieldname = trim((string) $this->searchInput($request, 'searchin'));
        $addressType = trim((string) $this->searchInput($request, 'show_address'));
        if ($addressType === '') {
            $addressType = trim((string) $request->input('type', ''));
        }
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $statusType = trim((string) $this->searchInput($request, 'status_type'));
        $paymentMethod = trim((string) $this->searchInput($request, 'payment_method'));

        if ($dateFrom !== '' && $dateTo === '') {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $q = DB::table('cs_lease_availabilities as a')
            ->leftJoin('cs_leases as l', 'l.id', '=', 'a.lease_id')
            ->where('a.user_id', $userId)
            ->select(['a.*']);

        // Legacy parity: non-empty `searchin` was passed through strtotime as a date filter.
        if ($fieldname !== '' && !in_array($fieldname, ['1', '2', '3', '4'], true)) {
            $parsed = $this->parseFlexibleDate($fieldname);
            if ($parsed) {
                $q->whereDate('a.start_date', '=', $parsed->format('Y-m-d'));
            }
        }

        if ($keyword !== '') {
            $escaped = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($addressType === '1') {
                $q->where('a.pickup_address', 'like', $escaped);
            } elseif ($addressType === '2') {
                $q->where('l.vehicle_unique_id', '=', $keyword);
            } elseif ($addressType === '3') {
                $q->where('a.id', '=', ctype_digit($keyword) ? (int) $keyword : $keyword);
            } elseif ($addressType === '4' && Schema::hasColumn('cs_lease_availabilities', 'telephone')) {
                $q->where('a.telephone', '=', $keyword);
            }
        }

        $df = $dateFrom !== '' ? $this->parseFlexibleDate($dateFrom) : null;
        if ($df) {
            $q->where('a.start_date', '>=', $df->format('Y-m-d'));
        }
        $dt = $dateTo !== '' ? $this->parseFlexibleDate($dateTo) : null;
        if ($dt) {
            $q->where('a.start_date', '<=', $dt->format('Y-m-d'));
        }

        if ($statusType === 'cancel') {
            $q->where('a.status', 2);
        } elseif ($statusType === 'complete') {
            $q->where('a.status', 3);
        } elseif ($statusType === 'incomplete') {
            $q->where('a.status', '!=', 3);
        }

        if ($paymentMethod !== '' && Schema::hasColumn('cs_lease_availabilities', 'payment_method')) {
            $q->where('a.payment_method', $paymentMethod);
        }

        $triploglist = $q->orderByDesc('a.id')->paginate($limit)->withQueryString();

        return view('lease_histories.index', [
            'title_for_layout' => 'Reports',
            'keyword' => $keyword,
            'payment_method' => $paymentMethod,
            'fieldname' => $fieldname,
            'address_type' => $addressType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_type' => $statusType,
            'triploglist' => $triploglist,
            'limit' => $limit,
        ]);
    }

    public function cancel_lease(Request $request, ?string $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $leaseId = $this->decodeId((string) $id);
        if (!$leaseId || !$this->userOwnsLeaseAvailability($leaseId, $this->effectiveUserId())) {
            return redirect('/lease_histories/index')->with('error', 'Invalid lease or not authorized.');
        }

        DB::table('cs_leases')->where('id', $leaseId)->update([
            'status' => 2,
            'modified' => now()->toDateTimeString(),
        ]);

        return redirect('/lease_histories/index')->with('success', 'Record updated successfully.');
    }

    public function auto_complete(Request $request, ?string $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $leaseId = $this->decodeId((string) $id);
        if (!$leaseId || !$this->userOwnsLeaseAvailability($leaseId, $this->effectiveUserId())) {
            return redirect('/lease_histories/index')->with('error', 'Invalid lease or not authorized.');
        }

        DB::table('cs_leases')->where('id', $leaseId)->update([
            'status' => 3,
            'modified' => now()->toDateTimeString(),
        ]);

        return redirect('/lease_histories/index')->with('success', 'Lease autocompleted successfully.');
    }

    public function lease_details(?string $id = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $leaseId = $this->decodeId((string) $id);
        if (!$leaseId) {
            abort(404);
        }

        $userId = $this->effectiveUserId();
        if (!$this->userOwnsLeaseAvailability($leaseId, $userId)) {
            abort(403);
        }

        $fareExpr = Schema::hasColumn('cs_leases', 'fare')
            ? '(COALESCE(l.fare, 0) * 0.025) AS black_car_fund'
            : '0 AS black_car_fund';

        $row = DB::table('cs_leases as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.id', $leaseId)
            ->selectRaw('l.*, u.unique_code, ' . $fareExpr)
            ->first();

        if (!$row) {
            abort(404);
        }

        $triplog = $this->formatTriplogRecord($row);

        return view('lease_histories.lease_details', [
            'title_for_layout' => 'Lease Detail',
            'triplog' => $triplog,
        ]);
    }

    public function edit_lease_details(Request $request, ?string $id = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $leaseId = $this->decodeId((string) $id);
        if (!$leaseId) {
            return redirect('/lease_histories/index')->with('error', 'Invalid lease.');
        }

        $userId = $this->effectiveUserId();
        if (!$this->userOwnsLeaseAvailability($leaseId, $userId)) {
            return redirect('/lease_histories/index')->with('error', 'Not authorized.');
        }

        if ($request->isMethod('post')) {
            $payload = $request->input('Lease', []);
            $update = ['modified' => now()->toDateTimeString()];

            if (isset($payload['pickup_address'])) {
                $update['pickup_address'] = (string) $payload['pickup_address'];
            }
            if (isset($payload['details'])) {
                $update['details'] = (string) $payload['details'];
            }
            if (!empty($payload['pickup_date'])) {
                $pd = $this->parseFlexibleDate((string) $payload['pickup_date']);
                if ($pd) {
                    $update['start_date'] = $pd->format('Y-m-d');
                }
            }

            DB::table('cs_leases')->where('id', $leaseId)->update($update);

            return redirect('/lease_histories/index')->with('success', 'Lease record updated successfully.');
        }

        $fareExpr = Schema::hasColumn('cs_leases', 'fare')
            ? '(COALESCE(l.fare, 0) * 0.025) AS black_car_fund'
            : '0 AS black_car_fund';

        $row = DB::table('cs_leases as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.id', $leaseId)
            ->selectRaw('l.*, u.unique_code, ' . $fareExpr)
            ->first();

        if (!$row) {
            return redirect('/lease_histories/index')->with('error', 'Lease not found.');
        }

        $triplog = $this->formatTriplogRecord($row);

        return view('lease_histories.edit_lease_details', [
            'title_for_layout' => 'Update Lease Detail',
            'triplog' => $triplog,
        ]);
    }

    private function userOwnsLeaseAvailability(int $leaseId, int $userId): bool
    {
        return DB::table('cs_lease_availabilities')
            ->where('lease_id', $leaseId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function formatTriplogRecord(object $row): array
    {
        $lease = (array) $row;
        $uniqueCode = (string) ($lease['unique_code'] ?? '');
        unset($lease['unique_code']);

        return [
            'Lease' => array_merge($lease, [
                'car_no' => $lease['vehicle_unique_id'] ?? $lease['vehicle_name'] ?? '',
                'pickup_date' => $lease['start_date'] ?? null,
                'pickup_time' => $lease['pickup_time'] ?? null,
                'pickup_address' => $lease['pickup_address'] ?? null,
            ]),
            'User' => ['unique_code' => $uniqueCode],
        ];
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent > 0 ? $parent : (int) session()->get('userid', 0);
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '' && is_scalar($v)) {
            return (string) $v;
        }

        $v2 = $request->input($key);
        if ($v2 !== null && $v2 !== '' && is_scalar($v2)) {
            return (string) $v2;
        }

        return null;
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

    private function parseFlexibleDate(string $value): ?Carbon
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
}
