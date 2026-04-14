<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LinkedPayoutsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/payouts/index')->with('error', 'Sorry, you are not authorized user for this action');
        }
        $dealerIds = $this->dealerIds((int)($admin['parent_id'] ?? 0));

        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));
        $listtype = trim((string)$this->searchInput($request, 'listtype'));
        $payoutId = trim((string)$this->searchInput($request, 'payout_id'));
        $userId = trim((string)$this->searchInput($request, 'user_id'));

        if ($request->input('search') === 'EXPORT') {
            return $this->cloudexport($request, $dealerIds);
        }

        $limit = $this->resolveLimit($request);

        if ($listtype === '') {
            $q = DB::table('cs_payouts as p')->whereIn('p.user_id', $dealerIds === [] ? [0] : $dealerIds);
            if ($dateFrom !== '') {
                $q->whereDate('p.processed_on', '>=', $dateFrom);
            }
            if ($dateTo !== '') {
                $q->whereDate('p.processed_on', '<=', $dateTo);
            }
            if ($payoutId !== '' && is_numeric($payoutId)) {
                $q->where('p.id', (int)$payoutId);
            }
            if ($userId !== '' && is_numeric($userId)) {
                $q->where('p.user_id', (int)$userId);
            }
            $payoutlists = $q->orderByDesc('p.processed_on')->paginate($limit)->withQueryString();
        } else {
            $q = DB::table('cs_payout_transactions as pt')
                ->where('pt.status', 1)
                ->whereIn('pt.user_id', $dealerIds === [] ? [0] : $dealerIds)
                ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
                ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
                ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
                ->select([
                    'pt.*',
                    'o.id as order_id',
                    'o.increment_id',
                    'v.vehicle_name',
                    'renter.first_name as renter_first_name',
                    'renter.last_name as renter_last_name',
                ]);
            if ($userId !== '' && is_numeric($userId)) {
                $q->where('pt.user_id', (int)$userId);
            }
            $payoutlists = $q->orderByDesc('pt.id')->paginate($limit)->withQueryString();
        }

        return view('cloud.linked_payouts.index', [
            'payoutlists' => $payoutlists,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'listtype' => $listtype,
            'payout_id' => $payoutId,
            'user_id' => $userId,
            'dealers' => $this->dealerMap($dealerIds),
        ]);
    }

    public function transactions(Request $request)
    {
        $csPayoutId = (int)$request->input('payoutid', 0);
        $transactions = DB::table('cs_payout_transactions as pt')
            ->where('pt.status', 1)
            ->where('pt.cs_payout_id', $csPayoutId)
            ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->orderByDesc('pt.id')
            ->select([
                'pt.*',
                'o.id as order_id',
                'o.increment_id',
                'o.start_datetime',
                'v.vehicle_name',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
            ])
            ->get();

        return response()->view('cloud.linked_payouts.transactions', compact('transactions'));
    }

    public function cloudexport(Request $request, ?array $dealerIds = null)
    {
        $dealerIds = $dealerIds ?? $this->dealerIds((int)($this->getAdminUserid()['parent_id'] ?? 0));
        $rows = DB::table('cs_payout_transactions as pt')
            ->whereIn('pt.user_id', $dealerIds === [] ? [0] : $dealerIds)
            ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->orderByDesc('pt.id')
            ->limit(1500)
            ->get([
                'pt.*',
                'o.increment_id',
                'o.start_datetime',
                'o.end_datetime',
                'o.pickup_address',
                'v.vehicle_name',
                'v.vin_no',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
            ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="linked_payout.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['No', 'Booking No', 'Car', 'VIN', 'Start Date', 'End Date', 'Driver Name', 'City', 'Amount To Owner', 'Payout#']);
            $i = 1;
            foreach ($rows as $r) {
                fputcsv($fp, [
                    $i++,
                    $r->increment_id,
                    $r->vehicle_name,
                    $r->vin_no,
                    $r->start_datetime,
                    $r->end_datetime,
                    trim(($r->renter_first_name ?? '') . ' ' . ($r->renter_last_name ?? '')),
                    $r->pickup_address,
                    $r->amount,
                    $r->cs_payout_id,
                ]);
            }
            fclose($fp);
        }, 200, $headers);
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

    private function dealerMap(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('users')->whereIn('id', $ids)->pluck('first_name', 'id')->toArray();
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['linked_payouts_limit' => $lim]);
            }
        }
        $limit = (int)session('linked_payouts_limit', 50);

        return $limit > 0 ? $limit : 50;
    }
}

