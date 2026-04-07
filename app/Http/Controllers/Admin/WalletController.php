<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\CsWalletTransaction;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class WalletController extends Controller
{
    use \App\Http\Controllers\Traits\WalletTrait;

    public function admin_index(Request $request, $userid = null) { return $this->index($request, $userid); }
    public function admin_diacredit(Request $request, $userid = null) { return $this->diacredit($request, $userid); }
    public function admin_createintent(Request $request) { return $this->createintent($request); }
    public function admin_updatebalance(Request $request, $userid = null) { return $this->updatebalance($request, $userid); }
    public function admin_refundbalance(Request $request, $userid = null) { return $this->refundbalance($request, $userid); }
    public function admin_chargepartialamtpopup(Request $request) { return $this->chargepartialamtpopup($request); }
    public function admin_chargepartialamt(Request $request) { return $this->chargepartialamt($request); }
    public function admin_diacreditprocess(Request $request) { return $this->diacreditprocess($request); }

    public function index(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.users.index');
        }

        $wallet = CsWallet::where('user_id', $userid)->first();
        $userData = User::find($userid, ['is_dealer']);

        $query = CsWalletTransaction::where('cs_wallet_id', $wallet->id)
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'cs_wallet_transactions.cs_order_id')
            ->select('cs_wallet_transactions.*', 'CsOrder.increment_id');

        if ($request->has('searchKey')) {
            $query->where('cs_wallet_transactions.transaction_id', 'LIKE', '%' . $request->input('searchKey') . '%');
        }

        $transactions = $query->orderBy('cs_wallet_transactions.id', 'DESC')->paginate(25);

        if ($request->ajax()) {
            return view('admin.elements.walletTransaction.transaction', compact('transactions', 'wallet', 'userid'));
        }

        return view('admin.wallet.index', compact('transactions', 'wallet', 'userid', 'userData'));
    }

    public function diacredit(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.users.index');
        }

        $userData = User::find($userid, ['is_dealer']);
        if ($userData->is_dealer) {
            session()->flash('error', "Sorry, you cant add credit to this user");
            return back();
        }

        return view('admin.wallet.diacredit', ['userid' => base64_encode($userid)]);
    }

    public function createintent(Request $request)
    {
        // Placeholder for Stripe Payment Intent creation logic
        // This will be fully functional once PaymentProcessor is migrated
        return response()->json(['status' => false, 'message' => "Stripe integration pending Lib migration."]);
    }

    public function updatebalance(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.users.index');
        }

        $userData = User::find($userid, ['is_dealer']);
        if (!$userData || !$userData->is_dealer) {
            session()->flash('error', "Sorry, you cant update this user balance");
            return back();
        }

        $wallet = CsWallet::where('user_id', $userid)->first();

        if ($request->isMethod('post')) {
            $deduction = (float)$request->input('Wallet.balance');
            if (!$deduction) {
                session()->flash('error', "Please enter a valid amount");
                return back();
            }

            $currentBalance = isset($wallet->balance) ? $wallet->balance : 0;
            $newBalance = $currentBalance - $deduction;

            if ($userData->is_dealer && $newBalance < 0) {
                $wallet->subtractBalance($deduction, $userid, "Forcefully deducted by DIA");
                session()->flash('success', "Wallet balance updated successfully");
                return redirect("/admin/wallet/index/" . base64_encode($userid));
            } elseif ($newBalance > 0) {
                $wallet->subtractBalance($deduction, $userid, "Forcefully deducted by DIA");
                session()->flash('success', "Wallet balance updated successfully");
                return redirect("/admin/wallet/index/" . base64_encode($userid));
            } else {
                session()->flash('error', "Sorry, you cant make this user balance in negative");
                return back();
            }
        }

        return view('admin.wallet.updatebalance', ['wallet' => $wallet, 'userid' => base64_encode($userid)]);
    }

    public function refundbalance(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.users.index');
        }

        $userData = User::find($userid, ['is_dealer']);
        if ($userData && $userData->is_dealer) {
            session()->flash('error', "Sorry, you cant refund this user balance");
            return back();
        }

        $wallet = CsWallet::where('user_id', $userid)->first();

        if ($request->isMethod('post')) {
            $deduction = (float)$request->input('Wallet.balance');
            $deductionNote = $request->input('Wallet.note');

            if (!$deduction) {
                session()->flash('error', "Please enter a valid amount");
                return back();
            }

            if ($wallet && $wallet->balance > 0 && $deduction <= $wallet->balance) {
                $resultRefund = $wallet->refundFromWallet($userid, $deduction, $deductionNote);
                if (!empty($resultRefund['status'])) {
                    session()->flash('success', "Wallet balance updated successfully");
                    return redirect("/admin/wallet/index/" . base64_encode($userid));
                } else {
                    session()->flash('error', $resultRefund['msg'] ?? 'Refund failed');
                    return back();
                }
            } else {
                session()->flash('error', "Sorry, this user dont have enough balance to refund");
                return back();
            }
        }

        return view('admin.wallet.refundbalance', ['wallet' => $wallet, 'userid' => base64_encode($userid)]);
    }

    public function chargepartialamtpopup(Request $request)
    {
        if (!$request->ajax()) {
            die("wrong attempt");
        }
        
        $userid = $request->input('userid');
        $bookingid = $request->input('bookingid', '');
        $currency = $request->input('currency', '');

        return view('admin.wallet._chargepartialamtpopup', compact('userid', 'bookingid', 'currency'));
    }

    public function chargepartialamt(Request $request)
    {
        $return = $this->chargePartialAmtLogic($request);
        return response()->json($return);
    }

    public function diacreditprocess(Request $request)
    {
        if (!$request->ajax() || !$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => "Sorry, not a valid request", 'result' => []]);
        }

        $amt = (float)($request->input('amount') / 100);
        $userid = base64_decode($request->input('userid'));
        $transaction = $request->input('transaction');

        if ($amt == 0 || empty($userid) || empty($transaction)) {
            return response()->json(['status' => false, 'message' => "Sorry, not a valid request", 'result' => []]);
        }

        $csWalletModel = new CsWallet();
        $walletbal = $csWalletModel->addBalance($amt, $userid, $transaction, "DIA Credits", 0, now());

        return response()->json(['status' => true, 'message' => "Charged successfully", 'result' => ["walletbal" => $walletbal]]);
    }

    protected function _chargepartialamt(Request $request)
    {
        return $this->chargePartialAmtLogic($request);
    }
}
