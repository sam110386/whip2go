<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\CsWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $wallet = CsWallet::where('user_id', $userId)->first();
        
        if (empty($wallet)) {
            $wallet = new CsWallet(['user_id' => $userId, 'balance' => 0]);
        }

        $query = CsWalletTransaction::where('cs_wallet_id', $wallet->id)
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'cs_wallet_transactions.cs_order_id')
            ->select('cs_wallet_transactions.*', 'CsOrder.increment_id');

        if ($request->has('searchKey')) {
            $query->where('cs_wallet_transactions.transaction_id', 'LIKE', '%' . $request->input('searchKey') . '%');
        }

        $transactions = $query->orderBy('cs_wallet_transactions.id', 'DESC')->paginate(25);

        if ($request->ajax()) {
            return view('legacy.elements.walletTransaction.transaction', compact('transactions', 'wallet'));
        }

        return view('legacy.wallet.index', compact('transactions', 'wallet'));
    }
}
