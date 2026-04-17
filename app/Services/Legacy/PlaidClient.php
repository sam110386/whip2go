<?php

namespace App\Services\Legacy;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Ported from CakePHP app/Controller/Component/PlaidComponent.php
 *
 * Plaid API integration using direct HTTP calls via the Http facade.
 * Replaces the legacy vendor Plaid\Client with REST-based requests.
 */
class PlaidClient
{
    private string $url;
    private string $clientId;
    private string $secret;
    private string $key;
    private string $env;
    private string $identifier;

    public function __construct()
    {
        $this->url        = config('services.plaid.url', '');
        $this->clientId   = config('services.plaid.client_id', '');
        $this->secret     = config('services.plaid.secret', '');
        $this->key        = config('services.plaid.key', '');
        $this->env        = config('services.plaid.env', 'sandbox');
        $this->identifier = config('services.plaid.identifier', 'driveitaway_');
    }

    // ── Auth / Token Exchange ──

    public function generateAuthToken(string $publicToken): array
    {
        try {
            $result = $this->post('/item/public_token/exchange', [
                'public_token' => $publicToken,
            ]);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        if (isset($result['access_token'])) {
            return ['status' => true, 'message' => '', 'access_token' => $result['access_token'], 'item_id' => $result['item_id'] ?? ''];
        }

        return ['status' => false, 'message' => $result, 'access_token' => ''];
    }

    public function create_link_token(array $userObj, string $user_token, string $deviceOS = ''): array
    {
        $siteUrl = config('app.url', '') . '/';
        $webhook = $siteUrl . 'plaid/webhook';

        $tempObj = [
            'user_token'          => $user_token,
            'client_id'           => $this->clientId,
            'secret'              => $this->secret,
            'income_verification' => [
                'income_source_types' => ['bank'],
                'bank_income'         => ['days_requested' => 120],
            ],
            'user' => [
                'client_user_id' => $this->identifier . ($userObj['User']['id'] ?? ''),
                'legal_name'     => ($userObj['User']['first_name'] ?? '') . ' ' . ($userObj['User']['last_name'] ?? ''),
                'phone_number'   => $this->formatPhone($userObj['User']['contact_number'] ?? ''),
                'email_address'  => $userObj['User']['email'] ?? '',
            ],
            'enable_multi_item_link' => true,
            'client_name'   => 'DriveItAway Inc.',
            'products'      => ['income_verification'],
            'country_codes' => ['US', 'CA'],
            'language'      => 'en',
            'account_filters' => [
                'depository' => ['account_subtypes' => ['checking', 'savings']],
                'credit'     => ['account_subtypes' => ['credit card']],
            ],
        ];

        if ($deviceOS === 'ios') {
            $tempObj['redirect_uri'] = 'https://w2272m466y.com.mindseye.carshare/';
        } elseif ($deviceOS === 'android') {
            $tempObj['android_package_name'] = 'com.carshare';
        } else {
            $tempObj['redirect_uri'] = $siteUrl . 'plaid/callback';
            $tempObj['webhook'] = $webhook;
        }

        return $this->createTokenLink($tempObj, $user_token);
    }

    public function incomeLinkToken(array $userObj, string $user_token, string $deviceOS = ''): array
    {
        $siteUrl = config('app.url', '') . '/';
        $webhook = $siteUrl . 'plaid/webhook';

        $tempObj = [
            'user_token'          => $user_token,
            'income_verification' => [
                'income_source_types' => ['payroll'],
                'payroll_income'      => ['flow_types' => ['document']],
            ],
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'user' => [
                'client_user_id' => $this->identifier . ($userObj['User']['id'] ?? ''),
                'legal_name'     => ($userObj['User']['first_name'] ?? '') . ' ' . ($userObj['User']['last_name'] ?? ''),
                'phone_number'   => $this->formatPhone($userObj['User']['contact_number'] ?? '', false),
                'email_address'  => $userObj['User']['email'] ?? '',
            ],
            'client_name'   => 'DriveItAway Inc.',
            'products'      => ['income_verification'],
            'country_codes' => ['US', 'CA'],
            'language'      => 'en',
        ];

        if ($deviceOS === 'ios') {
            $tempObj['redirect_uri'] = 'https://w2272m466y.com.mindseye.carshare/';
        } elseif ($deviceOS === 'android') {
            $tempObj['android_package_name'] = 'com.carshare';
        } else {
            $tempObj['redirect_uri'] = $siteUrl . 'plaid/callback';
            $tempObj['webhook'] = $webhook;
        }

        return $this->createTokenLink($tempObj, $user_token);
    }

    // ── Accounts & Balances ──

    public function getAccounts(string $accessToken): array
    {
        try {
            $accounts = $this->post('/accounts/get', [
                'access_token' => $accessToken,
            ]);
        } catch (Exception $e) {
            $accounts = $e->getMessage();
        }

        if (isset($accounts['accounts'])) {
            return ['status' => true, 'message' => '', 'accounts' => $accounts];
        }

        return ['status' => false, 'message' => $accounts, 'accounts' => ''];
    }

    public function getBalance(string $accessToken, array $otp = [], array $accountIds = []): array
    {
        try {
            $body = ['access_token' => $accessToken];
            if (!empty($accountIds)) {
                $body['options'] = ['account_ids' => $accountIds];
            }
            $balance = $this->post('/accounts/balance/get', $body);
        } catch (Exception $e) {
            $balance = $e->getMessage();
        }

        if (isset($balance['accounts'])) {
            return ['status' => true, 'message' => '', 'accounts' => $balance['accounts']];
        }

        return ['status' => false, 'message' => $balance, 'accounts' => ''];
    }

    // ── Transactions ──

    public function getTransactionHistory(
        string $accessToken,
        string $startDate,
        string $endDate,
        array $options = [],
        ?array $accountIds = null,
        ?int $count = null,
        ?int $offset = null
    ): array {
        try {
            $body = [
                'access_token' => $accessToken,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
            ];
            if (!empty($options)) {
                $body['options'] = $options;
            }
            if ($accountIds !== null) {
                $body['options']['account_ids'] = $accountIds;
            }
            if ($count !== null) {
                $body['options']['count'] = $count;
            }
            if ($offset !== null) {
                $body['options']['offset'] = $offset;
            }

            $transactions = $this->post('/transactions/get', $body);
        } catch (Exception $e) {
            $transactions = $e->getMessage();
        }

        if (isset($transactions['transactions'])) {
            return ['status' => true, 'message' => '', 'transactions' => $transactions['transactions']];
        }

        return ['status' => false, 'message' => $transactions, 'transactions' => ''];
    }

    // ── Income ──

    public function getIncomeHistory(string $user_token, int $accountCount = 1): array
    {
        try {
            $income = $this->post('/credit/bank_income/get', [
                'user_token' => $user_token,
                'options'    => ['count' => $accountCount],
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['bank_income'])) {
            return ['status' => true, 'message' => '', 'income' => $income];
        }

        return ['status' => false, 'message' => $income, 'transactions' => ''];
    }

    public function getIncomeTransactions(string $access_token, string $account_id): array
    {
        try {
            $income = $this->post('/transactions/get', [
                'access_token' => $access_token,
                'start_date'   => date('Y-m-d', strtotime('-200 days')),
                'end_date'     => date('Y-m-d'),
                'options'      => ['account_ids' => [$account_id]],
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['accounts'])) {
            return ['status' => true, 'message' => '', 'income' => $income];
        }

        return ['status' => false, 'message' => $income, 'transactions' => ''];
    }

    public function getStatements(string $access_token): array
    {
        try {
            $income = $this->post('/statements/list', [
                'access_token' => $access_token,
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['item_id'])) {
            return ['status' => true, 'message' => '', 'income' => $income];
        }

        return ['status' => false, 'message' => $income, 'transactions' => ''];
    }

    public function createIncomeVerification(string $webhook): array
    {
        try {
            $income = $this->post('/income/verification/create', [
                'webhook' => $webhook,
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['income_verification_id'])) {
            return ['status' => true, 'message' => '', 'income_verification_id' => $income['income_verification_id']];
        }

        return ['status' => false, 'message' => $income, 'income_verification_id' => ''];
    }

    public function createTokenLink(array $data, string $user_token = ''): array
    {
        try {
            $income = $this->post('/link/token/create', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['link_token'])) {
            return ['status' => true, 'message' => '', 'link_token' => $income['link_token'], 'user_token' => $user_token];
        }

        return ['status' => false, 'message' => $income, 'link_token' => '', 'user_token' => $user_token];
    }

    public function getIncomeSummery(array $data): array
    {
        try {
            $income = $this->post('/income/verification/summary/get', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['ytd_earnings'])) {
            return ['status' => true, 'message' => '', 'earnings' => $income['ytd_earnings']];
        }

        return ['status' => false, 'message' => $income, 'earnings' => 0];
    }

    public function getPaystubSummery(array $data)
    {
        try {
            $income = $this->post('/income/verification/paystub/get', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        return $income;
    }

    public function downloadPaystub(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->url . '/income/verification/documents/download', array_merge([
                'client_id' => $this->clientId,
                'secret'    => $this->secret,
            ], $data));

            return response()->streamDownload(function () use ($response) {
                echo $response->body();
            }, 'paystub.zip', [
                'Content-Type'        => 'application/octet-stream',
                'Content-Description' => 'File Transfer',
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // ── Payroll ──

    public function CreatePayrollUser($user): array
    {
        try {
            $usertoken = $this->post('/user/create', [
                'client_user_id' => $this->identifier . $user,
            ]);
        } catch (Exception $e) {
            $usertoken = $e->getMessage();
        }

        if (!isset($usertoken['user_token'])) {
            return ['status' => false, 'message' => $usertoken, 'transactions' => ''];
        }

        return ['status' => true, 'message' => '', 'user_token' => $usertoken['user_token']];
    }

    public function payrollIncome(string $user_token): array
    {
        try {
            $income = $this->post('/credit/payroll_income/get', [
                'user_token' => $user_token,
            ]);
            return ['status' => true, 'message' => '', 'transactions' => $income];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'transactions' => []];
        }
    }

    // ── Users ──

    public function createUser(array $userObj): array
    {
        try {
            $requestBody = [
                'client_user_id'                   => $this->identifier . ($userObj['id'] ?? ''),
                'consumer_report_user_identity' => [
                    'first_name'    => $userObj['first_name'] ?? '',
                    'last_name'     => $userObj['last_name'] ?? '',
                    'date_of_birth' => date('Y-m-d', strtotime($userObj['dob'] ?? 'now')),
                    'emails'        => [$userObj['email'] ?? ''],
                    'phone_numbers' => [$this->formatPhone($userObj['contact_number'] ?? '')],
                    'primary_address' => [
                        'street'      => $userObj['address'] ?? '',
                        'city'        => $userObj['city'] ?? '',
                        'region'      => $userObj['state'] ?? '',
                        'country'     => 'US',
                        'postal_code' => $userObj['zip'] ?? '',
                    ],
                ],
            ];

            if (empty($userObj['address']) || empty($userObj['city'])) {
                unset($requestBody['consumer_report_user_identity']['primary_address']);
            }

            $user = $this->post('/user/create', $requestBody);
        } catch (Exception $e) {
            $user = $e->getMessage();
        }

        if (isset($user['user_id'])) {
            return ['status' => true, 'message' => '', 'user_token' => $user['user_token'], 'user_id' => $user['user_id']];
        }

        return ['status' => false, 'message' => $user, 'user_token' => '', 'user_id' => ''];
    }

    public function removeUser(string $access_token): array
    {
        try {
            $resp = $this->post('/item/remove', [
                'access_token' => $access_token,
            ]);
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        if (isset($resp['request_id'])) {
            return ['status' => true, 'message' => 'removed successfully'];
        }

        return ['status' => false, 'message' => $resp];
    }

    public function accountIdentity(string $accessToken, array $account_ids): array
    {
        try {
            $identities = $this->post('/identity/get', [
                'access_token' => $accessToken,
                'options'      => ['account_ids' => $account_ids],
            ]);
            return ['status' => true, 'message' => '', 'identities' => $identities];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'identities' => []];
        }
    }

    // ── Metadata Helpers ──

    public function checkIfPlaidInstitutionIdExits($oldmetas, $newmetas): bool
    {
        if (empty($oldmetas) || empty($newmetas)) {
            return false;
        }

        $oldmetas = is_string($oldmetas) ? json_decode($oldmetas, true) : $oldmetas;
        $oldMetadataJson = isset($oldmetas['metadataJson']) ? json_decode($oldmetas['metadataJson'], true) : $oldmetas;
        $newMetadataJson = isset($newmetas['metadataJson']) ? json_decode($newmetas['metadataJson'], true) : $newmetas;

        if (empty($oldMetadataJson) || empty($newMetadataJson)) {
            return false;
        }

        $accountNew = $newMetadataJson['accounts'] ?? [$newMetadataJson['account'] ?? []];
        $accountOld = $oldMetadataJson['accounts'] ?? [$oldMetadataJson['account'] ?? []];

        foreach ($accountOld as $accOld) {
            foreach ($accountNew as $accNew) {
                $sameInstitution = ($newMetadataJson['institution']['institution_id'] ?? '') === ($oldMetadataJson['institution']['institution_id'] ?? '');
                $sameName = trim($accOld['name'] ?? '') === trim($accNew['name'] ?? '');
                $sameMask = ($accOld['mask'] ?? '') === ($accNew['mask'] ?? '');
                if ($sameInstitution && $sameName && $sameMask) {
                    return true;
                }
            }
        }

        return false;
    }

    // ── Save / Persist ──

    private function savePaystub(array $data, $user, ?object $newRecord = null): array
    {
        $token = $data['token'];
        $metadata = $data['metadata'];
        $user_token = $data['user_token'] ?? '';

        $oldRecords = DB::table('plaid_users')
            ->where('user_id', $user)
            ->whereNotNull('token')
            ->get();

        foreach ($oldRecords as $oldRecord) {
            $isDuplicate = $this->checkIfPlaidInstitutionIdExits($oldRecord->metadata, $metadata);
            if ($isDuplicate) {
                $deleted = $this->removeUser($oldRecord->token);
                if ($deleted['status']) {
                    DB::table('plaid_users')->where('id', $oldRecord->id)->delete();
                }
            }
        }

        $exists = $oldRecords->last();

        if (empty($exists)) {
            $authtoken = $this->generateAuthToken($token);
            $access_token = $authtoken['access_token'];
        } else {
            $access_token = $exists->token;
            $authtoken = [];
        }

        $dataToSave = [
            'user_id'    => $user,
            'item_id'    => $authtoken['item_id'] ?? '',
            'token'      => $access_token,
            'paystub'    => 1,
            'user_token' => $user_token,
            'metadata'   => json_encode($metadata),
        ];

        $existingId = null;
        if (!empty($exists) && $exists->paystub) {
            $existingId = $exists->id;
        } elseif ($newRecord) {
            $existingId = $newRecord->id;
        }

        if ($existingId) {
            DB::table('plaid_users')->where('id', $existingId)->update($dataToSave);
            $plaidId = $existingId;
        } else {
            $plaidId = DB::table('plaid_users')->insertGetId($dataToSave);
        }

        DB::table('users')->where('id', $user)->update(['bank' => $plaidId]);
        DB::table('vehicle_reservations')
            ->where('user_id', $user)
            ->where('status', 'pending')
            ->update(['bank_status' => 2]);

        return ['status' => true, 'message' => 'You are succcessfully connected now'];
    }

    public function saveUser(array $data, $user): array
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong, please try again'];

        $token = $data['token'];
        $metadata = $data['metadata'];
        $paystub = !empty($data['paystub']);
        $user_token = $data['user_token'] ?? '';

        $userObj = DB::table('users')->where('id', $user)->first(['id']);
        if (empty($userObj)) {
            return $return;
        }

        $newRecord = DB::table('plaid_users')
            ->where('user_id', $user)
            ->whereNull('token')
            ->first();

        if ($paystub) {
            return $this->savePaystub($data, $user, $newRecord);
        }

        $oldRecords = DB::table('plaid_users')
            ->where('user_id', $user)
            ->whereNotNull('token')
            ->get();

        foreach ($oldRecords as $oldRecord) {
            $isDuplicate = $this->checkIfPlaidInstitutionIdExits($oldRecord->metadata, $metadata);
            if ($isDuplicate) {
                $deleted = $this->removeUser($oldRecord->token);
                if ($deleted['status']) {
                    DB::table('plaid_users')->where('id', $oldRecord->id)->delete();
                }
            }
        }

        if ($this->env === 'sandbox') {
            $sandboxResp = $this->createSandboxPublicToken('ins_5', ['income_verification']);
            $token = $sandboxResp['public_token'] ?? $token;
        }

        $authtoken = $this->generateAuthToken($token);
        if (!$authtoken['status']) {
            return $authtoken;
        }
        $access_token = $authtoken['access_token'];

        $dataToSave = [
            'item_id'    => $authtoken['item_id'] ?? '',
            'user_id'    => $user,
            'token'      => $access_token,
            'user_token' => $user_token,
            'metadata'   => json_encode($metadata),
        ];

        if ($newRecord) {
            DB::table('plaid_users')->where('id', $newRecord->id)->update($dataToSave);
            $plaidId = $newRecord->id;
        } else {
            $plaidId = DB::table('plaid_users')->insertGetId($dataToSave);
        }

        DB::table('users')->where('id', $user)->update(['bank' => $plaidId]);
        DB::table('vehicle_reservations')
            ->where('user_id', $user)
            ->where('status', 'pending')
            ->update(['bank_status' => 2]);

        return ['status' => true, 'message' => 'You are succcessfully connected now'];
    }

    // ── Item ──

    public function getItem(string $access_token)
    {
        try {
            return $this->post('/item/get', [
                'access_token' => $access_token,
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // ── CRA Reports ──

    public function getCraBaseReport(string $user_id)
    {
        try {
            return $this->post('/cra/check_report/base_report/get', [
                'client_id' => $this->clientId,
                'secret'    => $this->secret,
                'user_id'   => $user_id,
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getCraIncomeInsights(string $user_id)
    {
        try {
            return $this->post('/cra/check_report/income_insights/get', [
                'client_id' => $this->clientId,
                'secret'    => $this->secret,
                'user_id'   => $user_id,
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getCraCheckReportPdf(string $user_id)
    {
        try {
            return $this->post('/cra/check_report/pdf/get', [
                'client_id' => $this->clientId,
                'secret'    => $this->secret,
                'user_id'   => $user_id,
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getCraCashflowInsights(string $user_id)
    {
        try {
            return $this->post('/cra/check_report/cashflow_insights/get', [
                'client_id' => $this->clientId,
                'secret'    => $this->secret,
                'user_id'   => $user_id,
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // ── Sandbox ──

    private function createSandboxPublicToken(string $institution_id, array $initial_products): array
    {
        try {
            $publicToken = $this->post('/sandbox/public_token/create', [
                'institution_id'   => $institution_id,
                'initial_products' => $initial_products,
            ]);
        } catch (Exception $e) {
            $publicToken = $e->getMessage();
        }

        if (isset($publicToken['public_token'])) {
            return ['status' => true, 'message' => '', 'public_token' => $publicToken['public_token']];
        }

        return ['status' => false, 'message' => $publicToken, 'public_token' => ''];
    }

    // ── HTTP Transport ──

    /**
     * Send authenticated POST to the Plaid API. Automatically injects client_id
     * and secret when not already present in the payload.
     */
    private function post(string $endpoint, array $body = []): array
    {
        if (!isset($body['client_id'])) {
            $body['client_id'] = $this->clientId;
        }
        if (!isset($body['secret'])) {
            $body['secret'] = $this->secret;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->url . $endpoint, $body);

        return $response->json() ?? [];
    }

    // ── Helpers ──

    /**
     * Format a contact number into E.164 with US +1 prefix.
     */
    private function formatPhone(string $number, bool $includeCountryCode = true): string
    {
        $digits = preg_replace('/[^0-9]/', '', $number);
        if (empty($digits)) {
            return '';
        }
        $last10 = substr($digits, -10);
        return $includeCountryCode ? '+1' . $last10 : '+' . $last10;
    }
}
