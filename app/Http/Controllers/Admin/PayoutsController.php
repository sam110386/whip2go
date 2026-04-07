<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /** @see app/Lib/Common.php getPayoutTypeValue(1) */
    private static function payoutTypeLabels(): array
    {
        return [
            1 => 'Deposit',
            2 => 'Usage Transaction',
            3 => 'Initial Fee',
            4 => 'Insurance Fee',
            5 => 'Cancelation fee',
            6 => 'Toll Fee',
            7 => 'Customer Balance Charge',
            8 => 'Toll Violation',
            9 => 'Red Light Violation',
            10 => 'Parking Violation',
            11 => 'Refund Balance',
            12 => 'Driver Credit,',
            13 => 'Geotab Monthly Fee',
            14 => 'DIA Insurance Fee',
            15 => 'Credit Card Chargebacks',
            16 => 'Extra Usage Fee',
            17 => 'Car Damage Fee',
            18 => 'Hazardous Driving Fee',
            19 => 'Ext/Late Fee',
            20 => 'Vehicle Insurance Penalty',
            21 => 'Credit Deposit to Virtual Card',
        ];
    }

    /**
     * Cake PayoutsController::admin_index
     */
    public function admin_index(Request $request)
    {
        if ($request->isMethod('POST') && (string)$request->input('search') === 'EXPORT') {
            return redirect()->back()->with('error', 'CSV export is not ported yet; use Cake admin or add export here.');
        }

        $listtype = trim((string)($request->query('listtype', $request->input('Search.listtype', ''))));
        $dateFrom = trim((string)$this->payoutSearch($request, 'date_from'));
        $dateTo = trim((string)$this->payoutSearch($request, 'date_to'));
        $payoutId = trim((string)$this->payoutSearch($request, 'payout_id'));
        $userId = trim((string)$this->payoutSearch($request, 'user_id'));

        if ($request->isMethod('POST') && $request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['admin_payouts_limit' => $lim]);
            }
        }
        $limit = (int)session('admin_payouts_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $paymentTypeValue = self::payoutTypeLabels();
        $batchMode = ($listtype === '');

        if ($batchMode) {
            $query = DB::table('cs_payouts as p');
            if ($dateFrom !== '') {
                try {
                    $df = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                    $query->where('p.processed_on', '>=', $df);
                } catch (\Throwable $e) {
                }
            }
            if ($dateTo !== '') {
                try {
                    $dt = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                    $query->where('p.processed_on', '<=', $dt);
                } catch (\Throwable $e) {
                }
            }
            if ($payoutId !== '') {
                $query->where('p.id', (int)$payoutId);
            }
            if ($userId !== '') {
                $query->where('p.user_id', (int)$userId);
            }
            $payoutlists = $query->select('p.*')->orderByDesc('p.processed_on')->paginate($limit)->withQueryString();
        } else {
            $query = DB::table('cs_payout_transactions as pt')
                ->where('pt.status', 1)
                ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
                ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
                ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
                ->select([
                    'pt.*',
                    'o.id as order_table_id',
                    'o.increment_id',
                    'o.start_datetime',
                    'v.vehicle_name',
                    'renter.first_name as renter_first_name',
                    'renter.last_name as renter_last_name',
                ])
                ->orderByDesc('pt.id');
            if ($userId !== '') {
                $query->where('pt.user_id', (int)$userId);
            }
            $payoutlists = $query->paginate($limit)->withQueryString();
        }

        if ($request->ajax()) {
            return response()->view('admin.payouts.listing', [
                'payoutlists' => $payoutlists,
                'batchMode' => $batchMode,
                'paymentTypeValue' => $paymentTypeValue,
                'listtype' => $listtype,
            ]);
        }

        return view('admin.payouts.index', [
            'payoutlists' => $payoutlists,
            'batchMode' => $batchMode,
            'paymentTypeValue' => $paymentTypeValue,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payout_id' => $payoutId,
            'user_id' => $userId,
            'listtype' => $listtype,
            'limit' => $limit,
        ]);
    }

    /**
     * Cake PayoutsController::admin_transactions (POST payoutid)
     */
    public function admin_transactions(Request $request)
    {
        $payoutId = (int)$request->input('payoutid');
        if ($payoutId <= 0) {
            return response('Invalid payout', 400);
        }

        $transactions = DB::table('cs_payout_transactions as pt')
            ->where('pt.cs_payout_id', $payoutId)
            ->leftJoin('cs_orders as o', 'o.id', '=', 'pt.cs_order_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->select([
                'pt.*',
                'o.id as order_table_id',
                'o.increment_id',
                'o.start_datetime',
                'v.vehicle_name',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
            ])
            ->orderByDesc('pt.id')
            ->get();

        return view('admin.payouts.batch_transactions', [
            'transactions' => $transactions,
            'paymentTypeValue' => self::payoutTypeLabels(),
        ]);
    }

    private function payoutSearch(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        $q = $request->query($key);

        return $q === null ? '' : (string)$q;
    }
}
