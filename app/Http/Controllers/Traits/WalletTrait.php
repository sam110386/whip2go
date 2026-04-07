<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\CsWallet;
use Illuminate\Http\Request;
// use App\Lib\PaymentProcessor; // Pending migration of PaymentProcessor

trait WalletTrait
{
    /**
     * Common charge partial amount logic extracted from CakePHP _chargepartialamt method.
     */
    protected function chargePartialAmtLogic(Request $request)
    {
        if (!$request->ajax() || !$request->isMethod('post')) {
            return ['status' => false, 'message' => "wrong attempt", 'result' => []];
        }

        $walletData = $request->input('Wallet', []);
        $amt = (float)($walletData['amount'] ?? 0);
        $userid = $walletData['user_id'] ?? null;
        $bookingid = $walletData['bookingid'] ?? null;
        $note = $walletData['note'] ?? '';
        $currency = $walletData['currency'] ?? '';

        if ($amt == 0 || empty($userid) || empty($note)) {
            return ['status' => false, 'message' => "Sorry, not a valid request", 'result' => []];
        }

        if (empty($currency)) {
            $userObj = User::find($userid, ['currency']);
            $currency = $userObj->currency ?? 'usd';
        }

        // TODO: Replace with migrated PaymentProcessor facade/class
        // $paymentProcessorObj = new PaymentProcessor();
        // $res = $paymentProcessorObj->chargeAmtToUser($amt, $userid, $note, $currency);
        
        // Placeholder simulation for the time being
        $res = [
            'status' => 'success', // or 'error'
            'message' => 'Processed successfully',
            'amt' => $amt,
            'transaction_id' => 'txn_placeholder_' . time()
        ];

        if ($res['status'] != 'success') {
            return ['status' => false, 'message' => $res["message"], 'result' => []];
        }

        // Save balance in wallet
        // CakePHP signature: addBalance($balance, $user_id, $transaction_id, $summary, $order_id = 0, $date = null)
        $csWalletModel = new CsWallet();
        $walletbal = $csWalletModel->addBalance(
            $res['amt'], 
            $userid, 
            $res['transaction_id'], 
            $note, 
            $bookingid, 
            now()
        );

        return ['status' => true, 'message' => "Charged successfully", 'result' => ["walletbal" => $walletbal]];
    }
}
