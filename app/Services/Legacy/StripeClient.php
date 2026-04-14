<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Port of CakePHP app/Lib/Stripe.php
 *
 * Wraps the Stripe PHP SDK for charges, customers, tokens, refunds,
 * accounts, transfers, payouts, and balance operations.
 *
 * Requires: stripe/stripe-php composer package.
 */
class StripeClient
{
    public string $mode = 'Test';
    public string $currency = 'usd';
    public array $fields = [
        'stripe_id' => 'id',
        'funding' => 'funding',
        'balance_transaction' => 'balance_transaction',
        'stripe_account' => 'stripe_account',
        'application_fee' => 'application_fee',
        'application_fee_amount' => 'application_fee_amount',
        'refunds' => 'refunds',
        'source_transfer' => 'source_transfer',
        'cust_id' => 'cust_id',
        'transfer_data' => 'transfer_data',
        'on_behalf_of' => 'on_behalf_of',
    ];

    public ?string $key = null;
    private ?DiaError $Error = null;

    protected array $_chargeParams = [
        'amount', 'currency', 'customer', 'card', 'charge', 'description',
        'metadata', 'capture', 'statement_descriptor', 'receipt_email',
        'application_fee', 'shipping', 'destination', 'reverse_transfer',
        'refund_application_fee', 'transfer_data', 'on_behalf_of',
        'application_fee_amount',
    ];

    public function __construct(string $key, ?string $mode = null, bool $setup = true)
    {
        $this->key = $key;
        $this->Error = new DiaError();
        if ($mode !== null) {
            $this->mode = $mode;
        }
        if ($setup) {
            $this->setup();
        }
    }

    public function setup(): ?string
    {
        if (!$this->Error) {
            $this->Error = new DiaError();
        }
        if (!class_exists('\\Stripe\\Stripe')) {
            $this->Error->logError('Stripe', 'Stripe API library is missing or could not be loaded', 'CRITICAL', true);
            return 'Stripe API library is missing or could not be loaded.';
        }
        if (!$this->key) {
            $this->Error->logError('Stripe', 'Stripe API key is not set', 'CRITICAL', true);
            return 'Stripe API key is not set.';
        }
        $currency = config('services.stripe.currency');
        if ($currency) {
            $this->currency = $currency;
        }
        $fields = config('services.stripe.fields');
        if ($fields) {
            $this->fields = $fields;
        }
        return null;
    }

    /**
     * @return array|string  Array on success, error string on failure.
     */
    public function charge(array $data, array $opt = []): array|string
    {
        if (!isset($data['stripeToken']) && !isset($data['stripeCustomer']) && !isset($data['source']) && !isset($data['stripe_id'])) {
            $this->Error->logError('Stripe', 'The required stripeToken or stripeCustomer fields are missing', 'WARN', true);
            return 'The required stripeToken or stripeCustomer fields are missing.';
        }
        if (!isset($data['amount']) || !$data['amount'] || !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['amount'])) {
            $this->Error->logError('Stripe', 'Amount is required and must be valid number', 'WARN', true);
            return 'Amount is required and must be numeric.';
        }
        $data['amount'] = $data['amount'] > 0.5 ? $data['amount'] : 0.5;
        $data['amount'] = sprintf("%0.0f", $data['amount'] * 100);

        if (isset($data['destination']['amount']) && $data['destination']['amount'] && preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['destination']['amount'])) {
            $data['destination']['amount'] = sprintf("%0.0f", $data['destination']['amount'] * 100);
        }
        if (isset($data['transfer_data']['amount']) && $data['transfer_data']['amount'] && preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['transfer_data']['amount'])) {
            $data['transfer_data']['amount'] = sprintf("%0.0f", $data['transfer_data']['amount'] * 100);
        }
        if (isset($data['application_fee_amount']) && $data['application_fee_amount'] && preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['application_fee_amount'])) {
            $data['application_fee_amount'] = sprintf("%0.0f", $data['application_fee_amount'] * 100);
        }

        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        $chargeData = [];

        foreach ($this->_chargeParams as $param) {
            if (isset($data[$param])) {
                $chargeData[$param] = $data[$param];
            }
        }

        if (!isset($chargeData['currency'])) {
            $chargeData['currency'] = $this->currency;
        } else {
            $chargeData['currency'] = strtolower($chargeData['currency']);
        }

        if (isset($data['stripeCustomer']) && strpos($data['stripeCustomer'], 'acct_') !== false) {
            $data['source'] = $data['stripeCustomer'];
            unset($data['stripeCustomer']);
        }

        if (isset($data['card_id'])) {
            $chargeData['card'] = $data['card_id'];
        }
        if (isset($data['source'])) {
            $chargeData['source'] = $data['source'];
        } elseif (isset($data['stripeToken'])) {
            $chargeData['card'] = $data['stripeToken'];
        } else {
            $chargeData['customer'] = $data['stripeCustomer'];
        }

        try {
            $charge = \Stripe\Charge::create($chargeData, $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', false);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError: Stripe could not be reached', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $this->Error->logError('Stripe', 'Error: Unknown Error. Body:' . print_r($body, true), 'WARN', true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $this->Error->logError('Stripe', $e->getMessage(), 'WARN', true);
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($charge);
    }

    /**
     * @return array|string
     */
    public function chargePaymentIntent(string $intent, array $opt = []): array|string
    {
        if (empty($intent)) {
            return 'The required stripeToken or stripeCustomer fields are missing.';
        }
        if (!isset($opt['amount']) || !$opt['amount'] || !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $opt['amount'])) {
            return 'Amount is required and must be numeric.';
        }
        $opt['amount'] = $opt['amount'] > 0.5 ? $opt['amount'] : 0.5;
        $opt['amount'] = sprintf("%0.0f", $opt['amount'] * 100);

        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        $chargeData = $opt;
        if (!isset($chargeData['currency'])) {
            $chargeData['currency'] = $this->currency;
        } else {
            $chargeData['currency'] = strtolower($chargeData['currency']);
        }

        try {
            $PaymentIntent = \Stripe\PaymentIntent::retrieve($intent);
            $charge = $PaymentIntent->capture($chargeData, []);
        } catch (\Stripe\Exception\CardException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', false);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError: Stripe could not be reached', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $this->Error->logError('Stripe', 'Error: Unknown Error. Body:' . print_r($body, true), 'WARN', true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $this->Error->logError('Stripe', $e->getMessage(), 'WARN', true);
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($charge);
    }

    /**
     * @return array|string
     */
    public function createPaymentIntent(array $opt = []): array|string
    {
        if (!isset($opt['amount']) || !$opt['amount'] || !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $opt['amount'])) {
            return 'Amount is required and must be numeric.';
        }
        $opt['amount'] = $opt['amount'] > 0.5 ? $opt['amount'] : 0.5;
        $opt['amount'] = sprintf("%0.0f", $opt['amount'] * 100);

        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        $chargeData = $opt;
        if (!isset($chargeData['currency'])) {
            $chargeData['currency'] = $this->currency;
        } else {
            $chargeData['currency'] = strtolower($chargeData['currency']);
        }

        try {
            $intent = \Stripe\PaymentIntent::create($chargeData);
        } catch (\Stripe\Exception\CardException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Card error.';
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return ['status' => true, 'message' => 'Payment Intent created successfully', 'client_secret' => $intent->client_secret];
    }

    /**
     * @return array|string
     */
    public function chargeRetrieve(array $data, array $opt = []): array|string
    {
        if (!isset($data['auth_token'])) {
            return 'The required auth_token fields are missing.';
        }
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            $charge = \Stripe\Charge::retrieve($data['auth_token'], $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', false);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError: Stripe could not be reached', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $this->Error->logError('Stripe', 'Error: ' . print_r($body, true), 'WARN', true);
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $this->Error->logError('Stripe', $e->getMessage(), 'WARN', true);
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $charge->toArray(true);
    }

    /**
     * @return array|string
     */
    public function customerCreate(array $data): array|string
    {
        if (isset($data['stripeToken'])) {
            $data['source'] = $data['stripeToken'];
            unset($data['stripeToken']);
        }
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            $customer = \Stripe\Customer::create($data);
        } catch (\Stripe\Exception\CardException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $body = $e->getJsonBody();
            $err = $body['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError: Stripe could not be reached', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $this->Error->logError('Stripe', 'Error: ' . print_r($body, true), 'WARN', true);
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $this->Error->logError('Stripe', $e->getMessage(), 'WARN', true);
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($customer);
    }

    /**
     * @return array|string
     */
    public function addCardToCustomer(string $cust, string $card, array $opt = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            try {
                $customer = \Stripe\Customer::retrieve($cust);
            } catch (Exception $e) {
                $body = $e->getJsonBody();
                return ['status' => 'success', 'msg' => ($body['error']['message'] ?? 'Error: Unknown Error.')];
            }
            if (!$customer) {
                return ['status' => 'success', 'msg' => 'Customer resource not found'];
            }
            $customer = \Stripe\Customer::createSource($cust, ['source' => $card], $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($customer);
    }

    /**
     * @return array|string
     */
    public function makeCardDefault(string $cust, array $opt = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            try {
                $customer = \Stripe\Customer::retrieve($cust);
            } catch (Exception $e) {
                $body = $e->getJsonBody();
                return ['status' => 'success', 'msg' => ($body['error']['message'] ?? 'Error: Unknown Error.')];
            }
            if (!$customer) {
                return ['status' => 'success', 'msg' => 'Customer resource not found'];
            }
            $customer = \Stripe\Customer::update($cust, $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($customer);
    }

    /**
     * @return array|string
     */
    public function deleteCustomerCard(string $cust, string $card): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            try {
                $customer = \Stripe\Customer::retrieve($cust);
            } catch (Exception $e) {
                $body = $e->getJsonBody();
                return ['status' => 'success', 'msg' => ($body['error']['message'] ?? 'Error: Unknown Error.')];
            }
            if (!$customer) {
                return ['status' => 'success', 'msg' => 'Customer resource not found'];
            }
            if (empty($card)) {
                $customer = $customer->delete();
            } else {
                $customer = \Stripe\Customer::deleteSource($cust, $card, []);
            }
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($customer);
    }

    /**
     * @return array|string
     */
    public function createSource(string $id, array $params): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;

        try {
            $customer = \Stripe\Customer::createSource($id, $params, null);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $this->Error->logError('Stripe', 'InvalidRequestError: ' . $err['type'] . ': ' . $err['message'], 'WARN', true);
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->Error->logError('Stripe', 'AuthenticationError: API key rejected', 'SEVERE', true);
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->Error->logError('Stripe', 'ApiConnectionError', 'WARN', true);
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            return (string) $error;
        }
        $stripe = $customer->toArray(true);
        return ['status' => 'success', 'card_id' => $stripe['id']];
    }

    /**
     * @return \Stripe\Customer|false
     */
    public function customerRetrieve(string $id)
    {
        \Stripe\Stripe::setApiKey($this->key);
        $customer = false;
        try {
            $customer = \Stripe\Customer::retrieve($id);
        } catch (Exception $e) {
            $body = method_exists($e, 'getJsonBody') ? $e->getJsonBody() : [];
            $this->Error->logError('Stripe', $body['error']['message'] ?? 'Error: Unknown Error.', 'WARN', false);
        }
        return $customer;
    }

    public function customerDelete(array $data)
    {
        \Stripe\Stripe::setApiKey($this->key);
        $customer = false;
        try {
            $customer = \Stripe\Customer::retrieve($data['cust_id'])->delete();
        } catch (Exception $e) {
            $body = method_exists($e, 'getJsonBody') ? $e->getJsonBody() : [];
            $this->Error->logError('Stripe', $body['error']['message'] ?? 'Error: Unknown Error.', 'WARN', false);
        }
        return $customer;
    }

    public function cancelsubscription(string $id, string $subscriptionid)
    {
        \Stripe\Stripe::setApiKey($this->key);
        $customer = false;
        try {
            $customer = \Stripe\Customer::retrieve($id);
            $customer->subscriptions->retrieve($subscriptionid)->cancel();
        } catch (Exception $e) {
            $body = method_exists($e, 'getJsonBody') ? $e->getJsonBody() : [];
            $this->Error->logError('Stripe', $body['error']['message'] ?? 'Error: Unknown Error.', 'WARN', true);
        }
        return $customer;
    }

    /**
     * @return array
     */
    public function createCardToken(array $data, array $opt = []): array
    {
        \Stripe\Stripe::setApiKey($this->key);
        $return = ['status' => 'error', 'msg' => 'something went wrong with stripe'];
        $error = null;

        try {
            $token = \Stripe\Token::create($data, $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'Card Error: ' . $error, 'SEVERE', true);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'InvalidRequest: ' . $error, 'SEVERE', true);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
            $this->Error->logError('Stripe', 'Authentication: Payment processor API key error', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
            $this->Error->logError('Stripe', 'ApiConnection: Network communication with payment processor failed', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = 'Payment processor error, try again later.';
            $this->Error->logError('Stripe', 'Base: Unknown Error', 'SEVERE', true);
        } catch (Exception $e) {
            $this->Error->logError('Stripe', $e->getMessage(), 'WARN', true);
            $error = 'There was an error, try again later.';
        }

        if ($error !== null) {
            $return['msg'] = $error;
            return $return;
        }
        $stripe = $token->toArray(true);
        $return['status'] = 'success';
        $return['token'] = $stripe['id'];
        $return['card_id'] = $stripe['card']['id'];
        $return['card_funding'] = !empty($stripe['card']['funding']) ? strtolower($stripe['card']['funding']) : '';
        return $return;
    }

    /**
     * @return array|string
     */
    public function refund(array $data, array $opt = []): array|string
    {
        if (!isset($data['charge'])) {
            return 'The required charge id field is missing.';
        }
        if (isset($data['amount']) && $data['amount'] && !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['amount'])) {
            $this->Error->logError('Stripe', 'Amount must be valid number if provided', 'WARN', true);
            return 'Amount given is not a valid number.';
        }
        if (isset($data['amount']) && $data['amount']) {
            $data['amount'] = sprintf("%0.0f", $data['amount'] * 100);
        }

        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        $chargeData = [];
        foreach ($this->_chargeParams as $param) {
            if (isset($data[$param])) {
                $chargeData[$param] = $data[$param];
            }
        }

        try {
            $charges = \Stripe\Charge::retrieve($chargeData['charge'], $opt);
            unset($chargeData['charge']);
            $charge = $charges->refund($chargeData, $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'SEVERE', true);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'InvalidRequest: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'SEVERE', true);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
            $this->Error->logError('Stripe', 'Authentication: API key rejected', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
            $this->Error->logError('Stripe', 'ApiConnection: Could not connect', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
            $this->Error->logError('Stripe', $error, 'SEVERE', true);
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
            $this->Error->logError('Stripe', $e->getMessage(), 'SEVERE', true);
        }

        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatResult($charge);
    }

    /**
     * @return array|string
     */
    public function capture(array $data, array $opt = []): array|string
    {
        if (!isset($data['auth_token'])) {
            return 'The required auth_token fields are missing.';
        }
        if (isset($data['amount']) && $data['amount'] && !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", $data['amount'])) {
            $this->Error->logError('Stripe', 'Amount must be valid number if provided', 'WARN', true);
            return 'Amount given is not a valid number.';
        }
        if (isset($data['amount'])) {
            $data['amount'] = $data['amount'] > 0.5 ? $data['amount'] : 0.5;
        }
        if (isset($data['amount']) && !empty($data['amount'])) {
            $data['amount'] = sprintf("%0.0f", $data['amount'] * 100);
        }

        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        $chargeData = [];
        foreach ($this->_chargeParams as $param) {
            if (isset($data[$param])) {
                $chargeData[$param] = $data[$param];
            }
        }

        try {
            $charges = \Stripe\Charge::retrieve($data['auth_token'], $opt);
            $charge = $charges->capture($chargeData, $opt);
        } catch (\Stripe\Exception\CardException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'CardError: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'SEVERE', true);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
            $this->Error->logError('Stripe', 'InvalidRequest: ' . $err['type'] . ': ' . ($err['code'] ?? '') . ': ' . $err['message'], 'SEVERE', true);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
            $this->Error->logError('Stripe', 'Authentication: API key rejected', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
            $this->Error->logError('Stripe', 'ApiConnection: Could not connect', 'SEVERE', true);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $body = $e->getJsonBody();
            $error = $body['error']['message'] ?? 'Payment processor error, try again later.';
            $this->Error->logError('Stripe', $error, 'SEVERE', true);
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
            $this->Error->logError('Stripe', $e->getMessage(), 'SEVERE', true);
        }

        if ($error !== null) {
            return (string) $error;
        }
        Log::channel('single')->info('Stripe: charge id ' . $charge->id);
        return $this->_formatResult($charge);
    }

    /**
     * @return array|string
     */
    public function accountRetrieve(string $id): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Account::retrieve($id);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $err = $e->getJsonBody()['error'];
            $error = $err['message'];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns->toArray(true);
    }

    /**
     * @return array|string
     */
    public function accountUpdate(string $id, array $options = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Account::update($id, $options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns->toArray(true);
    }

    /**
     * @return array|string
     */
    public function createLoginLink(string $id): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $customer = \Stripe\Account::createLoginLink($id);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        $customer = $customer->toArray(true);
        return ['status' => 'success', 'url' => $customer['url']];
    }

    /**
     * @return array|string
     */
    public function createPayout(array $params = [], array $options = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Payout::create($params, $options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns->toArray(true);
    }

    /**
     * @return array|string
     */
    public function retrivePayout(array $options, array $opt = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $return = \Stripe\Payout::all($options, $opt);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatTrResult($return);
    }

    /**
     * @return array|string
     */
    public function retriveBalanceTransactionDetails(string $id, array $opt = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $charge = \Stripe\BalanceTransaction::retrieve($id, $opt);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $charge->toArray(true);
    }

    /**
     * @return array|string
     */
    public function retriveBalanceTransaction(array $filters, array $opt = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\BalanceTransaction::all($filters, $opt);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $this->_formatTrResult($trns);
    }

    public function retriveExternalBankAccount(string $acc, array $options = [])
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Account::retrieve($acc)->external_accounts->all($options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns;
    }

    /**
     * @return array|string
     */
    public function transferToDealer(array $options): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Transfer::create($options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns->toArray(true);
    }

    /**
     * @return array|string
     */
    public function retrieveTransfer(string $transferid): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Transfer::retrieve($transferid);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return ['status' => 'success', 'result' => $trns->toArray(true)];
    }

    /**
     * @return array|string
     */
    public function reverseTransfer(string $transferid, array $options = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $tr = \Stripe\Transfer::retrieve($transferid);
            $trns = empty($options) ? $tr->reversals->create() : $tr->reversals->create($options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return ['status' => 'success', 'result' => $trns->toArray(true)];
    }

    /**
     * @return array|string
     */
    public function retrieveBalance(array $options = []): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Balance::retrieve($options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return ['status' => 'success', 'result' => $trns->toArray(true)];
    }

    /**
     * @return array|string
     */
    public function topupBalance(float $amount): array|string
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        if (!$amount || !preg_match("/^[0-9]*(?:\.[0-9]{1,2})?$/", (string) $amount)) {
            $this->Error->logError('Stripe', 'Amount is required and must be valid number', 'WARN', true);
            return 'Amount is required and must be numeric.';
        }
        try {
            $trns = \Stripe\Topup::create([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'description' => 'Top-up for my Stripe Account',
                'statement_descriptor' => 'Stripe top-up',
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return ['status' => 'success', 'result' => $trns->toArray(true)];
    }

    public function updateConnectedAccount(string $acc, array $options = ['tos_acceptance' => ['service_agreement' => 'recipient']])
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\Account::update($acc, $options);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns;
    }

    public function ExchangeRate()
    {
        \Stripe\Stripe::setApiKey($this->key);
        $error = null;
        try {
            $trns = \Stripe\ExchangeRate::all();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Invalid request.';
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $error = 'Payment processor API key error.';
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $error = 'Network communication with payment processor failed, try again later';
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getJsonBody()['error']['message'] ?? 'Payment processor error, try again later.';
        } catch (Exception $e) {
            $error = 'There was an error, try again later.';
        }
        if ($error !== null) {
            return (string) $error;
        }
        return $trns;
    }

    // ── Internal helpers ──

    protected function _formatResult($response): array
    {
        $result = [];
        foreach ($this->fields as $local => $stripe) {
            if (is_array($stripe)) {
                foreach ($stripe as $obj => $field) {
                    if (isset($response->$obj->$field)) {
                        $result[$local] = $response->$obj->$field;
                    }
                }
            } else {
                if (isset($response->$stripe)) {
                    $result[$local] = $response->$stripe;
                }
            }
        }
        if (empty($result)) {
            $result['stripe_id'] = $response->id;
        }
        $result['status'] = 'success';
        return $result;
    }

    protected function _formatTrResult($responses): array
    {
        $result = ['status' => 'success'];
        $responses = $responses->toArray(true);
        $result['data'] = $responses['data'];
        $result['has_more'] = $responses['has_more'] ?? false;
        return $result;
    }
}
