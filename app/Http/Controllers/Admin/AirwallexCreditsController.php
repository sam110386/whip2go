<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\SimpleEncrypt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AirwallexCreditsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $this->ensureAdminSession();

        $dateFrom = $dateTo = $status = $userid = '';
        $query = DB::table('airwallex_credits as AirwallexCredit')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'AirwallexCredit.user_id')
            ->select('AirwallexCredit.*', 'Driver.first_name', 'Driver.last_name');

        $search = $request->input('Search', $request->query());
        if (!empty($search)) {
            $dateFrom = $search['date_from'] ?? '';
            $dateTo = $search['date_to'] ?? '';
            $status = $search['status'] ?? '';
            $userid = $search['userid'] ?? '';

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = date('Y-m-d');
            }
            if (!empty($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
                $query->where('AirwallexCredit.created', '>=', $dateFrom);
            }
            if (!empty($dateTo)) {
                $dateTo = Carbon::parse($dateTo)->format('Y-m-d');
                $query->where('AirwallexCredit.created', '<=', $dateTo);
            }
        }

        $sessLimitName = 'airwallex_credits_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitName, 20);
        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $records = $query->orderByDesc('AirwallexCredit.id')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.airwallex_credits._index', compact('records'));
        }

        return view('admin.airwallex_credits.index', compact(
            'records', 'dateFrom', 'dateTo', 'status', 'userid'
        ));
    }

    public function issue(Request $request, $user = null)
    {
        $this->ensureAdminSession();

        $userId = base64_decode($user);
        if (empty($userId)) {
            return redirect()->back()->with('error', 'Sorry, please choose a user to issue credit card');
        }

        $user = DB::table('users')
            ->where('id', $userId)
            ->where('status', 1)
            ->select('id', 'first_name', 'last_name')
            ->first();

        if (empty($user)) {
            return redirect()->back()->with('error', 'Sorry, user not found');
        }

        $totalDeposit = DB::table('cs_order_payments as CsOrderPayment')
            ->leftJoin('cs_orders as CsOrder', function ($join) {
                $join->on('CsOrder.id', '=', 'CsOrderPayment.cs_order_id')
                    ->where('CsOrderPayment.status', 1)
                    ->where('CsOrderPayment.type', 1);
            })
            ->where('CsOrder.renter_id', $userId)
            ->where('CsOrder.status', 1)
            ->where('CsOrderPayment.status', 1)
            ->where('CsOrderPayment.type', 1)
            ->sum('CsOrderPayment.amount');

        if ($totalDeposit <= 0) {
            return redirect()->back()->with('error', 'Sorry, user has no deposit to issue credit card');
        }

        return view('admin.airwallex_credits.issue', compact('user', 'totalDeposit'));
    }

    public function issuecard(Request $request)
    {
        $this->ensureAdminSession();

        if (!$request->isMethod('post')) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $data = $request->input('AirwallexCredit');
        $userId = $data['user_id'] ?? null;
        $amount = $data['amount'] ?? null;
        $currentLimit = $data['current_limit'] ?? null;

        if (empty($userId) || empty($amount) || empty($currentLimit)) {
            return redirect()->back()->with('error', 'Please provide user and amount to issue credit card');
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (empty($user)) {
            return redirect()->back()->with('error', 'Sorry, user not found');
        }

        if ($amount <= 0 || !is_numeric($amount) || $amount > $currentLimit) {
            return redirect()->back()->with('error', 'Please provide valid amount to issue credit card');
        }

        $creditId = DB::table('airwallex_credits')->insertGetId([
            'user_id' => $userId,
            'amount' => $amount,
            'current_limit' => $currentLimit,
            'card_number' => (new SimpleEncrypt())->encrypt($data['card_number']),
            'exp' => $data['exp'],
            'cvv' => $data['cvv'],
            'status' => 1,
            'created' => now(),
        ]);

        if ($creditId) {
            $this->createCustomerBalance($data);
            return redirect()->back()->with('success', 'Virtual credit card saved successfully');
        }

        return redirect()->back()->with('error', 'Failed to save credit card details');
    }

    private function createCustomerBalance(array $data): int
    {
        $type = 21;
        $note = 'Credit Deposit to Virtual Card';
        $credit = (float) $data['amount'];

        DB::table('cs_user_balance_logs')->insert([
            'user_id' => $data['user_id'],
            'credit' => $credit,
            'type' => $type,
            'owner_id' => 0,
            'note' => $note,
        ]);

        return DB::table('cs_user_balances')->insertGetId([
            'user_id' => $data['user_id'],
            'owner_id' => 0,
            'status' => 1,
            'note' => $note,
            'credit' => $credit,
            'balance' => $credit,
            'debit' => 0,
            'type' => $type,
            'chargetype' => $data['chargetype'] ?? '',
            'installment_type' => $data['installment_type'] ?? '',
            'installment_day' => $data['installment_day'] ?? '',
            'installment' => $data['installment'] ?? 0,
            'created' => now(),
            'updated' => now(),
        ]);
    }

    private function ensureAdminSession(): void
    {
        $redirect = $this->ensureUserSession();
        if ($redirect) {
            abort(403, 'Unauthorized');
        }
    }
}
