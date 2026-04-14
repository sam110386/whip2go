<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Reportlib;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealerChargebacksController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        $statusType = $dateFrom = $dateTo = $dealerid = '';
        $query = DB::table('dealer_chargebacks as DealerChargeback')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'DealerChargeback.dealer_id')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'DealerChargeback.user_id')
            ->select(
                'DealerChargeback.*',
                'Owner.first_name as owner_first_name',
                'Owner.last_name as owner_last_name',
                'Driver.first_name as driver_first_name',
                'Driver.last_name as driver_last_name'
            );

        if ($request->has('Search') || $request->filled('date_from')) {
            $dateFrom = $request->input('Search.date_from', $request->input('date_from', ''));
            $dateTo = $request->input('Search.date_to', $request->input('date_to', ''));
            $statusType = $request->input('Search.status_type', $request->input('status_type', ''));
            $dealerid = $request->input('Search.dealer_id', $request->input('dealer_id', ''));

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = date('Y-m-d');
            }

            if (!empty($dateFrom)) {
                $parsedFrom = Carbon::parse($dateFrom)->format('Y-m-d');
                $query->where('DealerChargeback.created', '>=', $parsedFrom);
            }
            if (!empty($dateTo)) {
                $parsedTo = Carbon::parse($dateTo)->format('Y-m-d');
                $query->where('DealerChargeback.created', '<=', $parsedTo);
            }
        }

        $sessLimitName = 'dealer_chargebacks_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $records = $query->orderByDesc('DealerChargeback.id')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.charge_back._admin_index', compact('records', 'dateFrom', 'dateTo', 'dealerid'));
        }

        return view('admin.charge_back.index', compact('records', 'dateFrom', 'dateTo', 'statusType', 'dealerid'));
    }

    public function payment(Request $request)
    {
        if ($request->isMethod('post') && $request->filled('ChargeBack')) {
            $dealer = $request->input('ChargeBack.dealer_id');
            $amt = (float) $request->input('ChargeBack.amt');
            $driver = $request->input('ChargeBack.user_id');
            $note = $request->input('ChargeBack.note');

            if ($amt <= 0) {
                return redirect()->back()->with('error', 'Sorry, please enter correct amount value');
            }

            $paymentProcessor = new \PaymentProcessor();
            $res = $paymentProcessor->chargeFromDealer($amt, $dealer, 'Driver credit', 12);

            if ($res['status'] === 'error') {
                return redirect()->back()->with('error', $res['message']);
            }

            if (!empty($driver)) {
                // Add balance to wallet
                DB::table('cs_wallets')->where('user_id', $driver)->increment('balance', $amt);

                $reportlib = new Reportlib();
                $reportlib->saveAccountReportData([
                    'user_id'        => $driver,
                    'cs_order_id'    => '',
                    'rtype'          => 'D',
                    'type'           => 16,
                    'transaction_id' => $res['transaction_id'],
                    'amt'            => $amt,
                    'source'         => 'card',
                ]);
            }

            DB::table('dealer_chargebacks')->insert([
                'dealer_id' => $dealer,
                'user_id'   => !empty($driver) ? $driver : 0,
                'amt'       => $amt,
                'txn_id'    => $res['transaction_id'],
                'status'    => 1,
                'note'      => $note,
                'created'   => now(),
                'modified'  => now(),
            ]);

            return redirect('admin/charge_back/dealer_chargebacks/index')
                ->with('success', 'Dealer charged successfully');
        }

        return view('admin.charge_back.payment');
    }
}
