<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentProcessor
{
    private $_secret;
    private $_mode;
    private $Stripe;

    public function __construct()
    {
        $this->_secret = config('services.stripe.secret');
        $this->_mode = config('services.stripe.mode');
    }

    private function stripe()
    {
        $this->Stripe = new \App\Services\Legacy\StripeClient($this->_secret, $this->_mode);
        return $this->Stripe;
    }

    // ─── DB Helper: Payment Log ───

    private function savePaymentLogRecord($data)
    {
        try {
            $record = [
                'cs_order_id' => $data['orderid'] ?? 0,
                'type' => $data['type'] ?? 0,
                'amount' => $data['amount'] ?? 0,
                'transaction_id' => $data['transaction_id'] ?? '',
                'status' => $data['status'] ?? 0,
                'note' => isset($data['note']) ? (is_array($data['note']) ? json_encode($data['note']) : $data['note']) : '',
                'refund_transaction_id' => $data['refundtransactionid'] ?? '',
                'old_transaction_id' => $data['old_transaction_id'] ?? '',
                'created' => now(),
                'modified' => now(),
            ];
            DB::table('cs_payment_logs')->insert($record);
        } catch (\Exception $e) {
            Log::error('PaymentProcessor::savePaymentLogRecord – ' . $e->getMessage());
        }
    }

    private function saveOnlyPaymentLogRecord($data, $type)
    {
        try {
            DB::table('cs_payment_logs')->insert([
                'cs_order_id' => $data['orderid'] ?? 0,
                'type' => $type,
                'amount' => $data['amount'] ?? 0,
                'transaction_id' => $data['transaction_id'] ?? '',
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentProcessor::saveOnlyPaymentLogRecord – ' . $e->getMessage());
        }
    }

    // ─── DB Helper: Order Payment Queries ───

    private function getActiveDepositTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 1)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveInsuranceTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 4)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveRentalTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 2)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveInitialFeeTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 3)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveEmfTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 16)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveDiaInsuranceTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 14)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveLateFeeTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 19)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getActiveTollTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 6)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function getTotalInsurance($orderId)
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 4)->where('status', 1)
            ->sum('amount');
    }

    private function getTotalDiaInsurance($orderId)
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 14)->where('status', 1)
            ->sum('amount');
    }

    private function getTotalDeposit($orderId)
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 1)->where('status', 1)
            ->sum('amount');
    }

    private function getTotalRentalTax($orderId)
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 2)->where('status', 1)
            ->selectRaw('COALESCE(SUM(rent),0) as rent, COALESCE(SUM(tax),0) as tax, COALESCE(SUM(dia_fee),0) as dia_fee')
            ->first();
        return $row ? (array) $row : ['rent' => 0, 'tax' => 0, 'dia_fee' => 0];
    }

    private function getTotalEmf($orderId)
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 16)->where('status', 1)
            ->selectRaw('COALESCE(SUM(amount),0) as emf, COALESCE(SUM(tax),0) as tax')
            ->first();
        return $row ? (array) $row : ['emf' => 0, 'tax' => 0];
    }

    private function getTotalInitialFee($orderId)
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 3)->where('status', 1)
            ->selectRaw('COALESCE(SUM(amount),0) as initial_fee, COALESCE(SUM(tax),0) as initial_fee_tax')
            ->first();
        return $row ? (array) $row : ['initial_fee' => 0, 'initial_fee_tax' => 0];
    }

    private function getTotalPaidLateFee($orderId)
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 19)->where('status', 1)
            ->sum('amount');
    }

    private function getNoneTransferredDepositTransaction($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('type', 1)->where('status', 1)
            ->where(function ($q) { $q->whereNull('cs_transfer')->orWhere('cs_transfer', 0); })
            ->get()->map(fn($r) => (array) $r)->toArray();
    }

    private function updateOrderPayments($data, $conditions)
    {
        $query = DB::table('cs_order_payments');
        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }
        $data['modified'] = now();
        $query->update($data);
    }

    private function findOrderById($id, $fields = ['*'])
    {
        $row = DB::table('cs_orders')->where('id', $id)->first($fields);
        return $row ? ['CsOrder' => (array) $row] : null;
    }

    private function saveOrderData($data)
    {
        if (!empty($data['CsOrder']['id'])) {
            $id = $data['CsOrder']['id'];
            $update = $data['CsOrder'];
            unset($update['id']);
            $update['modified'] = now();
            DB::table('cs_orders')->where('id', $id)->update($update);
        }
    }

    // ─── DB Helper: Insert Order Payment records ───

    private function insertOrderPayment($data)
    {
        $data['created'] = $data['created'] ?? now();
        $data['modified'] = now();
        $data['charged_at'] = $data['charged_at'] ?? now();
        $data['status'] = $data['status'] ?? 1;
        return DB::table('cs_order_payments')->insertGetId($data);
    }

    private function saveDepositTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $type = 'C')
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 1, 'txntype' => $type, 'status' => 1,
        ]);
    }

    private function saveInsuranceTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $payerId = null)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 4, 'status' => 1, 'payer_id' => $payerId,
        ]);
    }

    private function saveRentalTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $tax = 0, $diaFee = 0)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'rent' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 2, 'status' => 1, 'tax' => $tax, 'dia_fee' => $diaFee,
        ]);
    }

    private function saveInitialFeeTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $tax = 0)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'rent' => $amount - $tax,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 3, 'status' => 1, 'tax' => $tax,
        ]);
    }

    private function saveEmfTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $tax = 0)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'rent' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 16, 'status' => 1, 'tax' => $tax,
        ]);
    }

    private function saveDiaInsuranceTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId, $payerId = null)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 14, 'status' => 1, 'payer_id' => $payerId,
        ]);
    }

    private function saveLateFeeTransactionRecord($orderId, $currency, $renterId, $amount, $transactionId)
    {
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $currency, 'renter_id' => $renterId,
            'amount' => $amount, 'rent' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 19, 'status' => 1, 'tax' => 0, 'dia_fee' => 0,
        ]);
    }

    private function saveTollTransactionRecord($orderId, $amount, $transactionId, $ownerId)
    {
        $order = DB::table('cs_orders')->where('id', $orderId)->first(['currency', 'renter_id']);
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $order->currency ?? 'USD',
            'renter_id' => $order->renter_id ?? 0,
            'amount' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 6, 'status' => 1,
        ]);
    }

    private function saveTDKTransactionRecord($orderId, $amount, $transactionId, $typeId, $ownerId = null)
    {
        $order = DB::table('cs_orders')->where('id', $orderId)->first(['currency', 'renter_id']);
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $order->currency ?? 'USD',
            'renter_id' => $order->renter_id ?? 0,
            'amount' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => $typeId, 'status' => 1,
        ]);
    }

    private function saveCustomerBalanceTransactionRecord($orderId, $amount, $transactionId, $ownerId)
    {
        $order = DB::table('cs_orders')->where('id', $orderId)->first(['currency', 'renter_id']);
        $this->insertOrderPayment([
            'cs_order_id' => $orderId, 'currency' => $order->currency ?? 'USD',
            'renter_id' => $order->renter_id ?? 0,
            'amount' => $amount,
            'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'type' => 7, 'status' => 1,
        ]);
    }

    private function copyDeposits($fromOrderId, $toOrderId)
    {
        $deposits = DB::table('cs_order_payments')
            ->where('cs_order_id', $fromOrderId)->where('type', 1)->where('status', 1)->get();
        foreach ($deposits as $dep) {
            $data = (array) $dep;
            unset($data['id']);
            $data['cs_order_id'] = $toOrderId;
            $data['created'] = now();
            $data['modified'] = now();
            DB::table('cs_order_payments')->insert($data);
        }
    }

    private function getAllOrderPayments($orderId)
    {
        return DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)->where('status', 1)
            ->get()->map(fn($r) => ['CsOrderPayment' => (array) $r])->toArray();
    }

    private function saveInPayoutTransaction($data)
    {
        try {
            if (!empty($data['transaction_id'])) {
                DB::table('cs_payout_transactions')->insert([
                    'transaction_id' => $data['transaction_id'],
                    'cs_order_id' => $data['order_id'] ?? 0,
                    'type' => $data['type'] ?? 0,
                    'status' => 1,
                    'created' => now(),
                    'modified' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('saveInPayoutTransaction: ' . $e->getMessage());
        }
    }

    private function assignBookingIdToPayoutTransaction($data)
    {
        if (!empty($data['transaction_id'])) {
            DB::table('cs_payout_transactions')
                ->where('transaction_id', $data['transaction_id'])
                ->update(['cs_order_id' => $data['order_id'], 'modified' => now()]);
        }
    }

    // ─── DB Helper: Payout Transaction Queries ───

    private function getActivePayoutTransactions($orderId, $paymentId)
    {
        $row = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)->where('cs_payment_id', $paymentId)->where('status', 1)
            ->first();
        return $row ? (array) $row : [];
    }

    private function getAllActivePayoutTransactions($orderId, $type = 2)
    {
        return DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)->where('type', $type)->where('status', 1)
            ->get()->map(fn($r) => ['CsPayoutTransaction' => (array) $r])->toArray();
    }

    private function savePayoutTransactions($meta, $amount, $result, $type = 2)
    {
        try {
            DB::table('cs_payout_transactions')->insert([
                'cs_order_id' => $meta['cs_order_id'] ?? 0,
                'user_id' => $meta['user_id'] ?? 0,
                'type' => $type,
                'amount' => $amount,
                'currency' => $meta['currency'] ?? 'USD',
                'base_amt' => $amount,
                'base_currency' => 'USD',
                'transaction_id' => $result['stripe_id'] ?? '',
                'transfer_id' => $result['stripe_id'] ?? '',
                'balance_transaction' => $result['balance_transaction'] ?? '',
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('savePayoutTransactions: ' . $e->getMessage());
        }
    }

    private function saveRefundPayoutTransactions($txn, $amount, $result)
    {
        try {
            DB::table('cs_payout_transactions')->where('id', $txn['id'])->update(['status' => 2, 'modified' => now()]);
            DB::table('cs_payout_transactions')->insert([
                'cs_order_id' => $txn['cs_order_id'] ?? 0,
                'cs_payment_id' => $txn['cs_payment_id'] ?? 0,
                'user_id' => $txn['user_id'] ?? 0,
                'type' => 11,
                'amount' => 0,
                'refund' => $amount,
                'currency' => $txn['currency'] ?? 'USD',
                'base_amt' => 0,
                'base_currency' => 'USD',
                'transaction_id' => $txn['transaction_id'] ?? '',
                'transfer_id' => $txn['transfer_id'] ?? '',
                'balance_transaction' => is_array($result) ? ($result['balance_transaction'] ?? '') : '',
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('saveRefundPayoutTransactions: ' . $e->getMessage());
        }
    }

    // ─── Wallet Helper Methods ───

    private function walletChargeFromWallet($userId, $amount, $description, $type, $orderId = null)
    {
        $wallet = DB::table('cs_wallets')->where('user_id', $userId)->first();
        $balance = $wallet ? (float) $wallet->balance : 0;
        if ($balance <= 0 || $amount <= 0) {
            return ['status' => false, 'transactions' => null];
        }
        if ($balance >= $amount) {
            DB::table('cs_wallets')->where('user_id', $userId)->update([
                'balance' => DB::raw("balance - {$amount}"), 'modified' => now(),
            ]);
            $txnId = DB::table('cs_wallet_transactions')->insertGetId([
                'user_id' => $userId, 'amount' => $amount, 'type' => 'debit',
                'payment_type' => $type, 'description' => $description,
                'cs_order_id' => $orderId, 'created' => now(), 'modified' => now(),
            ]);
            return [
                'status' => true,
                'transactions' => [['amt' => $amount, 'transaction_id' => 'W' . $txnId, 'source' => 'wallet', 'charged_at' => now()->toDateTimeString()]],
            ];
        }
        return ['status' => false, 'transactions' => null];
    }

    private function walletChargePartialFromWallet($userId, $amount, $description, $orderId, $type)
    {
        $wallet = DB::table('cs_wallets')->where('user_id', $userId)->first();
        $balance = $wallet ? (float) $wallet->balance : 0;
        if ($balance <= 0 || $amount <= 0) {
            return ['status' => false, 'transactions' => null, 'pending' => $amount];
        }
        $chargeAmt = min($balance, $amount);
        $pending = sprintf('%0.2f', $amount - $chargeAmt);
        DB::table('cs_wallets')->where('user_id', $userId)->update([
            'balance' => DB::raw("balance - {$chargeAmt}"), 'modified' => now(),
        ]);
        $txnId = DB::table('cs_wallet_transactions')->insertGetId([
            'user_id' => $userId, 'amount' => $chargeAmt, 'type' => 'debit',
            'payment_type' => $type, 'description' => $description,
            'cs_order_id' => $orderId, 'created' => now(), 'modified' => now(),
        ]);
        return [
            'status' => true,
            'transactions' => [['amt' => $chargeAmt, 'transaction_id' => 'W' . $txnId, 'source' => 'wallet', 'charged_at' => now()->toDateTimeString()]],
            'pending' => (float) $pending,
        ];
    }

    private function walletAddBalance($amount, $userId, $transactionId, $description, $orderId, $chargedAt = null)
    {
        if ($amount <= 0) return;
        DB::table('cs_wallets')->updateOrInsert(
            ['user_id' => $userId],
            ['balance' => DB::raw("COALESCE(balance,0) + {$amount}"), 'modified' => now()]
        );
        DB::table('cs_wallet_transactions')->insert([
            'user_id' => $userId, 'amount' => $amount, 'type' => 'credit',
            'description' => $description, 'transaction_id' => is_array($transactionId) ? json_encode($transactionId) : $transactionId,
            'cs_order_id' => $orderId, 'charged_at' => $chargedAt, 'created' => now(), 'modified' => now(),
        ]);
    }

    // ─── Stripe Connect Token ───

    private function createConnectAccountToken($customertoken, $stripe_account)
    {
        return $this->Stripe->createCardToken(['customer' => $customertoken], ["stripe_account" => $stripe_account]);
    }

    // ═══════════════════════════════════════════
    // CARD MANAGEMENT
    // ═══════════════════════════════════════════

    public function addNewCard($dataValues, $cust_id = '')
    {
        $return = ['status' => 'error', 'authcode' => '', 'message' => 'Required inputs are missing'];
        $dataValues->credit_card_number = preg_replace("/[^0-9]/", "", $dataValues->credit_card_number);
        if (empty($dataValues->credit_card_number) || empty($dataValues->cvv) || empty($dataValues->expiration)) {
            return $return;
        }
        $ccexpdate = explode("/", $dataValues->expiration);
        $this->stripe();
        $result = $this->Stripe->createCardToken([
            "card" => [
                "number" => $dataValues->credit_card_number,
                "exp_month" => $ccexpdate[0],
                "exp_year" => $ccexpdate[1],
                "cvc" => $dataValues->cvv,
                "name" => $dataValues->card_holder_name,
                "address_zip" => $dataValues->zip,
                "address_city" => $dataValues->city,
                "address_state" => $dataValues->state,
                "address_country" => ($dataValues->country != '' ? $dataValues->country : 'US'),
                "address_line1" => $dataValues->address,
            ],
        ]);
        if (!isset($result['status']) || $result['status'] != 'success') {
            $return['message'] = $result['msg'] ?? ($result['message'] ?? 'Card token creation failed');
            return $return;
        }
        if (!empty($cust_id)) {
            $result2 = $this->Stripe->addCardToCustomer($cust_id, $result['token'], ["name" => $dataValues->card_holder_name]);
            if (isset($result2['status']) && $result2['status'] == 'success') {
                $return['status'] = 'success';
                $return['stripe_token'] = $cust_id;
                $return['card_id'] = $result2['stripe_id'];
                $return['card_funding'] = $result['card_funding'];
            } else {
                $return['message'] = $result2;
            }
            return $return;
        }
        $result1 = $this->Stripe->customerCreate(["stripeToken" => $result['token']]);
        if (isset($result1['status']) && $result1['status'] == 'success') {
            $return['status'] = 'success';
            $return['stripe_token'] = $result1['stripe_id'];
            $return['card_id'] = $result['card_id'];
            $return['card_funding'] = $result['card_funding'];
        } else {
            $return['message'] = $result1;
        }
        return $return;
    }

    public function addCardToCustomer($cust_id, $card_id, $opt = [])
    {
        $this->stripe();
        return $this->Stripe->addCardToCustomer($cust_id, $card_id, $opt);
    }

    public function makeCardDefault($cust_id, $card_id)
    {
        $this->stripe();
        return $this->Stripe->makeCardDefault($cust_id, ["default_source" => $card_id]);
    }

    public function deleteCustomerCard($cust_id, $card_id)
    {
        $this->stripe();
        return $this->Stripe->deleteCustomerCard($cust_id, $card_id);
    }

    public function customerDelete($cust_id)
    {
        $return = ['status' => 'error', 'message' => 'Required inputs are missing'];
        if (empty($cust_id)) return $return;
        $this->stripe();
        $this->Stripe->customerDelete(['cust_id' => $cust_id]);
        return true;
    }

    // ═══════════════════════════════════════════
    // CUSTOMER / DEALER LOOKUPS
    // ═══════════════════════════════════════════

    public function getdealerourcekey($userid)
    {
        $row = DB::table('users')->where('id', $userid)->first(['stripe_key', 'currency']);
        return $row ? (array) $row : '';
    }

    public function getCustomer($customerid, $cc_token_id = '')
    {
        if (!empty($cc_token_id)) {
            $token = DB::table('user_cc_tokens')->where('id', $cc_token_id)->first();
            if ($token) {
                return [
                    'UserCcToken' => (array) $token,
                    'User' => ['id' => $token->user_id, 'currency' => 'USD'],
                ];
            }
        }
        if (!empty($customerid)) {
            $user = DB::table('users')
                ->leftJoin('user_cc_tokens', 'user_cc_tokens.id', '=', 'users.cc_token_id')
                ->where('users.id', $customerid)
                ->select('user_cc_tokens.*', 'users.email', 'users.id as user_id', 'users.currency')
                ->first();
            if ($user) {
                $usrData = [
                    'UserCcToken' => (array) $user,
                    'User' => ['id' => $user->user_id, 'email' => $user->email ?? '', 'currency' => $user->currency ?? 'USD'],
                ];
                $usrData['UserCcToken']['stripe_token'] = $user->stripe_token ?? '';
                $usrData['UserCcToken']['card_funding'] = $user->card_funding ?? '';
                $usrData['UserCcToken']['is_dealer'] = 0;
                if (($usrData['UserCcToken']['stripe_token'] ?? '') == 'HTICH' || ($usrData['UserCcToken']['card_funding'] ?? '') == 'fake') {
                    $dealerid = config($usrData['UserCcToken']['stripe_token']);
                    $dealer = DB::table('users')->where('id', $dealerid)->first(['id', 'stripe_key', 'currency']);
                    if (empty($dealer)) return [];
                    $usrData['UserCcToken']['stripe_token'] = $dealer->stripe_key;
                    $usrData['UserCcToken']['is_dealer'] = 1;
                    $usrData['User']['id'] = $dealer->id;
                }
                return $usrData;
            }
        }
        return [];
    }

    // ═══════════════════════════════════════════
    // CHARGE INSURANCE FROM DEALER
    // ═══════════════════════════════════════════

    public function chargeInsuranceFromDealer($return, $amt, $ownerid, $statement = '', $setting = [], $CsOrderId = '', $dia = false)
    {
        $stripeKey = $this->getdealerourcekey($ownerid);
        $maxNegativeBalance = !empty($setting) && !empty($setting['max_stripe_balance']) ? abs($setting['max_stripe_balance']) : 1000;
        $balanceResp = $this->retrieveBalance($ownerid, $stripeKey['stripe_key'] ?? '');
        if ($balanceResp['status'] != 'success' || $balanceResp['balance'] < -($maxNegativeBalance)) {
            $return['message'] = "Sorry, dealer dont have enough balance to pay insurance fee";
            $return['status'] = "error";
            $return[$dia ? 'dia_insu_status' : 'insu_status'] = 2;
            return $return;
        }
        $this->stripe();
        $insuresult = $this->Stripe->charge([
            "amount" => $amt,
            "currency" => $stripeKey['currency'] ?? 'USD',
            "source" => $stripeKey['stripe_key'],
            "capture" => true,
            "description" => $dia ? "DIA INS&FEE ADDON" : "DIA Insurance Paid By Dealer",
            "statement_descriptor" => $dia ? "DIA INS&FEE ADDON" : "DIA INS&FEES " . $statement,
            "metadata" => ["payer_id" => $ownerid],
        ]);
        if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
            if ($dia) {
                $return['dia_insu_transaction_id'] = $insuresult['stripe_id'];
                $return['dia_insu'] = $amt;
                $return['dia_insu_status'] = 1;
            } else {
                $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                $return['insurance_amt'] = $amt;
                $return['insu_status'] = 1;
            }
            $return['insu_payerid'] = $ownerid;
            $return['status'] = "success";
            $this->savePayoutTransactions(['user_id' => $ownerid, 'cs_order_id' => $CsOrderId, "currency" => $stripeKey['currency'] ?? 'USD'], $amt, $insuresult, 4);
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => ($dia ? 26 : 10), "amount" => $amt, "transaction_id" => $insuresult['stripe_id'], "status" => 1]);
        } else {
            $return['status'] = "error";
            $return['message'] = "Sorry, dealer dont have enough balance, to pay insurance";
            $return[$dia ? 'dia_insu_status' : 'insu_status'] = 2;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // BOOKING CREATE: checkAndProcessForMobile
    // ═══════════════════════════════════════════

    public function checkAndProcessForMobile($renterid, $owner_id, $priceRulesAmt = [])
    {
        if (empty($priceRulesAmt)) {
            return ['status' => 'error', 'message' => 'Payment Details not saved', 'payment_id' => ''];
        }
        $usrData = $this->getCustomer($renterid);
        $return = [
            'rent' => $priceRulesAmt['time_fee'], 'tax' => $priceRulesAmt['tax'],
            'dia_fee' => $priceRulesAmt['dia_fee'], 'deposit' => $priceRulesAmt['deposit_amt'],
            'deposit_type' => $priceRulesAmt['deposit_type'], 'status' => 'success',
            'deposit_auth' => '', 'transaction_id' => '', 'renter_id' => $renterid,
            'user_id' => $owner_id, 'message' => 'Sorry, one of payment get failed',
            'insurance_amt' => 0, 'insurance_transaction_id' => '', 'emf_transaction_id' => '',
            'initial_fee' => $priceRulesAmt['initial_fee'],
            'initial_fee_tax' => $priceRulesAmt['initial_fee_tax'],
            'dpa_status' => 0, 'insu_status' => 0, 'payment_status' => 0,
            'infee_status' => 0, 'emf_status' => 0, 'currency' => $priceRulesAmt['currency'],
        ];
        $this->stripe();

        if ($priceRulesAmt['deposit_amt'] > 0 && ($priceRulesAmt['deposit_event'] == 'P' || $priceRulesAmt['deposit_event'] == 'D')) {
            $result = $this->walletChargeFromWallet($renterid, $priceRulesAmt['deposit_amt'], $priceRulesAmt['deposit_amt'] . ' Deposit amount from checkAndProcessForMobile', 1);
            if ($result['status']) {
                $return['deposit'] = $priceRulesAmt['deposit_amt'];
                $return['deposit_auth'] = $result['transactions'];
                $return['message'] = 'success';
                $return['dpa_status'] = 1;
                $return['deposit_type'] = "C";
            } else {
                $result = $this->Stripe->charge([
                    "amount" => $priceRulesAmt['deposit_amt'], "currency" => $priceRulesAmt['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => ($priceRulesAmt['deposit_type'] == 'P') ? false : true,
                    "description" => "DIA Deposit", "statement_descriptor" => "DIA Deposit",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $return['deposit'] = $priceRulesAmt['deposit_amt'];
                    $return['deposit_auth'] = $result['stripe_id'];
                    $return['message'] = 'success';
                    $return['dpa_status'] = 1;
                } else {
                    $return['message'] = $result;
                    $return['status'] = 'error';
                    $return['dpa_status'] = 2;
                }
            }
        }

        if ($return['status'] == 'success' && $priceRulesAmt['initial_fee'] > 0 && $priceRulesAmt['initial_event'] == 'P') {
            $initialfeeresult = $this->walletChargeFromWallet($renterid, ($priceRulesAmt['initial_fee'] + $priceRulesAmt['initial_fee_tax']), $priceRulesAmt['initial_fee'] . ' initial fee amount from checkAndProcessForMobile', 3);
            if ($initialfeeresult['status']) {
                $return['status'] = 'success';
                $return['initial_fee_id'] = $initialfeeresult['transactions'];
                $return['initial_fee_tax'] = $priceRulesAmt['initial_fee_tax'];
                $return['infee_status'] = 1;
            } else {
                $initialfeeObj = [
                    "amount" => ($priceRulesAmt['initial_fee'] + $priceRulesAmt['initial_fee_tax']),
                    "currency" => $priceRulesAmt['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA Initial Fee",
                    "statement_descriptor" => "DIA Initial Fee",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $initialfeeresult = $this->Stripe->charge($initialfeeObj);
                if (isset($initialfeeresult['status']) && $initialfeeresult['status'] == 'success') {
                    $return['status'] = 'success';
                    $return['initial_fee_id'] = $initialfeeresult['stripe_id'];
                    $return['initial_fee'] = $priceRulesAmt['initial_fee'];
                    $return['initial_fee_tax'] = $priceRulesAmt['initial_fee_tax'];
                    $return['infee_status'] = 1;
                } else {
                    $return['infee_status'] = 2;
                }
            }
        }

        if ($return['status'] == 'success' && $priceRulesAmt['time_fee'] > 0 && $priceRulesAmt['charge_rent_event'] == 'P') {
            $Rentresult = $this->walletChargeFromWallet($renterid, ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']), ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']) . ' rental fee amount from checkAndProcessForMobile', 2);
            if ($Rentresult['status']) {
                $return['transaction_id'] = $Rentresult['transactions'];
                $return['payment_status'] = 1;
            } else {
                $RentObj = [
                    "amount" => ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']),
                    "currency" => $priceRulesAmt['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA CAR", "statement_descriptor" => "DIA CAR",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $Rentresult = $this->Stripe->charge($RentObj);
                if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                    $return['transaction_id'] = $Rentresult['stripe_id'];
                    $return['rental_amt'] = ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']);
                    $return['payment_status'] = 1;
                } else {
                    $return['payment_status'] = 2;
                }
            }
        }

        if ($return['status'] == 'success' && ($priceRulesAmt['extra_mileage_fee'] ?? 0) > 0 && $priceRulesAmt['charge_rent_event'] == 'P') {
            $extra_mileage_fee = ($priceRulesAmt['extra_mileage_fee'] + ($priceRulesAmt['emf_tax'] ?? 0));
            $Rentresult = $this->walletChargeFromWallet($renterid, $extra_mileage_fee, $extra_mileage_fee . ' emf fee amount from checkAndProcessForMobile', 16);
            if ($Rentresult['status']) {
                $return['emf_transaction_id'] = $Rentresult['transactions'];
                $return['emf_status'] = 1;
            } else {
                $RentObj = [
                    "amount" => $extra_mileage_fee, "currency" => $priceRulesAmt['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA EMF", "statement_descriptor" => "DIA EMF",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $Rentresult = $this->Stripe->charge($RentObj);
                if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                    $return['emf_transaction_id'] = $Rentresult['stripe_id'];
                    $return['emf_fee'] = $extra_mileage_fee;
                    $return['emf_status'] = 1;
                } else {
                    $return['emf_status'] = 2;
                }
            }
        }

        if ($return['status'] == 'success' && $priceRulesAmt['insurance_amt'] > 0 && $priceRulesAmt['insurance_event'] == 'P') {
            if (($priceRulesAmt['insurance_payer'] ?? 0) == 1) {
                $CsSetting = DB::table('cs_settings')->where('user_id', $owner_id)->first(['max_stripe_balance']);
                $return = $this->chargeInsuranceFromDealer($return, $priceRulesAmt['insurance_amt'], $owner_id, "", $CsSetting ? (array) $CsSetting : []);
            } else {
                $insuresult = $this->walletChargeFromWallet($renterid, $priceRulesAmt['insurance_amt'], $priceRulesAmt['insurance_amt'] . ' insurance fee amount from checkAndProcessForMobile', 4);
                if ($insuresult['status']) {
                    $return['insurance_transaction_id'] = $insuresult['transactions'];
                    $return['insurance_amt'] = $priceRulesAmt['insurance_amt'];
                    $return['insu_status'] = 1;
                    $return['insu_payerid'] = $renterid;
                } else {
                    $stripe_token = $usrData['UserCcToken']['stripe_token'];
                    $insuresult = $this->Stripe->charge([
                        "amount" => $priceRulesAmt['insurance_amt'], "currency" => $priceRulesAmt['currency'],
                        "stripeCustomer" => $stripe_token, "capture" => true,
                        "description" => (($priceRulesAmt['insurance_payer'] ?? 0) == 1) ? "DIA Insurance Paid By Dealer" : "DIA Insurance",
                        "statement_descriptor" => "DIA INS&FEES",
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
                        $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                        $return['insurance_amt'] = $priceRulesAmt['insurance_amt'];
                        $return['insu_status'] = 1;
                        $return['insu_payerid'] = $renterid;
                    } else {
                        $return['insu_status'] = 2;
                    }
                }
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE AMOUNT (Booking Start Event)
    // ═══════════════════════════════════════════

    private function ChargeAmountDeposit($usrData, $CsOrder, $return, $DepositRule, $error)
    {
        if ($CsOrder['CsOrder']['dpa_status'] == 0 && $CsOrder['CsOrder']['deposit'] > 0 && $DepositRule['deposit_event'] == 'S') {
            $result = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['deposit'], $CsOrder['CsOrder']['deposit'] . ' deposit amount from ChargeAmount', $CsOrder['CsOrder']['id'], 1);
            if ($result['status']) {
                $return['deposit'] = $CsOrder['CsOrder']['deposit'];
                $return['deposit_auth'] = $result['transactions'];
                $return['dpa_status'] = 1;
                if ($result['pending']) {
                    $subresult = $this->Stripe->charge([
                        "amount" => $result['pending'], "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                        "description" => "DIA Deposit",
                        "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subresult['status']) && $subresult['status'] == 'success') {
                        $return['deposit_auth'][] = ["amt" => $result['pending'], "transaction_id" => $subresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 1, "amount" => $result['pending'], "transaction_id" => $subresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['dpa_status'] = 2; $error = true; $return['message'] = $subresult;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 1, "amount" => $result['pending'], "note" => $subresult, "status" => 2]);
                    }
                }
            } else {
                $result = $this->Stripe->charge([
                    "amount" => $CsOrder['CsOrder']['deposit'], "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => ($DepositRule['deposit_type'] == "P") ? false : true,
                    "description" => "DIA Deposit",
                    "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $return['deposit'] = $CsOrder['CsOrder']['deposit'];
                    $return['deposit_auth'] = $result['stripe_id'];
                    $return['dpa_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 1, "amount" => $CsOrder['CsOrder']['deposit'], "transaction_id" => $result['stripe_id'], "status" => 1]);
                } else {
                    $error = true; $return['dpa_status'] = 2; $return['message'] = $result;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 1, "amount" => $CsOrder['CsOrder']['deposit'], "note" => $result, "status" => 2]);
                }
            }
        }
        return [$error, $return];
    }

    private function ChargeAmountInitialFee($usrData, $CsOrder, $return, $error)
    {
        if ($CsOrder['CsOrder']['infee_status'] == 0 && ($CsOrder['CsOrder']['initial_fee'] > 0 || $CsOrder['CsOrder']['initial_fee_tax'] > 0) && $error) {
            $return['infee_status'] = 2;
        }
        if ($CsOrder['CsOrder']['initial_fee'] == 0 && $CsOrder['CsOrder']['initial_fee_tax']) {
            $return['infee_status'] = 1;
        }
        if (!$error && $CsOrder['CsOrder']['infee_status'] == 0 && $CsOrder['CsOrder']['initial_fee'] > 0 && $CsOrder['CsOrder']['initial_event'] == 'S') {
            $initialfeeresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], ($CsOrder['CsOrder']['initial_fee'] + $CsOrder['CsOrder']['initial_fee_tax']), $CsOrder['CsOrder']['initial_fee'] . ' initial fee amount from ChargeAmount', $CsOrder['CsOrder']['id'], 3);
            if ($initialfeeresult['status']) {
                $return['initial_fee'] = $CsOrder['CsOrder']['initial_fee'];
                $return['initial_fee_tax'] = $CsOrder['CsOrder']['initial_fee_tax'];
                $return['initial_fee_id'] = $initialfeeresult['transactions'];
                $return['infee_status'] = 1;
                if ($initialfeeresult['pending'] > 0) {
                    $subinitialfeeresult = $this->Stripe->charge([
                        "amount" => $initialfeeresult['pending'], "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                        "description" => "DIA Initial Fee",
                        "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subinitialfeeresult['status']) && $subinitialfeeresult['status'] == 'success') {
                        $return['initial_fee_id'][] = ["amt" => $initialfeeresult['pending'], "transaction_id" => $subinitialfeeresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $initialfeeresult['pending'], "transaction_id" => $subinitialfeeresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['infee_status'] = 2; $error = true; $return['message'] = $subinitialfeeresult;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $initialfeeresult['pending'], "note" => $subinitialfeeresult, "status" => 2]);
                    }
                }
            } else {
                $initialfeeOj = [
                    "amount" => ($CsOrder['CsOrder']['initial_fee'] + $CsOrder['CsOrder']['initial_fee_tax']),
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA Initial Fee",
                    "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $initialfeeresult = $this->Stripe->charge($initialfeeOj);
                if (isset($initialfeeresult['status']) && $initialfeeresult['status'] == 'success') {
                    $return['initial_fee'] = $CsOrder['CsOrder']['initial_fee'];
                    $return['initial_fee_tax'] = $CsOrder['CsOrder']['initial_fee_tax'];
                    $return['initial_fee_id'] = $initialfeeresult['stripe_id'];
                    $return['infee_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $CsOrder['CsOrder']['initial_fee'], "transaction_id" => $initialfeeresult['stripe_id'], "status" => 1]);
                } else {
                    $error = true; $return['message'] = $initialfeeresult; $return['infee_status'] = 2;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $CsOrder['CsOrder']['initial_fee'], "note" => $initialfeeresult, "status" => 2]);
                }
            }
        }
        return [$error, $return];
    }

    private function ChargeAmountInsuranceFee($usrData, $CsOrder, $return, $DepositRule, $error)
    {
        if ($CsOrder['CsOrder']['insu_status'] == 0 && $CsOrder['CsOrder']['insurance_amt'] > 0 && $error) {
            $return['insu_status'] = 2;
        }
        if ($CsOrder['CsOrder']['insurance_amt'] == 0) {
            $return['insu_status'] = 1;
            return [$error, $return];
        }
        if ($error || $DepositRule['insurance_event'] != 'S') {
            return [$error, $return];
        }
        $newamt = $CsOrder['CsOrder']['insurance_amt'];
        $PrePaidInsu = $this->getTotalInsurance($CsOrder['CsOrder']['id']);
        $amt = $newamt - $PrePaidInsu;
        if ($amt == 0 || $amt < 0) {
            $return['insu_status'] = 1;
            return [$error, $return];
        }
        if ($CsOrder['CsOrder']['insurance_payer'] == 1) {
            $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['CsOrder']['user_id'])->first(['max_stripe_balance']);
            $return = $this->chargeInsuranceFromDealer($return, $amt, $CsOrder['CsOrder']['user_id'], date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])), $CsSetting ? (array) $CsSetting : [], $CsOrder['CsOrder']['id']);
            return [$error, $return];
        }
        $insuresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $amt, $amt . ' insurance fee from ChargeAmountInsuranceFee', $CsOrder['CsOrder']['id'], 4);
        if ($insuresult['status']) {
            $return['insurance_transaction_id'] = $insuresult['transactions'];
            $return['insurance_amt'] = $amt;
            $return['insu_status'] = 1;
            $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
            if ($insuresult['pending'] > 0) {
                $subinsuresult = $this->Stripe->charge([
                    "amount" => $insuresult['pending'], "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA Insurance",
                    "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                    $return['insurance_transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                } else {
                    $return['insu_status'] = 2; $error = true; $return['message'] = $subinsuresult;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                }
            }
        } else {
            $insuresult = $this->Stripe->charge([
                "amount" => $amt, "currency" => $CsOrder['CsOrder']['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                "description" => ($CsOrder['CsOrder']['insurance_payer'] == 1) ? "DIA Insurance Paid By Dealer" : "DIA Insurance",
                "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
                $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                $return['insurance_amt'] = $amt;
                $return['insu_status'] = 1;
                $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $amt, "transaction_id" => $insuresult['stripe_id'], "status" => 1]);
            } else {
                $return['insu_status'] = 2; $error = true; $return['message'] = $insuresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $amt, "note" => $insuresult, "status" => 2]);
            }
        }
        return [$error, $return];
    }

    private function ChargeAmountRental($usrData, $CsOrder, $return, $DepositRule, $error)
    {
        if ($CsOrder['CsOrder']['payment_status'] == 0 && $CsOrder['CsOrder']['rent'] > 0 && $error) {
            $return['payment_status'] = 2;
        }
        $paidData = $this->getTotalRentalTax($CsOrder['CsOrder']['id']);
        $TotalRentPaid = sprintf('%0.2f', ($paidData['rent'] + $paidData['tax'] + $paidData['dia_fee']));
        $TotalRentOp = sprintf('%0.2f', ($CsOrder['CsOrder']['rent'] + $CsOrder['CsOrder']['tax'] + $CsOrder['CsOrder']['dia_fee']));
        $totalRent = sprintf('%0.2f', ($TotalRentOp - $TotalRentPaid));
        if ($totalRent == 0 || $totalRent < 0) {
            $return['payment_status'] = 1;
            return [$error, $return];
        }
        if ($error || $DepositRule['charge_rent_event'] != 'S') {
            return [$error, $return];
        }
        $rentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $totalRent, $totalRent . ' rental amount from ChargeAmount', $CsOrder['CsOrder']['id'], 2);
        if ($rentresult['status']) {
            $return['transaction_id'] = $rentresult['transactions'];
            $return['payment_status'] = 1;
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'], "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA CAR",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['payment_status'] = 2; $error = true; $return['message'] = $subrentresult;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                }
            }
        } else {
            $rentresult = $this->Stripe->charge([
                "amount" => $totalRent, "currency" => $CsOrder['CsOrder']['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                "description" => "DIA CAR",
                "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['payment_status'] = 1;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalRent, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $error = true; $return['message'] = $rentresult; $return['payment_status'] = 2;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalRent, "note" => $rentresult, "status" => 2]);
            }
        }
        return [$error, $return];
    }

    private function ChargeAmountEmf($usrData, $CsOrder, $return, $DepositRule, $error)
    {
        if ($CsOrder['CsOrder']['emf_status'] == 0 && $CsOrder['CsOrder']['extra_mileage_fee'] > 0 && $error) {
            $return['emf_status'] = 2;
        }
        $totalRent = sprintf('%0.2f', ($CsOrder['CsOrder']['extra_mileage_fee'] + $CsOrder['CsOrder']['emf_tax']));
        if ($totalRent == 0) {
            $return['emf_status'] = 1;
        }
        if (!$error && $CsOrder['CsOrder']['emf_status'] == 0 && $CsOrder['CsOrder']['extra_mileage_fee'] > 0 && $DepositRule['charge_rent_event'] == 'S') {
            $rentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $totalRent, $totalRent . ' emf amount from ChargeAmount', $CsOrder['CsOrder']['id'], 16);
            if ($rentresult['status']) {
                $return['emf_transaction_id'] = $rentresult['transactions'];
                $return['emf_status'] = 1;
                if ($rentresult['pending'] > 0) {
                    $subrentresult = $this->Stripe->charge([
                        "amount" => $rentresult['pending'], "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                        "description" => "DIA EMF",
                        "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                        $return['emf_transaction_id'][] = ["amt" => $rentresult['pending'], "emf_transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['emf_status'] = 2; $error = true; $return['message'] = $subrentresult;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                    }
                }
            } else {
                $rentresult = $this->Stripe->charge([
                    "amount" => $totalRent, "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA EMF",
                    "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                    $return['emf_transaction_id'] = $rentresult['stripe_id'];
                    $return['emf_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $totalRent, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $rentresult; $return['emf_status'] = 2; $error = true;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $totalRent, "note" => $rentresult, "status" => 2]);
                }
            }
        }
        return [$error, $return];
    }

    public function ChargeAmount($CsOrder, $DepositRule)
    {
        $usrData = $this->getCustomer($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['cc_token_id']);
        $this->stripe();
        $return = ['currency' => $CsOrder['CsOrder']['currency'], 'deposit' => $CsOrder['CsOrder']['deposit'], 'deposit_type' => $DepositRule['deposit_type'], 'status' => 'error', 'deposit_auth' => '', 'transaction_id' => '', 'message' => 'Sorry, one of payment get failed'];
        $error = false;
        list($error, $return) = $this->ChargeAmountDeposit($usrData, $CsOrder, $return, $DepositRule, $error);
        list($error, $return) = $this->ChargeAmountInitialFee($usrData, $CsOrder, $return, $error);
        list($error, $return) = $this->ChargeAmountInsuranceFee($usrData, $CsOrder, $return, $DepositRule, $error);
        list($error, $return) = $this->ChargeAmountRental($usrData, $CsOrder, $return, $DepositRule, $error);
        list($error, $return) = $this->ChargeAmountEmf($usrData, $CsOrder, $return, $DepositRule, $error);
        $return['status'] = !$error ? 'success' : "error";
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE AMOUNT ON COMPLETE (Booking Close Event)
    // ═══════════════════════════════════════════

    private function chargeInitialFee($CsOrder, $return, $usrData, $PaymentError = false)
    {
        $prePaidInitalAmt = $this->getTotalInitialFee($CsOrder['CsOrder']['id']);
        $balanceInitialAmt = sprintf('%0.2f', ($CsOrder['CsOrder']['initial_fee'] - $prePaidInitalAmt['initial_fee']));
        $balanceTax = sprintf('%0.2f', ($CsOrder['CsOrder']['initial_fee_tax'] - $prePaidInitalAmt['initial_fee_tax']));
        if ($balanceInitialAmt == 0 && $balanceTax == 0) {
            return [$return, $PaymentError];
        }
        $initialfeeresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], ($balanceInitialAmt + $balanceTax), ($balanceInitialAmt + $balanceTax) . ' fixed amount charged from ChargeAmountOnComplete', $CsOrder['CsOrder']['id'], 3);
        if ($initialfeeresult['status']) {
            $return['initial_fee_id'] = $initialfeeresult['transactions'];
            $return['infee_status'] = 1;
            if ($initialfeeresult['pending'] > 0) {
                $subinitialfeeresult = $this->Stripe->charge([
                    "amount" => $initialfeeresult['pending'],
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Initial Fee",
                    "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinitialfeeresult['status']) && $subinitialfeeresult['status'] == 'success') {
                    $return['initial_fee_id'][] = ["amt" => $initialfeeresult['pending'], "transaction_id" => $subinitialfeeresult['stripe_id'], "tax" => $balanceTax, "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $balanceInitialAmt, "transaction_id" => $subinitialfeeresult['transaction_id'] ?? '', "status" => 1]);
                } else {
                    $return['bad_debt'] = ($return['bad_debt'] ?? 0) + $initialfeeresult['pending'];
                    $return['infee_status'] = 2;
                    $PaymentError = true;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $balanceInitialAmt, "note" => $subinitialfeeresult, "status" => 2]);
                }
            }
            return [$return, $PaymentError];
        }

        $initialfeeObj = [
            "amount" => ($balanceInitialAmt + $balanceTax),
            "currency" => $CsOrder['CsOrder']['currency'],
            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
            "capture" => true,
            "description" => "DIA Initial Fee",
            "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
            "metadata" => ["payer_id" => $usrData['User']['id']],
        ];
        $initialfeeresult = $this->Stripe->charge($initialfeeObj);
        if (isset($initialfeeresult['status']) && $initialfeeresult['status'] == 'success') {
            $return['initial_fee_id'] = $initialfeeresult['stripe_id'];
            $return['infee_status'] = 1;
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $balanceInitialAmt, "transaction_id" => $initialfeeresult['stripe_id'], "status" => 1]);
        } else {
            $return['message'] = $initialfeeresult;
            $PaymentError = true;
            $return['infee_status'] = 2;
            $return['bad_debt'] = ($return['bad_debt'] ?? 0) + ($CsOrder['CsOrder']['initial_fee'] + $balanceTax);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 15, "amount" => $balanceInitialAmt, "note" => $initialfeeresult, "status" => 2]);
        }
        return [$return, $PaymentError];
    }

    private function ChargeDiaInsurance($CsOrder, $return, $usrData, $PaymentError = false, $isComplete = false)
    {
        $PrePaid = $this->getTotalDiaInsurance($CsOrder['CsOrder']['id']);
        if ($CsOrder['CsOrder']['dia_insu'] == $PrePaid) {
            $return['dia_insu_status'] = 1;
            return [$return, $PaymentError];
        }
        $balanceAmt = $PrePaid < $CsOrder['CsOrder']['dia_insu'] ? sprintf('%0.2f', ($CsOrder['CsOrder']['dia_insu'] - $PrePaid)) : 0;

        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['CsOrder']['user_id'])->first();

        if ($balanceAmt > 0) {
            if ($CsOrder['CsOrder']['insurance_payer'] == 1) {
                $return = $this->chargeInsuranceFromDealer($return, $balanceAmt, $CsOrder['CsOrder']['user_id'], date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])), $CsSetting ? (array) $CsSetting : [], $CsOrder['CsOrder']['id'], true);
                if ($return['status'] == 'error') {
                    $PaymentError = true;
                    $return['dia_insu_status'] = 2;
                }
            }

            if ($CsOrder['CsOrder']['insurance_payer'] != 1 && $CsOrder['CsOrder']['insurance_payer'] != 3) {
                $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
                $diaresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $balanceAmt, $balanceAmt . ' dia insurance fee from ChargeAmountOnComplete', $CsOrder['CsOrder']['id'], 14);
                if ($diaresult['status']) {
                    $return['dia_insu_transaction_id'] = $diaresult['transactions'];
                    $return['dia_insu'] = $balanceAmt;
                    $return['dia_insu_status'] = 1;

                    if ($diaresult['pending'] > 0) {
                        $diasubinsuresult = $this->Stripe->charge([
                            "amount" => $diaresult['pending'],
                            "currency" => $CsOrder['CsOrder']['currency'],
                            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                            "capture" => true,
                            "description" => "DIA INS&FEE ADDON",
                            "statement_descriptor" => "DIA INS&FEE ADDON",
                            "metadata" => ["payer_id" => $usrData['User']['id']],
                        ]);
                        if (isset($diasubinsuresult['status']) && $diasubinsuresult['status'] == 'success') {
                            $return['dia_insu_transaction_id'][] = ["amt" => $diaresult['pending'], "transaction_id" => $diasubinsuresult['stripe_id'], "source" => 'card'];
                            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 26, "amount" => $diaresult['pending'], "transaction_id" => $diasubinsuresult['stripe_id'], "status" => 1]);
                        } elseif ($isComplete) {
                            $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $diaresult['pending']) : $diaresult['pending'];
                            $return['dia_insu_status'] = 2;
                            $return['status'] = 'error';
                        }
                    }
                } else {
                    $diainsuresult = $this->Stripe->charge([
                        "amount" => $balanceAmt,
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA INS&FEE ADDON",
                        "statement_descriptor" => "DIA INS&FEE ADDON",
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($diainsuresult['status']) && $diainsuresult['status'] == 'success') {
                        $return['dia_insu_transaction_id'] = $diainsuresult['stripe_id'];
                        $return['dia_insu'] = $CsOrder['CsOrder']['dia_insu'];
                        $return['dia_insu_status'] = 1;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 26, "amount" => $balanceAmt, "transaction_id" => $diainsuresult['stripe_id'], "status" => 1]);
                    } elseif ($isComplete) {
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $balanceAmt) : $balanceAmt;
                        $return['dia_insu_status'] = 2;
                        $return['status'] = 'error';
                    }
                }
            }
        }
        if ($CsOrder['CsOrder']['dia_insu'] < $PrePaid) {
            $balance = ($PrePaid - $CsOrder['CsOrder']['dia_insu']) > 1 ? ($PrePaid - $CsOrder['CsOrder']['dia_insu']) : 0;
            $diainsurances = $this->getActiveDiaInsuranceTransaction($CsOrder['CsOrder']['id']);
            $refundableAmt = $balance;
            foreach ($diainsurances as $insurance) {
                if (!$refundableAmt) {
                    break;
                }
                if ($insurance['amount'] <= $refundableAmt) {
                    $refundamount = $insurance['amount'];
                } elseif ($insurance['amount'] > $refundableAmt) {
                    $refundamount = $refundableAmt;
                }
                $refundableAmt = $refundableAmt - $refundamount;
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($refundamount, $insurance['payer_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                } else {
                    $this->walletAddBalance($refundamount, $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "partial dia insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                }
                if ($refundamount < $insurance['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($insurance['amount'] - $refundamount)], ['id' => $insurance['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "source" => 'wallet', 'type' => 14, 'charged_at' => $insurance['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 32, "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "status" => 1, "refundtransactionid" => '']);
            }
        }
        return [$return, $PaymentError];
    }

    private function ChargeDiaInsuranceForAutoRenew($CsOrder, $return, $usrData, $PaymentError = false)
    {
        $PrePaid = $this->getTotalDiaInsurance($CsOrder['CsOrder']['id']);
        if ($CsOrder['CsOrder']['dia_insu'] == $PrePaid) {
            $return['dia_insu_status'] = 1;
            return [$return, $PaymentError];
        }
        $balanceAmt = $PrePaid < $CsOrder['CsOrder']['dia_insu'] ? sprintf('%0.2f', ($CsOrder['CsOrder']['dia_insu'] - $PrePaid)) : 0;

        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['CsOrder']['user_id'])->first();

        if ($balanceAmt > 0) {
            if ($CsOrder['CsOrder']['insurance_payer'] == 1) {
                $return = $this->chargeInsuranceFromDealer($return, $balanceAmt, $CsOrder['CsOrder']['user_id'], date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])), $CsSetting ? (array) $CsSetting : [], $CsOrder['CsOrder']['id'], true);
                if ($return['status'] == 'error') {
                    $PaymentError = true;
                    $return['dia_insu_status'] = 2;
                }
            }

            if ($CsOrder['CsOrder']['insurance_payer'] != 1 && $CsOrder['CsOrder']['insurance_payer'] != 3) {
                $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
                $diaresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $balanceAmt, $balanceAmt . ' dia insurance fee from ChargeAmountOnComplete', $CsOrder['CsOrder']['id'], 14);
                if ($diaresult['status']) {
                    $return['dia_insu_transaction_id'] = $diaresult['transactions'];
                    $return['dia_insu'] = $balanceAmt;
                    $return['dia_insu_status'] = 1;

                    if ($diaresult['pending'] > 0) {
                        $diasubinsuresult = $this->Stripe->charge([
                            "amount" => $diaresult['pending'],
                            "currency" => $CsOrder['CsOrder']['currency'],
                            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                            "capture" => true,
                            "description" => "DIA INS&FEE ADDON",
                            "statement_descriptor" => "DIA INS&FEE ADDON",
                            "metadata" => ["payer_id" => $usrData['User']['id']],
                        ]);
                        if (isset($diasubinsuresult['status']) && $diasubinsuresult['status'] == 'success') {
                            $return['dia_insu_transaction_id'][] = ["amt" => $diaresult['pending'], "transaction_id" => $diasubinsuresult['stripe_id'], "source" => 'card'];
                            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 26, "amount" => $diaresult['pending'], "transaction_id" => $diasubinsuresult['stripe_id'], "status" => 1]);
                        } else {
                            $return['dia_insu_status'] = 2;
                            $return['message'] = $diasubinsuresult;
                            $PaymentError = true;
                        }
                    }
                } else {
                    $diainsuresult = $this->Stripe->charge([
                        "amount" => $balanceAmt,
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA INS&FEE ADDON",
                        "statement_descriptor" => "DIA INS&FEE ADDON",
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($diainsuresult['status']) && $diainsuresult['status'] == 'success') {
                        $return['dia_insu_transaction_id'] = $diainsuresult['stripe_id'];
                        $return['dia_insu'] = $CsOrder['CsOrder']['dia_insu'];
                        $return['dia_insu_status'] = 1;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 26, "amount" => $balanceAmt, "transaction_id" => $diainsuresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['dia_insu_status'] = 2;
                        $return['message'] = $diainsuresult;
                    }
                }
            }
        }
        if ($CsOrder['CsOrder']['dia_insu'] < $PrePaid) {
            $balance = ($PrePaid - $CsOrder['CsOrder']['dia_insu']) > 1 ? ($PrePaid - $CsOrder['CsOrder']['dia_insu']) : 0;
            $diainsurances = $this->getActiveDiaInsuranceTransaction($CsOrder['CsOrder']['id']);
            $refundableAmt = $balance;
            foreach ($diainsurances as $insurance) {
                if (!$refundableAmt) {
                    break;
                }
                if ($insurance['amount'] <= $refundableAmt) {
                    $refundamount = $insurance['amount'];
                } elseif ($insurance['amount'] > $refundableAmt) {
                    $refundamount = $refundableAmt;
                }
                $refundableAmt = $refundableAmt - $refundamount;
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($refundamount, $insurance['payer_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                } else {
                    $this->walletAddBalance($refundamount, $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "partial dia insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                }
                if ($refundamount < $insurance['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($insurance['amount'] - $refundamount)], ['id' => $insurance['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "source" => 'wallet', 'type' => 14, 'charged_at' => $insurance['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 32, "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "status" => 1, "refundtransactionid" => '']);
            }
        }
        return [$return, $PaymentError];
    }

    private function ChargeInsurance($CsOrder, $return, $usrData, $PaymentError = false)
    {
        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['CsOrder']['user_id'])->first();

        $PrePaidInsu = $this->getTotalInsurance($CsOrder['CsOrder']['id']);
        $balanceInsurance = sprintf('%0.2f', ($CsOrder['CsOrder']['insurance_amt'] - $PrePaidInsu));
        if ($balanceInsurance > 0) {
            if ($CsOrder['CsOrder']['insurance_payer'] == 1) {
                $return = $this->chargeInsuranceFromDealer($return, $balanceInsurance, $CsOrder['CsOrder']['user_id'], date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])), $CsSetting ? (array) $CsSetting : [], $CsOrder['CsOrder']['id']);
                if ($return['status'] == 'error') {
                    $PaymentError = true;
                }
            }
            if ($CsOrder['CsOrder']['insurance_payer'] != 1 && $CsOrder['CsOrder']['insurance_payer'] != 3) {
                $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
                $insuresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $balanceInsurance, $balanceInsurance . ' insurance fee from ChargeInsurance', $CsOrder['CsOrder']['id'], 4);
                if ($insuresult['status']) {
                    $return['insurance_transaction_id'] = $insuresult['transactions'];
                    $return['insurance_amt'] = $balanceInsurance;
                    $return['insu_status'] = 1;

                    if ($insuresult['pending'] > 0) {
                        $subinsuresult = $this->Stripe->charge([
                            "amount" => $insuresult['pending'],
                            "currency" => $CsOrder['CsOrder']['currency'],
                            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                            "capture" => true,
                            "description" => "DIA INS&FEES",
                            "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                            "metadata" => ["payer_id" => $usrData['User']['id']],
                        ]);
                        if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                            $return['insurance_transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                        } else {
                            $PaymentError = true;
                            $return['insu_status'] = 2;
                            $return['message'] = $subinsuresult;
                            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                        }
                    }
                } else {
                    $stripe_token = $usrData['UserCcToken']['stripe_token'];
                    $insuresult = $this->Stripe->charge([
                        "amount" => $balanceInsurance,
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $stripe_token,
                        "capture" => true,
                        "description" => "DIA INS&FEES",
                        "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
                        $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                        $return['insurance_amt'] = $balanceInsurance;
                        $return['insu_status'] = 1;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $balanceInsurance, "transaction_id" => $insuresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['insu_status'] = 2;
                        $return['message'] = $insuresult;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $balanceInsurance, "note" => $insuresult, "status" => 2]);
                    }
                }
            }
        }
        if (($CsOrder['CsOrder']['insurance_amt'] < $PrePaidInsu)) {
            $balanceInsurance = sprintf('%0.2f', ($PrePaidInsu - $CsOrder['CsOrder']['insurance_amt']));
            $insurances = $this->getActiveInsuranceTransaction($CsOrder['CsOrder']['id']);
            $refundableAmt = $balanceInsurance;
            foreach ($insurances as $insurance) {
                if (!$refundableAmt) {
                    break;
                }
                if ($insurance['amount'] <= $refundableAmt) {
                    $refundamount = $insurance['amount'];
                } elseif ($insurance['amount'] > $refundableAmt) {
                    $refundamount = $refundableAmt;
                }
                $refundableAmt = $refundableAmt - $refundamount;
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($refundamount, $insurance['payer_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                } else {
                    $this->walletAddBalance($refundamount, $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                }
                if ($refundamount < $insurance['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($insurance['amount'] - $refundamount)], ['id' => $insurance['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "source" => 'wallet', 'type' => 4, 'charged_at' => $insurance['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 13, "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "status" => 1, "refundtransactionid" => ""]);
            }
        }
        if ($CsOrder['CsOrder']['insurance_amt'] == $PrePaidInsu) {
            $return['insu_status'] = 1;
        }
        return [$return, $PaymentError];
    }

    private function ChargeInsuranceOnComplete($CsOrder, $return, $usrData, $PaymentError)
    {
        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['CsOrder']['user_id'])->first();

        $PrePaidInsu = $this->getTotalInsurance($CsOrder['CsOrder']['id']);
        $balanceInsurance = sprintf('%0.2f', ($CsOrder['CsOrder']['insurance_amt'] - $PrePaidInsu));
        if ($balanceInsurance > 0) {
            if ($CsOrder['CsOrder']['insurance_payer'] == 1) {
                $return = $this->chargeInsuranceFromDealer($return, $balanceInsurance, $CsOrder['CsOrder']['user_id'], date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])), $CsSetting ? (array) $CsSetting : [], $CsOrder['CsOrder']['id']);
                if ($return['status'] == 'error') {
                    $PaymentError = true;
                }
            }

            if ($CsOrder['CsOrder']['insurance_payer'] != 1 && $CsOrder['CsOrder']['insurance_payer'] != 3) {
                $return['insu_payerid'] = $CsOrder['CsOrder']['renter_id'];
                $insuresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $balanceInsurance, $balanceInsurance . ' insurance fee from ChargeAmountOnComplete', $CsOrder['CsOrder']['id'], 4);
                if ($insuresult['status']) {
                    $return['insurance_transaction_id'] = $insuresult['transactions'];
                    $return['insurance_amt'] = $balanceInsurance;
                    $return['insu_status'] = 1;

                    if ($insuresult['pending'] > 0) {
                        $subinsuresult = $this->Stripe->charge([
                            "amount" => $insuresult['pending'],
                            "currency" => $CsOrder['CsOrder']['currency'],
                            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                            "capture" => true,
                            "description" => "DIA INS&FEES",
                            "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                            "metadata" => ["payer_id" => $usrData['User']['id']],
                        ]);
                        if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                            $return['insurance_transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                        } else {
                            $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $insuresult['pending']) : $insuresult['pending'];
                            $return['insu_status'] = 2;
                            $return['status'] = 'error';
                        }
                    }
                } else {
                    $insuresult = $this->Stripe->charge([
                        "amount" => $balanceInsurance,
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA INS&FEES",
                        "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
                        $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                        $return['insurance_amt'] = $CsOrder['CsOrder']['insurance_amt'];
                        $return['insu_status'] = 1;
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 10, "amount" => $balanceInsurance, "transaction_id" => $insuresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $balanceInsurance) : $balanceInsurance;
                        $return['insu_status'] = 2;
                        $return['status'] = 'error';
                    }
                }
            }
        }
        if (($CsOrder['CsOrder']['insurance_amt'] < $PrePaidInsu)) {
            $balanceInsurance = sprintf('%0.2f', ($PrePaidInsu - $CsOrder['CsOrder']['insurance_amt']));
            $insurances = $this->getActiveInsuranceTransaction($CsOrder['CsOrder']['id']);
            $refundableAmt = $balanceInsurance;

            foreach ($insurances as $insurance) {
                if (!$refundableAmt) {
                    break;
                }
                if ($insurance['amount'] <= $refundableAmt) {
                    $refundamount = $insurance['amount'];
                } elseif ($insurance['amount'] > $refundableAmt) {
                    $refundamount = $refundableAmt;
                }
                $refundableAmt = $refundableAmt - $refundamount;
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($refundamount, $insurance['payer_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                } else {
                    $this->walletAddBalance($refundamount, $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "partial insurance refund from booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                }
                if ($refundamount < $insurance['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($insurance['amount'] - $refundamount)], ['id' => $insurance['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
                }
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 13, "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "status" => 1, "refundtransactionid" => '']);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $refundamount, "transaction_id" => $insurance['transaction_id'], "source" => 'wallet', 'type' => 4, 'charged_at' => $insurance['charged_at']]);
            }
        }
        if ($CsOrder['CsOrder']['insurance_amt'] == $PrePaidInsu) {
            $return['insu_status'] = 1;
        }
        return [$return, $PaymentError];
    }

    private function ChargeRental($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError)
    {
        $paidData = $this->getTotalRentalTax($CsOrder['CsOrder']['id']);
        $TotalRentPaid = sprintf('%0.2f', ($paidData['rent'] + $paidData['tax'] + $paidData['dia_fee']));
        $totalAmount = sprintf('%0.2f', ($CsOrder['CsOrder']['rent'] + $CsOrder['CsOrder']['tax'] + $CsOrder['CsOrder']['dia_fee'] + $CsOrder['CsOrder']['damage_fee'] + $CsOrder['CsOrder']['uncleanness_fee']));

        if (!$PaymentError && $totalAmount > 0 && $TotalRentPaid == 0) {
            $endrentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $totalAmount, $totalAmount . ' partial rental amount from ChargeRental', $CsOrder['CsOrder']['id'], 2);
            if ($endrentresult['status']) {
                $return['transaction_id'] = $endrentresult['transactions'];
                $return['new_payment'] = 1;
                $return['payment_status'] = 1;
                if ($endrentresult['pending'] > 0) {
                    $subendrentresult = $this->Stripe->charge([
                        "amount" => $endrentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA CAR",
                        "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subendrentresult['status']) && $subendrentresult['status'] == 'success') {
                        $return['transaction_id'][] = ["amt" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subendrentresult;
                        $return['payment_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $endrentresult['pending']) : $endrentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $endrentresult['pending'], "note" => $subendrentresult, "status" => 2]);
                    }
                }
            } else {
                $endRentObj = [
                    "amount" => $totalAmount,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA CAR",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $endrentresult = $this->Stripe->charge($endRentObj);
                if (isset($endrentresult['status']) && $endrentresult['status'] == 'success') {
                    $return['transaction_id'] = $endrentresult['stripe_id'];
                    $return['new_payment'] = 1;
                    $return['payment_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalAmount, "transaction_id" => $endrentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $endrentresult;
                    $return['payment_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $totalAmount) : $totalAmount;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalAmount, "note" => $endrentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount == $TotalRentPaid) {
            $return['payment_status'] = 1;
            $return['new_payment'] = 2;
        } elseif ($totalAmount > $TotalRentPaid && $TotalRentPaid != 0) {
            $remainngAmt = sprintf('%0.2f', ($totalAmount - $TotalRentPaid));
            $rentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $remainngAmt, $remainngAmt . ' partial rental amount from ChargeRental', $CsOrder['CsOrder']['id'], 2);
            if ($rentresult['status']) {
                $return['transaction_id'] = $rentresult['transactions'];
                $return['payment_status'] = 1;
                $return['new_payment'] = 3;
                $return['balance_rent'] = $totalAmount - $CsOrder['CsOrder']['tax'] - $CsOrder['CsOrder']['dia_fee'] - $paidData['rent'];
                $return['balance_tax'] = $CsOrder['CsOrder']['tax'] - $paidData['tax'];
                $return['balance_dia_fee'] = $CsOrder['CsOrder']['dia_fee'] - $paidData['dia_fee'];
                if ($rentresult['pending'] > 0) {
                    $subrentresult = $this->Stripe->charge([
                        "amount" => $rentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA CAR",
                        "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                        $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subrentresult;
                        $return['payment_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $rentresult['pending']) : $rentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                    }
                }
            } else {
                $RentObj = [
                    "amount" => $remainngAmt,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA CAR",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $rentresult = $this->Stripe->charge($RentObj);
                if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                    $return['status'] = 'success';
                    $return['transaction_id'] = $rentresult['stripe_id'];
                    $return['payment_status'] = 1;
                    $return['new_payment'] = 3;
                    $return['balance_rent'] = $totalAmount - $CsOrder['CsOrder']['tax'] - $CsOrder['CsOrder']['dia_fee'] - $paidData['rent'];
                    $return['balance_tax'] = $CsOrder['CsOrder']['tax'] - $paidData['tax'];
                    $return['balance_dia_fee'] = $CsOrder['CsOrder']['dia_fee'] - $paidData['dia_fee'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $remainngAmt, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $rentresult;
                    $return['payment_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $remainngAmt) : $remainngAmt;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $remainngAmt, "note" => $rentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount < $TotalRentPaid) {
            $refundableAmt = ($paidData['rent'] + $paidData['dia_fee']) - ($CsOrder['CsOrder']['rent'] + $CsOrder['CsOrder']['dia_fee'] + $CsOrder['CsOrder']['damage_fee'] + $CsOrder['CsOrder']['uncleanness_fee']);
            $refundTax = $paidData['tax'] - $CsOrder['CsOrder']['tax'];
            if ($refundableAmt <= 0) {
                $return['payment_status'] = 1;
                return [$return, $PaymentError];
            }
            $refundamount = 0;
            $pendingtax = $refundTax;
            $rentals = $this->getActiveRentalTransaction($CsOrderTemp['CsOrder']['id']);

            foreach ($rentals as $rental) {
                if (!$refundableAmt) {
                    break;
                }
                $totalRefundAmount = 0;
                if ($rental['amount'] <= $refundableAmt) {
                    $totalRefundAmount = $refundamount = $rental['amount'];
                } elseif ($rental['amount'] > $refundableAmt) {
                    $totalRefundAmount = $refundamount = $refundableAmt;
                }
                if ($rental['tax'] >= $refundTax) {
                    $pendingtax = $rental['tax'] - $refundTax;
                    $totalRefundAmount += $refundTax;
                    $refundTax = 0;
                } else {
                    $refundTax -= $rental['tax'];
                    $pendingtax = 0;
                    $totalRefundAmount += $rental['tax'];
                }
                $refundableAmt = $refundableAmt - $refundamount;
                $this->walletAddBalance($totalRefundAmount, $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "partial rental refund from booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                if ($totalRefundAmount < $rental['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $totalRefundAmount), "rent" => (($rental['amount'] - $totalRefundAmount)), "tax" => $pendingtax], ['id' => $rental['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $rental['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 2, 'charged_at' => $rental['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 8, "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "status" => 1, "refundtransactionid" => $rental['transaction_id']]);
            }
            $return['payment_status'] = 1;
        }
        return [$return, $PaymentError];
    }

    private function ChargeEmf($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError)
    {
        $paidData = $this->getTotalEmf($CsOrder['CsOrder']['id']);
        $TotalRentPaid = sprintf('%0.2f', ($paidData['emf'] + $paidData['tax']));
        $totalAmount = sprintf('%0.2f', ($CsOrder['CsOrder']['emf_tax'] + $CsOrder['CsOrder']['extra_mileage_fee']));
        if ($totalAmount > $TotalRentPaid) {
            $return['emf_status'] = 2;
        }
        if ($PaymentError && ($totalAmount > $TotalRentPaid)) {
            $return['bad_debt'] = ($return['bad_debt'] ?? 0) + ($totalAmount - $TotalRentPaid);
        }
        if (!$PaymentError && $totalAmount > 0 && $TotalRentPaid == 0) {
            $endrentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $totalAmount, $totalAmount . ' partial EMF amount from ChargeEmf', $CsOrder['CsOrder']['id'], 2);
            if ($endrentresult['status']) {
                $return['emf_transaction_id'] = $endrentresult['transactions'];
                $return['new_emf_payment'] = 1;
                $return['emf_status'] = 1;
                if ($endrentresult['pending'] > 0) {
                    $subendrentresult = $this->Stripe->charge([
                        "amount" => $endrentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA EMF",
                        "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subendrentresult['status']) && $subendrentresult['status'] == 'success') {
                        $return['emf_transaction_id'][] = ["amt" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subendrentresult;
                        $return['emf_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $endrentresult['pending']) : $endrentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $endrentresult['pending'], "note" => $subendrentresult, "status" => 2]);
                    }
                }
            } else {
                $endRentObj = [
                    "amount" => $totalAmount,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA EMF",
                    "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $endrentresult = $this->Stripe->charge($endRentObj);
                if (isset($endrentresult['status']) && $endrentresult['status'] == 'success') {
                    $return['emf_transaction_id'] = $endrentresult['stripe_id'];
                    $return['new_emf_payment'] = 1;
                    $return['emf_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $totalAmount, "transaction_id" => $endrentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $endrentresult;
                    $return['emf_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $totalAmount) : $totalAmount;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $totalAmount, "note" => $endrentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount == $TotalRentPaid) {
            $return['emf_status'] = 1;
            $return['new_emf_payment'] = 2;
        } elseif ($totalAmount > $TotalRentPaid && $TotalRentPaid != 0) {
            $remainngAmt = sprintf('%0.2f', ($totalAmount - $TotalRentPaid));
            $rentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $remainngAmt, $remainngAmt . ' partial emf amount from ChargeEmf', $CsOrder['CsOrder']['id'], 2);
            if ($rentresult['status']) {
                $return['emf_transaction_id'] = $rentresult['transactions'];
                $return['emf_status'] = 1;
                $return['new_emf_payment'] = 3;
                $return['balance_emf'] = ($totalAmount - $CsOrder['CsOrder']['emf_tax'] - $paidData['emf']);
                $return['balance_emf_tax'] = $CsOrder['CsOrder']['emf_tax'] - $paidData['tax'];
                if ($rentresult['pending'] > 0) {
                    $subrentresult = $this->Stripe->charge([
                        "amount" => $rentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA EMF",
                        "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                        $return['emf_transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subrentresult;
                        $return['emf_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $rentresult['pending']) : $rentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                    }
                }
            } else {
                $RentObj = [
                    "amount" => $remainngAmt,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA EMF",
                    "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $rentresult = $this->Stripe->charge($RentObj);
                if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                    $return['status'] = 'success';
                    $return['emf_transaction_id'] = $rentresult['stripe_id'];
                    $return['emf_status'] = 1;
                    $return['new_emf_payment'] = 3;
                    $return['balance_emf'] = ($totalAmount - $CsOrder['CsOrder']['emf_tax'] - $paidData['emf']);
                    $return['balance_emf_tax'] = ($CsOrder['CsOrder']['emf_tax'] - $paidData['tax']);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $remainngAmt, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $rentresult;
                    $return['emf_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $remainngAmt) : $remainngAmt;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $remainngAmt, "note" => $rentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount < $TotalRentPaid) {
            $refundableAmt = $paidData['emf'] - $CsOrder['CsOrder']['extra_mileage_fee'];
            if ($refundableAmt <= 0) {
                $return['emf_status'] = 1;
                return [$return, $PaymentError];
            }
            $refundTax = $paidData['tax'] - $CsOrder['CsOrder']['emf_tax'];
            $rentals = $this->getActiveEmfTransaction($CsOrderTemp['CsOrder']['id']);
            $refundamount = 0;
            $pendingtax = $refundTax;
            foreach ($rentals as $rental) {
                if (!$refundableAmt) {
                    break;
                }
                $totalRefundAmount = 0;
                if ($rental['amount'] <= $refundableAmt) {
                    $totalRefundAmount = $refundamount = $rental['amount'];
                } elseif ($rental['amount'] > $refundableAmt) {
                    $totalRefundAmount = $refundamount = $refundableAmt;
                }
                if ($rental['tax'] >= $refundTax) {
                    $pendingtax = $rental['tax'] - $refundTax;
                    $totalRefundAmount += $refundTax;
                    $refundTax = 0;
                } else {
                    $refundTax -= $rental['tax'];
                    $pendingtax = 0;
                    $totalRefundAmount += $rental['tax'];
                }
                $refundableAmt = $refundableAmt - $refundamount;
                $this->walletAddBalance($totalRefundAmount, $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "partial emf refund from booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                if ($totalRefundAmount < $rental['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $totalRefundAmount), "rent" => (($rental['amount'] - $totalRefundAmount)), "tax" => $pendingtax], ['id' => $rental['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $rental['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 16, 'charged_at' => $rental['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "status" => 1, "refundtransactionid" => $rental['transaction_id']]);
            }
            $return['emf_status'] = 1;
        }
        return [$return, $PaymentError];
    }

    private function ChargeToll($CsOrder, $return, $usrData, $PaymentError)
    {
        if ($CsOrder['CsOrder']['pending_toll'] > 0) {
            $return['toll_status'] = 2;
            $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $CsOrder['CsOrder']['pending_toll']) : $CsOrder['CsOrder']['pending_toll'];
        }
        if (!$PaymentError && $CsOrder['CsOrder']['pending_toll'] > 0) {
            $tollresult = $this->walletChargeFromWallet($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['pending_toll'], $CsOrder['CsOrder']['pending_toll'] . ' toll amount from ChargeToll', 6, $CsOrder['CsOrder']['id']);
            if ($tollresult['status']) {
                $return['toll_transaction_id'] = $tollresult['transactions'];
                $return['pending_toll'] = $CsOrder['CsOrder']['pending_toll'];
                $return['toll_status'] = 1;
            } else {
                $tollresult = $this->Stripe->charge([
                    "amount" => $CsOrder['CsOrder']['pending_toll'],
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Misc",
                    "statement_descriptor" => "DIA Misc",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($tollresult['status']) && $tollresult['status'] == 'success') {
                    $return['toll_transaction_id'] = $tollresult['stripe_id'];
                    $return['pending_toll'] = $CsOrder['CsOrder']['pending_toll'];
                    $return['toll_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 22, "amount" => $CsOrder['CsOrder']['pending_toll'], "transaction_id" => $tollresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['pending_toll'] = 0;
                    $return['toll_status'] = 2;
                    $return['message'] = $tollresult;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $CsOrder['CsOrder']['pending_toll']) : $CsOrder['CsOrder']['pending_toll'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 22, "amount" => $CsOrder['CsOrder']['pending_toll'], "note" => $tollresult, "status" => 1]);
                }
            }
        }
        return [$return, $PaymentError];
    }

    private function ChargeLateFee($CsOrder, $return, $usrData, $PaymentError)
    {
        $TotalPaid = $this->getTotalPaidLateFee($CsOrder['CsOrder']['id']);
        $totalAmount = sprintf('%0.2f', $CsOrder['CsOrder']['lateness_fee']);

        if (!$PaymentError && $totalAmount > 0 && $TotalPaid == 0) {
            $endrentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $totalAmount, $totalAmount . ' late fee from ChargeLateFee', $CsOrder['CsOrder']['id'], 19);
            if ($endrentresult['status']) {
                $return['latefee_transaction_id'] = $endrentresult['transactions'];
                $return['latefee_status'] = 1;
                if ($endrentresult['pending'] > 0) {
                    $subendrentresult = $this->Stripe->charge([
                        "amount" => $endrentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA Latefee",
                        "statement_descriptor" => "DIA Latefee " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subendrentresult['status']) && $subendrentresult['status'] == 'success') {
                        $return['latefee_transaction_id'][] = ["amt" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $endrentresult['pending'], "transaction_id" => $subendrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subendrentresult;
                        $return['latefee_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $endrentresult['pending']) : $endrentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $endrentresult['pending'], "note" => $subendrentresult, "status" => 2]);
                    }
                }
            } else {
                $endRentObj = [
                    "amount" => $totalAmount,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Latefee",
                    "statement_descriptor" => "DIA Late " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $endrentresult = $this->Stripe->charge($endRentObj);
                if (isset($endrentresult['status']) && $endrentresult['status'] == 'success') {
                    $return['latefee_transaction_id'] = $endrentresult['stripe_id'];
                    $return['latefee_status'] = 1;
                    $return['latefee'] = $totalAmount;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalAmount, "transaction_id" => $endrentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $endrentresult;
                    $return['latefee_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $totalAmount) : $totalAmount;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 5, "amount" => $totalAmount, "note" => $endrentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount == $TotalPaid) {
            $return['latefee_status'] = 1;
        } elseif ($totalAmount > $TotalPaid && $TotalPaid != 0) {
            $remainngAmt = sprintf('%0.2f', ($totalAmount - $TotalPaid));
            $rentresult = $this->walletChargePartialFromWallet($CsOrder['CsOrder']['renter_id'], $remainngAmt, $remainngAmt . ' partial rental amount from ChargeLateFee', $CsOrder['CsOrder']['id'], 2);
            if ($rentresult['status']) {
                $return['latefee_transaction_id'] = $rentresult['transactions'];
                $return['latefee_status'] = 1;
                $return['latefee'] = $remainngAmt;
                if ($rentresult['pending'] > 0) {
                    $subrentresult = $this->Stripe->charge([
                        "amount" => $rentresult['pending'],
                        "currency" => $CsOrder['CsOrder']['currency'],
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                        "capture" => true,
                        "description" => "DIA Latefee",
                        "statement_descriptor" => "DIA Late " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                        $return['latefee_transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                    } else {
                        $PaymentError = true;
                        $return['message'] = $subrentresult;
                        $return['latefee_status'] = 2;
                        $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $rentresult['pending']) : $rentresult['pending'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                    }
                }
            } else {
                $RentObj = [
                    "amount" => $remainngAmt,
                    "currency" => $CsOrder['CsOrder']['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Latefee",
                    "statement_descriptor" => "DIA Late " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $rentresult = $this->Stripe->charge($RentObj);
                if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                    $return['status'] = 'success';
                    $return['latefee_transaction_id'] = $rentresult['stripe_id'];
                    $return['latefee_status'] = 1;
                    $return['latefee'] = $remainngAmt;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $remainngAmt, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
                } else {
                    $PaymentError = true;
                    $return['message'] = $rentresult;
                    $return['latefee_status'] = 2;
                    $return['bad_debt'] = isset($return['bad_debt']) ? ($return['bad_debt'] + $remainngAmt) : $remainngAmt;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 9, "amount" => $remainngAmt, "note" => $rentresult, "status" => 2]);
                }
            }
        } elseif ($totalAmount < $TotalPaid) {
            $refundableAmt = sprintf('%0.2f', ($TotalPaid - $totalAmount));
            $rentals = $this->getActiveLateFeeTransaction($CsOrder['CsOrder']['id']);
            foreach ($rentals as $rental) {
                if (!$refundableAmt) {
                    break;
                }
                if ($rental['amount'] <= $refundableAmt) {
                    $refundamount = $rental['amount'];
                } elseif ($rental['amount'] > $refundableAmt) {
                    $refundamount = $refundableAmt;
                }
                $refundableAmt = $refundableAmt - $refundamount;
                $this->walletAddBalance($refundamount, $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "partial late fee from booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                if ($refundamount < $rental['amount']) {
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $refundamount)], ['id' => $rental['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $rental['id']]);
                }
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 19, 'charged_at' => $rental['charged_at']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 8, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "status" => 1, "refundtransactionid" => ""]);
            }
            $return['payment_status'] = 1;
        }
        return [$return, $PaymentError];
    }

    public function ChargeCancelAmount($CsOrder, $cancellation_fee)
    {
        $usrData = $this->getCustomer($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        if ($CsOrder['CsOrder']['dpa_status'] == 1 && ($CsOrder['CsOrder']['deposit'] > 0)) {
            $deposits = $this->getActiveDepositTransaction($CsOrder['CsOrder']['id']);
            foreach ($deposits as $deposit) {
                if ($CsOrder['CsOrder']['deposit_type'] == 'C') {
                    $this->walletAddBalance($deposit['amount'], $CsOrder['CsOrder']['renter_id'], $deposit['transaction_id'], $deposit['amount'] . " deposit refund from cancel booking ", $CsOrder['CsOrder']['id'], $deposit['charged_at']);
                } else {
                    $this->Stripe->refund(["charge" => $deposit['transaction_id'], "amount" => $deposit['amount']]);
                }
                $this->updateOrderPayments(['status' => 2], ['id' => $deposit['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 3, "amount" => $CsOrder['CsOrder']['deposit'], "transaction_id" => $deposit['transaction_id'], "status" => 1, 'refundtransactionid' => '']);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $deposit['amount'], "transaction_id" => $deposit['transaction_id'], "source" => 'wallet', 'type' => 1]);
            }
        }

        if ($CsOrder['CsOrder']['infee_status'] == 1 && ($CsOrder['CsOrder']['initial_fee'] > 0)) {
            $infees = $this->getActiveInitialFeeTransaction($CsOrder['CsOrder']['id']);
            foreach ($infees as $infee) {
                $this->walletAddBalance($infee['amount'], $CsOrder['CsOrder']['renter_id'], $infee['transaction_id'], $infee['amount'] . " initial refund from cancel booking ", $CsOrder['CsOrder']['id'], $infee['charged_at']);
                $this->updateOrderPayments(['status' => 2], ['id' => $infee['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 16, "amount" => $CsOrder['CsOrder']['initial_fee'], "transaction_id" => $infee['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $infee['amount'], "transaction_id" => $infee['transaction_id'], "source" => 'wallet', 'type' => 3]);
            }
        }

        if ($CsOrder['CsOrder']['insu_status'] == 1 && ($CsOrder['CsOrder']['insurance_amt'] > 0)) {
            $insus = $this->getActiveInsuranceTransaction($CsOrder['CsOrder']['id']);
            foreach ($insus as $insu) {
                if (!empty($insu['payer_id'])) {
                    $this->walletAddBalance($insu['amount'], $insu['payer_id'], $insu['transaction_id'], $insu['amount'] . " insurance refund from cancel booking", $CsOrder['CsOrder']['id'], $insu['charged_at']);
                } else {
                    $this->walletAddBalance($insu['amount'], $CsOrder['CsOrder']['renter_id'], $insu['transaction_id'], $insu['amount'] . " insurance refund from cancel booking", $CsOrder['CsOrder']['id'], $insu['charged_at']);
                }
                $this->updateOrderPayments(['status' => 2], ['id' => $insu['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 11, "amount" => $CsOrder['CsOrder']['insurance_amt'], "transaction_id" => $insu['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $insu['amount'], "transaction_id" => $insu['transaction_id'], "source" => 'wallet', 'type' => 4]);
            }
        }

        if ($CsOrder['CsOrder']['payment_status'] == 1 && ($CsOrder['CsOrder']['rent'] > 0)) {
            $payments = $this->getActiveRentalTransaction($CsOrder['CsOrder']['id']);
            foreach ($payments as $payment) {
                $this->walletAddBalance($payment['amount'], $CsOrder['CsOrder']['renter_id'], $payment['transaction_id'], $payment['amount'] . " rental refund from cancel booking", $CsOrder['CsOrder']['id'], $payment['charged_at']);
                $this->updateOrderPayments(['status' => 2], ['id' => $payment['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 7, "amount" => ($CsOrder['CsOrder']['rent'] + $CsOrder['CsOrder']['tax'] + $CsOrder['CsOrder']['dia_fee']), "transaction_id" => $payment['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $payment['amount'], "transaction_id" => $payment['transaction_id'], "source" => 'wallet', 'type' => 2]);
            }
        }

        if ($CsOrder['CsOrder']['emf_status'] == 1 && ($CsOrder['CsOrder']['extra_mileage_fee'] > 0)) {
            $payments = $this->getActiveEmfTransaction($CsOrder['CsOrder']['id']);
            foreach ($payments as $payment) {
                $this->walletAddBalance($payment['amount'], $CsOrder['CsOrder']['renter_id'], $payment['transaction_id'], $payment['amount'] . " rental refund from cancel booking", $CsOrder['CsOrder']['id'], $payment['charged_at']);
                $this->updateOrderPayments(['status' => 2], ['id' => $payment['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 7, "amount" => ($CsOrder['CsOrder']['extra_mileage_fee'] + $CsOrder['CsOrder']['emf_tax']), "transaction_id" => $payment['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $payment['amount'], "transaction_id" => $payment['transaction_id'], "source" => 'wallet', 'type' => 2]);
            }
        }

        if ($cancellation_fee <= 0) {
            $return['status'] = 'success';
            return $return;
        }

        $cancelresult = $this->walletChargeFromWallet($CsOrder['CsOrder']['renter_id'], $cancellation_fee, $cancellation_fee . ' canceleation amount charged from ChargeCancelAmount', 5, $CsOrder['CsOrder']['id']);
        if ($cancelresult['status']) {
            $return['status'] = 'success';
            $return['cancel_fee_transaction_id'] = $cancelresult['transactions'];
            $return['message'] = 'success';
        } else {
            $cancelObj = [
                "amount" => $cancellation_fee,
                "currency" => $CsOrder['CsOrder']['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Cancellation",
                "statement_descriptor" => "DIA Cancel " . date('mdy', strtotime($CsOrder['CsOrder']['start_datetime'])),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $cancelresult = $this->Stripe->charge($cancelObj);
            if (isset($cancelresult['status']) && $cancelresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['cancel_fee_transaction_id'] = $cancelresult['stripe_id'];
                $return['message'] = 'success';
            } else {
                $return['message'] = $cancelresult;
            }
        }
        return $return;
    }

    public function ChargeAmountOnComplete($CsOrder, $CsOrderTemp)
    {
        $usrData = $this->getCustomer($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['cc_token_id']);
        $return = [
            'status' => 'success',
            'message' => 'success',
            "bad_debt" => 0,
            "dia_debt" => 0,
            'deposit_auth' => $CsOrder['CsOrder']['dpa_status'],
            "payment_status" => $CsOrder['CsOrder']['payment_status'],
            "insu_status" => $CsOrder['CsOrder']['insu_status'],
            "infee_status" => $CsOrder['CsOrder']['infee_status'],
            "dpa_status" => $CsOrder['CsOrder']['dpa_status'],
            "toll_status" => $CsOrder['CsOrder']['toll_status'],
            "dia_insu_status" => $CsOrder['CsOrder']['dia_insu_status'],
            "emf_status" => $CsOrder['CsOrder']['emf_status'],
            "currency" => $CsOrder['CsOrder']['currency'],
        ];
        if (empty($usrData['UserCcToken']['stripe_token'])) {
            $return['message'] = "Stripe token is missing for customer";
            $return['status'] = 'error';
            return $return;
        }
        $this->stripe();
        $PaymentError = false;

        list($return, $PaymentError) = $this->ChargeRental($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError);
        list($return, $PaymentError) = $this->chargeInitialFee($CsOrder, $return, $usrData, $PaymentError);
        list($return, $PaymentError) = $this->ChargeInsuranceOnComplete($CsOrder, $return, $usrData, $PaymentError);
        list($return, $PaymentError) = $this->ChargeEmf($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError);
        list($return, $PaymentError) = $this->ChargeToll($CsOrder, $return, $usrData, $PaymentError);
        list($return, $PaymentError) = $this->ChargeDiaInsurance($CsOrder, $return, $usrData, $PaymentError, true);
        list($return, $PaymentError) = $this->ChargeLateFee($CsOrder, $return, $usrData, $PaymentError);

        $return['status'] = $PaymentError ? "error" : "success";
        return $return;
    }

    public function ChargeAmountOnCompleteForRenew($CsOrder, $CsOrderTemp)
    {
        $usrData = $this->getCustomer($CsOrder['CsOrder']['renter_id'], $CsOrder['CsOrder']['cc_token_id']);
        $return = [
            'status' => 'success',
            'message' => 'success',
            'deposit_auth' => $CsOrder['CsOrder']['dpa_status'],
            "payment_status" => $CsOrder['CsOrder']['payment_status'],
            "insu_status" => $CsOrder['CsOrder']['insu_status'],
            "infee_status" => $CsOrder['CsOrder']['infee_status'],
            "dpa_status" => $CsOrder['CsOrder']['dpa_status'],
            "toll_status" => $CsOrder['CsOrder']['toll_status'],
            "dia_insu_status" => $CsOrder['CsOrder']['dia_insu_status'],
            "emf_status" => $CsOrder['CsOrder']['emf_status'],
            "currency" => $CsOrder['CsOrder']['currency'],
        ];
        if (empty($usrData['UserCcToken']['stripe_token'])) {
            $return['message'] = "Stripe token is missing for customer";
            $return['status'] = 'error';
            return $return;
        }
        $this->stripe();
        $PaymentError = false;

        list($return, $PaymentError) = $this->chargeInitialFee($CsOrder, $return, $usrData, $PaymentError);
        if (!$PaymentError) {
            list($return, $PaymentError) = $this->ChargeInsurance($CsOrder, $return, $usrData, $PaymentError);
        }
        list($return, $PaymentError) = $this->ChargeRental($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError);
        list($return, $PaymentError) = $this->ChargeEmf($CsOrder, $return, $usrData, $CsOrderTemp, $PaymentError);
        list($return, $PaymentError) = $this->ChargeToll($CsOrder, $return, $usrData, $PaymentError);
        list($return, $PaymentError) = $this->ChargeDiaInsuranceForAutoRenew($CsOrder, $return, $usrData, $PaymentError);
        list($return, $PaymentError) = $this->ChargeLateFee($CsOrder, $return, $usrData, $PaymentError);

        $return['status'] = $PaymentError ? "error" : "success";
        return $return;
    }
    // ═══════════════════════════════════════════
    // RENEW PAYMENT PROCESSING
    // ═══════════════════════════════════════════

    public function checkAndProcessRenew($renterid, $owner_id, $priceRulesAmt = [], $CsOrderId = null, $preOrderId = '', $cc_token_id = '', $parentid = 0)
    {
        if (empty($priceRulesAmt)) {
            return ['status' => 'success', 'message' => 'Payment Details not saved', 'payment_id' => ''];
        }
        $OrderObj = $this->findOrderById($CsOrderId);
        $startDate = $OrderObj['CsOrder']['start_datetime'] ?? '';
        $usrData = $this->getCustomer($renterid, $cc_token_id);
        $this->stripe();
        $return = [
            'rent' => $priceRulesAmt['time_fee'], 'tax' => $priceRulesAmt['tax'],
            'dia_fee' => $priceRulesAmt['dia_fee'], 'extra_mileage_fee' => $priceRulesAmt['extra_mileage_fee'] ?? 0,
            'status' => 'success', 'transaction_id' => '', 'renter_id' => $renterid,
            'user_id' => $owner_id, 'message' => 'success',
            'insurance_amt' => $priceRulesAmt['insurance_amt'], 'insurance_transaction_id' => '',
            'initial_fee' => 0, 'currency' => $OrderObj['CsOrder']['currency'] ?? 'USD',
        ];
        $preDeposits = $this->getTotalDeposit($preOrderId);
        $error = false;
        if (!empty($preOrderId) && $preDeposits) {
            $isRefundable = DB::table('deposit_templates')->where('user_id', $owner_id)->value('refundable');
            if ($isRefundable) {
                $return['deposit'] = $preDeposits;
                $return['deposit_type'] = $priceRulesAmt['deposit_type'];
                $return['dpa_status'] = 1;
                $this->copyDeposits($preOrderId, $CsOrderId);
            } else {
                $return['deposit'] = 0;
                $return['deposit_type'] = $priceRulesAmt['deposit_type'];
                $return['dpa_status'] = 0;
            }
        } else {
            if ($priceRulesAmt['deposit_amt'] > 0 && ($priceRulesAmt['deposit_event'] == 'P' || $priceRulesAmt['deposit_event'] == 'S')) {
                $result = $this->Stripe->charge([
                    "amount" => $priceRulesAmt['deposit_amt'], "currency" => $OrderObj['CsOrder']['currency'] ?? 'USD',
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => ($priceRulesAmt['deposit_type'] == 'P') ? false : true,
                    "description" => "DIA Deposit",
                    "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $return['deposit'] = $priceRulesAmt['deposit_amt'];
                    $return['deposit_auth'] = $result['stripe_id'];
                    $return['dpa_status'] = 1;
                    $return['deposit_type'] = $priceRulesAmt['deposit_type'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 1, "amount" => $priceRulesAmt['deposit_amt'], "transaction_id" => $result['stripe_id'], "status" => 1]);
                } else {
                    $error = true;
                    $return['message'] = $result;
                    $return['dpa_status'] = 2;
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 1, "amount" => $priceRulesAmt['deposit_amt'], "note" => $result, "status" => 2]);
                }
            }
        }
        if ($priceRulesAmt['time_fee'] > 0) {
            $return['payment_status'] = 2;
        }
        if (!$error && $priceRulesAmt['time_fee'] > 0 && ($priceRulesAmt['charge_rent_event'] == 'P' || $priceRulesAmt['charge_rent_event'] == 'S')) {
            $totalRent = sprintf('%0.2f', ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']));
            $Rentresult = $this->walletChargePartialFromWallet($renterid, $totalRent, $totalRent . ' rental amount from checkAndProcessRenew', $CsOrderId, 2);
            if ($Rentresult['status']) {
                $return['transaction_id'] = $Rentresult['transactions'];
                $return['payment_status'] = 1;
                if ($Rentresult['pending'] > 0) {
                    $SubRentresult = $this->Stripe->charge([
                        "amount" => $Rentresult['pending'], "currency" => $OrderObj['CsOrder']['currency'] ?? 'USD',
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                        "description" => "DIA CAR", "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                        $return['transaction_id'][] = ["amt" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "source" => 'card'];
                        $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 5, "amount" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['message'] = $SubRentresult; $return['payment_status'] = 2; $error = true;
                        $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 5, "amount" => $Rentresult['pending'], "note" => $SubRentresult, "status" => 2]);
                    }
                }
            } else {
                $Rentresult = $this->Stripe->charge([
                    "amount" => $totalRent, "currency" => $OrderObj['CsOrder']['currency'] ?? 'USD',
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA CAR", "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                    $return['transaction_id'] = $Rentresult['stripe_id']; $return['payment_status'] = 1;
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 5, "amount" => $totalRent, "transaction_id" => $Rentresult['stripe_id'], "status" => 1]);
                } else {
                    $error = true; $return['message'] = $Rentresult; $return['payment_status'] = 2;
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 5, "amount" => $totalRent, "note" => $Rentresult, "status" => 2]);
                }
            }
        }
        if ($priceRulesAmt['insurance_amt'] > 0) { $return['insu_status'] = 2; }
        if (!$error && $priceRulesAmt['insurance_amt'] > 0 && ($priceRulesAmt['insurance_event'] == 'P' || $priceRulesAmt['insurance_event'] == 'S')) {
            $CsSetting = DB::table('cs_settings')->where('user_id', $owner_id)->first(['max_stripe_balance']);
            if (($priceRulesAmt['insurance_payer'] ?? 0) == 1) {
                $return = $this->chargeInsuranceFromDealer($return, $priceRulesAmt['insurance_amt'], $owner_id, date('mdy', strtotime($startDate)), $CsSetting ? (array) $CsSetting : [], $CsOrderId);
            } else {
                $return['insu_payerid'] = $renterid;
                $insuresult = $this->walletChargePartialFromWallet($renterid, $priceRulesAmt['insurance_amt'], $priceRulesAmt['insurance_amt'] . ' insurance fee from checkAndProcessRenew', $CsOrderId, 4);
                if ($insuresult['status']) {
                    $return['insurance_transaction_id'] = $insuresult['transactions'];
                    $return['insurance_amt'] = $priceRulesAmt['insurance_amt'];
                    $return['insu_status'] = 1;
                    if ($insuresult['pending'] > 0) {
                        $subinsuresult = $this->Stripe->charge([
                            "amount" => $insuresult['pending'], "currency" => $OrderObj['CsOrder']['currency'] ?? 'USD',
                            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                            "description" => "DIA INS&FEES", "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                            "metadata" => ["payer_id" => $usrData['User']['id']],
                        ]);
                        if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                            $return['insurance_transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                        } else {
                            $error = true; $return['insu_status'] = 2;
                            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                        }
                    }
                } else {
                    $insuresult = $this->Stripe->charge([
                        "amount" => $priceRulesAmt['insurance_amt'], "currency" => $OrderObj['CsOrder']['currency'] ?? 'USD',
                        "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                        "description" => (($priceRulesAmt['insurance_payer'] ?? 0) == 1) ? "DIA INS&FEES Paid By Dealer" : "DIA INS&FEES",
                        "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                        "metadata" => ["payer_id" => $usrData['User']['id']],
                    ]);
                    if (isset($insuresult['status']) && $insuresult['status'] == 'success') {
                        $return['insurance_transaction_id'] = $insuresult['stripe_id'];
                        $return['insurance_amt'] = $priceRulesAmt['insurance_amt']; $return['insu_status'] = 1;
                        $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $priceRulesAmt['insurance_amt'], "transaction_id" => $insuresult['stripe_id'], "status" => 1]);
                    } else {
                        $return['insu_status'] = 2; $error = true;
                        $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $priceRulesAmt['insurance_amt'], "note" => $insuresult, "status" => 2]);
                    }
                }
            }
        }
        if ($priceRulesAmt['insurance_amt'] == 0) { $return['insu_status'] = 1; $return['insurance_amt'] = 0; }
        $return['status'] = !$error ? 'success' : "error";
        return $this->savePaymentDetails($priceRulesAmt, $return, $CsOrderId, $owner_id);
    }

    // ═══════════════════════════════════════════
    // SAVE PAYMENT DETAILS (transaction records)
    // ═══════════════════════════════════════════

    public function savePaymentDetails($priceRulesAmt, $return, $CsOrderId, $ownerid = '')
    {
        $shouldSave = (
            ($priceRulesAmt['charge_rent_event'] ?? '') == 'P' || ($priceRulesAmt['charge_rent_event'] ?? '') == 'S' ||
            ($priceRulesAmt['deposit_event'] ?? '') == 'P' || ($priceRulesAmt['deposit_event'] ?? '') == 'S' ||
            ($priceRulesAmt['insurance_event'] ?? '') == 'P' || ($priceRulesAmt['insurance_event'] ?? '') == 'S' ||
            ($priceRulesAmt['initial_event'] ?? '') == 'P' || ($priceRulesAmt['initial_event'] ?? '') == 'S'
        );
        if (!$shouldSave) {
            return ['status' => 'success', 'message' => 'Payment Details not saved', 'payment_id' => ''];
        }
        $dataToSave = ['CsOrder' => $return];
        unset($dataToSave['CsOrder']['status'], $dataToSave['CsOrder']['deposit_auth'], $dataToSave['CsOrder']['insurance_transaction_id'], $dataToSave['CsOrder']['transaction_id'], $dataToSave['CsOrder']['initial_fee']);
        $dataToSave['CsOrder']['id'] = $CsOrderId;
        if (!empty($CsOrderId)) {
            $this->saveOrderData($dataToSave);
        }
        $currency = $return['currency'] ?? 'USD';
        $renterId = $return['renter_id'] ?? 0;
        if (!empty($return['deposit_auth'])) {
            $this->saveDepositTransactionRecord($CsOrderId, $currency, $renterId, $return['deposit'] ?? 0, $return['deposit_auth'], $return['deposit_type'] ?? 'C');
        }
        if (!empty($return['insurance_transaction_id'])) {
            $this->saveInsuranceTransactionRecord($CsOrderId, $currency, $renterId, $return['insurance_amt'] ?? 0, $return['insurance_transaction_id'], $return['insu_payerid'] ?? null);
        }
        if (!empty($return['transaction_id'])) {
            $totalRent = ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']);
            $this->saveRentalTransactionRecord($CsOrderId, $currency, $renterId, $totalRent, $return['transaction_id'], $priceRulesAmt['tax'], $priceRulesAmt['dia_fee']);
        }
        if (!empty($return['initial_fee_id'])) {
            $this->saveInitialFeeTransactionRecord($CsOrderId, $currency, $renterId, ($return['initial_fee'] ?? 0) + ($return['initial_fee_tax'] ?? 0), $return['initial_fee_id'], $return['initial_fee_tax'] ?? 0);
        }
        return ['status' => $return['status'], 'message' => $return['message'], 'payment_id' => $CsOrderId];
    }

    // ═══════════════════════════════════════════
    // RENTAL REFUND (full)
    // ═══════════════════════════════════════════

    public function rentRefundtotal($CsOrder, $refundToStripe = false)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have any rental transaction to refund.'];
        $rentals = $this->getActiveRentalTransaction($CsOrder['CsOrder']['id']);
        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if ($refundToStripe) {
                $return = $this->Stripe->refund(["charge" => $rental['transaction_id'], "amount" => $rental['amount']]);
            } else {
                $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund rentRefundtotal for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            }
            if ($return['status'] !== 'success') { return $return; }
            $this->updateOrderPayments(['status' => 2], ['id' => $rental['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 7, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => ($refundToStripe ? 'stripe' : 'wallet'), 'type' => 2, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // RENTAL REFUND (partial balance)
    // ═══════════════════════════════════════════

    public function refundBalanceAmount($needtorefund, $CsOrderId, $refundabletax, $refandablediafee, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveRentalTransaction($CsOrderId);
        $pendingtax = $refundabletax;
        $pendingdiafee = $refandablediafee;
        $refundamount = 0;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            $totalRefundAmount = 0;
            if ($rental['amount'] <= $needtorefund) {
                $totalRefundAmount = $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $totalRefundAmount = $refundamount = $needtorefund;
            }
            $needtorefund = sprintf('%0.2f', ($needtorefund - $refundamount));
            if ($rental['tax'] >= $refundabletax) {
                $pendingtax = $rental['tax'] - $refundabletax;
                $totalRefundAmount += $refundabletax;
                $refundabletax = 0;
            } else {
                $refundabletax = 0; $pendingtax = 0;
                $totalRefundAmount += $rental['tax'];
            }
            if (($rental['dia_fee'] ?? 0) >= $refandablediafee) {
                $pendingdiafee = ($rental['dia_fee'] ?? 0) - $refandablediafee;
                $totalRefundAmount += $refandablediafee;
                $refandablediafee = 0;
            } else {
                $refandablediafee = 0; $pendingdiafee = 0;
                $totalRefundAmount += ($rental['dia_fee'] ?? 0);
            }
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $totalRefundAmount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($totalRefundAmount, $renterid, $rental['transaction_id'], "refund refundBalanceAmount for booking", $CsOrderId, $rental['charged_at']);
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            if ($totalRefundAmount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $totalRefundAmount), 'rent' => ($rental['amount'] - $totalRefundAmount), 'tax' => $pendingtax, "dia_fee" => $pendingdiafee, 'dealer_amt' => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, 'dealer_amt' => $dealerAmt], ['id' => $rental['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 8, "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 2, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // RENTAL CHARGE (balance)
    // ═══════════════════════════════════════════

    public function chargeBalanceAmount($amount, $renterid, $userid, $CsOrderId, $tax, $dia_fee)
    {
        $usrData = $this->getCustomer($renterid);
        $CsOrder = $this->findOrderById($CsOrderId);
        $startDate = $CsOrder['CsOrder']['start_datetime'] ?? '';
        $currency = $CsOrder['CsOrder']['currency'] ?? 'USD';
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $currency];
        $rentresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' partial rental amount from chargeBalanceAmount', $CsOrderId, 2);
        if ($rentresult['status']) {
            $return['status'] = 'success'; $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'], "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA Partial Rental",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->saveRentalTransactionRecord($CsOrderId, $currency, $renterid, $rentresult['pending'], $subrentresult['stripe_id'], $tax, $dia_fee);
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 9, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $rentresult; $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 9, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                }
            }
            if (($amount - $rentresult['pending']) > 0) {
                $this->saveRentalTransactionRecord($CsOrderId, $currency, $renterid, ($amount - $rentresult['pending']), $rentresult['transactions'], $tax, $dia_fee);
            }
        } else {
            $rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                "description" => "DIA Partial Rental",
                "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success'; $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveRentalTransactionRecord($CsOrderId, $currency, $renterid, $amount, $rentresult['stripe_id'], $tax, $dia_fee);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 9, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 9, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // INSURANCE REFUND (full)
    // ═══════════════════════════════════════════

    public function insuranceRefund($CsOrder, $refundToStripe = false)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have anything to refund'];
        $insurances = $this->getActiveInsuranceTransaction($CsOrder['CsOrder']['id']);
        foreach ($insurances as $insurance) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $insurance['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if ($refundToStripe) {
                $return = $this->Stripe->refund(["charge" => $insurance['transaction_id'], "amount" => $insurance['amount']]);
            } else {
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($insurance['amount'], $insurance['payer_id'], $insurance['transaction_id'], "refund insuranceRefund for booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                } else {
                    $this->walletAddBalance($insurance['amount'], $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "refund insuranceRefund for booking", $CsOrder['CsOrder']['id'], $insurance['charged_at']);
                }
                $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            }
            if ($return['status'] !== 'success') { return $return; }
            $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 11, "amount" => $insurance['amount'], "transaction_id" => $insurance['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $insurance['amount'], "transaction_id" => $insurance['transaction_id'], "source" => ($refundToStripe ? 'stripe' : 'wallet'), 'type' => 4, 'charged_at' => $insurance['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // INSURANCE REFUND (partial balance)
    // ═══════════════════════════════════════════

    public function refundBalanceInsurance($needToRefund, $CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Amount must be greater than $1'];
        if ($needToRefund < 1) { return $return; }
        $insurances = $this->getActiveInsuranceTransaction($CsOrder['id']);
        $amount = 0;
        foreach ($insurances as $insurance) {
            if (!$needToRefund) { return $return; }
            if ($insurance['amount'] <= $needToRefund) { $amount = $insurance['amount']; } else { $amount = $needToRefund; }
            $needToRefund = sprintf('%0.2f', ($needToRefund - $amount));
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['id'], $insurance['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $insurance['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($insurance['amount'] - $amount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if (!empty($insurance['payer_id'])) {
                $this->walletAddBalance($amount, $insurance['payer_id'], $insurance['transaction_id'], "refund refundBalanceInsurance for booking", $CsOrder['id'], $insurance['charged_at']);
            } else {
                $this->walletAddBalance($amount, $CsOrder['renter_id'], $insurance['transaction_id'], "refund refundBalanceInsurance for booking", $CsOrder['id'], $insurance['charged_at']);
            }
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            if ($amount < $insurance['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($insurance['amount'] - $amount), 'dealer_amt' => $dealerAmt], ['id' => $insurance['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, 'dealer_amt' => $dealerAmt], ['id' => $insurance['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 13, "amount" => $amount, "transaction_id" => $insurance['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['id'], "amount" => $amount, "transaction_id" => $insurance['transaction_id'], "source" => 'wallet', 'type' => 4, 'charged_at' => $insurance['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // INSURANCE CHARGE (balance)
    // ═══════════════════════════════════════════

    public function chargeBalanceInsurance($amount, $CsOrder, $insurancePayer)
    {
        $renterid = $CsOrder['renter_id'];
        $CsOrderId = $CsOrder['id'];
        $owner_id = $CsOrder['user_id'];
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $startDate = $CsOrder['start_datetime'] ?? '';
        $currency = $CsOrder['currency'] ?? 'USD';
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];
        $CsSetting = DB::table('cs_settings')->where('user_id', $owner_id)->first(['max_stripe_balance']);
        if (($insurancePayer['insurance_payer'] ?? 0) == 1) {
            $return = $this->chargeInsuranceFromDealer($return, $amount, $owner_id, date('mdy', strtotime($startDate)), $CsSetting ? (array) $CsSetting : [], $CsOrderId);
            if ($return['status'] != 'error') {
                $return['status'] = 'success';
                $return['transaction_id'] = $return['insurance_transaction_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveInsuranceTransactionRecord($CsOrderId, $currency, $renterid, $amount, $return['transaction_id'], $owner_id);
            }
            return $return;
        }
        $insuresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' insurance fee from chargeBalanceInsurance', $CsOrderId, 4);
        if ($insuresult['status']) {
            $return['transaction_id'] = $insuresult['transactions'];
            $return['insurance_amt'] = $amount; $return['insu_status'] = 1; $return['status'] = 'success';
            if ($insuresult['pending'] > 0) {
                $subinsuresult = $this->Stripe->charge([
                    "amount" => $insuresult['pending'], "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA INS&FEES",
                    "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $subinsuresult; $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 10, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                }
            }
            $this->saveInsuranceTransactionRecord($CsOrderId, $currency, $renterid, $amount, $return['transaction_id'], $renterid);
        } else {
            $rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                "description" => (($insurancePayer['insurance_payer'] ?? 0) == 1) ? "DIA Partial Insurance By Dealer" : "DIA INS&FEES",
                "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success'; $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveInsuranceTransactionRecord($CsOrderId, $currency, $renterid, $amount, $rentresult['stripe_id'], $renterid);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 14, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 14, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // DEPOSIT REFUND (full)
    // ═══════════════════════════════════════════

    public function depositRefund($CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, this booking dont have any deposit'];
        $totalDeposit = $this->getTotalDeposit($CsOrder['CsOrder']['id']);
        if ($totalDeposit <= 0) { return $return; }
        $alldeposits = $this->getActiveDepositTransaction($CsOrder['CsOrder']['id']);
        if (empty($alldeposits)) { return $return; }
        foreach ($alldeposits as $alldeposit) {
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $alldeposit['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($alldeposit['amount'], $CsOrder['CsOrder']['renter_id'], $alldeposit['transaction_id'], "refund depositRefund for booking", $CsOrder['CsOrder']['id'], $alldeposit['charged_at']);
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $alldeposit['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 3, "amount" => $CsOrder['CsOrder']['deposit'] ?? $alldeposit['amount'], "transaction_id" => $alldeposit['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $alldeposit['amount'], "transaction_id" => $alldeposit['transaction_id'], "source" => 'wallet', 'type' => 1, 'charged_at' => $alldeposit['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // DEPOSIT REFUND (partial balance)
    // ═══════════════════════════════════════════

    public function refundBalanceDeposit($needToRefund, $CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Refund amount must be greater than 0'];
        if ($needToRefund <= 0) { return $return; }
        $return['message'] = "Sorry, no deposit found for this order";
        $alldeposits = $this->getActiveDepositTransaction($CsOrder['id']);
        if (empty($alldeposits)) { return $return; }
        $amount = 0;
        foreach ($alldeposits as $alldeposit) {
            if (!$needToRefund) { return $return; }
            if ($alldeposit['amount'] <= $needToRefund) { $amount = $alldeposit['amount']; } else { $amount = $needToRefund; }
            $needToRefund = sprintf('%0.2f', ($needToRefund - $amount));
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['id'], $alldeposit['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $alldeposit['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($alldeposit['amount'] - $amount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($amount, $CsOrder['renter_id'], $alldeposit['transaction_id'], "refund refundBalanceDeposit for booking", $CsOrder['id'], $alldeposit['charged_at']);
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            if ($amount < $alldeposit['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($alldeposit['amount'] - $amount), "dealer_amt" => $dealerAmt], ['id' => $alldeposit['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $alldeposit['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 4, "amount" => $amount, "transaction_id" => $alldeposit['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['id'], "amount" => $alldeposit['amount'], "transaction_id" => $alldeposit['transaction_id'], "source" => 'wallet', 'type' => 1, 'charged_at' => $alldeposit['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // DEPOSIT CHARGE (balance)
    // ═══════════════════════════════════════════

    public function chargeBalanceDeposit($amount, $renterid, $CsOrderId)
    {
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $CsOrder = $this->findOrderById($CsOrderId);
        $startDate = $CsOrder['CsOrder']['start_datetime'] ?? '';
        $currency = $CsOrder['CsOrder']['currency'] ?? 'USD';
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];
        $rentresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' balance amount from chargeBalanceDeposit', $CsOrderId, 1);
        if ($rentresult['status']) {
            $return['status'] = 'success'; $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'], "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                    "description" => "DIA Partial Deposit",
                    "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->saveDepositTransactionRecord($CsOrderId, $currency, $renterid, $rentresult['pending'], $subrentresult['stripe_id'], 'C');
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 20, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $subrentresult; $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 20, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 1]);
                }
            }
            if (($amount - $rentresult['pending']) > 0) {
                $this->saveDepositTransactionRecord($CsOrderId, $currency, $renterid, ($amount - $rentresult['pending']), $rentresult['transactions'], 'C');
            }
        } else {
            $rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'], "capture" => true,
                "description" => "DIA Partial Deposit",
                "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success'; $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveDepositTransactionRecord($CsOrderId, $currency, $renterid, $amount, $rentresult['stripe_id'], 'C');
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 20, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 20, "amount" => $amount, "note" => $rentresult, "status" => 1]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // INITIAL FEE REFUND (full)
    // ═══════════════════════════════════════════

    public function initialfeeRefund($CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];
        if (($CsOrder['CsOrder']['infee_status'] ?? 0) != 1) { return $return; }
        $return['message'] = 'Sorry, you dont have any rental transaction to refund.';
        $rentals = $this->getActiveInitialFeeTransaction($CsOrder['CsOrder']['id']);
        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund initialfeeRefund for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => 0], ['id' => $rental['id']]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 3]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // INITIAL FEE REFUND (partial balance)
    // ═══════════════════════════════════════════

    public function refundBalanceInitialfee($needtorefund, $refundTax, $CsOrderId, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveInitialFeeTransaction($CsOrderId);
        $refundamount = 0;
        $pendingtax = $refundTax;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            if ($rental['amount'] <= $needtorefund) {
                $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $refundamount = $needtorefund;
            }
            if (($rental['tax'] ?? 0) >= $refundTax) {
                $pendingtax = ($rental['tax'] ?? 0) - $refundTax;
                $refundTax = 0;
            } else {
                $refundTax = 0; $pendingtax = 0;
            }
            $needtorefund = $needtorefund - $refundamount;
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $refundamount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($refundamount, $renterid, $rental['transaction_id'], "refund refundBalanceInitialfee for booking", $CsOrderId, $rental['charged_at']);
            $return['status'] = 'success'; $return['message'] = "Your request successfully processed";
            if ($refundamount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $refundamount), 'rent' => ($rental['amount'] - $refundamount - $pendingtax), 'tax' => $pendingtax, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            }
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 3, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }


    // ═══════════════════════════════════════════
    // RETRY METHODS
    // ═══════════════════════════════════════════

    public function retryInsurance($amount, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $startDate = $CsOrder['start_datetime'];
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];

        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['user_id'])->first();
        $settingArr = $CsSetting ? (array) $CsSetting : [];

        if ($CsOrder['insurance_payer'] == 1) {
            $return = $this->chargeInsuranceFromDealer($return, $amount, $CsOrder['user_id'], date('mdy', strtotime($startDate)), $settingArr, $CsOrder['id']);
            if ($return['status'] != 'error') {
                $return['status'] = 'success';
                $return['transaction_id'] = $return['insurance_transaction_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['id'], $amount, $return['transaction_id'], $CsOrder['user_id']);
            }
            return $return;
        }

        $insuresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' insurance fee from retryInsurance', $CsOrder['id'], 4);
        if ($insuresult['status']) {
            $return['transaction_id'] = $insuresult['transactions'];
            $return['insurance_amt'] = $amount;
            $return['insu_status'] = 1;
            $return['status'] = 'success';
            if ($insuresult['pending'] > 0) {
                $subinsuresult = $this->Stripe->charge([
                    "amount" => $insuresult['pending'],
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA INS&FEES",
                    "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 10, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $subinsuresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 10, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                }
            }
            $this->saveInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['id'], $amount, $return['transaction_id'], $CsOrder['renter_id']);
        } else {
            $stripe_token = $usrData['UserCcToken']['stripe_token'];
            $rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $stripe_token,
                "capture" => true,
                "description" => ($CsOrder['insurance_payer'] == 1) ? "DIA INS&FEES Retry By Dealer" : "DIA INS&FEES Retry",
                "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['id'], $amount, $rentresult['stripe_id'], $CsOrder['renter_id']);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 12, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 12, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryDiaInsurance($amount, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $startDate = $CsOrder['start_datetime'];
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];
        $owner_id = $CsOrder['user_id'];
        $renterid = $CsOrder['renter_id'];
        $CsOrderId = $CsOrder['id'];

        $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['user_id'])->first();
        $settingArr = $CsSetting ? (array) $CsSetting : [];

        if ($CsOrder['insurance_payer'] == 1) {
            $return = $this->chargeInsuranceFromDealer($return, $amount, $owner_id, date('mdy', strtotime($startDate)), $settingArr, $CsOrderId, true);
            if ($return['status'] != 'error') {
                $return['status'] = 'success';
                $return['transaction_id'] = $return['dia_insu_transaction_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveDiaInsuranceTransactionRecord($CsOrderId, $CsOrder['currency'], $renterid, $amount, $return['transaction_id'], $owner_id);
            }
            return $return;
        }

        $insuresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' insurance fee from retryDiaInsurance', $CsOrderId, 4);
        if ($insuresult['status']) {
            $return['transaction_id'] = $insuresult['transactions'];
            $return['dia_insu'] = $amount;
            $return['dia_insu_status'] = 1;
            $return['status'] = 'success';
            if ($insuresult['pending'] > 0) {
                $subinsuresult = $this->Stripe->charge([
                    "amount" => $insuresult['pending'],
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA INS&FEE ADDON",
                    "statement_descriptor" => "DIA INS&FEE ADDON",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 26, "amount" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $subinsuresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 26, "amount" => $insuresult['pending'], "note" => $subinsuresult, "status" => 2]);
                }
            }
            $this->saveDiaInsuranceTransactionRecord($CsOrderId, $CsOrder['currency'], $renterid, $amount, $return['transaction_id'], $renterid);
        } else {
            $stripe_token = $usrData['UserCcToken']['stripe_token'];
            $rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $stripe_token,
                "capture" => true,
                "description" => "DIA INS&FEE ADDON",
                "statement_descriptor" => "DIA INS&FEE ADDON",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveDiaInsuranceTransactionRecord($CsOrderId, $CsOrder['currency'], $renterid, $amount, $rentresult['stripe_id'], $renterid);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 26, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 26, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryInitialfee($amount, $CsOrder, $pndingTax = 0)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $startDate = $CsOrder['start_datetime'];
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];

        $result = $this->walletChargePartialFromWallet($CsOrder['renter_id'], ($amount + $pndingTax), ($amount + $pndingTax) . ' initial amount from retryInitialfee', $CsOrder['id'], 3);

        if ($result['status']) {
            $return['status'] = 'success';
            $return['initial_fee_id'] = $result['transactions'];
            $return['message'] = 'success';
            if ($result['pending'] > 0) {
                $subresult = $this->Stripe->charge([
                    "amount" => $result['pending'],
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Initial Fee - Retry",
                    "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subresult['status']) && $subresult['status'] == 'success') {
                    $return['initial_fee_id'][] = ["amt" => $result['pending'], "transaction_id" => $subresult['stripe_id'], "source" => 'card'];
                    $this->saveInitialFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $result['pending'], $subresult['stripe_id'], 0);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 17, "amount" => $result['pending'], "transaction_id" => $subresult['stripe_id'], "status" => 1]);
                } else {
                    $return['status'] = 'error';
                    $return['message'] = $subresult;
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 17, "amount" => $result['pending'], "note" => $subresult, "status" => 2]);
                }
            }
            if ((($amount + $pndingTax) - $result['pending']) > 0) {
                $this->saveInitialFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount + $pndingTax - $result['pending'], $result['transactions'], $pndingTax);
            }
        } else {
            $result = $this->Stripe->charge([
                "amount" => ($amount + $pndingTax),
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Initial Fee - Retry",
                "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $CsOrder['renter_id']],
            ]);
            if (isset($result['status']) && $result['status'] == 'success') {
                $return['status'] = 'success';
                $return['initial_fee_id'] = $result['stripe_id'];
                $return['message'] = 'success';
                $this->saveInitialFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount + $pndingTax, $result['stripe_id'], $pndingTax);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 17, "amount" => $amount, "transaction_id" => $result['stripe_id'], "status" => 1]);
            } else {
                $return['status'] = 'error';
                $return['message'] = $result;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 17, "amount" => $amount, "note" => $result, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryDeposit($amount, $CsOrder, $deposit_type)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];
        $startDate = $CsOrder['start_datetime'];

        $result = ['status' => false];
        if ($deposit_type == 'C') {
            $result = $this->walletChargeFromWallet($CsOrder['renter_id'], $amount, $amount . ' deposit amount from retryDeposit', 1, $CsOrder['id']);
        }

        if ($result['status']) {
            $return['status'] = 'success';
            $return['deposit_auth'] = $result['transactions'];
            $return['message'] = 'success';
            $this->saveDepositTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount, $result['transactions'], 'C');
        } else {
            $result = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => ($deposit_type == 'P') ? false : true,
                "description" => "DIA Deposit",
                "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($result['status']) && $result['status'] == 'success') {
                $return['status'] = 'success';
                $return['deposit_auth'] = $result['stripe_id'];
                $return['message'] = 'success';
                $this->saveDepositTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount, $result['stripe_id'], $deposit_type);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 2, "amount" => $amount, "transaction_id" => $result['stripe_id'], "status" => 1]);
            } else {
                $return['status'] = 'error';
                $return['message'] = $result;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 2, "amount" => $amount, "note" => $result, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryRental($rent, $tax, $dia_fee, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];
        $startDate = $CsOrder['start_datetime'];
        $amount = sprintf('%0.2f', ($rent + $tax + $dia_fee));

        $Rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' rental amount from retryRental', $CsOrder['id'], 2);

        if ($Rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $Rentresult['transactions'];
            $return['message'] = 'Success';
            if ($Rentresult['pending'] > 0) {
                $SubRentresult = $this->Stripe->charge([
                    "amount" => sprintf('%0.2f', $Rentresult['pending']),
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA CAR",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                    $return['transaction_id'] = ["amt" => $Rentresult['pending'], "transaction_id" => $Rentresult['stripe_id'] ?? null, "source" => 'card'];
                    $this->saveRentalTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $Rentresult['pending'], $SubRentresult['stripe_id'], $tax, $dia_fee);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $SubRentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $Rentresult['pending'], "note" => $SubRentresult, "status" => 2]);
                }
            }
            if (($amount - $Rentresult['pending']) > 0) {
                $this->saveRentalTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], ($amount - $Rentresult['pending']), $Rentresult['transactions'], $tax, $dia_fee);
            }
        } else {
            $Rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA CAR",
                "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $Rentresult['stripe_id'];
                $return['message'] = 'Success';
                $this->saveRentalTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount, $Rentresult['stripe_id'], $tax, $dia_fee);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $amount, "transaction_id" => $Rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $Rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $amount, "note" => $Rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryEmf($emf, $tax, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];
        $startDate = $CsOrder['start_datetime'];
        $amount = sprintf('%0.2f', ($emf + $tax));

        $Rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' emf amount from retryRental', $CsOrder['id'], 2);

        if ($Rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $Rentresult['transactions'];
            $return['message'] = 'Success';
            if ($Rentresult['pending'] > 0) {
                $SubRentresult = $this->Stripe->charge([
                    "amount" => sprintf('%0.2f', $Rentresult['pending']),
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA EMF",
                    "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                    $this->saveEmfTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $Rentresult['pending'], $SubRentresult['stripe_id'], 0);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $SubRentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $Rentresult['pending'], "note" => $SubRentresult, "status" => 2]);
                }
            }
            if (($amount - $Rentresult['pending']) > 0) {
                $this->saveEmfTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], ($amount - $Rentresult['pending']), $Rentresult['transactions'], $tax);
            }
        } else {
            $Rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA EMF",
                "statement_descriptor" => "DIA EMF " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $Rentresult['stripe_id'];
                $return['message'] = 'Success';
                $this->saveEmfTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount, $Rentresult['stripe_id'], $tax);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $amount, "transaction_id" => $Rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $Rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $amount, "note" => $Rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryTollfee($amount, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        $rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' toll amount from retryTollfee', $CsOrder['id'], 6);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'],
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Misc Retry",
                    "statement_descriptor" => "DIA Misc",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->saveTollTransactionRecord($CsOrder['id'], $rentresult['pending'], $subrentresult['stripe_id'], $CsOrder['user_id']);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 23, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $subrentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 23, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                }
            }
            $this->saveTollTransactionRecord($CsOrder['id'], ($amount - $rentresult['pending']), $rentresult['transactions'], $CsOrder['user_id']);
        } else {
            $rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Misc Retry",
                "statement_descriptor" => "DIA Misc",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveTollTransactionRecord($CsOrder['id'], $amount, $rentresult['stripe_id'], $CsOrder['user_id']);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 23, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 23, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    public function retryLatefee($amt, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id'], $CsOrder['cc_token_id']);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'currency' => $CsOrder['currency']];
        $startDate = $CsOrder['start_datetime'];

        $TotalPaid = $this->getTotalPaidLateFee($CsOrder['id']);
        $amount = $amt - $TotalPaid;
        if ($amount == 0 || $amount <= 0.5) {
            $return['status'] = 'success';
            $return['message'] = 'Success';
            return $return;
        }

        $Rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' lateness fee from retryLatefee', $CsOrder['id'], 19);

        if ($Rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $Rentresult['transactions'];
            $return['message'] = 'Success';
            if ($Rentresult['pending'] > 0) {
                $SubRentresult = $this->Stripe->charge([
                    "amount" => sprintf('%0.2f', $Rentresult['pending']),
                    "currency" => $CsOrder['currency'],
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA latefe",
                    "statement_descriptor" => "DIA latefee " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                    $return['transaction_id'] = ["amt" => $Rentresult['pending'], "transaction_id" => $Rentresult['stripe_id'] ?? null, "source" => 'card'];
                    $this->saveLateFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $Rentresult['pending'], $SubRentresult['stripe_id']);
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $SubRentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $Rentresult['pending'], "note" => $SubRentresult, "status" => 2]);
                }
            }
            if (($amount - $Rentresult['pending']) > 0) {
                $this->saveLateFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], ($amount - $Rentresult['pending']), $Rentresult['transactions']);
            }
        } else {
            $Rentresult = $this->Stripe->charge([
                "amount" => $amount,
                "currency" => $CsOrder['currency'],
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA latefe",
                "statement_descriptor" => "DIA Latefee " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $Rentresult['stripe_id'];
                $return['message'] = 'Success';
                $this->saveLateFeeTransactionRecord($CsOrder['id'], $CsOrder['currency'], $CsOrder['renter_id'], $amount, $Rentresult['stripe_id']);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $amount, "transaction_id" => $Rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $Rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 6, "amount" => $amount, "note" => $Rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // TOLL FEE CHARGE
    // ═══════════════════════════════════════════

    public function chargeTollfee($amount, $renterid, $CsOrderId, $cc_token_id, $owner_id)
    {
        $usrData = $this->getCustomer($renterid, $cc_token_id);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed', 'pending' => 0];
        $rentresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' toll amount from chargeTollfee', $CsOrderId, 6);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'],
                    "currency" => "usd",
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "DIA Misc Payment",
                    "statement_descriptor" => "DIA Misc",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->saveTollTransactionRecord($CsOrderId, $rentresult['pending'], $subrentresult['stripe_id'], $owner_id);
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 22, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['status'] = 'error';
                    $return['message'] = $subrentresult;
                    $return['pending'] = $rentresult['pending'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 22, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 1]);
                }
            }
            $this->saveTollTransactionRecord($CsOrderId, ($amount - $rentresult['pending']), $rentresult['transactions'], $owner_id);
        } else {
            $RentObj = [
                "amount" => $amount,
                "currency" => "usd",
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Misc Payment",
                "statement_descriptor" => "DIA Misc",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveTollTransactionRecord($CsOrderId, $amount, $rentresult['stripe_id'], $owner_id);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 22, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 22, "amount" => $amount, "note" => $rentresult, "status" => 1]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE TOLL FROM DEPOSIT
    // ═══════════════════════════════════════════

    public function chargeTollFromDeposit($toll, $CsOrder)
    {
        $return = ['status' => 'error', 'message' => 'Toll Amount must be valid value'];
        if ($toll <= 0) {
            return $return;
        }
        $return['message'] = "Sorry, no deposit found for this order";
        $alldeposits = $this->getActiveDepositTransaction($CsOrder['id']);
        if (empty($alldeposits)) {
            return $return;
        }
        $pendings = $toll;
        foreach ($alldeposits as $alldeposit) {
            if (!$pendings) {
                return $return;
            }
            if ($alldeposit['amount'] < $pendings) {
                $toll = $alldeposit['amount'];
            } else {
                $toll = $pendings;
            }
            $pendings = $pendings - $toll;
            if ($toll > 0) {
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
                if ($toll <= $alldeposit['amount']) {
                    $this->saveTollTransactionRecord($CsOrder['id'], $toll, $alldeposit['transaction_id'], $CsOrder['user_id']);
                    $this->updateOrderPayments(['status' => 1, 'amount' => ($alldeposit['amount'] - $toll)], ['id' => $alldeposit['id']]);
                } else {
                    $this->updateOrderPayments(['status' => 2], ['id' => $alldeposit['id']]);
                }
            } else {
                $return['message'] = "Toll amount is 0";
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // TRANSFER DEPOSIT TO DEALER
    // ═══════════════════════════════════════════

    public function transferDepositToDealer($amount, $CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Amount must be valid value'];
        if ($amount <= 0) {
            return $return;
        }
        $return['message'] = "Sorry, no deposit found for this transfer";
        $alldeposits = $this->getNoneTransferredDepositTransaction($CsOrder['id']);
        if (empty($alldeposits)) {
            return $return;
        }
        $pendings = $amount;
        $stripeKey = $this->getdealerourcekey($CsOrder['user_id']);
        $currencyRate = 1;

        foreach ($alldeposits as $alldeposit) {
            if (!$pendings) {
                return $return;
            }
            if ($alldeposit['amount'] < $pendings) {
                $amount = $alldeposit['amount'];
            } else {
                $amount = $pendings;
            }
            $pendings = $pendings - $amount;
            $baseAmt = sprintf('%0.2f', ($amount / $currencyRate));
            $resp = $this->Stripe->transferToDealer([
                "amount" => ($baseAmt * 100),
                "currency" => 'USD',
                "destination" => $stripeKey['stripe_key'],
                "description" => "DIA Deposit Fee Part",
                'source_transaction' => $alldeposit['transaction_id'],
            ]);
            if (is_array($resp) && isset($resp['id'])) {
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
                $dataToSave = [];
                $dataToSave['cs_order_id'] = $CsOrder['id'];
                $dataToSave['user_id'] = $CsOrder['user_id'];
                $dataToSave['type'] = 1;
                $dataToSave['amount'] = $amount;
                $dataToSave['currency'] = $alldeposit['currency'];
                $dataToSave['base_amt'] = $baseAmt;
                $dataToSave['base_currency'] = 'USD';
                $dataToSave['transaction_id'] = $alldeposit['transaction_id'];
                $dataToSave['cs_payment_id'] = $alldeposit['id'];
                $dataToSave['status'] = 1;
                $dataToSave['transfer_id'] = $resp['id'];
                $dataToSave['balance_transaction'] = $resp['balance_transaction'];
                $dataToSave['destination_payment'] = $resp['destination_payment'];
                $dataToSave['created'] = now();
                $dataToSave['modified'] = now();
                DB::table('cs_payout_transactions')->insert($dataToSave);
                $this->updateOrderPayments(['cs_transfer' => 1], ['id' => $alldeposit['id']]);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 21, "amount" => $amount, "transaction_id" => $alldeposit['transaction_id'], "status" => 1, 'old_transaction_id' => $alldeposit['transaction_id']]);
            } else {
                $return['message'] = $resp;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 21, "amount" => $amount, "transaction_id" => $alldeposit['transaction_id'], "status" => 2, 'old_transaction_id' => $alldeposit['transaction_id'], "note" => $resp]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // STRIPE CONNECT WRAPPERS
    // ═══════════════════════════════════════════

    public function createLoginLink($stripekey)
    {
        $this->stripe();
        return $this->Stripe->createLoginLink($stripekey);
    }

    public function transferToDealer($options)
    {
        $this->stripe();
        $resp = $this->Stripe->transferToDealer($options);
        if (is_array($resp) && isset($resp['id'])) {
            return ['status' => 'success', 'result' => $resp];
        }
        return ['status' => 'error', 'message' => $resp];
    }

    public function retrieveTransfer($transferid)
    {
        $this->stripe();
        return $this->Stripe->retrieveTransfer($transferid);
    }

    public function retriveBalanceTransaction($options, $stripe_account)
    {
        $this->stripe();
        return $this->Stripe->retriveBalanceTransaction($options, $stripe_account);
    }

    public function retriveBalanceTransactionDetails($id, $opt = [])
    {
        $this->stripe();
        return $this->Stripe->retriveBalanceTransactionDetails($id, $opt);
    }

    public function retrivePayout($options, $stripe_account)
    {
        $this->stripe();
        return $this->Stripe->retrivePayout($options, $stripe_account);
    }

    public function createPayout($params = [], $options = [])
    {
        $this->stripe();
        $resp = $this->Stripe->createPayout($params, $options);
        if (is_array($resp) && isset($resp['id'])) {
            return ['status' => 'success', 'result' => $resp];
        }
        return ['status' => 'error', 'message' => $resp];
    }

    // ═══════════════════════════════════════════
    // CHARGE CUSTOMER BALANCE
    // ═══════════════════════════════════════════

    public function chargeCustomerBalance($amount, $renterid, $CsOrderId, $ownerId)
    {
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        $rentresult = $this->walletChargeFromWallet($renterid, $amount, $amount . ' balance amount from chargeCustomerBalance', 7, $CsOrderId);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            $this->saveCustomerBalanceTransactionRecord($CsOrderId, $amount, $rentresult['transactions'], $ownerId);
        } else {
            $RentObj = [
                "amount" => $amount,
                "currency" => $usrData['User']['currency'] ?? 'USD',
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Balance Charges",
                "statement_descriptor" => "DIA Balance Charges",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveCustomerBalanceTransactionRecord($CsOrderId, $amount, $rentresult['stripe_id'], $ownerId);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 24, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 24, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE TDK BALANCE
    // ═══════════════════════════════════════════

    public function chargeTDKBalance($amount, $renterid, $CsOrderId, $token, $typeid, $ownerId)
    {
        if (empty($token)) {
            $usrData = $this->getCustomer($renterid);
            $token = $usrData['UserCcToken']['stripe_token'];
        }
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        $rentresult = $this->walletChargeFromWallet($renterid, $amount, $amount . ' TDK amount from chargeTDKBalance', $typeid, $CsOrderId);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            $this->saveTDKTransactionRecord($CsOrderId, $amount, $rentresult['transactions'], $typeid, $ownerId);
        } else {
            if (!isset($usrData)) {
                $usrData = $this->getCustomer($renterid);
            }
            $RentObj = [
                "amount" => $amount,
                "currency" => $usrData['User']['currency'] ?? 'USD',
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'] ?? $token,
                "capture" => true,
                "description" => "DIA Violation Charges",
                "statement_descriptor" => "DIA Violation Charge",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveTDKTransactionRecord($CsOrderId, $amount, $rentresult['stripe_id'], $typeid, $ownerId);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 25, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 25, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE MISC BALANCE
    // ═══════════════════════════════════════════

    public function chargeMiscBalance($amount, $renterid, $CsOrderId, $typeid)
    {
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        $rentresult = $this->walletChargeFromWallet($renterid, $amount, $amount . ' Misc amount from chargeMiscBalance', $typeid, $CsOrderId);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            $this->saveTDKTransactionRecord($CsOrderId, $amount, $rentresult['transactions'], $typeid);
        } else {
            $RentObj = [
                "amount" => $amount,
                "currency" => $usrData['User']['currency'] ?? 'USD',
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "DIA Misc Charges",
                "statement_descriptor" => "DIA Misc Charge",
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveTDKTransactionRecord($CsOrderId, $amount, $rentresult['stripe_id'], $typeid);
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 25, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 25, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE FROM DEALER
    // ═══════════════════════════════════════════

    public function chargeFromDealer($amt, $ownerid, $statement = '', $type = 12)
    {
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        $stripeKey = $this->getdealerourcekey($ownerid);
        $balanceResp = $this->retrieveBalance($ownerid, $stripeKey['stripe_key'] ?? '');
        if ($balanceResp['status'] != 'success' || $balanceResp['balance'] < -1000) {
            $return['message'] = "Sorry, dealer dont have enough balance";
            return $return;
        }
        $this->stripe();
        $result = $this->Stripe->charge([
            "amount" => $amt,
            "currency" => $stripeKey['currency'] ?? 'USD',
            "source" => $stripeKey['stripe_key'],
            "capture" => true,
            "description" => $statement,
            "statement_descriptor" => "DIA Credit " . date('mdy'),
            "metadata" => ["payer_id" => $ownerid],
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['transaction_id'] = $result['stripe_id'];
            $return['status'] = "success";
            $this->savePayoutTransactions(['user_id' => $ownerid, "currency" => $stripeKey['currency'] ?? 'USD'], $amt, $result, $type);
            $this->saveOnlyPaymentLogRecord(["orderid" => 0, "amount" => $amt, "transaction_id" => $result['stripe_id']], $type);
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // RETRIEVE BALANCE
    // ═══════════════════════════════════════════

    public function retrieveBalance($ownerid, $stripeKey = '')
    {
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        if (empty($stripeKey)) {
            $stripeKey = $this->getdealerourcekey($ownerid)['stripe_key'] ?? '';
        }
        $this->stripe();
        $result = $this->Stripe->retrieveBalance([
            "stripe_account" => $stripeKey,
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['status'] = "success";
            $balance = isset($result['result']['instant_available'][0]['amount']) ? $result['result']['instant_available'][0]['amount'] : ($result['result']['available'][0]['amount'] ?? 0);
            $return['balance'] = $balance == 0 ? 0 : $balance / 100;
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // ACCOUNT RETRIEVE / UPDATE
    // ═══════════════════════════════════════════

    public function accountRetrieve($accountId)
    {
        $this->stripe();
        return $this->Stripe->accountRetrieve($accountId);
    }

    public function updateConnectedAccount($accountId, $options)
    {
        $this->stripe();
        return $this->Stripe->accountUpdate($accountId, $options);
    }

    // ═══════════════════════════════════════════
    // CHARGE AMOUNT TO USER
    // ═══════════════════════════════════════════

    public function chargeAmtToUser($amt, $ownerid, $statement = '', $currency = '', $cc_token_id = '')
    {
        $usrData = $this->getCustomer($ownerid, $cc_token_id);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong', 'currency' => (!empty($currency) ? $currency : ($usrData['User']['currency'] ?? 'USD'))];
        if (empty($usrData['UserCcToken']['stripe_token'])) {
            return $return;
        }
        $result = $this->Stripe->charge([
            "amount" => $amt,
            "currency" => (!empty($currency) ? $currency : ($usrData['User']['currency'] ?? 'USD')),
            "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
            "capture" => true,
            "description" => (!empty($statement) ? $statement : "DIA Advance Payment"),
            "statement_descriptor" => !empty($statement) ? $statement . '_' . date('mdy') : "DIA Pay " . date('mdy'),
            "metadata" => ["payer_id" => $usrData['User']['id']],
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['transaction_id'] = $result['stripe_id'];
            $return['amt'] = $amt;
            $return['status'] = 'success';
            $return['source_transfer'] = null;
            $return['balance_transaction'] = $result['balance_transaction'];
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE AMOUNT (STRIPE TOKEN)
    // ═══════════════════════════════════════════

    public function chargeAmt($amt, $stripe_token, $statement = '', $currency = 'USD', $type = 34)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        if (empty($stripe_token)) {
            return $return;
        }
        $result = $this->Stripe->charge([
            "amount" => $amt,
            "currency" => $currency,
            "stripeCustomer" => $stripe_token,
            "capture" => true,
            "description" => ($statement != '' ? $statement : "DIA Advance Payment"),
            "statement_descriptor" => !empty($statement) ? $statement . date('mdy') : "DIA Uber " . date('mdy'),
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['transaction_id'] = $result['stripe_id'];
            $return['amt'] = $amt;
            $return['status'] = 'success';
            $this->saveOnlyPaymentLogRecord(["orderid" => 0, "amount" => $amt, "transaction_id" => $result['stripe_id']], $type);
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // REFUND WALLET BALANCE
    // ═══════════════════════════════════════════

    public function refundWalletBalance($amt, $transactionid, $orderid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid'];
        if ($amt <= 0) {
            return $return;
        }
        $return['message'] = "Sorry, no deposit found for this order";
        $result = $this->Stripe->refund([
            "charge" => $transactionid,
            "amount" => $amt,
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['status'] = 'success';
            $this->saveOnlyPaymentLogRecord(["orderid" => $orderid, "amount" => $amt, "transaction_id" => $transactionid], 33);
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE RETRIEVE
    // ═══════════════════════════════════════════

    public function chargeRetrieve($data, $options = [])
    {
        $this->stripe();
        return $this->Stripe->chargeRetrieve($data, $options);
    }

    // ═══════════════════════════════════════════
    // DEALER FULL REVERSE
    // ═══════════════════════════════════════════

    public function DealerFullReverse($orderTransfer)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, transaction details are missing'];
        $cstransfertxn = $orderTransfer['CsPayoutTransaction'] ?? [];
        if (empty($cstransfertxn)) {
            return $return;
        }
        $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
        if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
            $return['status'] = 'success';
            $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $cstransfertxn['cs_order_id'], "amount" => $cstransfertxn['amount'], "transaction_id" => $cstransfertxn['transaction_id'], "source" => 'stripe', 'type' => 2, 'charged_at' => date('Y-m-d H:i:s')]);
        } else {
            $return['message'] = $transfrResp;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // DEALER PARTIAL REVERSE
    // ═══════════════════════════════════════════

    public function DealerPartialReverse($needToRefund, $CsOrderId, $type = 2)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Refundable amount must be valid value'];
        if ($needToRefund <= 0) {
            return $return;
        }
        $return = ['status' => 'success', 'message' => ''];
        $payouts = $this->getAllActivePayoutTransactions($CsOrderId, $type);
        foreach ($payouts as $payout) {
            $cstransfertxn = $payout['CsPayoutTransaction'];
            if (!$needToRefund) {
                return $return;
            }
            if ($cstransfertxn['amount'] <= $needToRefund) {
                $amount = $cstransfertxn['amount'];
            } else {
                $amount = $needToRefund;
            }
            $needToRefund = $needToRefund - $amount;

            $currencyRate = 1;
            $reversableAmountwithCurrency = sprintf('%0.2f', ($needToRefund / $currencyRate));
            $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
            if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmountwithCurrency, $transfrResp['result']);
                \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $cstransfertxn['cs_order_id'], "amount" => $amount, "transaction_id" => $cstransfertxn['transaction_id'], "source" => 'stripe', 'type' => 2, 'charged_at' => date('Y-m-d H:i:s')]);
                $return['message'] = $return["message"] . "\n" . $cstransfertxn["transfer_id"] . " => " . $cstransfertxn['amount'] . " reversed.";
            } else {
                $return["message"] = $return["message"] . "\n" . $cstransfertxn["transfer_id"] . " => " . $cstransfertxn['amount'] . " not reversed due to error '" . $transfrResp . "'";
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // DEALER REVERSE FOR CREDIT
    // ═══════════════════════════════════════════

    public function DealerReverseForCredit($cstransfertxn, $amount)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, transaction details are missing'];
        if (empty($cstransfertxn)) {
            return $return;
        }
        $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => number_format($amount, 2, '.', '') * 100]);
        if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
            $return['status'] = 'success';
            $this->saveRefundPayoutTransactions($cstransfertxn, $amount, $transfrResp['result']);
        } else {
            $return['message'] = $transfrResp;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // EXCHANGE RATE
    // ═══════════════════════════════════════════

    public function ExchangeRate()
    {
        $this->stripe();
        return $this->Stripe->ExchangeRate();
    }

    // ═══════════════════════════════════════════
    // CHARGE BALANCE EMF
    // ═══════════════════════════════════════════

    public function chargeBalanceEmf($amount, $CsOrder, $tax)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id']);
        $startDate = $CsOrder['start_datetime'] ?? date('Y-m-d H:i:s');
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];
        $rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' partial rental amount from chargeBalanceEmf', $CsOrder['id'], 2);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'],
                    "currency" => $CsOrder['currency'] ?? 'USD',
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "EMF Partial Rental",
                    "statement_descriptor" => "EMF " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $rentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                }
            }
            $this->saveEmfTransactionRecord($CsOrder['id'], $CsOrder['currency'] ?? 'USD', $CsOrder['renter_id'], $amount, $return['transaction_id'], $tax);
        } else {
            $RentObj = [
                "amount" => $amount,
                "currency" => $CsOrder['currency'] ?? 'USD',
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "EMF Partial Rental",
                "statement_descriptor" => "EMF " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveEmfTransactionRecord($CsOrder['id'], $CsOrder['currency'] ?? 'USD', $CsOrder['renter_id'], $amount, $rentresult['stripe_id'], $tax);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 28, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // CHARGE BALANCE DIA INSURANCE
    // ═══════════════════════════════════════════

    public function chargeBalanceDiainsu($amount, $CsOrder)
    {
        $usrData = $this->getCustomer($CsOrder['renter_id']);
        $startDate = $CsOrder['start_datetime'] ?? date('Y-m-d H:i:s');
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        if (($CsOrder['insurance_payer'] ?? 0) == 1) {
            $CsSetting = DB::table('cs_settings')->where('user_id', $CsOrder['user_id'])->first();
            $settingArr = $CsSetting ? (array) $CsSetting : [];

            $return = $this->chargeInsuranceFromDealer($return, $amount, $CsOrder['user_id'], date('mdy', strtotime($startDate)), $settingArr, $CsOrder['id'], true);
            if ($return['status'] == 'success') {
                $this->saveDiaInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'] ?? 'USD', $CsOrder['renter_id'], $amount, $return['dia_insu_transaction_id'] ?? '', $CsOrder['user_id']);
                return $return;
            }
        }

        $rentresult = $this->walletChargePartialFromWallet($CsOrder['renter_id'], $amount, $amount . ' partial rental amount from chargeBalanceDiainsu', $CsOrder['id'], 2);
        if ($rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['transactions'];
            $return['message'] = 'Your request processed successfully';
            if ($rentresult['pending'] > 0) {
                $subrentresult = $this->Stripe->charge([
                    "amount" => $rentresult['pending'],
                    "currency" => $CsOrder['currency'] ?? 'USD',
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true,
                    "description" => "EMF Insu Partial Rental",
                    "statement_descriptor" => "EMFI " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subrentresult['status']) && $subrentresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "source" => 'card'];
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 29, "amount" => $rentresult['pending'], "transaction_id" => $subrentresult['stripe_id'], "status" => 1]);
                } else {
                    $return['message'] = $rentresult;
                    $return['status'] = 'error';
                    $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 29, "amount" => $rentresult['pending'], "note" => $subrentresult, "status" => 2]);
                }
            }
            $this->saveDiaInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'] ?? 'USD', $CsOrder['renter_id'], $amount, $return['transaction_id'], $CsOrder['user_id'] ?? null);
        } else {
            $RentObj = [
                "amount" => $amount,
                "currency" => $CsOrder['currency'] ?? 'USD',
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true,
                "description" => "EMF Partial Rental",
                "statement_descriptor" => "EMF " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ];
            $rentresult = $this->Stripe->charge($RentObj);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                $this->saveDiaInsuranceTransactionRecord($CsOrder['id'], $CsOrder['currency'] ?? 'USD', $CsOrder['renter_id'], $amount, $rentresult['stripe_id'], $CsOrder['user_id'] ?? null);
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 29, "amount" => $amount, "transaction_id" => $rentresult['stripe_id'], "status" => 1]);
            } else {
                $return['message'] = $rentresult;
                $this->savePaymentLogRecord(["orderid" => $CsOrder['id'], "type" => 29, "amount" => $amount, "note" => $rentresult, "status" => 2]);
            }
        }
        return $return;
    }

    // ─── Helper: commitRefundPayoutTransactions (used by chargeBalanceDiainsu refund flows) ───

    private function commitRefundPayoutTransactions($obg, $amount, $responseobj)
    {
        try {
            $dataTosave = [
                'cs_order_id' => $obg['cs_order_id'] ?? 0,
                'cs_payment_id' => $obg['cs_payment_id'] ?? 0,
                'user_id' => $obg['user_id'] ?? 0,
                'type' => 11,
                'refund' => $amount,
                'transaction_id' => $responseobj['stripe_id'] ?? '',
                'transfer_id' => $responseobj['source_transfer'] ?? '',
                'balance_transaction' => $responseobj['balance_transaction'] ?? '',
                'destination_payment' => $responseobj['source_transfer'] ?? '',
                'currency' => $responseobj['currency'] ?? ($obg['currency'] ?? 'USD'),
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ];
            if (!empty($amount)) {
                DB::table('cs_payout_transactions')->insert($dataTosave);
            }
        } catch (\Exception $e) {
            Log::error('commitRefundPayoutTransactions: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════
    // 1. PaymentAutherizeOnly – Authorize deposit + initial fee
    // ═══════════════════════════════════════════

    public function PaymentAutherizeOnly($renterid, $owner_id, $priceRulesAmt = [], $currency = 'USD')
    {
        if (empty($priceRulesAmt)) {
            return ['status' => 'success', 'message' => 'Payment charged', 'payment_id' => ''];
        }
        $usrData = $this->getCustomer($renterid);
        $return = [
            'deposit' => $priceRulesAmt['deposit_amt'], 'deposit_type' => 'P',
            'status' => 'success', 'deposit_auth' => '', 'renter_id' => $renterid,
            'user_id' => $owner_id, 'message' => 'Sorry, one of payment get failed',
            'initial_fee' => $priceRulesAmt['initial_fee'],
            'initial_fee_tax' => $priceRulesAmt['initial_fee_tax'],
            'infee_status' => 0, 'infee_type' => 'P', 'currency' => $currency,
        ];
        $this->stripe();
        $DepositFromWallet = ['status' => false];

        if ($priceRulesAmt['deposit_amt'] > 0 && $priceRulesAmt['deposit_event'] == 'P') {
            $DepositFromWallet = $this->walletChargeFromWallet($renterid, $priceRulesAmt['deposit_amt'], "deposit is charged for new pending booking", 1);
            if ($DepositFromWallet['status']) {
                $return['deposit_auth'] = $DepositFromWallet['transactions'];
                $return['dpa_status'] = 1;
                $return['deposit_type'] = 'C';
            } else {
                $result = $this->Stripe->charge([
                    "amount" => $priceRulesAmt['deposit_amt'],
                    "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => isset($usrData['UserCcToken']['is_dealer']) ? true : false,
                    "description" => "DIA Deposit Authorization",
                    "statement_descriptor" => "DIA Deposit",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $return['deposit_auth'] = $result['stripe_id'];
                    $return['message'] = 'success';
                    $return['dpa_status'] = 1;
                    $return['deposit_type'] = isset($usrData['UserCcToken']['is_dealer']) ? 'C' : 'P';
                } else {
                    $return['message'] = $result;
                    $return['status'] = 'error';
                    $return['dpa_status'] = 2;
                }
            }
        }

        if ($return['status'] == 'success' && $priceRulesAmt['initial_fee'] > 0) {
            $FixedFeeFromWallet = $this->walletChargeFromWallet($renterid, ($priceRulesAmt['initial_fee'] + $priceRulesAmt['initial_fee_tax']), "initial fee is charged for new pending booking", 3);
            if ($FixedFeeFromWallet['status']) {
                $return['initial_fee_id'] = $FixedFeeFromWallet['transactions'];
                $return['status'] = 'success';
                $return['infee_status'] = 1;
                $return['infee_type'] = 'C';
            } else {
                $initialfeeObj = [
                    "amount" => ($priceRulesAmt['initial_fee'] + $priceRulesAmt['initial_fee_tax']),
                    "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => isset($usrData['UserCcToken']['is_dealer']) ? true : false,
                    "description" => "DIA Initial Fee Authorization",
                    "statement_descriptor" => "DIA Initial Fee",
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ];
                $initialfeeresult = $this->Stripe->charge($initialfeeObj);
                if (isset($initialfeeresult['status']) && $initialfeeresult['status'] == 'success') {
                    $return['status'] = 'success';
                    $return['initial_fee_id'] = $initialfeeresult['stripe_id'];
                    $return['infee_status'] = 1;
                    $return['infee_type'] = isset($usrData['UserCcToken']['is_dealer']) ? 'C' : 'P';
                    if (isset($usrData['UserCcToken']['is_dealer']) && $usrData['UserCcToken']['is_dealer']) {
                        $this->saveInPayoutTransaction(['transaction_id' => $initialfeeresult['stripe_id'], 'order_id' => 0, 'type' => 3]);
                    }
                } else {
                    $return['infee_status'] = 2;
                    $return['message'] = $initialfeeresult;
                    $return['status'] = 'error';
                    if (!($DepositFromWallet['status']) && !empty($return['deposit_auth'])) {
                        $this->Stripe->refund(["charge" => $return['deposit_auth']]);
                    }
                    if (($DepositFromWallet['status']) && isset($DepositFromWallet['transactions'])) {
                        foreach ($DepositFromWallet['transactions'] as $transaction) {
                            $this->walletAddBalance($transaction['amt'], $renterid, $transaction['transaction_id'], "deposit is refunded from new pending booking", '', $transaction['charged_at']);
                        }
                    }
                }
            }
        }

        if (($usrData['UserCcToken']['is_dealer'] ?? 0) && !($DepositFromWallet['status']) && !empty($return['deposit_auth']) && $return['status'] == 'success') {
            $this->saveInPayoutTransaction(['transaction_id' => $return['deposit_auth'], 'order_id' => 0, 'type' => 1]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 2. PaymentCaptureOnly – Capture all authorized payments
    // ═══════════════════════════════════════════

    public function PaymentCaptureOnly($orderid, $ownerid)
    {
        if (empty($orderid)) {
            return ['status' => 'error', 'message' => 'Sorry, booking id missing'];
        }
        $order = $this->findOrderById($orderid);
        $startDate = $order['CsOrder']['start_datetime'] ?? now()->toDateTimeString();
        $allPayments = $this->getAllOrderPayments($orderid);
        $this->stripe();

        foreach ($allPayments as $allPayment) {
            $type = $allPayment['CsOrderPayment']['type'];
            if ($type == 1) {
                $statement_descriptor = "DIA Deposit " . date('mdy', strtotime($startDate));
            } elseif ($type == 2) {
                $statement_descriptor = "DIA CAR " . date('mdy', strtotime($startDate));
            } elseif ($type == 3) {
                $statement_descriptor = "DIA InitialFee " . date('mdy', strtotime($startDate));
            } elseif ($type == 4) {
                $statement_descriptor = "DIA INS&FEES " . date('mdy', strtotime($startDate));
            } else {
                $statement_descriptor = "DIA Fee " . date('mdy', strtotime($startDate));
            }
            $this->Stripe->capture([
                "auth_token" => $allPayment['CsOrderPayment']['transaction_id'],
                "statement_descriptor" => $statement_descriptor,
            ]);
        }
        return ['status' => 'success', 'message' => 'Payment captured successfully'];
    }

    // ═══════════════════════════════════════════
    // 3. ReleaseAuthorizePayment – Release/refund all authorized
    // ═══════════════════════════════════════════

    public function ReleaseAuthorizePayment($orderid, $ownerid = '')
    {
        if (empty($orderid)) {
            return ['status' => 'error', 'message' => 'Sorry, booking id is missing'];
        }
        $allPayments = $this->getAllOrderPayments($orderid);
        $this->stripe();

        foreach ($allPayments as $allPayment) {
            $result = $this->Stripe->refund(["charge" => $allPayment['CsOrderPayment']['transaction_id']]);
            if (isset($result['status']) && $result['status'] == 'success') {
                $this->updateOrderPayments(['status' => 3], ['id' => $allPayment['CsOrderPayment']['id']]);
            }
        }
        return ['status' => 'success', 'message' => 'Payment released successfully'];
    }

    // ═══════════════════════════════════════════
    // 4. ReservationPaymentCaptureOnly – Capture reservation payment
    // ═══════════════════════════════════════════

    public function ReservationPaymentCaptureOnly($orderid, $transaction, $ownerid)
    {
        $error = true;
        if (!empty($transaction)) {
            $order = $this->findOrderById($orderid);
            $startDate = $order['CsOrder']['start_datetime'] ?? now()->toDateTimeString();
            $this->stripe();

            if (isset($transaction['transaction_id'])) {
                $transactions = [0 => $transaction];
            } else {
                $transactions = $transaction;
            }

            $error = false;
            foreach ($transactions as $transaction) {
                if ($transaction['txntype'] == 'C') {
                    $saveData = $transaction;
                    $saveData['id'] = null;
                    $saveData['cs_order_id'] = $orderid;
                    $saveData['charged_at'] = $transaction['created'] ?? now();
                    $saveData['status'] = 1;
                    $saveData['created'] = $saveData['created'] ?? now();
                    $saveData['modified'] = now();
                    unset($saveData['id']);
                    DB::table('cs_order_payments')->insert($saveData);
                    $this->assignBookingIdToPayoutTransaction(['transaction_id' => $transaction['transaction_id'], 'order_id' => $orderid]);
                    \App\Services\Legacy\ReportPayment::saveCharge([
                        "orderid" => $orderid,
                        "amount" => (($transaction['amount'] ?? 0) + ($transaction['tax'] ?? 0)),
                        "transaction_id" => $transaction['transaction_id'],
                        "source" => $transaction['source'] ?? 'wallet',
                        'type' => $transaction['type'],
                        'charged_at' => $transaction['created'] ?? now(),
                    ]);
                    continue;
                }

                $type = $transaction['type'];
                if ($type == 1) {
                    $statement_descriptor = "DIA Deposit " . date('mdy', strtotime($startDate));
                } elseif ($type == 2) {
                    $statement_descriptor = "DIA CAR " . date('mdy', strtotime($startDate));
                } elseif ($type == 3) {
                    $statement_descriptor = "DIA InitialFee " . date('mdy', strtotime($startDate));
                } elseif ($type == 4) {
                    $statement_descriptor = "DIA INS&FEES " . date('mdy', strtotime($startDate));
                } else {
                    $statement_descriptor = "DIA Fee " . date('mdy', strtotime($startDate));
                }

                $resp = $this->Stripe->capture([
                    "auth_token" => $transaction['transaction_id'],
                    "statement_descriptor" => $statement_descriptor,
                ]);

                if (isset($resp['status']) && $resp['status'] == 'success') {
                    $saveData = $transaction;
                    $saveData['cs_order_id'] = $orderid;
                    $saveData['txntype'] = 'C';
                    $saveData['charged_at'] = $transaction['created'] ?? now();
                    $saveData['status'] = 1;
                    $saveData['created'] = $saveData['created'] ?? now();
                    $saveData['modified'] = now();
                    unset($saveData['id']);
                    $paymentId = DB::table('cs_order_payments')->insertGetId($saveData);
                    $this->saveInPayoutTransaction($transaction);
                    \App\Services\Legacy\ReportPayment::saveCharge([
                        "orderid" => $orderid,
                        "amount" => $transaction['amount'] ?? 0,
                        "transaction_id" => $transaction['transaction_id'],
                        "source" => 'stripe',
                        'type' => $type,
                        'charged_at' => $transaction['created'] ?? now(),
                    ]);
                    // Email queue removed – handled differently in Laravel
                } else {
                    $error = true;
                }
            }
        }
        return ['status' => $error ? 'error' : 'success', 'message' => 'Payment auth details were empty'];
    }

    // ═══════════════════════════════════════════
    // 5. ReservationReleaseAuthorizePayment – Release reservation payment
    // ═══════════════════════════════════════════

    public function ReservationReleaseAuthorizePayment($transaction, $ownerid)
    {
        $result = [];
        if (!empty($transaction)) {
            $this->stripe();
            $result = $this->Stripe->refund(["charge" => $transaction['transaction_id']]);
        }
        return ['status' => 'success', 'message' => 'Payment released successfully', 'result' => $result];
    }

    // ═══════════════════════════════════════════
    // 6. PaymentCapture – Entire body commented out in source
    // ═══════════════════════════════════════════

    public function PaymentCapture($renterid, $owner_id, $priceRulesAmt = [], $user_cctoken_id = null)
    {
        return ['status' => 'success', 'message' => 'Payment Details not saved', 'payment_id' => ''];
    }

    // ═══════════════════════════════════════════
    // 7. chargeInsuranceForVehicleReservation
    // ═══════════════════════════════════════════

    public function chargeInsuranceForVehicleReservation($amount, $opt = [])
    {
        $renterid = $opt['VehicleReservation']['renter_id'];
        $owner_id = $opt['VehicleReservation']['user_id'];
        $OrderId = $opt['VehicleReservation']['id'];
        $usrData = $this->getCustomer($renterid, '');
        $this->stripe();
        $startDate = $opt['VehicleReservation']['start_datetime'];
        $currency = $opt['Owner']['currency'] ?? 'USD';
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        $CsSetting = DB::table('cs_settings')->where('user_id', $owner_id)->first(['max_stripe_balance']);

        if (($opt['OrderDepositRule']['insurance_payer'] ?? 0) == 1) {
            $return = $this->chargeInsuranceFromDealer($return, $amount, $owner_id, date('mdy', strtotime($startDate)), $CsSetting ? (array) $CsSetting : [], $OrderId);
            if ($return['status'] != 'error') {
                $return['status'] = 'success';
                $return['transaction_id'] = $return['insurance_transaction_id'] ?? '';
                $return['message'] = 'Your request processed successfully';
                DB::table('cs_reservation_payments')->insert([
                    'cs_order_id' => $OrderId, 'type' => 4, 'amount' => $amount,
                    'transaction_id' => is_array($return['transaction_id']) ? json_encode($return['transaction_id']) : $return['transaction_id'],
                    'txntype' => 'C', 'payer_id' => $owner_id, 'currency' => $currency,
                    'status' => 1, 'created' => now(), 'modified' => now(),
                ]);
            }
            return $return;
        }

        $insuresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' insurance fee from pending order', $OrderId, 4);
        if ($insuresult['status']) {
            $return['transaction_id'] = $insuresult['transactions'];
            $return['insurance_amt'] = $amount;
            $return['insu_status'] = 1;
            $return['status'] = 'success';
            if ($insuresult['pending'] > 0) {
                $subinsuresult = $this->Stripe->charge([
                    "amount" => $insuresult['pending'], "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA INS&FEES",
                    "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subinsuresult['status']) && $subinsuresult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $insuresult['pending'], "transaction_id" => $subinsuresult['stripe_id'], "source" => 'card'];
                } else {
                    $return['message'] = $subinsuresult;
                    $return['status'] = 'error';
                }
            }
            DB::table('cs_reservation_payments')->insert([
                'cs_order_id' => $OrderId, 'type' => 4, 'amount' => $amount,
                'transaction_id' => is_array($return['transaction_id']) ? json_encode($return['transaction_id']) : $return['transaction_id'],
                'txntype' => 'C', 'payer_id' => $renterid, 'currency' => $currency,
                'status' => 1, 'created' => now(), 'modified' => now(),
            ]);
        } else {
            $stripe_token = $usrData['UserCcToken']['stripe_token'];
            $rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $stripe_token, "capture" => true,
                "description" => (($opt['OrderDepositRule']['insurance_payer'] ?? 0) == 1) ? "DIA INS&FEES  By Dealer" : "DIA INS&FEES ",
                "statement_descriptor" => "DIA INS&FEES " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $rentresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                DB::table('cs_reservation_payments')->insert([
                    'cs_order_id' => $OrderId, 'type' => 4, 'amount' => $amount,
                    'transaction_id' => $rentresult['stripe_id'],
                    'txntype' => 'C', 'payer_id' => $renterid, 'currency' => $currency,
                    'status' => 1, 'created' => now(), 'modified' => now(),
                ]);
            } else {
                $return['message'] = $rentresult;
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 8. chargeRentalForVehicleReservation
    // ═══════════════════════════════════════════

    public function chargeRentalForVehicleReservation($opt = [], $currency = 'USD')
    {
        $rent = $opt['time_fee'];
        $tax = $opt['tax'];
        $dia_fee = $opt['dia_fee'];
        $renterid = $opt['renter_id'];
        $OrderId = $opt['id'];
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment got failed'];
        $startDate = $opt['start_datetime'];
        $amount = sprintf('%0.2f', ($rent + $tax + $dia_fee));

        $paidRentalPayments = DB::table('cs_reservation_payments')
            ->where('cs_order_id', $OrderId)->where('type', 2)->where('status', 1)
            ->get()->map(fn($r) => (array) $r)->toArray();

        if (!empty($paidRentalPayments)) {
            $paidrent = sprintf('%0.2f', array_sum(array_column($paidRentalPayments, 'rent')));
            $paidtax = sprintf('%0.2f', array_sum(array_column($paidRentalPayments, 'tax')));
            $paiddia_fee = sprintf('%0.2f', array_sum(array_column($paidRentalPayments, 'dia_fee')));
            $rent = $rent - $paidrent;
            $tax = $tax - $paidtax;
            $dia_fee = $dia_fee - $paiddia_fee;
            $amount = sprintf('%0.2f', ($rent + $tax + $dia_fee));
        }
        if ($rent == 0 && $tax == 0 && $dia_fee == 0) {
            return ['status' => 'success', 'message' => 'All payments already paid'];
        }

        $Rentresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' rental amount from pending order Rental', $OrderId, 2);
        if ($Rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $Rentresult['transactions'];
            $return['message'] = 'Success';
            if ($Rentresult['pending'] > 0) {
                $SubRentresult = $this->Stripe->charge([
                    "amount" => sprintf('%0.2f', $Rentresult['pending']), "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA CAR",
                    "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                    $return['transaction_id'] = ["amt" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "source" => 'card'];
                    DB::table('cs_reservation_payments')->insert([
                        'cs_order_id' => $OrderId, 'type' => 2, 'amount' => $Rentresult['pending'],
                        'rent' => $Rentresult['pending'], 'tax' => $tax, 'dia_fee' => $dia_fee,
                        'transaction_id' => $SubRentresult['stripe_id'], 'txntype' => 'C',
                        'currency' => $currency, 'status' => 1, 'created' => now(), 'modified' => now(),
                    ]);
                } else {
                    $return['message'] = $SubRentresult;
                    $return['status'] = 'error';
                }
            }
            DB::table('cs_reservation_payments')->insert([
                'cs_order_id' => $OrderId, 'type' => 2, 'amount' => ($amount - $Rentresult['pending']),
                'rent' => ($amount - $Rentresult['pending']), 'tax' => $tax, 'dia_fee' => $dia_fee,
                'transaction_id' => is_array($Rentresult['transactions']) ? json_encode($Rentresult['transactions']) : $Rentresult['transactions'],
                'txntype' => 'C', 'currency' => $currency, 'status' => 1,
                'created' => now(), 'modified' => now(),
            ]);
        } else {
            $Rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true, "description" => "DIA CAR",
                "statement_descriptor" => "DIA CAR " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $Rentresult['stripe_id'];
                $return['message'] = 'Success';
                DB::table('cs_reservation_payments')->insert([
                    'cs_order_id' => $OrderId, 'type' => 2, 'amount' => $amount,
                    'rent' => $amount, 'tax' => $tax, 'dia_fee' => $dia_fee,
                    'transaction_id' => $Rentresult['stripe_id'], 'txntype' => 'C',
                    'currency' => $currency, 'status' => 1, 'created' => now(), 'modified' => now(),
                ]);
            } else {
                $return['message'] = $Rentresult;
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 9. chargeDepositForVehicleReservation
    // ═══════════════════════════════════════════

    public function chargeDepositForVehicleReservation($amount, $opt = [])
    {
        $renterid = $opt['VehicleReservation']['renter_id'];
        $OrderId = $opt['VehicleReservation']['id'];
        $usrData = $this->getCustomer($renterid, '');
        $this->stripe();
        $startDate = $opt['VehicleReservation']['start_datetime'];
        $currency = $opt['Owner']['currency'] ?? 'USD';
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        $diaresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' deposit fee from pending order', $OrderId, 1);
        if ($diaresult['status']) {
            $return['transaction_id'] = $diaresult['transactions'];
            $return['insurance_amt'] = $amount;
            $return['insu_status'] = 1;
            $return['status'] = 'success';
            if ($diaresult['pending'] > 0) {
                $subdeporesult = $this->Stripe->charge([
                    "amount" => $diaresult['pending'], "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA Deposit",
                    "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($subdeporesult['status']) && $subdeporesult['status'] == 'success') {
                    $return['transaction_id'][] = ["amt" => $diaresult['pending'], "transaction_id" => $subdeporesult['stripe_id'], "source" => 'card'];
                } else {
                    $return['message'] = $subdeporesult;
                    $return['status'] = 'error';
                }
            }
            DB::table('cs_reservation_payments')->insert([
                'cs_order_id' => $OrderId, 'type' => 1, 'amount' => $amount,
                'transaction_id' => is_array($return['transaction_id']) ? json_encode($return['transaction_id']) : $return['transaction_id'],
                'txntype' => 'C', 'payer_id' => $renterid, 'currency' => $currency,
                'status' => 1, 'created' => now(), 'modified' => now(),
            ]);
        } else {
            $stripe_token = $usrData['UserCcToken']['stripe_token'];
            $diaresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $stripe_token, "capture" => true,
                "description" => "DIA Deposit",
                "statement_descriptor" => "DIA Deposit " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($diaresult['status']) && $diaresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $diaresult['stripe_id'];
                $return['message'] = 'Your request processed successfully';
                DB::table('cs_reservation_payments')->insert([
                    'cs_order_id' => $OrderId, 'type' => 1, 'amount' => $amount,
                    'transaction_id' => $diaresult['stripe_id'],
                    'txntype' => 'C', 'payer_id' => $renterid, 'currency' => $currency,
                    'status' => 1, 'created' => now(), 'modified' => now(),
                ]);
            } else {
                $return['message'] = $diaresult;
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 10. chargeInitialFeeForVehicleReservation
    // ═══════════════════════════════════════════

    public function chargeInitialFeeForVehicleReservation($opt = [], $currency = 'USD')
    {
        $amount = $opt['amount'];
        $tax = $opt['tax'];
        $renterid = $opt['renter_id'];
        $OrderId = $opt['id'];
        $usrData = $this->getCustomer($renterid);
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment got failed'];
        $startDate = $opt['start_datetime'];
        $amount = sprintf('%0.2f', ($amount + $tax));

        $Rentresult = $this->walletChargePartialFromWallet($renterid, $amount, $amount . ' initial fee from pending order', $OrderId, 3);
        if ($Rentresult['status']) {
            $return['status'] = 'success';
            $return['transaction_id'] = $Rentresult['transactions'];
            $return['message'] = 'Success';
            if ($Rentresult['pending'] > 0) {
                $SubRentresult = $this->Stripe->charge([
                    "amount" => sprintf('%0.2f', $Rentresult['pending']), "currency" => $currency,
                    "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                    "capture" => true, "description" => "DIA Initial Fee",
                    "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($startDate)),
                    "metadata" => ["payer_id" => $usrData['User']['id']],
                ]);
                if (isset($SubRentresult['status']) && $SubRentresult['status'] == 'success') {
                    $return['transaction_id'] = ["amt" => $Rentresult['pending'], "transaction_id" => $SubRentresult['stripe_id'], "source" => 'card'];
                    DB::table('cs_reservation_payments')->insert([
                        'cs_order_id' => $OrderId, 'type' => 3, 'amount' => $Rentresult['pending'],
                        'tax' => $tax, 'transaction_id' => $SubRentresult['stripe_id'],
                        'txntype' => 'C', 'currency' => $currency,
                        'status' => 1, 'created' => now(), 'modified' => now(),
                    ]);
                } else {
                    $return['message'] = $SubRentresult;
                    $return['status'] = 'error';
                }
            }
            DB::table('cs_reservation_payments')->insert([
                'cs_order_id' => $OrderId, 'type' => 3, 'amount' => ($amount - $Rentresult['pending']),
                'tax' => $tax,
                'transaction_id' => is_array($Rentresult['transactions']) ? json_encode($Rentresult['transactions']) : $Rentresult['transactions'],
                'txntype' => 'C', 'currency' => $currency,
                'status' => 1, 'created' => now(), 'modified' => now(),
            ]);
        } else {
            $Rentresult = $this->Stripe->charge([
                "amount" => $amount, "currency" => $currency,
                "stripeCustomer" => $usrData['UserCcToken']['stripe_token'],
                "capture" => true, "description" => "DIA Initial Fee",
                "statement_descriptor" => "DIA InitialFee " . date('mdy', strtotime($startDate)),
                "metadata" => ["payer_id" => $usrData['User']['id']],
            ]);
            if (isset($Rentresult['status']) && $Rentresult['status'] == 'success') {
                $return['status'] = 'success';
                $return['transaction_id'] = $Rentresult['stripe_id'];
                $return['message'] = 'Success';
                DB::table('cs_reservation_payments')->insert([
                    'cs_order_id' => $OrderId, 'type' => 3, 'amount' => $amount,
                    'tax' => $tax, 'transaction_id' => $Rentresult['stripe_id'],
                    'txntype' => 'C', 'currency' => $currency,
                    'status' => 1, 'created' => now(), 'modified' => now(),
                ]);
            } else {
                $return['message'] = $Rentresult;
            }
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 11. dealerInsuranceRefund – Body commented out in source
    // ═══════════════════════════════════════════

    public function dealerInsuranceRefund($insurance)
    {
        // Entire method body was commented out in legacy source
    }

    // ═══════════════════════════════════════════
    // 13. emfRefundtotal – Full EMF refund
    // ═══════════════════════════════════════════

    public function emfRefundtotal($CsOrder, $refundToStripe = false)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have any rental  transaction to refund.'];
        $rentals = $this->getActiveEmfTransaction($CsOrder['CsOrder']['id']);

        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                    DB::table('cs_payout_transactions')->where('id', $cstransfertxn['id'])->update(['status' => 2, 'modified' => now()]);
                } else {
                    $reverse = false;
                    $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if ($refundToStripe) {
                $return = $this->Stripe->refund(["charge" => $rental['transaction_id'], "amount" => $rental['amount']]);
            } else {
                $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund emfRefundtotal for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
            }
            if ($return['status'] !== 'success') { return $return; }
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => 0], ['id' => $rental['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 27, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => ($refundToStripe ? 'stripe' : 'wallet'), 'type' => 16, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 14. refundBalanceEmf – Partial EMF refund
    // ═══════════════════════════════════════════

    public function refundBalanceEmf($needtorefund, $CsOrderId, $refundabletax, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveEmfTransaction($CsOrderId);
        $pendingtax = $refundabletax;
        $refundamount = 0;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            $totalRefundAmount = 0;
            if ($rental['amount'] <= $needtorefund) {
                $totalRefundAmount = $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $totalRefundAmount = $refundamount = $needtorefund;
            }
            $needtorefund = sprintf('%0.2f', ($needtorefund - $refundamount));
            if (($rental['tax'] ?? 0) >= $refundabletax) {
                $pendingtax = ($rental['tax'] ?? 0) - $refundabletax;
                $totalRefundAmount += $refundabletax;
                $refundabletax = 0;
            } else {
                $refundabletax = 0;
                $pendingtax = 0;
                $totalRefundAmount += ($rental['tax'] ?? 0);
            }
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $totalRefundAmount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($totalRefundAmount, $renterid, $rental['transaction_id'], "refund refundBalanceEmf for booking", $CsOrderId, $rental['charged_at']);
            $return['status'] = 'success';
            $return['message'] = "Your request successfully processed";
            if ($totalRefundAmount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $totalRefundAmount), 'rent' => ($rental['amount'] - $totalRefundAmount), 'tax' => $pendingtax, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 31, "amount" => $totalRefundAmount, "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 16, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 15. lateFeeRefundtotal – Full late fee refund
    // ═══════════════════════════════════════════

    public function lateFeeRefundtotal($CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have any rental  transaction to refund.'];
        $rentals = $this->getActiveLateFeeTransaction($CsOrder['CsOrder']['id']);

        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                    DB::table('cs_payout_transactions')->where('id', $cstransfertxn['id'])->update(['status' => 2, 'modified' => now()]);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund lateFeeRefundtotal for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
            $return['status'] = 'success';
            $return['message'] = "Your request successfully processed";
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => 0], ['id' => $rental['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 31, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 19, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 16. refundBalanceLateFee – Partial late fee refund
    // ═══════════════════════════════════════════

    public function refundBalanceLateFee($needtorefund, $CsOrderId, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveLateFeeTransaction($CsOrderId);
        $refundamount = 0;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            if ($rental['amount'] <= $needtorefund) {
                $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $refundamount = $needtorefund;
            }
            $needtorefund = sprintf('%0.2f', ($needtorefund - $refundamount));
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $refundamount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($refundamount, $renterid, $rental['transaction_id'], "refund refundBalanceLateFee for booking", $CsOrderId, $rental['charged_at']);
            $return['status'] = 'success';
            $return['message'] = "Your request successfully processed";
            if ($refundamount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $refundamount), 'rent' => ($rental['amount'] - $refundamount), 'dealer_amt' => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, 'dealer_amt' => $dealerAmt], ['id' => $rental['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 31, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 19, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 17. diainsuRefundtotal – Full DIA insurance refund
    // ═══════════════════════════════════════════

    public function diainsuRefundtotal($CsOrder)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have any rental  transaction to refund.'];
        $rentals = $this->getActiveDiaInsuranceTransaction($CsOrder['CsOrder']['id']);

        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                    DB::table('cs_payout_transactions')->where('id', $cstransfertxn['id'])->update(['status' => 2, 'modified' => now()]);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }

            if (($rental['payer_id'] ?? null) == ($CsOrder['CsOrder']['user_id'] ?? null)) {
                $result = $this->Stripe->refund(["charge" => $rental['transaction_id']]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $temp = ['cs_order_id' => $CsOrder['CsOrder']['id'], 'cs_payment_id' => $rental['id'], 'user_id' => $rental['payer_id'], 'type' => 11, 'refund' => $rental['amount'], 'transaction_id' => $rental['transaction_id'], 'currency' => $rental['currency'] ?? 'USD'];
                    $this->commitRefundPayoutTransactions($temp, $rental['amount'], $result);
                }
            } else {
                $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund diainsuRefundtotal for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
            }
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => 0], ['id' => $rental['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 32, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 14, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 18. refundBalanceDiainsu – Partial DIA insurance refund
    // ═══════════════════════════════════════════

    public function refundBalanceDiainsu($needtorefund, $CsOrderId, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveDiaInsuranceTransaction($CsOrderId);
        $refundamount = 0;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            if ($rental['amount'] <= $needtorefund) {
                $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $refundamount = $needtorefund;
            }
            $needtorefund = sprintf('%0.2f', ($needtorefund - $refundamount));
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $refundamount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if (!empty($rental['payer_id']) && $rental['payer_id'] != $renterid) {
                $result = $this->Stripe->refund(["charge" => $rental['transaction_id']]);
                if (isset($result['status']) && $result['status'] == 'success') {
                    $temp = ['cs_order_id' => $CsOrderId, 'cs_payment_id' => $rental['id'], 'user_id' => $rental['payer_id'], 'type' => 11, 'refund' => $refundamount, 'transaction_id' => $rental['transaction_id'], 'currency' => $rental['currency'] ?? 'USD'];
                    $this->commitRefundPayoutTransactions($temp, $rental['amount'], $result);
                }
            } else {
                $this->walletAddBalance($refundamount, $renterid, $rental['transaction_id'], "refund refundBalanceDiainsu for booking", $CsOrderId, $rental['charged_at']);
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
            }
            if ($refundamount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $refundamount), 'rent' => ($rental['amount'] - $refundamount), "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 32, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 14, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 19. tollRefundtotal – Full toll refund
    // ═══════════════════════════════════════════

    public function tollRefundtotal($CsOrder, $refundToStripe = false)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have any rental  transaction to refund.'];
        $rentals = $this->getActiveTollTransaction($CsOrder['CsOrder']['id']);

        foreach ($rentals as $rental) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                    DB::table('cs_payout_transactions')->where('id', $cstransfertxn['id'])->update(['status' => 2, 'modified' => now()]);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if ($refundToStripe) {
                $return = $this->Stripe->refund(["charge" => $rental['transaction_id'], "amount" => $rental['amount']]);
            } else {
                $this->walletAddBalance($rental['amount'], $CsOrder['CsOrder']['renter_id'], $rental['transaction_id'], "refund tollRefundtotal for booking", $CsOrder['CsOrder']['id'], $rental['charged_at']);
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
            }
            if ($return['status'] !== 'success') { return $return; }
            $this->updateOrderPayments(['status' => 2, "dealer_amt" => 0], ['id' => $rental['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 37, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => ($refundToStripe ? 'stripe' : 'wallet'), 'type' => 6, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 20. refundBalanceToll – Partial toll refund
    // ═══════════════════════════════════════════

    public function refundBalanceToll($needtorefund, $CsOrderId, $renterid)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, amount must be valid value'];
        if ($needtorefund <= 0) { return $return; }
        $rentals = $this->getActiveTollTransaction($CsOrderId);
        $refundamount = 0;
        foreach ($rentals as $rental) {
            if (!$needtorefund) { return $return; }
            if ($rental['amount'] <= $needtorefund) {
                $refundamount = $rental['amount'];
            } elseif ($rental['amount'] > $needtorefund) {
                $refundamount = $needtorefund;
            }
            $needtorefund = sprintf('%0.2f', ($needtorefund - $refundamount));
            $dealerAmt = 0;
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrderId, $rental['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $currencyRate = 1;
                $previousRent = $rental['amount'];
                $mypreviousPart = $cstransfertxn['amount'];
                $mynewPart = sprintf('%0.2f', (($rental['amount'] - $refundamount) * $mypreviousPart / $previousRent));
                $reversableAmount = sprintf('%0.2f', ($mypreviousPart - $mynewPart));
                $reversableAmountwithCurrency = sprintf('%0.2f', ($reversableAmount / $currencyRate));
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id'], ["amount" => $reversableAmountwithCurrency * 100]);
                $dealerAmt = $cstransfertxn['base_amt'] - $reversableAmountwithCurrency;
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $reversableAmount, $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            $this->walletAddBalance($refundamount, $renterid, $rental['transaction_id'], "refund refundBalanceToll for booking", $CsOrderId, $rental['charged_at']);
            $return['status'] = 'success';
            $return['message'] = "Your request successfully processed";
            if ($refundamount < $rental['amount']) {
                $this->updateOrderPayments(['status' => 1, 'amount' => ($rental['amount'] - $refundamount), 'rent' => ($rental['amount'] - $refundamount), "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            } else {
                $this->updateOrderPayments(['status' => 2, "dealer_amt" => $dealerAmt], ['id' => $rental['id']]);
            }
            $this->savePaymentLogRecord(["orderid" => $CsOrderId, "type" => 37, "amount" => $refundamount, "transaction_id" => $rental['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrderId, "amount" => $rental['amount'], "transaction_id" => $rental['transaction_id'], "source" => 'wallet', 'type' => 6, 'charged_at' => $rental['charged_at']]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 21. PaymentAuthorizeOnly – Authorize payment (Uber)
    // ═══════════════════════════════════════════

    public function PaymentAuthorizeOnly($amt, $payer, $type = 35, $statement = '', $capture = false, $currency = 'USD')
    {
        $usrData = $this->getCustomer($payer);
        $stripe_token = $usrData['UserCcToken']['stripe_token'] ?? '';
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        if (empty($stripe_token)) { return $return; }
        $result = $this->Stripe->charge([
            "amount" => $amt,
            "currency" => $currency,
            "stripeCustomer" => $stripe_token,
            "capture" => $capture ?: false,
            "description" => ($statement != '' ? $statement : "Uber Payment"),
            "statement_descriptor" => !empty($statement) ? $statement . date('mdy') : "DIA Uber " . date('mdy'),
        ]);
        if (isset($result['status']) && $result['status'] == 'success') {
            $return['transaction_id'] = $result['stripe_id'];
            $return['amt'] = $amt;
            $return['status'] = 'success';
            if ($capture) {
                $this->saveOnlyPaymentLogRecord(["orderid" => 0, "amount" => $amt, "transaction_id" => $result['stripe_id']], $type);
            }
        } else {
            $return['message'] = $result;
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 22. RefundAuthorizePayment – Refund authorized payment
    // ═══════════════════════════════════════════

    public function RefundAuthorizePayment($transaction, $type = 36)
    {
        if (!empty($transaction)) {
            $this->stripe();
            $result = $this->Stripe->refund(["charge" => $transaction['transaction_id']]);
            if (isset($result['status']) && $result['status'] == 'success') {
                return ['status' => 'success', 'message' => 'Payment released successfully', 'result' => $result];
            }
            return ['status' => 'error', 'message' => $result, 'result' => []];
        }
        return ['status' => 'error', 'message' => 'Something went wrong', 'result' => []];
    }

    // ═══════════════════════════════════════════
    // 23. UberPaymentCaptureOnly – Capture Uber payment
    // ═══════════════════════════════════════════

    public function UberPaymentCaptureOnly($transaction, $statement = '')
    {
        if (!empty($transaction)) {
            $this->stripe();
            $result = $this->Stripe->capture([
                "auth_token" => $transaction['transaction_id'],
                "statement_descriptor" => !empty($statement) ? $statement . date('mdy') : "DIA Uber " . date('mdy'),
            ]);
            if (isset($result['status']) && $result['status'] == 'success') {
                $this->saveOnlyPaymentLogRecord(["orderid" => 0, "amount" => $transaction['amount'], "transaction_id" => $transaction['transaction_id']], 35);
                return ['status' => 'success', 'message' => 'Payment released successfully', 'result' => $result];
            }
            return ['status' => 'error', 'message' => $result, 'result' => []];
        }
        return ['status' => 'error', 'message' => 'Something went wrong', 'result' => []];
    }

    // ═══════════════════════════════════════════
    // 24. updateAccount – Update Stripe account
    // ═══════════════════════════════════════════

    public function updateAccount($accountid)
    {
        $this->stripe();
        $options = [
            'tos_acceptance' => ['service_agreement' => 'recipient'],
            'capabilities' => ['transfers' => ['requested' => true]],
        ];
        $result = $this->Stripe->updateConnectedAccount($accountid, $options);
        return $result;
    }

    // ═══════════════════════════════════════════
    // 25. createCustomerBalanceRecord
    // ═══════════════════════════════════════════

    private function createCustomerBalanceRecord($orderObj, $amount)
    {
        $ownerId = $orderObj['CsOrder']['user_id'];
        $userId = $orderObj['CsOrder']['renter_id'];
        $orderId = $orderObj['CsOrder']['id'];

        DB::table('cs_user_balance_logs')->insert([
            'user_id' => $userId,
            'credit' => $amount,
            'type' => 19,
            'owner_id' => $ownerId,
            'note' => "Driver Bad Debt for #" . $orderId,
            'created' => now(),
            'modified' => now(),
        ]);

        DB::table('cs_user_balances')->insert([
            'owner_id' => $ownerId,
            'user_id' => $userId,
            'note' => "Driver Bad Debt for #" . $orderId,
            'credit' => $amount,
            'balance' => $amount,
            'debit' => 0,
            'type' => 19,
            'chargetype' => 'lumpsum',
            'installment_type' => 'daily',
            'installment_day' => null,
            'installment' => 0,
            'created' => now(),
            'modified' => now(),
        ]);
    }

    // ═══════════════════════════════════════════
    // 26. deailerPaidInsuranceRefund – Refund dealer-paid insurance
    // ═══════════════════════════════════════════

    public function deailerPaidInsuranceRefund($CsOrder, $refundToStripe = false)
    {
        $this->stripe();
        $return = ['status' => 'error', 'message' => 'Sorry, you dont have anything to refund'];
        $insurances = DB::table('cs_order_payments')
            ->where('cs_order_id', $CsOrder['CsOrder']['id'])
            ->where('payer_id', $CsOrder['CsOrder']['user_id'])
            ->get()->map(fn($r) => (array) $r)->toArray();

        if (empty($insurances)) {
            $return['status'] = 'success';
            return $return;
        }

        foreach ($insurances as $insurance) {
            $cstransfertxn = $this->getActivePayoutTransactions($CsOrder['CsOrder']['id'], $insurance['id']);
            $reverse = true;
            if (!empty($cstransfertxn)) {
                $transfrResp = $this->Stripe->reverseTransfer($cstransfertxn['transfer_id']);
                if (isset($transfrResp['status']) && $transfrResp['status'] == 'success') {
                    $this->saveRefundPayoutTransactions($cstransfertxn, $cstransfertxn['amount'], $transfrResp['result']);
                } else {
                    $reverse = false; $return['message'] = $transfrResp;
                }
            }
            if (!$reverse) { return $return; }
            if ($refundToStripe) {
                $return = $this->Stripe->refund(["charge" => $insurance['transaction_id'], "amount" => $insurance['amount']]);
            } else {
                if (!empty($insurance['payer_id'])) {
                    $this->walletAddBalance($insurance['amount'], $insurance['payer_id'], $insurance['transaction_id'], "refund insuranceRefund for booking", $CsOrder['CsOrder']['id'], $insurance['charged_at'] ?? null);
                } else {
                    $this->walletAddBalance($insurance['amount'], $CsOrder['CsOrder']['renter_id'], $insurance['transaction_id'], "refund insuranceRefund for booking", $CsOrder['CsOrder']['id'], $insurance['charged_at'] ?? null);
                }
                $return['status'] = 'success';
                $return['message'] = "Your request successfully processed";
            }
            if ($return['status'] !== 'success') { return $return; }
            $this->updateOrderPayments(['status' => 2], ['id' => $insurance['id']]);
            $this->savePaymentLogRecord(["orderid" => $CsOrder['CsOrder']['id'], "type" => 11, "amount" => $insurance['amount'], "transaction_id" => $insurance['transaction_id'], "status" => 1, 'refundtransactionid' => ""]);
            \App\Services\Legacy\ReportPayment::saveWalletRefund(["orderid" => $CsOrder['CsOrder']['id'], "amount" => $insurance['amount'], "transaction_id" => $insurance['transaction_id'], "source" => ($refundToStripe ? 'stripe' : 'wallet'), 'type' => 4, 'charged_at' => $insurance['charged_at'] ?? null]);
        }
        return $return;
    }

    // ═══════════════════════════════════════════
    // 27. createPaymentIntent – Simple wrapper
    // ═══════════════════════════════════════════

    public function createPaymentIntent($opt = [])
    {
        $this->stripe();
        return $this->Stripe->createPaymentIntent($opt);
    }

    // ═══════════════════════════════════════════
    // 28. chargePaymentIntent – Simple wrapper
    // ═══════════════════════════════════════════

    public function chargePaymentIntent($intent, $opt = [])
    {
        $this->stripe();
        return $this->Stripe->chargePaymentIntent($intent, $opt);
    }

} // end class PaymentProcessor
