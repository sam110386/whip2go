<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `WalletController` — logged-in user wallet index.
 */
class WalletController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private function legacyOwnerUserId(): int
    {
        $parent = (int)session()->get('userParentId', 0);

        return $parent !== 0 ? $parent : (int)session()->get('userid', 0);
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
        DB::table('cs_wallets')->insert(['user_id' => $userId, 'balance' => 0, 'created' => now()]);

        return DB::table('cs_wallets')->where('user_id', $userId)->first();
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $uid = $this->legacyOwnerUserId();
        $limit = $this->resolveWalletLimit($request, 'wallet_limit');
        if ($uid <= 0 || !Schema::hasTable('cs_wallets') || !Schema::hasTable('cs_wallet_transactions')) {
            $emptyPage = new LengthAwarePaginator([], 0, max(1, $limit), 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('wallet.index', [
                'wallet' => (object)['balance' => 0],
                'transactions' => $emptyPage,
                'keyword' => '',
                'limit' => $limit,
            ]);
        }

        $wallet = $this->ensureWalletRow($uid);
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

        if ($request->ajax()) {
            return response()->view('admin.wallet._transaction_panel', [
                'transactions' => $transactions,
                'keyword' => $keyword,
                'limit' => $limit,
                'adminContext' => false,
                'useridB64' => null,
            ]);
        }

        return view('wallet.index', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'keyword' => $keyword,
            'limit' => $limit,
        ]);
    }
}
