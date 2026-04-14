<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `WalletController` admin actions.
 */
class WalletController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private function decodeWalletUserId(?string $b64): ?int
    {
        if ($b64 === null || $b64 === '') {
            return null;
        }
        $raw = base64_decode($b64, true);
        if ($raw === false || !ctype_digit((string)$raw)) {
            return null;
        }

        return (int)$raw;
    }

    private function resolveWalletLimit(Request $request, string $sessionKey): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int)$fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put($sessionKey, $lim);

                return $lim;
            }
        }

        $sess = (int)session()->get($sessionKey, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 50;
    }

    private function ensureWalletRow(int $userId): object
    {
        $row = DB::table('cs_wallets')->where('user_id', $userId)->first();
        if ($row) {
            return $row;
        }
        DB::table('cs_wallets')->insert(['user_id' => $userId, 'balance' => 0]);

        return DB::table('cs_wallets')->where('user_id', $userId)->first();
    }

    /**
     * Cake `CsWallet::subtractBalance` (DB + ledger row only; accounting hooks omitted).
     */
    private function subtractBalance(float $balance, int $userId, string $note, int $orderId = 0): float
    {
        if ($balance <= 0 || !Schema::hasTable('cs_wallets') || !Schema::hasTable('cs_wallet_transactions')) {
            return 0.0;
        }

        $normNote = str_replace(' ', '_', strtolower(trim($note)));
        $wallet = DB::table('cs_wallets')->where('user_id', $userId)->first();
        if ($wallet) {
            $walletId = (int)$wallet->id;
            $currentBal = (float)$wallet->balance - $balance;
            DB::table('cs_wallets')->where('id', $walletId)->update(['balance' => $currentBal]);
        } else {
            $currentBal = -$balance;
            $walletId = (int)DB::table('cs_wallets')->insertGetId([
                'user_id' => $userId,
                'balance' => $currentBal,
            ]);
        }

        DB::table('cs_wallet_transactions')->insert([
            'cs_wallet_id' => $walletId,
            'amount' => '-' . $balance,
            'transaction_id' => '',
            'cs_order_id' => $orderId,
            'amt' => $balance,
            'note' => $normNote,
            'type' => 1,
            'status' => 0,
            'balance' => $currentBal,
            'charged_at' => date('Y-m-d H:i:s'),
        ]);

        return $currentBal;
    }

    /**
     * Cake `CsWallet::addBalance` — common credit path only (no negative-balance edge cases).
     */
    private function addBalanceSimple(float $balance, int $userId, string $transactionId, string $note, int $orderId = 0, ?string $chargedAt = null): float
    {
        if ($balance <= 0 || !Schema::hasTable('cs_wallets') || !Schema::hasTable('cs_wallet_transactions')) {
            return (float)(DB::table('cs_wallets')->where('user_id', $userId)->value('balance') ?? 0);
        }

        $when = $chargedAt ?: date('Y-m-d H:i:s');
        $normNote = str_replace(' ', '_', strtolower(trim($note)));
        $wallet = DB::table('cs_wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            $walletId = (int)DB::table('cs_wallets')->insertGetId([
                'user_id' => $userId,
                'balance' => $balance,
            ]);
            $newBal = $balance;
        } else {
            $newBal = (float)$wallet->balance + $balance;
            DB::table('cs_wallets')->where('id', $wallet->id)->update(['balance' => $newBal]);
            $walletId = (int)$wallet->id;
        }

        DB::table('cs_wallet_transactions')->insert([
            'cs_wallet_id' => $walletId,
            'amount' => (string)$balance,
            'transaction_id' => $transactionId,
            'cs_order_id' => $orderId,
            'amt' => $balance,
            'note' => $normNote,
            'type' => 0,
            'status' => 0,
            'balance' => $newBal,
            'charged_at' => $when,
        ]);

        return $newBal;
    }

    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeWalletUserId($userid !== null ? (string)$userid : '');
        if (!$uid) {
            return redirect('/admin/users');
        }

        $wallet = $this->ensureWalletRow($uid);
        $limit = $this->resolveWalletLimit($request, 'wallet_limit');
        $keyword = trim((string)$request->input('searchKey', $request->query('keyword', '')));

        $q = DB::table('cs_wallet_transactions as wt')
            ->leftJoin('cs_orders as o', 'o.id', '=', 'wt.cs_order_id')
            ->where('wt.cs_wallet_id', $wallet->id)
            ->orderByDesc('wt.id')
            ->select('wt.*', 'o.increment_id as order_increment_id');
        if ($keyword !== '') {
            $q->where('wt.transaction_id', $keyword);
        }

        $transactions = $q->paginate($limit)->withQueryString();

        $isDealer = (int)DB::table('users')->where('id', $uid)->value('is_dealer') === 1;
        $useridB64 = base64_encode((string)$uid);

        if ($request->ajax()) {
            return response()->view('admin.wallet._transaction_panel', [
                'transactions' => $transactions,
                'keyword' => $keyword,
                'limit' => $limit,
                'adminContext' => true,
                'useridB64' => $useridB64,
            ]);
        }

        return view('admin.wallet.index', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'keyword' => $keyword,
            'limit' => $limit,
            'is_dealer' => $isDealer,
            'userid' => $useridB64,
        ]);
    }

    public function updatebalance(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeWalletUserId($userid !== null ? (string)$userid : '');
        if (!$uid) {
            return redirect('/admin/users');
        }

        $userData = DB::table('users')->where('id', $uid)->first();
        if (!$userData || (int)$userData->is_dealer !== 1) {
            session()->flash('error', 'Sorry, you cant update this user balance');

            return redirect()->back();
        }

        $wallet = $this->ensureWalletRow($uid);

        if ($request->isMethod('post')) {
            $deduction = (float)$request->input('Wallet.balance', 0);
            if ($deduction == 0.0) {
                session()->flash('error', 'Please enter a valid amount');

                return redirect()->back();
            }
            $bal = (float)($wallet->balance ?? 0);
            $newBalance = $bal - $deduction;

            if ((int)$userData->is_dealer === 1 && $newBalance < 0) {
                $this->subtractBalance($deduction, $uid, 'Forcefully deducted by DIA');
                session()->flash('success', 'Wallet balance updated successfully');

                return redirect('/admin/wallet/index/' . base64_encode((string)$uid));
            }
            if ($newBalance > 0) {
                $this->subtractBalance($deduction, $uid, 'Forcefully deducted by DIA');
                session()->flash('success', 'Wallet balance updated successfully');

                return redirect('/admin/wallet/index/' . base64_encode((string)$uid));
            }
            session()->flash('error', 'Sorry, you cant make this user balance in negative');

            return redirect()->back();
        }

        return view('admin.wallet.updatebalance', [
            'wallet' => $wallet,
            'userid' => base64_encode((string)$uid),
        ]);
    }

    public function refundbalance(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeWalletUserId($userid !== null ? (string)$userid : '');
        if (!$uid) {
            return redirect('/admin/users');
        }

        $userData = DB::table('users')->where('id', $uid)->first();
        if (!$userData || (int)$userData->is_dealer === 1) {
            session()->flash('error', 'Sorry, you cant refund this user balance');

            return redirect()->back();
        }

        $wallet = $this->ensureWalletRow($uid);

        if ($request->isMethod('post')) {
            session()->flash('error', 'Wallet refund to card requires PaymentProcessor (Stripe) — not migrated in Laravel yet.');

            return redirect()->back();
        }

        return view('admin.wallet.refundbalance', [
            'wallet' => $wallet,
            'userid' => base64_encode((string)$uid),
        ]);
    }

    public function chargepartialamtpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        if (!$request->ajax()) {
            abort(403, 'wrong attempt');
        }

        $userid = (string)$request->input('userid', '');
        $bookingid = (string)$request->input('bookingid', '');
        $currency = (string)$request->input('currency', '');

        return response()->view('admin.wallet._chargepartialamtpopup', compact('userid', 'bookingid', 'currency'));
    }

    public function chargepartialamt(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []], 401);
        }
        if (!$request->ajax() || !$request->isMethod('post')) {
            abort(403, 'wrong attempt');
        }

        return response()->json([
            'status' => false,
            'message' => 'chargeAmtToUser / PaymentProcessor is not migrated in Laravel yet.',
            'result' => [],
        ]);
    }

    public function diacredit(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeWalletUserId($userid !== null ? (string)$userid : '');
        if (!$uid) {
            return redirect('/admin/users');
        }

        $userData = DB::table('users')->where('id', $uid)->first();
        if (!$userData || (int)$userData->is_dealer === 1) {
            session()->flash('error', 'Sorry, you cant add credit to this user');

            return redirect()->back();
        }

        return view('admin.wallet.diacredit', [
            'userid' => base64_encode((string)$uid),
        ]);
    }

    public function createintent(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []], 401);
        }
        if (!$request->ajax() || !$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Sorry, not a valid request', 'result' => []]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Stripe PaymentIntent creation is not wired in Laravel yet.',
            'result' => [],
        ]);
    }

    public function diacreditprocess(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []], 401);
        }
        if (!$request->ajax() || !$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Sorry, not a valid request', 'result' => []]);
        }

        $payload = json_decode((string)$request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->all();
        }
        $amt = isset($payload['amount']) ? (float)$payload['amount'] / 100.0 : 0.0;
        $useridB64 = isset($payload['userid']) ? (string)$payload['userid'] : '';
        $transaction = isset($payload['transaction']) ? (string)$payload['transaction'] : '';
        $uid = $this->decodeWalletUserId($useridB64);
        if ($amt == 0.0 || !$uid || $transaction === '') {
            return response()->json(['status' => false, 'message' => 'Sorry, not a valid request', 'result' => []]);
        }

        $walletbal = $this->addBalanceSimple($amt, $uid, $transaction, 'DIA Credits', 0, date('Y-m-d H:i:s'));

        return response()->json([
            'status' => true,
            'message' => 'Charged successfully',
            'result' => ['walletbal' => $walletbal],
        ]);
    }
}
