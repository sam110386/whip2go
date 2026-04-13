<?php

namespace App\Services\Legacy;

use App\Models\Legacy\User;
use App\Models\Legacy\UserCcToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentProcessor
{
    private StripeGateway $stripeGateway;

    public function __construct(?StripeGateway $stripeGateway = null)
    {
        $this->stripeGateway = $stripeGateway ?? app(StripeGateway::class);
    }

    public function createLoginLink(string $stripekey): array
    {
        return $this->stripeGateway->createLoginLink($stripekey);
    }

    public function addNewCard($dataValues, $cust_id = ''): array
    {
        $return = ['status' => 'error', 'authcode' => '', 'message' => 'Required inputs are missing'];

        $cardNumber = preg_replace("/[^0-9]/", "", (string) $this->value($dataValues, 'credit_card_number'));
        $cvv = (string) $this->value($dataValues, 'cvv');
        $expiration = (string) $this->value($dataValues, 'expiration');
        if ($cardNumber === '' || $cvv === '' || $expiration === '') {
            return $return;
        }

        $exp = explode('/', $expiration);
        $expMonth = trim($exp[0] ?? '');
        $expYear = trim($exp[1] ?? '');
        if ($expMonth === '' || $expYear === '') {
            return $return;
        }

        $tokenResult = $this->stripeGateway->createCardToken([
            'card' => [
                'number' => $cardNumber,
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'cvc' => $cvv,
                'name' => (string) $this->value($dataValues, 'card_holder_name'),
                'address_zip' => (string) $this->value($dataValues, 'zip'),
                'address_city' => (string) $this->value($dataValues, 'city'),
                'address_state' => (string) $this->value($dataValues, 'state'),
                'address_country' => (string) ($this->value($dataValues, 'country') ?: 'US'),
                'address_line1' => (string) $this->value($dataValues, 'address'),
            ],
        ]);

        if (($tokenResult['status'] ?? 'error') !== 'success') {
            return ['status' => 'error', 'message' => $tokenResult['msg'] ?? 'Stripe token creation failed'];
        }

        if (!empty($cust_id)) {
            $attach = $this->stripeGateway->addCardToCustomer((string) $cust_id, (string) ($tokenResult['token'] ?? ''), [
                'name' => (string) $this->value($dataValues, 'card_holder_name'),
            ]);
            if (($attach['status'] ?? 'error') === 'success') {
                return [
                    'status' => 'success',
                    'stripe_token' => (string) $cust_id,
                    'card_id' => $attach['stripe_id'] ?? '',
                    'card_funding' => $tokenResult['card_funding'] ?? '',
                ];
            }
            return ['status' => 'error', 'message' => $attach['msg'] ?? 'Failed to attach card to customer'];
        }

        $customer = $this->stripeGateway->customerCreate(['stripeToken' => $tokenResult['token'] ?? '']);
        if (($customer['status'] ?? 'error') === 'success') {
            return [
                'status' => 'success',
                'stripe_token' => $customer['stripe_id'] ?? '',
                'card_id' => $tokenResult['card_id'] ?? '',
                'card_funding' => $tokenResult['card_funding'] ?? '',
            ];
        }
        return ['status' => 'error', 'message' => $customer['msg'] ?? 'Failed to create customer'];
    }

    public function addCardToCustomer($cust_id, $card_id, $opt = []): array
    {
        return $this->stripeGateway->addCardToCustomer((string) $cust_id, (string) $card_id, (array) $opt);
    }

    public function makeCardDefault($cust_id, $card_id): array
    {
        return $this->stripeGateway->makeCardDefault((string) $cust_id, ['default_source' => $card_id]);
    }

    public function deleteCustomerCard($cust_id, $card_id): array
    {
        return $this->stripeGateway->deleteCustomerCard((string) $cust_id, empty($card_id) ? null : (string) $card_id);
    }

    public function customerDelete($cust_id): array
    {
        return $this->stripeGateway->customerDelete((string) $cust_id);
    }

    public function createSource($id, $params): array
    {
        return $this->stripeGateway->createSource((string) $id, (array) $params);
    }

    public function customerRetrieve($id): array
    {
        return $this->stripeGateway->customerRetrieve((string) $id);
    }

    public function charge($data, $opt = []): array
    {
        return $this->stripeGateway->charge((array) $data, (array) $opt);
    }

    public function refund($data, $opt = []): array
    {
        return $this->stripeGateway->refund((array) $data, (array) $opt);
    }

    public function capture($data, $opt = []): array
    {
        return $this->stripeGateway->capture((array) $data, (array) $opt);
    }

    public function transferToDealer($options): array
    {
        return $this->stripeGateway->transferToDealer((array) $options);
    }

    public function retrieveTransfer($transferId): array
    {
        return $this->stripeGateway->retrieveTransfer((string) $transferId);
    }

    public function retriveBalanceTransaction($options, $stripe_account = null): array
    {
        $filters = (array) $options;
        if (!empty($stripe_account)) {
            $filters['stripe_account'] = $stripe_account;
        }
        return $this->stripeGateway->retriveBalanceTransaction($filters);
    }

    public function retriveBalanceTransactionDetails($id, $opt = []): array
    {
        return $this->stripeGateway->retriveBalanceTransactionDetails((string) $id, (array) $opt);
    }

    public function chargeAmtToUser($amt, $ownerid, $statement = '', $currency = '', $cc_token_id = ''): array
    {
        $return = ['status' => 'error', 'message' => 'Something went wrong', 'currency' => ($currency ?: 'usd')];
        $user = User::find($ownerid);
        if (!$user) {
            return $return;
        }

        $tokenId = !empty($cc_token_id) ? (int) $cc_token_id : (int) ($user->cc_token_id ?? 0);
        $ccToken = $tokenId ? UserCcToken::find($tokenId) : null;
        if (!$ccToken || empty($ccToken->stripe_token)) {
            return $return;
        }

        $charge = $this->stripeGateway->charge([
            'amount' => (float) $amt,
            'currency' => $currency ?: ($user->currency ?: 'usd'),
            'customer' => $ccToken->stripe_token,
            'capture' => 'true',
            'description' => $statement !== '' ? $statement : 'DIA Advance Payment',
            'statement_descriptor' => ($statement !== '' ? $statement . '_' : 'DIA Pay ') . date('mdy'),
            'metadata' => ['payer_id' => $user->id],
        ]);

        if (($charge['status'] ?? '') !== 'success') {
            $return['message'] = $charge['msg'] ?? 'Charge failed';
            return $return;
        }

        return [
            'status' => 'success',
            'transaction_id' => $charge['stripe_id'] ?? '',
            'amt' => (float) $amt,
            'currency' => $currency ?: ($user->currency ?: 'usd'),
            'source_transfer' => null,
            'balance_transaction' => '',
        ];
    }

    public function refundWalletBalance($amt, $transactionid, $orderid = 0): array
    {
        if ((float) $amt <= 0) {
            return ['status' => 'error', 'message' => 'Sorry, amount must be valid'];
        }

        $refund = $this->stripeGateway->refund([
            'charge' => $transactionid,
            'amount' => (float) $amt,
        ]);
        if (($refund['status'] ?? '') === 'success') {
            return ['status' => 'success', 'message' => 'Success'];
        }
        return ['status' => 'error', 'message' => $refund['msg'] ?? 'Refund failed'];
    }

    public function createPaymentIntent($opt = []): array
    {
        return $this->stripeGateway->createPaymentIntent((array) $opt);
    }

    public function chargePaymentIntent($intent, $opt = []): array
    {
        return $this->stripeGateway->chargePaymentIntent((string) $intent, (array) $opt);
    }

    public function chargeAmt($amt, $stripe_token, $statement = '', $currency = 'USD', $type = 34): array
    {
        if (empty($stripe_token)) {
            return ['status' => 'error', 'message' => 'Something went wrong'];
        }

        $charge = $this->stripeGateway->charge([
            'amount' => (float) $amt,
            'currency' => $currency,
            'customer' => $stripe_token,
            'capture' => 'true',
            'description' => $statement !== '' ? $statement : 'DIA Advance Payment',
            'statement_descriptor' => $statement !== '' ? $statement . date('mdy') : 'DIA Uber ' . date('mdy'),
        ]);

        if (($charge['status'] ?? '') !== 'success') {
            return ['status' => 'error', 'message' => $charge['msg'] ?? 'Charge failed'];
        }

        return [
            'status' => 'success',
            'transaction_id' => $charge['stripe_id'] ?? '',
            'amt' => (float) $amt,
        ];
    }

    public function retrieveBalance($ownerid, $stripeKey = ''): array
    {
        $return = ['status' => 'error', 'message' => 'Something went wrong'];
        $user = !empty($ownerid) ? User::find($ownerid) : null;
        $key = $stripeKey ?: ($user->stripe_key ?? '');
        if ($key === '') {
            return $return;
        }

        $res = $this->stripeGateway->retrieveBalance(['stripe_account' => $key]);
        if (($res['status'] ?? 'error') !== 'success') {
            $return['message'] = $res['message'] ?? 'Stripe balance lookup failed';
            return $return;
        }

        $result = $res['result'] ?? [];
        $available = $result['instant_available'][0]['amount'] ?? ($result['available'][0]['amount'] ?? 0);
        return [
            'status' => 'success',
            'balance' => $available == 0 ? 0 : ((float) $available / 100),
        ];
    }

    public function updateAccount($accountid): array
    {
        if (empty($accountid)) {
            return ['status' => false, 'message' => 'Invalid account id'];
        }
        return $this->updateConnectedAccount((string) $accountid, ['tos_acceptance' => ['service_agreement' => 'recipient']]);
    }

    /**
     * Compatibility stub while full Cake business logic is migrated.
     */
    public function ChargeAmountOnCompleteForRenew($CsOrder, $CsOrderTemp): array
    {
        Log::warning('PaymentProcessor::ChargeAmountOnCompleteForRenew is not fully migrated yet.');
        return ['status' => 'success', 'message' => 'Stub success during migration', 'currency' => 'USD'];
    }

    /**
     * Compatibility stub while full Cake business logic is migrated.
     */
    public function checkAndProcessRenew($renterid, $owner_id, $priceRulesAmt = [], $CsOrderId = null, $preOrderId = '', $cc_token_id = '', $parentid = 0): array
    {
        Log::warning('PaymentProcessor::checkAndProcessRenew is not fully migrated yet.', [
            'renter_id' => $renterid,
            'owner_id' => $owner_id,
            'order_id' => $CsOrderId,
        ]);
        return ['status' => 'success', 'message' => 'Stub success during migration'];
    }

    public function retryRental($pendingRent, $pendingTax, $pendingDiaFee, $queue): array
    {
        $queueArr = is_array($queue) ? $queue : (array) $queue;
        $orderId = (int) ($queueArr['id'] ?? 0);
        $renterId = (int) ($queueArr['renter_id'] ?? 0);
        $currency = (string) ($queueArr['currency'] ?? 'usd');
        $amount = (float) sprintf('%0.2f', ((float) $pendingRent + (float) $pendingTax + (float) $pendingDiaFee));

        if ($orderId <= 0 || $renterId <= 0 || $amount <= 0) {
            return ['status' => 'error', 'message' => 'Invalid retryRental payload'];
        }

        $charge = $this->chargeAmtToUser($amount, $renterId, 'retryRental', $currency, $queueArr['cc_token_id'] ?? '');
        if (($charge['status'] ?? 'error') !== 'success') {
            return ['status' => 'error', 'message' => $charge['message'] ?? 'retryRental charge failed'];
        }

        $this->saveOrderPayment([
            'cs_order_id' => $orderId,
            'type' => 2,
            'txntype' => 'C',
            'amount' => (float) $pendingRent,
            'tax' => (float) $pendingTax,
            'dia_fee' => (float) $pendingDiaFee,
            'transaction_id' => (string) ($charge['transaction_id'] ?? ''),
            'status' => 1,
            'charged_at' => now()->toDateTimeString(),
            'created' => now()->toDateTimeString(),
            'modified' => now()->toDateTimeString(),
        ]);
        return ['status' => 'success', 'message' => 'retryRental processed'];
    }

    public function retryDeposit($balanceDeposit, $renter_id, $deposit_type, $order_id, $cc_token_id = null): array
    {
        $amount = (float) $balanceDeposit;
        $orderId = (int) $order_id;
        $renterId = (int) $renter_id;
        if ($amount <= 0 || $orderId <= 0 || $renterId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid retryDeposit payload'];
        }

        $queue = DB::table('cs_orders')->where('id', $orderId)->select('currency')->first();
        $currency = (string) ($queue->currency ?? 'usd');

        // For card-based deposits, retry by charging card.
        if ($deposit_type !== 'C') {
            $charge = $this->chargeAmtToUser($amount, $renterId, 'retryDeposit', $currency, $cc_token_id ?: '');
            if (($charge['status'] ?? 'error') !== 'success') {
                return ['status' => 'error', 'message' => $charge['message'] ?? 'retryDeposit charge failed'];
            }
            $transactionId = (string) ($charge['transaction_id'] ?? '');
            $txnType = 'C';
        } else {
            // Wallet style - keep compatibility marker.
            $transactionId = 'wallet_retry_' . $orderId . '_' . time();
            $txnType = 'C';
        }

        $this->saveOrderPayment([
            'cs_order_id' => $orderId,
            'type' => 1,
            'txntype' => $txnType,
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'status' => 1,
            'charged_at' => now()->toDateTimeString(),
            'created' => now()->toDateTimeString(),
            'modified' => now()->toDateTimeString(),
        ]);
        return ['status' => 'success', 'message' => 'retryDeposit processed'];
    }

    public function retryInsurance($pendingInsurance, $queue): array
    {
        Log::warning('PaymentProcessor::retryInsurance is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryInsurance pending migration'];
    }

    public function retryInitialfee($pending, $queue, $pendingTax = 0): array
    {
        Log::warning('PaymentProcessor::retryInitialfee is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryInitialfee pending migration'];
    }

    public function retryTollfee($pendingToll, $queue): array
    {
        Log::warning('PaymentProcessor::retryTollfee is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryTollfee pending migration'];
    }

    public function retryDiaInsurance($pendingDiaInsurance, $queue): array
    {
        Log::warning('PaymentProcessor::retryDiaInsurance is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryDiaInsurance pending migration'];
    }

    public function retryEmf($pendingRent, $pendingTax, $queue): array
    {
        Log::warning('PaymentProcessor::retryEmf is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryEmf pending migration'];
    }

    public function retryLatefee($pendingLateFee, $queue): array
    {
        Log::warning('PaymentProcessor::retryLatefee is not fully migrated yet.');
        return ['status' => 'error', 'message' => 'retryLatefee pending migration'];
    }

    public function ReservationReleaseAuthorizePayment($transaction, $ownerid = null): array
    {
        if (empty($transaction) || empty($transaction['transaction_id'])) {
            return ['status' => 'error', 'message' => 'Payment transaction details were empty'];
        }

        $refund = $this->refund([
            'charge' => (string) $transaction['transaction_id'],
        ]);

        if (($refund['status'] ?? '') === 'success') {
            return ['status' => 'success', 'message' => 'Payment released successfully', 'result' => $refund];
        }

        return ['status' => 'error', 'message' => $refund['msg'] ?? 'Unable to release payment', 'result' => $refund];
    }

    public function ReservationPaymentCaptureOnly($orderid, $transaction, $ownerid): array
    {
        if (empty($transaction)) {
            return ['status' => 'error', 'message' => 'Payment auth details were empty'];
        }

        $transactions = isset($transaction['transaction_id']) ? [$transaction] : (array) $transaction;
        $hasError = false;
        foreach ($transactions as $txn) {
            if (empty($txn['transaction_id'])) {
                $hasError = true;
                continue;
            }
            // Wallet-backed transactions were treated as already captured.
            if (($txn['txntype'] ?? '') === 'C') {
                continue;
            }

            $capture = $this->capture([
                'auth_token' => (string) $txn['transaction_id'],
                'statement_descriptor' => 'DIA Fee ' . date('mdy'),
            ]);
            if (($capture['status'] ?? '') !== 'success') {
                $hasError = true;
            }
        }

        return ['status' => $hasError ? 'error' : 'success', 'message' => 'Payment auth details were empty'];
    }

    public function PaymentCapture($renterid, $owner_id, $priceRulesAmt = [], $user_cctoken_id = null): array
    {
        // Cake version currently returns success placeholder because core block is commented out.
        return ['status' => 'success', 'message' => 'Payment Details not saved', 'payment_id' => ''];
    }

    public function accountRetrieve(string $accountId): array
    {
        return $this->stripeGateway->accountRetrieve($accountId);
    }

    public function updateConnectedAccount(string $accountId, array $options): array
    {
        return $this->stripeGateway->accountUpdate($accountId, $options);
    }

    private function value($source, string $key)
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }
        if (is_object($source)) {
            return $source->{$key} ?? null;
        }
        return null;
    }

    private function saveOrderPayment(array $data): void
    {
        DB::table('cs_order_payments')->insert($data);
    }
}
