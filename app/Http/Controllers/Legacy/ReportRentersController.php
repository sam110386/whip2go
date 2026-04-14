<?php

namespace App\Http\Controllers\Legacy;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CakePHP `ReportRentersController` — dealer session, renter-grouped completed bookings.
 */
class ReportRentersController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $sessionKey = 'report_renters_limit';
        $limit = $this->resolveLimit($request, $sessionKey);

        $queryNamed = array_filter(
            [
                $request->query('keyword'),
                $request->query('searchin'),
                $request->query('date_from'),
                $request->query('date_to'),
                $request->query('status_type'),
            ],
            fn ($v) => $v !== null && $v !== ''
        );
        $hasSearch = $request->has('Search') || $queryNamed !== [];

        $keyword = $hasSearch ? trim((string) $this->searchInput($request, 'keyword')) : '';
        $fieldname = $hasSearch ? trim((string) $this->searchInput($request, 'searchin')) : '';
        $dateFrom = $hasSearch ? trim((string) $this->searchInput($request, 'date_from')) : '';
        $dateTo = $hasSearch ? trim((string) $this->searchInput($request, 'date_to')) : '';
        $statusType = $hasSearch ? trim((string) $this->searchInput($request, 'status_type')) : '';

        if ($hasSearch && $dateFrom !== '' && $dateTo === '') {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $q = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.user_id', $userId)
            ->where('o.status', 3);

        if ($hasSearch) {
            if ($keyword !== '' && $fieldname !== '') {
                if ($fieldname === '1') {
                    $q->where(function ($w) use ($keyword) {
                        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                        $w->where('u.first_name', 'like', $like)
                            ->orWhere('u.last_name', 'like', $like);
                    });
                } elseif ($fieldname === '2') {
                    $q->where('o.vehicle_name', $keyword);
                } elseif ($fieldname === '3') {
                    $q->where('o.id', $keyword);
                } elseif ($fieldname === '4') {
                    $q->where('u.contact_number', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%');
                }
            }

            if ($dateFrom !== '') {
                $df = $this->parseReportDate($dateFrom);
                if ($df) {
                    $q->where('o.start_datetime', '>=', $df->format('Y-m-d 00:00:00'));
                }
            }
            if ($dateTo !== '') {
                $dt = $this->parseReportDate($dateTo);
                if ($dt) {
                    $q->where('o.end_datetime', '<=', $dt->format('Y-m-d 23:59:59'));
                }
            }

            if ($statusType === 'cancel') {
                $q->where('o.status', 2);
            } elseif ($statusType === 'complete') {
                $q->where('o.status', 3);
            } elseif ($statusType === 'incomplete') {
                $q->where('o.status', '!=', 3);
            }
        }

        $q->selectRaw(
            'MAX(o.id) as id, o.renter_id, COUNT(o.id) as totalbooking, ' .
            'MAX(u.first_name) as first_name, MAX(u.last_name) as last_name, MAX(u.contact_number) as contact_number, MAX(u.id) as user_id'
        )
            ->groupBy('o.renter_id');

        $reportlists = $q->orderByDesc('id')->paginate($limit)->withQueryString();

        return view('report_renters.index', [
            'title_for_layout' => 'Renter Reports',
            'reportlists' => $reportlists,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_from_display' => $this->formatInputDateForView($dateFrom),
            'date_to_display' => $this->formatInputDateForView($dateTo),
            'status_type' => $statusType,
            'limit' => $limit,
        ]);
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function details(Request $request, ?string $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeOrderId($id ?? '');
        if (!$orderId) {
            return view('report_renters.details', ['csorder' => null]);
        }

        $orderRow = DB::table('cs_orders')->where('id', $orderId)->first();
        $csorder = null;
        if ($orderRow) {
            $userRow = !empty($orderRow->renter_id)
                ? DB::table('users')->where('id', $orderRow->renter_id)->first()
                : null;
            $csorder = [
                'CsOrder' => (array) $orderRow,
                'User' => [
                    'first_name' => $userRow->first_name ?? '',
                    'last_name' => $userRow->last_name ?? '',
                    'contact_number' => $userRow->contact_number ?? '',
                ],
            ];
        }

        return view('report_renters.details', compact('csorder'));
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function history(Request $request, ?string $pathRenterId = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $rawRenter = (string) $request->input('renterid', '');
        if ($rawRenter === '' && $pathRenterId !== null && $pathRenterId !== '') {
            $rawRenter = $pathRenterId;
        }

        $renterId = $this->decodeOrderId($rawRenter);
        $bookings = collect();

        if ($renterId) {
            $bookings = DB::table('cs_orders as o')
                ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
                ->where('o.user_id', $userId)
                ->where('o.status', '!=', 1)
                ->where('o.renter_id', $renterId)
                ->orderByDesc('o.id')
                ->limit(200)
                ->get(['o.*', 'v.vehicle_unique_id']);
        }

        return view('report_renters.history', [
            'title_for_layout' => 'Rental Orders',
            'bookings' => $bookings,
        ]);
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

        $q = $request->query($key);

        return $q !== null && $q !== '' ? (string) $q : null;
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

    private function decodeOrderId(string $id): ?int
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

    private function formatInputDateForView(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $c = $this->parseReportDate($value);

        return $c ? $c->format('m/d/Y') : $value;
    }
}
