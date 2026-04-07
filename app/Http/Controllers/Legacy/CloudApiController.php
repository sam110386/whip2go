<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CloudApiController extends LegacyApiBaseController
{
    public function dispatch(Request $request, string $ver, string $action)
    {
        $version = 'v1';
        if ($ver !== $version) {
            return $this->unsupportedVersion($ver);
        }

        if (!method_exists($this, $action)) {
            return $this->notImplemented($request, $ver, $action, 404);
        }

        // Cake action names are passed directly as URL params.
        return $this->{$action}($request);
    }

    // ---------------------------------------------------------------------
    // Action stubs (CakePHP CloudOneApi plugin: CloudApiController)
    // ---------------------------------------------------------------------
    public function getAuthToken(Request $request)
    {
        $pepper = 'driveitaway';
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $raw = (string) $request->getContent();
        $dataValues = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $dataValues = $decoded;
            }
        }
        if (empty($dataValues)) {
            $dataValues = $request->input();
        }

        $username = isset($dataValues['username']) ? trim((string)$dataValues['username']) : '';
        $password = isset($dataValues['password']) ? (string)$dataValues['password'] : '';

        if ($username === '' || $password === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid inputs',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        // Cake: find by username OR email = username.
        $user = DB::table('users')
            ->select(['id', 'password'])
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if (empty($user)) {
            return response()->json([
                'message' => 'Sorry, you are not registered user.',
                'status' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $hash = sha1($salt . $password);
        $storedPassword = (string)($user->password ?? '');

        if ($storedPassword === '' || $storedPassword !== $hash) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your password does not match, please try again',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $token = $this->getUniqueId(8);

        DB::table('users')
            ->where('id', (int)$user->id)
            ->update(['token' => $token]);

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        $now = time();
        $jwtPayload = [
            'iss' => 'https://www.whip2go.com',
            'aud' => 'https://www.whip2go.com',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 72 * 3600,
            'user' => [
                'userId' => (int)$user->id,
                'token' => $token,
            ],
        ];

        $authToken = \JWT::encode($jwtPayload, $pepper);

        return response()->json([
            'status' => 1,
            'message' => '',
            'auth_token' => $authToken,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function refreshToken(Request $request)
    {
        $pepper = 'driveitaway';

        $jwtToken = $this->getJwtFromAuthorizationHeader($request);
        if ($jwtToken === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Token is required',
                'auth_token' => '',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            // If decode succeeds, Cake returns "Token is valid" and the same token.
            \JWT::decode($jwtToken, $pepper, ['HS256']);
            return response()->json([
                'status' => 1,
                'message' => 'Token is valid',
                'auth_token' => $jwtToken,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\ExpiredException $e) {
            // Match Cake's leeway + refresh behavior.
            \JWT::$leeway = 720000;
            $decoded = (array) \JWT::decode($jwtToken, $pepper, ['HS256']);
            $decoded['iat'] = time();
            $decoded['exp'] = time() + 72 * 3600;

            $newToken = \JWT::encode($decoded, $pepper);
            return response()->json([
                'status' => 1,
                'message' => 'Token refreshed',
                'auth_token' => $newToken,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'auth_token' => '',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    public function addVehicles(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || !isset($dataValues[0])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $return = ['status' => 1, 'message' => 'Vehicle data added', 'result' => []];
        foreach ($dataValues as $dataValue) {
            if (!is_array($dataValue)) {
                continue;
            }
            if (empty($dataValue['dealer_id']) || empty($dataValue['vin_no']) || empty($dataValue['address']) || empty($dataValue['lat']) || empty($dataValue['lng'])) {
                continue;
            }
            $this->saveVehicleRecord($dataValue, (int) $dataValue['dealer_id']);
            $return['result'][] = (string) $dataValue['vin_no'];
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function addVehicle(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];

        // Cake rejects array payloads for this endpoint (bulk is handled by addVehicles).
        if (empty($dataValues) || empty($userObj['id']) || isset($dataValues[0])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (empty($dataValues['dealer_id']) || empty($dataValues['vin_no']) || empty($dataValues['address']) || empty($dataValues['lat']) || empty($dataValues['lng'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $this->saveVehicleRecord($dataValues, (int) $dataValues['dealer_id']);

        return response()->json([
            'status' => 1,
            'message' => 'Vehicle data added',
            'result' => [(string) $dataValues['vin_no']],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function removeVehicle(Request $request)
    {
        // Mirrors CakePHP response payload for this endpoint.
        return response()->json([
            'status' => false,
            'message' => "we dont support this end point now",
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function createOffer(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || !isset($dataValues['vin_no']) || empty($dataValues['phone'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vin = trim((string) $dataValues['vin_no']);
        $return['message'] = 'Vehicle record not found for vin #' . $vin;

        $vehicle = DB::table('vehicles')
            ->select(['id'])
            ->where('vin_no', $vin)
            ->first();

        if (empty($vehicle)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phone = $this->normalizePhone10((string) $dataValues['phone']);

        $alreadyExist = DB::table('vehicle_offers')
            ->where('vehicle_id', (int) $vehicle->id)
            ->where('driver_phone', $phone)
            ->whereIn('status', [0, 1])
            ->first();

        if (!empty($alreadyExist) && (int) ($alreadyExist->status ?? 0) === 1) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry you can't update any existing accepted offer.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $user = DB::table('users')->select(['id'])->where('username', $phone)->first();

        $initialFeeOptArr = isset($dataValues['initial_fee_opt']) && is_array($dataValues['initial_fee_opt']) ? $dataValues['initial_fee_opt'] : [];
        $depositOptArr = isset($dataValues['deposit_opt']) && is_array($dataValues['deposit_opt']) ? $dataValues['deposit_opt'] : [];
        $rentOptArr = isset($dataValues['rent_opt']) && is_array($dataValues['rent_opt']) ? $dataValues['rent_opt'] : [];

        $initialFee = (float) ($dataValues['initial_fee'] ?? 0);
        $depositAmt = (float) ($dataValues['deposit_amt'] ?? 0);
        $dayRent = (float) ($dataValues['day_rent'] ?? 0);

        $totalInitialFee = $this->sumAmountArray($initialFeeOptArr) + $initialFee;
        $totalDepositAmt = $this->sumAmountArray($depositOptArr) + $depositAmt;
        $rentOptJson = $this->sumAmountArray($rentOptArr) > 0 ? json_encode($rentOptArr) : '';
        $initialFeeOptJson = $this->sumAmountArray($initialFeeOptArr) > 0 ? json_encode($initialFeeOptArr) : '';
        $depositOptJson = $this->sumAmountArray($depositOptArr) > 0 ? json_encode($depositOptArr) : '';

        $startDatetime = null;
        if (!empty($dataValues['start_datetime'])) {
            $ts = strtotime((string) $dataValues['start_datetime']);
            if ($ts !== false) {
                $startDatetime = date('Y-m-d H:i:s', $ts);
            }
        }

        $offerToSave = [
            'user_id' => !empty($user) ? (int) $user->id : null,
            'vehicle_id' => (int) $vehicle->id,
            'initial_fee' => $initialFee,
            'deposit_amt' => $depositAmt,
            'day_rent' => $dayRent,
            'driver_phone' => $phone,
            'rent_opt' => $rentOptJson,
            'initial_fee_opt' => $initialFeeOptJson,
            'deposit_opt' => $depositOptJson,
            'total_initial_fee' => $totalInitialFee,
            'total_deposit_amt' => $totalDepositAmt,
            'start_datetime' => $startDatetime,
            // Default status is "new" (0) in schema.
        ];

        // Only persist columns that exist.
        $filtered = [];
        foreach ($offerToSave as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (Schema::hasColumn('vehicle_offers', $col)) {
                $filtered[$col] = $val;
            }
        }

        if (!empty($alreadyExist)) {
            DB::table('vehicle_offers')->where('id', (int) $alreadyExist->id)->update($filtered);
        } else {
            DB::table('vehicle_offers')->insert($filtered);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function cancelOffer(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || !isset($dataValues['vin_no']) || empty($dataValues['phone'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vin = trim((string) $dataValues['vin_no']);
        $phone = $this->normalizePhone10((string) $dataValues['phone']);

        $vehicle = DB::table('vehicles')
            ->select(['id'])
            ->where('vin_no', $vin)
            ->first();

        if (empty($vehicle)) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry we couldnt find any active offer for given vehicle & driver.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $offer = DB::table('vehicle_offers')
            ->where('vehicle_id', (int) $vehicle->id)
            ->where('driver_phone', $phone)
            ->whereIn('status', [0, 1])
            ->first();

        if (empty($offer)) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry we couldnt find any active offer for given vehicle & driver.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ((int) ($offer->status ?? 0) === 1) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry you cant cancel any accepted offer.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        DB::table('vehicle_offers')->where('id', (int) $offer->id)->update(['status' => 2]);

        return response()->json([
            'status' => 1,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function checkDealerExists(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || empty($dataValues['phone'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $dataValues['phone']);
        $phone = (string) $phone;

        if ($phone !== '') {
            $user = DB::table('users')
                ->select(['id', 'is_dealer'])
                ->where('username', $phone)
                ->first();

            if (!empty($user)) {
                if (!empty($user->is_dealer)) {
                    $return = [
                        'status' => 1,
                        'message' => 'Dealer found',
                        'result' => ['dealer_id' => (int) $user->id],
                    ];
                } else {
                    $return['message'] = "Sorry, Phone is registered but user didnt complete profile till dealer account complete";
                }
            } else {
                $return['message'] = 'Sorry, dealer is not registered yet';
            }
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function createUser(Request $request)
    {
        $userObj = $this->requireCloudJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj; // JsonResponse
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || !array_key_exists('phone_number', $dataValues)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phoneUsername = $this->normalizePhone10((string) ($dataValues['phone_number'] ?? ''));
        $exists = DB::table('users')->where('username', $phoneUsername)->first();
        if (!empty($exists)) {
            return response()->json([
                'status' => 0,
                'message' => 'User alredy exists with given phone number',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $firstName = isset($dataValues['first_name']) ? ucwords(strtolower((string) $dataValues['first_name'])) : '';
        $lastName = isset($dataValues['last_name']) ? ucwords(strtolower((string) $dataValues['last_name'])) : '';
        $email = (string) ($dataValues['email'] ?? '');

        $licenseCipher = (string) ($dataValues['license_number'] ?? '');
        $licenseNumber = '';
        if ($licenseCipher !== '') {
            $decrypted = $this->cakeDecrypt($licenseCipher);
            $licenseNumber = ($decrypted === false) ? '' : (string) $decrypted;
        }

        $dob = '';
        if (!empty($dataValues['dateOfBirth'])) {
            $ts = strtotime((string) $dataValues['dateOfBirth']);
            if ($ts !== false) {
                $dob = date('m/d/Y', $ts);
            }
        }

        $candidate = [
            'username' => $phoneUsername,
            'password' => sha1($salt . $phoneUsername),
            'contact_number' => (string) ($dataValues['phone_number'] ?? ''),
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'verify_token' => $this->getUniqueId(5, '0123456789'),
            'status' => 1,
            'is_verified' => 0,
            'licence_number' => $licenseNumber,
            'is_driver' => 1,
            'licence_state' => (string) ($dataValues['licence_state'] ?? ''),
            'state' => (string) ($dataValues['state'] ?? ''),
            'licence_exp_date' => (string) ($dataValues['licence_exp_date'] ?? ''),
            'dob' => $dob,
            'address' => (string) ($dataValues['address'] ?? ''),
            'city' => (string) ($dataValues['city'] ?? ''),
            'zip' => (string) ($dataValues['zip'] ?? ''),
            'parent_id' => (int) ($userObj['id'] ?? 0),
        ];

        $insert = [];
        foreach ($candidate as $col => $val) {
            if ($val === '' || $val === null) {
                continue;
            }
            if (Schema::hasColumn('users', $col)) {
                $insert[$col] = $val;
            }
        }

        try {
            $newUserId = DB::table('users')->insertGetId($insert);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $baseUrl = rtrim((string) (config('app.url') ?: $request->getSchemeAndHttpHost()), '/');

        return response()->json([
            'status' => 1,
            'message' => 'User id added successfully.',
            'user_id' => (int) $newUserId,
            'plaid_url' => $baseUrl . '/plaid/index/' . base64_encode((string) $newUserId),
            'atomic_url' => $baseUrl . '/atomic/connect/index/' . base64_encode((string) $newUserId) . '/true',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    private function getUniqueId(int $length = 32, string $pool = ''): string
    {
        if ($pool === '') {
            $pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        }
        mt_srand((double) microtime() * 1000000);
        $uniqueId = '';
        for ($index = 0; $index < $length; $index++) {
            $uniqueId .= substr($pool, (mt_rand() % strlen($pool)), 1);
        }
        return $uniqueId;
    }

    private function getJwtFromAuthorizationHeader(Request $request): string
    {
        $authorization = (string) ($request->header('Authorization') ?? '');
        if ($authorization === '') {
            $authorization = (string) ($request->header('authorization') ?? '');
        }

        // Cake: trim(str_replace('Basic', '', $Authorization))
        $jwt = trim(str_replace('Basic', '', $authorization));
        return trim($jwt);
    }

    /**
     * Mirrors Cake's `CloudUserApiController::beforeFilter()` auth check:
     * - JWT is expected inside `Authorization: Basic <jwt>`
     * - validates signature + then validates `users.id` + `users.token` and scope:
     *   status=1, role_id=2, is_admin=1
     *
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function requireCloudJwtUser(Request $request)
    {
        $pepper = 'driveitaway';
        $jwtToken = $this->getJwtFromAuthorizationHeader($request);

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            $decoded = \JWT::decode($jwtToken, $pepper, ['HS256']);
            $userId = isset($decoded->user->userId) ? (int) $decoded->user->userId : 0;
            $userToken = isset($decoded->user->token) ? (string) $decoded->user->token : '';
            if ($userId <= 0 || $userToken === '') {
                throw new \Exception('Please login again');
            }

            $user = DB::table('users')
                ->where('id', $userId)
                ->where('token', $userToken)
                ->where('status', 1)
                ->where('role_id', 2)
                ->where('is_admin', 1)
                ->first();

            if (empty($user)) {
                throw new \Exception('Please login again');
            }

            return (array) $user;
        } catch (\Exception $e) {
            return response()
                ->json(['status' => 0, 'message' => 'Please log back in'])
                ->setStatusCode(400)
                ->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    private function saveVehicleRecord(array $record, int $userId): void
    {
        $action = strtolower(trim((string) ($record['action'] ?? 'add')));
        if (!in_array($action, ['add', 'remove'], true)) {
            return;
        }

        $vin = (string) ($record['vin_no'] ?? '');
        if ($vin === '') {
            return;
        }

        $existing = DB::table('vehicles')->where('vin_no', $vin)->first();
        if ($action === 'remove' && !empty($existing)) {
            $update = [];
            if (Schema::hasColumn('vehicles', 'trash')) {
                $update['trash'] = 1;
            }
            if (!empty($update)) {
                DB::table('vehicles')->where('id', (int) $existing->id)->update($update);
            }
            return;
        }

        $dataToSave = [];
        $dataToSave['user_id'] = $userId;
        $dataToSave['make'] = $record['make'] ?? null;
        $dataToSave['model'] = $record['model'] ?? null;
        $dataToSave['year'] = $record['year'] ?? null;
        $dataToSave['color'] = $record['color'] ?? null;
        $dataToSave['vin_no'] = $vin;
        $dataToSave['details'] = $record['details'] ?? ($record['stock_no'] ?? null);
        $dataToSave['address'] = $record['address'] ?? null;
        $dataToSave['lat'] = $record['lat'] ?? null;
        $dataToSave['lng'] = $record['lng'] ?? null;
        $dataToSave['program'] = 2;
        $dataToSave['financing'] = 4;
        $dataToSave['msrp'] = $record['price'] ?? null;
        $dataToSave['interior_color'] = $record['color'] ?? null;
        $dataToSave['trim'] = $record['trim'] ?? null;
        $dataToSave['mileage'] = $record['mileage'] ?? null;
        $dataToSave['allowed_miles'] = 150;

        $dayRent = isset($record['day_rent']) ? (float) $record['day_rent'] : 0.0;
        if ($dayRent > 0) {
            $dataToSave['day_rent'] = $dayRent;
            $dataToSave['fare_type'] = 'S';
        } else {
            $dataToSave['fare_type'] = 'D';
        }

        $dataToSave['transmition_type'] = !empty($record['transmission']) ? (string) $record['transmission'] : 'A';

        $vehicleName =
            (!empty($dataToSave['year']) ? substr((string) $dataToSave['year'], -2) . '-' : '') .
            (!empty($dataToSave['make']) ? str_replace(' ', '_', (string) $dataToSave['make']) . '-' : '') .
            (!empty($dataToSave['model']) ? str_replace(' ', '_', (string) $dataToSave['model']) : '') .
            (!empty($dataToSave['vin_no']) ? '-' . substr((string) $dataToSave['vin_no'], -6) : '');
        $dataToSave['vehicle_name'] = $vehicleName;

        // Only persist columns that exist in current schema.
        $filtered = [];
        foreach ($dataToSave as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (Schema::hasColumn('vehicles', $col)) {
                $filtered[$col] = $val;
            }
        }

        if (empty($filtered)) {
            return;
        }

        if (!empty($existing)) {
            DB::table('vehicles')->where('id', (int) $existing->id)->update($filtered);
        } else {
            DB::table('vehicles')->insert($filtered);
        }
    }

    private function normalizePhone10(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $digits = (string) $digits;
        if (strlen($digits) <= 10) {
            return $digits;
        }
        return substr($digits, -10);
    }

    private function sumAmountArray(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $row) {
            if (is_array($row) && isset($row['amount'])) {
                $sum += (float) $row['amount'];
            }
        }
        return $sum;
    }

    /**
     * Replicates CakePHP 2.x `Security::decrypt()` (AES-256-CBC + HMAC).
     * Key material from Cake config:
     * - Security.encryptKey: DYhG93b0qyJfIxfs2guR2G0FgaC9mixf
     * - Security.salt: DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi
     *
     * @return string|false
     */
    private function cakeDecrypt(string $cipher)
    {
        if ($cipher === '') {
            return '';
        }

        $encryptKey = 'DYhG93b0qyJfIxfs2guR2G0FgaC9mixf';
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $key = substr(hash('sha256', $encryptKey . $salt), 0, 32);

        $macSize = 64;
        $hmac = substr($cipher, 0, $macSize);
        $cipherBody = substr($cipher, $macSize);

        $compareHmac = hash_hmac('sha256', $cipherBody, $key);
        if ($hmac !== $compareHmac) {
            return false;
        }

        $method = 'AES-256-CBC';
        $ivSize = openssl_cipher_iv_length($method);
        $iv = substr($cipherBody, 0, $ivSize);
        $cipherText = substr($cipherBody, $ivSize);

        // Regenerate PKCS#7 padding block (Cake compatibility trick).
        $padding = openssl_encrypt('', $method, $key, true, substr($cipherText, -$ivSize));
        $plain = openssl_decrypt($cipherText . $padding, $method, $key, true, $iv);
        if ($plain === false) {
            return false;
        }
        return rtrim($plain, "\0");
    }
}

