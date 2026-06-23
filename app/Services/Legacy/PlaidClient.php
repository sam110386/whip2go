<?php

namespace App\Services\Legacy;

use Plaid\Client;
use Exception;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\User;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Support\Facades\Log;

/**
 * Ported from CakePHP app/Controller/Component/PlaidComponent.php
 */
class PlaidClient
{
    private $url = '';
    private $client_id = '';
    private $secret = '';
    private $key = '';
    private $env = '';
    private $identifier = 'driveitaway_';


    public function __construct()
    {
        $this->url = config('legacy.plaid.url');
        $this->client_id = config('legacy.plaid.client_id');
        $this->secret = config('legacy.plaid.secret');
        $this->key = config('legacy.plaid.key');
        $this->env = config('legacy.plaid.env');
        $this->identifier = config('legacy.plaid.identifier', 'driveitaway_');
    }
    public function generateAuthToken($publicToken)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $actualReport = $client->item()->publicToken()->exchange($publicToken);
        } catch (Exception $e) {
            $actualReport = $e->getMessage();
        }

        if (isset($actualReport['access_token'])) {
            return [
                "status" => true,
                "message" => "",
                "access_token" => $actualReport['access_token'],
            ];
        }

        return [
            "status" => false,
            "message" => $actualReport,
            "access_token" => ""
        ];
    }
    public function create_link_token($userObj, $user_token, $deviceOS = '')
    {
        $webhook = url('plaid/webhook');

        if (is_object($userObj) && method_exists($userObj, 'toArray')) {
            $userObj = $userObj->toArray();
        }

        $userData = isset($userObj['User']) ? $userObj['User'] : $userObj;

        $tempObj = [
            "user_token" => $user_token,
            "client_id" => $this->client_id,
            "secret" => $this->secret,
            "income_verification" => [
                'income_source_types' => ["bank"],
                "bank_income" => [
                    "days_requested" => 120
                ]
            ],
            "user" => [
                "client_user_id" => $this->identifier . ($userData['id'] ?? ''),
                "legal_name" => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
                "phone_number" => (preg_replace('/[^0-9]/', '', $userData['contact_number'] ?? '') ? '+1' . substr(preg_replace('/[^0-9]/', '', $userData['contact_number']), -10) : ''),
                "email_address" => $userData['email'] ?? ''
            ],
            "enable_multi_item_link" => true,
            "client_name" => 'DriveItAway Inc.',
            "products" => ['income_verification'],
            "country_codes" => ['US', 'CA'],
            "language" => 'en',
            "account_filters" => [
                "depository" => [
                    "account_subtypes" => ["checking", "savings"]
                ],
                "credit" => [
                    "account_subtypes" => ["credit card"]
                ]
            ]

        ];

        if ($deviceOS === 'ios') {
            $tempObj['redirect_uri'] = 'https://w2272m466y.com.mindseye.carshare/';
        } elseif ($deviceOS == 'android') {
            $tempObj['android_package_name'] = 'com.carshare';
        } else {
            $tempObj['redirect_uri'] = url('plaid/callback');
            $tempObj["webhook"] = $webhook;
        }

        return $this->createTokenLink($tempObj, $user_token);
    }
    public function incomeLinkToken($userObj, $user_token, $deviceOS = '')
    {
        $webhook = url('plaid/webhook');

        if (is_object($userObj) && method_exists($userObj, 'toArray')) {
            $userObj = $userObj->toArray();
        }

        $userData = isset($userObj['User']) ? $userObj['User'] : $userObj;

        $tempObj = [
            "user_token" => $user_token,
            "income_verification" => [
                'income_source_types' => ["payroll"],
                "payroll_income" => [
                    "flow_types" => ["document"]
                ]
            ],
            "client_id" => $this->client_id,
            "secret" => $this->secret,
            "user" => [
                "client_user_id" => $this->identifier . ($userData['id'] ?? ''),
                "legal_name" => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
                "phone_number" => (preg_replace('/[^0-9]/', '', $userData['contact_number'] ?? '') ? '+' . substr(preg_replace('/[^0-9]/', '', $userData['contact_number']), -10) : ''),
                "email_address" => $userData['email'] ?? ''
            ],
            "client_name" => 'DriveItAway Inc.',
            "products" => ['income_verification'],
            "country_codes" => ['US', 'CA'],
            "language" => 'en'
        ];

        if ($deviceOS === 'ios') {
            $tempObj['redirect_uri'] = 'https://w2272m466y.com.mindseye.carshare/';
        } elseif ($deviceOS == 'android') {
            $tempObj['android_package_name'] = 'com.carshare';
        } else {
            $tempObj['redirect_uri'] = url('plaid/callback');
            $tempObj["webhook"] = $webhook;
        }

        return $this->createTokenLink($tempObj, $user_token);
    }
    public function getAccounts($accessToken)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $accounts = $client->accounts()->get($accessToken);
        } catch (Exception $e) {
            $accounts = $e->getMessage();
        }

        if (isset($accounts['accounts'])) {
            return [
                "status" => true,
                "message" => "",
                "accounts" => $accounts
            ];
        }

        return [
            "status" => false,
            "message" => $accounts,
            "accounts" => ""
        ];
    }
    public function getBalance($accessToken, $otp = [], $accountIds = [])
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $balance = $client->balance()->get($accessToken, $otp, $accountIds);
        } catch (Exception $e) {
            $balance = $e->getMessage();
        }

        if (isset($balance['accounts'])) {
            return [
                "status" => true,
                "message" => "",
                "accounts" => $balance['accounts']
            ];
        }

        return [
            "status" => false,
            "message" => $balance,
            "accounts" => ""
        ];
    }
    public function getTransactionHistory($accessToken, $startDate, $endDate, $options = [], $accountIds = null, $count = null, $offset = null)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $transactions = $client->transactions()->get($accessToken, $startDate, $endDate, $options, $accountIds, $count, $offset);
        } catch (Exception $e) {
            $transactions = $e->getMessage();
        }

        if (isset($transactions['transactions'])) {
            return [
                "status" => true,
                "message" => "",
                "transactions" => $transactions['transactions']
            ];
        }

        return [
            "status" => false,
            "message" => $transactions,
            "transactions" => ""
        ];
    }
    public function getIncomeHistory($user_token, $accountCount = 1)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/credit/bank_income/get', [
                'user_token' => $user_token,
                "options" => [
                    "count" => $accountCount
                ]
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['bank_income'])) {
            return [
                "status" => true,
                "message" => "",
                "income" => $income
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "transactions" => ""
        ];
    }
    public function getIncomeTransactions($access_token, $account_id)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/transactions/get', [
                'access_token' => $access_token,
                "start_date" => date('Y-m-d', strtotime('-200 days')),
                "end_date" => date('Y-m-d'),
                "options" => [
                    "account_ids" => [$account_id]
                ]
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['accounts'])) {
            return [
                "status" => true,
                "message" => "",
                "income" => $income
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "transactions" => ""
        ];
    }
    public function getStatements($access_token)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/statements/list', [
                'access_token' => $access_token
            ]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['item_id'])) {
            return [
                "status" => true,
                "message" => "",
                "income" => $income
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "transactions" => ""
        ];
    }
    public function createIncomeVerification($webhook)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/income/verification/create', ['webhook' => $webhook]);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['income_verification_id'])) {
            return [
                "status" => true,
                "message" => "",
                "income_verification_id" => $income['income_verification_id']
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "income_verification_id" => ""
        ];
    }
    public function createTokenLink($data, $user_token = '')
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/link/token/create', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['link_token'])) {
            return [
                "status" => true,
                "message" => "",
                "link_token" => $income['link_token'],
                "user_token" => $user_token
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "link_token" => "",
            "user_token" => $user_token
        ];
    }
    public function getIncomeSummery($data)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/income/verification/summary/get', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        if (isset($income['ytd_earnings'])) {
            return [
                "status" => true,
                "message" => "",
                "earnings" => $income['ytd_earnings']
            ];
        }

        return [
            "status" => false,
            "message" => $income,
            "earnings" => 0
        ];
    }
    public function getPaystubSummery($data)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/income/verification/paystub/get', $data);
        } catch (Exception $e) {
            $income = $e->getMessage();
        }

        return $income;
    }
    public function downloadPaystub($data)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->post('/income/verification/documents/download', $data, false);

            ob_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-type: application/octet-stream");
            header('Content-Disposition: attachment; filename="paystub.zip"');
            header("Content-Transfer-Encoding: binary");
            ob_end_flush();
            echo $income;
            die;
        } catch (Exception $e) {
            $income = $e->getMessage();
        }
    }
    public function CreatePayrollUser($user)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $usertoken = $client->payrollincome()->getUserToken($this->identifier . $user);
        } catch (Exception $e) {
            $usertoken = $e->getMessage();
        }

        if (!isset($usertoken['user_token'])) {
            return [
                "status" => false,
                "message" => $usertoken,
                "transactions" => ""
            ];
        }

        return [
            "status" => true,
            "message" => '',
            "user_token" => $usertoken['user_token']
        ];
    }
    public function payrollIncome($user_token)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $income = $client->payrollincome()->get($user_token);
            return [
                "status" => true,
                "message" => "",
                "transactions" => $income
            ];
        } catch (Exception $e) {
            $income = $e->getMessage();
            return [
                "status" => false,
                "message" => $income,
                "transactions" => []
            ];
        }
    }
    public function createUser($userObj)
    {
        if (is_object($userObj) && method_exists($userObj, 'toArray')) {
            $userObj = $userObj->toArray();
        }

        $userData = isset($userObj['User']) ? $userObj['User'] : $userObj;

        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $requestBody = [
                "client_user_id" => $this->identifier . ($userData['id'] ?? ''),
                "consumer_report_user_identity" => [
                    "first_name" => $userData['first_name'] ?? '',
                    "last_name" => $userData['last_name'] ?? '',
                    "date_of_birth" => date('Y-m-d', strtotime($userData['dob'] ?? '')),
                    "emails" => [$userData['email'] ?? ''],
                    "phone_numbers" => [
                        (!empty($userData['contact_number']) ? '+1' . substr(preg_replace('/[^0-9]/', '', $userData['contact_number']), -10) : '')
                    ],
                    "primary_address" =>
                        [
                            "street" => $userData['address'] ?? '',
                            "city" => $userData['city'] ?? '',
                            "region" => $userData['state'] ?? '',
                            "country" => "US",
                            "postal_code" => $userData['zip'] ?? ''
                        ]

                ]
            ];

            if (empty($userData['address']) || empty($userData['city'])) {
                unset($requestBody['consumer_report_user_identity']['primary_address']);
            }

            Log::info('create user requestBody:', $requestBody);
            $user = $client->post('/user/create', $requestBody);
            Log::info('create user response:===>', (array) $user);
        } catch (Exception $e) {
            $user = $e->getMessage();
        }

        if (isset($user['user_id'])) {
            return [
                "status" => true,
                "message" => "",
                "user_token" => $user['user_token'],
                "user_id" => $user['user_id']
            ];
        }

        return [
            "status" => false,
            "message" => $user,
            "user_token" => "",
            "user_id" => ""
        ];
    }
    public function removeUser($access_token)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post('/item/remove', ['access_token' => $access_token]);
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        if (isset($resp['request_id'])) {
            return [
                "status" => true,
                "message" => "removed successfully"
            ];
        }

        return [
            "status" => false,
            "message" => $resp
        ];
    }
    public function accountIdentity($accessToken, $account_ids)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $identities = $client->identity()->get($accessToken, ["account_ids" => $account_ids]);
            return [
                "status" => true,
                "message" => "",
                "identities" => $identities
            ];
        } catch (Exception $e) {
            $income = $e->getMessage();
            return [
                "status" => false,
                "message" => $income,
                "identities" => []
            ];
        }
    }
    public function checkIfPlaidInstitutionIdExits($oldmetas, $newmetas)
    {
        $return = false;

        if (empty($oldmetas) || empty($newmetas)) {
            return $return;
        }

        $oldmetas = is_string($oldmetas) ? json_decode($oldmetas, true) : $oldmetas;
        $oldMetadataJson = isset($oldmetas['metadataJson'])
            ? (is_string($oldmetas['metadataJson']) ? json_decode($oldmetas['metadataJson'], true) : $oldmetas['metadataJson'])
            : $oldmetas;

        $newmetas = is_string($newmetas) ? json_decode($newmetas, true) : $newmetas;
        $newMetadataJson = isset($newmetas['metadataJson'])
            ? (is_string($newmetas['metadataJson']) ? json_decode($newmetas['metadataJson'], true) : $newmetas['metadataJson'])
            : $newmetas;

        if (empty($oldMetadataJson) || empty($newMetadataJson)) {
            return $return;
        }

        $accountNew = isset($newMetadataJson['accounts'])
            ? $newMetadataJson['accounts']
            : [$newMetadataJson['account']];
        $accountOld = isset($oldMetadataJson['accounts'])
            ? $oldMetadataJson['accounts']
            : [$oldMetadataJson['account']];

        foreach ($accountOld as $accOld) {
            foreach ($accountNew as $accNew) {
                if (
                    isset($newMetadataJson['institution']['institution_id'], $oldMetadataJson['institution']['institution_id'])
                    && $newMetadataJson['institution']['institution_id'] == $oldMetadataJson['institution']['institution_id']
                    && trim($accOld['name']) == trim($accNew['name'])
                    && $accOld['mask'] == $accNew['mask']
                ) {
                    $return = true;
                    break 2;
                }
            }
        }

        return $return;
    }
    private function _savePaystub($data, $userId, $newRecords = null)
    {
        $token = $data['token'];
        $metadata = $data['metadata'];
        $user_token = isset($data['user_token']) ? $data['user_token'] : '';

        $oldRecords = PlaidUser::where('user_id', $userId)->whereNotNull('token')->get();
        $isDuplicate = false;

        if ($oldRecords->isNotEmpty()) {
            foreach ($oldRecords as $oldRecord) {
                $isDuplicate = $this->checkIfPlaidInstitutionIdExits($oldRecord->metadata, $metadata);
                if ($isDuplicate) {
                    $deleted = $this->removeUser($oldRecord->token);
                    if ($deleted['status']) {
                        $oldRecord->delete();
                    }
                }
            }
        }

        $exists = $oldRecords->last();

        if (empty($exists)) {
            $authtoken = $this->generateAuthToken($token);
            $access_token = $authtoken['access_token'];
        } else {
            $access_token = $exists->token;
        }

        $dataToSave = [
            "user_id" => $userId,
            'item_id' => ($authtoken['item_id'] ?? ""),
            'token' => $access_token,
            "paystub" => 1,
            "user_token" => $user_token,
            'metadata' => is_string($metadata) ? $metadata : json_encode($metadata)
        ];

        $idToUpdate = null;

        if (!empty($exists) && $exists->paystub) {
            $idToUpdate = $exists->id;
        } elseif (!empty($newRecords)) {
            $idToUpdate = $newRecords->id;
        }

        if ($idToUpdate) {
            PlaidUser::where('id', $idToUpdate)->update($dataToSave);
            $plaidUserId = $idToUpdate;
        } else {
            $plaidUser = PlaidUser::create($dataToSave);
            $plaidUserId = $plaidUser->id;
        }

        User::where('id', $userId)->update(["bank" => $plaidUserId]);

        VehicleReservation::updatePendingBooking($userId, 2);

        return [
            "status" => true,
            "message" => "You are succcessfully connected now"
        ];
    }
    public function saveUser($data, $userId)
    {
        $return = [
            "status" => false,
            "message" => "Sorry, something went wrong, please try again"
        ];
        $token = $data['token'] ?? '';
        $metadata = $data['metadata'] ?? [];
        $paystub = isset($data['paystub']) && $data['paystub'] ? true : false;
        $user_token = isset($data['user_token']) ? $data['user_token'] : '';

        $userObj = User::select('id')->find($userId);

        if (empty($userObj)) {
            return $return;
        }

        $newRecords = PlaidUser::where('user_id', $userId)->whereNull('token')->first();

        if ($paystub) {
            return $this->_savePaystub($data, $userId, $newRecords);
        }

        $oldRecords = PlaidUser::where('user_id', $userId)->whereNotNull('token')->get();
        $isDuplicate = false;

        if ($oldRecords->isNotEmpty()) {
            foreach ($oldRecords as $oldRecord) {
                $isDuplicate = $this->checkIfPlaidInstitutionIdExits($oldRecord->metadata, $metadata);
                if ($isDuplicate) {
                    $deleted = $this->removeUser($oldRecord->token);
                    if ($deleted['status']) {
                        $oldRecord->delete();
                    }
                }
            }
        }

        if ($this->env === 'sandbox') {
            $sandboxresp = $this->_createSandboxPublicToken('ins_5', ['income_verification']);
            $token = $sandboxresp['public_token'] ?? $token;
        }

        $authtoken = $this->generateAuthToken($token);

        if (!$authtoken['status']) {
            return $authtoken;
        }

        $access_token = $authtoken['access_token'];

        $dataToSave = [
            'item_id' => ($authtoken['item_id'] ?? ""),
            "user_id" => $userId,
            "token" => $access_token,
            "user_token" => $user_token,
            'metadata' => is_string($metadata) ? $metadata : json_encode($metadata)
        ];

        if (!empty($newRecords)) {
            PlaidUser::where('id', $newRecords->id)->update($dataToSave);
            $plaidUserId = $newRecords->id;
        } else {
            $plaidUser = PlaidUser::create($dataToSave);
            $plaidUserId = $plaidUser->id;
        }

        User::where('id', $userId)->update(["bank" => $plaidUserId]);
        VehicleReservation::updatePendingBooking($userId, 2);

        return [
            "status" => true,
            "message" => "You are succcessfully connected now"
        ];
    }
    private function _createSandboxPublicToken($institution_id, $initial_products)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $publicToken = $client->post('/sandbox/public_token/create', [
                "institution_id" => $institution_id,
                "initial_products" => $initial_products
            ]);
        } catch (Exception $e) {
            $publicToken = $e->getMessage();
        }

        if (isset($publicToken['public_token'])) {
            return [
                "status" => true,
                "message" => "",
                "public_token" => $publicToken['public_token']
            ];
        }

        return [
            "status" => false,
            "message" => $publicToken,
            "public_token" => ""
        ];
    }
    public function getItem($access_token)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post('/item/get', ['access_token' => $access_token]);
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        return $resp;
    }
    public function getCraBaseReport($user_id)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post(
                '/cra/check_report/base_report/get',
                [
                    "client_id" => $this->client_id,
                    "secret" => $this->secret,
                    "user_id" => $user_id
                ]
            );
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        return $resp;
    }
    public function getCraIncomeInsights($user_id)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post(
                '/cra/check_report/income_insights/get',
                [
                    "client_id" => $this->client_id,
                    "secret" => $this->secret,
                    "user_id" => $user_id
                ]
            );
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        return $resp;
    }
    public function getCraCheckReportPdf($user_id)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post(
                '/cra/check_report/pdf/get',
                [
                    "client_id" => $this->client_id,
                    "secret" => $this->secret,
                    "user_id" => $user_id
                ]
            );
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        return $resp;
    }
    public function getCraCashflowInsights($user_id)
    {
        try {
            $client = new Client($this->client_id, $this->secret, $this->key, $this->env);
            $resp = $client->post(
                '/cra/check_report/cashflow_insights/get',
                [
                    "client_id" => $this->client_id,
                    "secret" => $this->secret,
                    "user_id" => $user_id
                ]
            );
        } catch (Exception $e) {
            $resp = $e->getMessage();
        }

        return $resp;
    }
}
