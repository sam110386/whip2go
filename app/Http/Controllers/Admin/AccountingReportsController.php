<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Reportlib;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;
    private function getTimezone($userid): string
    {
        $tz = DB::table('users')->where('id', $userid)->value('timezone');
        return !empty($tz) ? $tz : config('app.timezone');
    }

    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $uid = $this->decodeId($userid !== null ? (string)$userid : '');
        if (!$uid) {
            return redirect('/admin/users/index');
        }
        $userid = $uid;
        $useridB64 = base64_encode((string)$userid);

        $keyword = $rtype = $dateFrom = $dateTo = $type = '';

        $query = DB::table('reports as Report')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'Report.cs_order_id')
            ->select('Report.*', 'CsOrder.increment_id', 'CsOrder.id as cs_order_id');

        if ($request->has('Search') || $request->filled('keyword')) {
            $rtype = $request->input('Search.rtype', $request->input('rtype', ''));
            $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
            $dateFrom = $request->input('Search.date_from', $request->input('date_from', ''));
            $dateTo = $request->input('Search.date_to', $request->input('date_to', ''));
            $userid = $request->input('Search.user_id', $request->input('user_id', $userid));
            $type = $request->input('Search.type', $request->input('type', ''));

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = date('Y-m-d');
            }

            if (!empty($keyword)) {
                $query->where('Report.transaction_id', 'LIKE', "%{$keyword}%");
            }
            if (!empty($dateFrom)) {
                $parsedFrom = Carbon::parse($dateFrom)->format('Y-m-d');
                $query->where('Report.created', '>=', $parsedFrom);
            }
            if (!empty($dateTo)) {
                $parsedTo = Carbon::parse($dateTo)->format('Y-m-d');
                $query->where('Report.created', '<=', $parsedTo);
            }
            if (!empty($rtype)) {
                $query->where('Report.rtype', $rtype);
            }
            if (!empty($type)) {
                $query->where('Report.type', $type);
            }
        }

        if (!empty($userid)) {
            $query->where('Report.user_id', $userid);
        }

        $sessLimitName = 'reports_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $reportlists = $query->orderByDesc('Report.id')->paginate($limit);
        $timezone = $this->getTimezone($userid);
        $reportlib = new Reportlib();

        return view('admin.accounting.index', compact(
            'reportlists', 'keyword', 'rtype', 'dateFrom', 'dateTo', 'userid', 'type', 'timezone', 'reportlib', 'useridB64'
        ));
    }

    public function booking(Request $request)
    {
        $bookingid = $request->input('orderid');

        $payments = DB::table('cs_order_payments as CsOrderPayment')
            ->where('CsOrderPayment.cs_order_id', $bookingid)
            ->get();

        $wallets = DB::table('cs_wallet_transactions as CsWalletTransaction')
            ->where('CsWalletTransaction.cs_order_id', $bookingid)
            ->get();

        $reportlib = new Reportlib();

        return view('admin.accounting.booking', compact('payments', 'wallets', 'reportlib'));
    }

    public function payout(Request $request)
    {
        $csPayout = $request->input('payoutid');

        $csPayoutId = DB::table('cs_payouts')
            ->where('transaction_id', $csPayout)
            ->value('id');

        $transactions = DB::table('cs_payout_transactions as CsPayoutTransaction')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsPayoutTransaction.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
            ->where('CsPayoutTransaction.status', 1)
            ->where('CsPayoutTransaction.cs_payout_id', $csPayoutId)
            ->select(
                'CsPayoutTransaction.*',
                'CsOrder.id as order_id',
                'CsOrder.increment_id',
                'Renter.first_name as renter_first_name',
                'Renter.last_name as renter_last_name',
                'Vehicle.vehicle_name',
                'CsOrder.start_datetime'
            )
            ->orderByDesc('CsPayoutTransaction.id')
            ->get();

        $reportlib = new Reportlib();

        return view('admin.accounting.payout', compact('transactions', 'reportlib'));
    }

    public function transaction(Request $request)
    {
        $transactionId = $request->input('transaction');

        $transactions = DB::table('cs_payout_transactions as CsPayoutTransaction')
            ->leftJoin('cs_payouts as CsPayout', 'CsPayout.id', '=', 'CsPayoutTransaction.cs_payout_id')
            ->where('CsPayoutTransaction.transaction_id', $transactionId)
            ->select('CsPayoutTransaction.*', 'CsPayout.*')
            ->orderByDesc('CsPayoutTransaction.id')
            ->get();

        $payments = DB::table('cs_order_payments as CsOrderPayment')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsOrderPayment.cs_order_id')
            ->where('CsOrderPayment.transaction_id', $transactionId)
            ->select('CsOrderPayment.*', 'CsOrder.increment_id')
            ->orderByDesc('CsOrderPayment.id')
            ->get();

        $reportlib = new Reportlib();

        return view('admin.accounting.transaction', compact('transactions', 'payments', 'reportlib'));
    }
}
