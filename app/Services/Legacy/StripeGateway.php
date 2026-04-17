<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class StripeGateway
{
    private string $secret;

    public function __construct()
    {
        $this->secret = (string) Config::get('services.stripe.secret', '');
    }

    public function createLoginLink(string $accountId): array
    {
        if ($accountId === '' || $this->secret === '') {
            return ['status' => 'error', 'message' => 'Missing Stripe credentials or account id'];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/accounts/{$accountId}/login_links");

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'url' => $response->json('url')];
    }

    public function createCardToken(array $data, array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'msg' => 'Payment processor API key error.'];
        }

        $payload = array_merge($data, $opt);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/tokens', $this->toFormFields($payload));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        $json = $response->json();
        return [
            'status' => 'success',
            'token' => $json['id'] ?? '',
            'card_id' => $json['card']['id'] ?? '',
            'card_funding' => strtolower((string) ($json['card']['funding'] ?? '')),
        ];
    }

    public function customerCreate(array $data): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'msg' => 'Payment processor API key error.'];
        }

        if (isset($data['stripeToken'])) {
            $data['source'] = $data['stripeToken'];
            unset($data['stripeToken']);
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/customers', $this->toFormFields($data));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function addCardToCustomer(string $customerId, string $cardToken, array $opt = []): array
    {
        if ($customerId === '' || $cardToken === '' || $this->secret === '') {
            return ['status' => 'error', 'msg' => 'Required inputs are missing'];
        }

        $payload = array_merge(['source' => $cardToken], $opt);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/customers/{$customerId}/sources", $this->toFormFields($payload));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function makeCardDefault(string $customerId, array $opt): array
    {
        if ($customerId === '' || $this->secret === '') {
            return ['status' => 'error', 'msg' => 'Required inputs are missing'];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/customers/{$customerId}", $this->toFormFields($opt));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function deleteCustomerCard(string $customerId, ?string $cardId = null): array
    {
        if ($customerId === '' || $this->secret === '') {
            return ['status' => 'error', 'msg' => 'Required inputs are missing'];
        }

        if (empty($cardId)) {
            return $this->customerDelete($customerId);
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->delete("https://api.stripe.com/v1/customers/{$customerId}/sources/{$cardId}");

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function customerRetrieve(string $customerId): array
    {
        if ($customerId === '' || $this->secret === '') {
            return ['status' => false, 'message' => 'Required inputs are missing'];
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->get("https://api.stripe.com/v1/customers/{$customerId}");

        if ($response->failed()) {
            return ['status' => false, 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => true, 'result' => $response->json()];
    }

    public function customerDelete(string $customerId): array
    {
        if ($customerId === '' || $this->secret === '') {
            return ['status' => 'error', 'msg' => 'Required inputs are missing'];
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->delete("https://api.stripe.com/v1/customers/{$customerId}");

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function createSource(string $customerId, array $params): array
    {
        if ($customerId === '' || $this->secret === '') {
            return ['status' => 'error', 'msg' => 'Required inputs are missing'];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/customers/{$customerId}/sources", $this->toFormFields($params));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'card_id' => $response->json('id')];
    }

    public function charge(array $data, array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'msg' => 'Payment processor API key error.'];
        }

        $payload = array_merge($data, $opt);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/charges', $this->toFormFields($payload));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function refund(array $data, array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'msg' => 'Payment processor API key error.'];
        }

        $payload = array_merge($data, $opt);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/refunds', $this->toFormFields($payload));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function capture(array $data, array $opt = []): array
    {
        if ($this->secret === '' || empty($data['auth_token'])) {
            return ['status' => 'error', 'msg' => 'Required auth_token fields are missing.'];
        }

        $authToken = (string) $data['auth_token'];
        unset($data['auth_token']);
        $payload = array_merge($data, $opt);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/charges/{$authToken}/capture", $this->toFormFields($payload));

        if ($response->failed()) {
            return ['status' => 'error', 'msg' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'stripe_id' => $response->json('id')];
    }

    public function transferToDealer(array $options): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'message' => 'Payment processor API key error.'];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/transfers', $this->toFormFields($options));

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'result' => $response->json()];
    }

    public function retrieveTransfer(string $transferId): array
    {
        if ($this->secret === '' || $transferId === '') {
            return ['status' => 'error', 'message' => 'Required inputs are missing'];
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->get("https://api.stripe.com/v1/transfers/{$transferId}");

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'result' => $response->json()];
    }

    public function retriveBalanceTransaction(array $filters, array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'message' => 'Payment processor API key error.'];
        }

        $query = array_merge($filters, $opt);
        $response = Http::withBasicAuth($this->secret, '')
            ->get('https://api.stripe.com/v1/balance_transactions', $query);

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'result' => $response->json()];
    }

    public function retriveBalanceTransactionDetails(string $id, array $opt = []): array
    {
        if ($this->secret === '' || $id === '') {
            return ['status' => 'error', 'message' => 'Required inputs are missing'];
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->get("https://api.stripe.com/v1/balance_transactions/{$id}", $opt);

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'result' => $response->json()];
    }

    public function createPaymentIntent(array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => false, 'message' => 'Payment processor API key error.'];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post('https://api.stripe.com/v1/payment_intents', $this->toFormFields($opt));

        if ($response->failed()) {
            return ['status' => false, 'message' => $response->json('error.message') ?? 'Stripe request failed', 'result' => []];
        }

        return ['status' => true, 'message' => 'Success', 'result' => $response->json()];
    }

    public function chargePaymentIntent(string $intentId, array $opt = []): array
    {
        if ($this->secret === '' || $intentId === '') {
            return ['status' => false, 'message' => 'Required inputs are missing', 'result' => []];
        }

        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/payment_intents/{$intentId}/confirm", $this->toFormFields($opt));

        if ($response->failed()) {
            return ['status' => false, 'message' => $response->json('error.message') ?? 'Stripe request failed', 'result' => []];
        }

        return ['status' => true, 'message' => 'Success', 'result' => $response->json()];
    }

    public function retrieveBalance(array $opt = []): array
    {
        if ($this->secret === '') {
            return ['status' => 'error', 'message' => 'Payment processor API key error.'];
        }

        $pending = Http::withBasicAuth($this->secret, '');
        if (!empty($opt['stripe_account'])) {
            $pending = $pending->withHeaders(['Stripe-Account' => (string) $opt['stripe_account']]);
        }

        $response = $pending->get('https://api.stripe.com/v1/balance');
        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => 'success', 'result' => $response->json()];
    }

    public function accountRetrieve(string $accountId): array
    {
        if ($accountId === '' || $this->secret === '') {
            return ['status' => 'error', 'message' => 'Missing Stripe credentials or account id'];
        }

        $response = Http::withBasicAuth($this->secret, '')
            ->get("https://api.stripe.com/v1/accounts/{$accountId}");

        if ($response->failed()) {
            return ['status' => 'error', 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return $response->json();
    }

    public function accountUpdate(string $accountId, array $options): array
    {
        if ($accountId === '' || $this->secret === '') {
            return ['status' => false, 'message' => 'Missing Stripe credentials or account id'];
        }

        $payload = $this->toFormFields($options);
        $response = Http::asForm()
            ->withBasicAuth($this->secret, '')
            ->post("https://api.stripe.com/v1/accounts/{$accountId}", $payload);

        if ($response->failed()) {
            return ['status' => false, 'message' => $response->json('error.message') ?? 'Stripe request failed'];
        }

        return ['status' => true, 'message' => 'Payout updated successfully', 'result' => $response->json()];
    }

    private function toFormFields(array $input, string $prefix = ''): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $result += $this->toFormFields($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }
        return $result;
    }
}
