<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsPayout;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\CsWalletTransaction;
use App\Models\Legacy\Report;
use App\Models\Legacy\User;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class AccountingReportsController extends LegacyAppController
{
    private function getTimezone($userid)
    {
        $user = User::select('timezone')->find($userid);
        return ($user && !empty($user->timezone))
            ? $user->timezone
            : config('app.timezone', 'UTC');
    }
    private function calculateBalances(LengthAwarePaginator $reportlists, string $direction): array
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $runningBal = 0;
        $items = $reportlists->items();
        $chronologicalItems = ($direction === 'desc') ? array_reverse($items) : $items;

        foreach ($chronologicalItems as $trip) {
            $amt = (float) $trip->amt;

            if ($trip->rtype === 'C') {
                $totalCredit += $amt;
                $runningBal = sprintf('%0.2f', ($runningBal + $amt));
            } elseif ($trip->rtype === 'D') {
                $totalDebit += $amt;
                $runningBal = sprintf('%0.2f', ($runningBal - $amt));
            }

            $trip->running_bal = $runningBal;
        }

        return [
            sprintf('%0.2f', $totalDebit),
            sprintf('%0.2f', $totalCredit),
            sprintf('%0.2f', $runningBal),
        ];
    }
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Account Reports';
        $sessLimitName = 'accounting_reports_limit';
        $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
        $rtype = $request->input('Search.rtype', $request->input('rtype', ''));
        $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->input('date_to', ''));
        $type = $request->input('Search.type', $request->input('type', ''));

        if ($request->has('user_id')) {
            $userid = $request->input('user_id');
        }

        if (!$userid) {
            return redirect('/admin/users/index');
        }

        if (!empty($date_from) && empty($date_to)) {
            $date_to = date('Y-m-d');
        }

        $query = Report::with('csOrder:id,increment_id');

        if (!empty($keyword)) {
            $query->where('transaction_id', 'LIKE', '%' . $keyword . '%');
        }

        if (!empty($date_from)) {
            $formattedFrom = Carbon::parse($date_from)->startOfDay()->toDateTimeString();
            $query->where('created', '>=', $formattedFrom);
        }

        if (!empty($date_to)) {
            $formattedTo = Carbon::parse($date_to)->endOfDay()->toDateTimeString();
            $query->where('created', '<=', $formattedTo);
        }

        if (!empty($rtype)) {
            $query->where('rtype', $rtype);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($userid)) {
            $query->where('user_id', $userid);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage;
        }

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc'));

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        if ($sort === 'increment_id') {
            $query->orderBy(
                CsOrder::select('increment_id')->whereColumn('cs_orders.id', 'reports.cs_order_id'),
                $direction
            );
        } elseif ($sort === 'created') {
            $query->orderBy('created', $direction);
        } else {
            $query->orderBy('id', $direction);
        }

        $reportlists = $query->paginate($limit);
        [$totalDebit, $totalCredit, $runningBal] = $this->calculateBalances($reportlists, $direction);

        $timezone = $this->getTimezone($userid);

        if ($request->ajax()) {
            return view('admin.accounting.elements.index', compact(
                'reportlists',
                'limit',
                'timezone',
                'totalDebit',
                'totalCredit',
                'runningBal'
            ));
        }

        return view('admin.accounting.index', compact(
            'keyword',
            'rtype',
            'date_from',
            'date_to',
            'userid',
            'type',
            'reportlists',
            'timezone',
            'title',
            'limit',
            'totalDebit',
            'totalCredit',
            'runningBal'
        ));
    }
    public function booking(Request $request)
    {
        $bookingid = $request->input('orderid');
        $payments = CsOrderPayment::where('cs_order_id', $bookingid)->get();
        $wallets = CsWalletTransaction::where('cs_order_id', $bookingid)->get();

        return view('admin.accounting.booking', compact('payments', 'wallets'));
    }
    public function payout(Request $request)
    {
        $cs_payout = $request->input('payoutid');

        $csPayoutRecord = CsPayout::where('transaction_id', $cs_payout)->first();
        $cs_payout_id = $csPayoutRecord ? $csPayoutRecord->id : null;

        $transactions = CsPayoutTransaction::with([
            'csOrder:id,vehicle_id,renter_id,increment_id,start_datetime',
            'csOrder.vehicle:id,vehicle_name',
            'csOrder.renter:id,first_name,last_name'
        ])
            ->where('status', 1)
            ->where('cs_payout_id', $cs_payout_id)
            ->orderBy('id', 'DESC')
            ->get();

        return view('admin.accounting.payout', compact('transactions'));
    }
    public function transaction(Request $request)
    {
        $transactionId = $request->input('transaction');

        $transactions = CsPayoutTransaction::with('csPayout')
            ->where('transaction_id', $transactionId)
            ->orderBy('id', 'DESC')
            ->get();

        $payments = CsOrderPayment::with('csOrder')
            ->where('transaction_id', $transactionId)
            ->orderBy('id', 'DESC')
            ->get();

        return view('admin.accounting.transaction', compact('transactions', 'payments'));
    }
}
