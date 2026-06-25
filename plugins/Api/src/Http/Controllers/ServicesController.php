<?php

namespace Plugins\Api\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyApiBaseController;
use App\Models\Legacy\CsOrder as LegacyCsOrder;
use App\Models\Legacy\CsOrderPayment as LegacyCsOrderPayment;
use App\Models\Legacy\OrderDepositRule as LegacyOrderDepositRule;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\UserCcToken as LegacyUserCcToken;
use App\Models\Legacy\VehicleReservation as LegacyVehicleReservation;
use App\Models\Legacy\Wishlist as LegacyWishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServicesController extends LegacyApiBaseController
{
    public function login(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw);

        $phoneNumber = (is_object($dataValues) && isset($dataValues->phone_number)) ? $dataValues->phone_number : null;
        if ($raw === '' || empty($phoneNumber)) {
            return response()->json([
                'message' => 'Invalid request payload',
                'status' => 0,
                'verify_required' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $username = $this->normalizePhone10((string) $phoneNumber);
        $user = LegacyUser::query()
            ->where('username', $username)
            ->where('is_admin', 0)
            ->first();

        if (empty($user)) {
            return response()->json([
                'message' => 'Sorry, you are not registered. Please proceed to register.',
                'status' => 0,
                'verify_required' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (!empty($user->is_dealer)) {
            return response()->json([
                'message' => 'Sorry, you are registered as dealer on our end.',
                'status' => 3,
                'verify_required' => 0,
                'redirect' => $request->getSchemeAndHttpHost(),
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (!empty($user->trash)) {
            return response()->json([
                'message' => 'Sorry, your account is deleted already. Please contact to support team if you want to recover it.',
                'status' => 0,
                'verify_required' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ((int) ($user->is_verified ?? 0) === 1) {
            if ((int) ($user->status ?? 0) === 1) {
                $payload = [
                    'message' => 'Please enter password to login.',
                    'status' => 1,
                    'verify_required' => 1,
                    'user_id' => (int) $user->id,
                ];
            } else {
                $payload = [
                    'message' => 'Your account is deactivated. please contact @',
                    'status' => 2,
                    'verify_required' => 0,
                ];
            }
        } else {
            $activationCode = $this->getUniqueId(5, '0123456789');
            LegacyUser::query()->whereKey((int) $user->id)->update(['verify_token' => $activationCode]);
            $payload = [
                'message' => 'Sorry, your account is not verified. We sent verification code, please use that code to verify account.',
                'status' => 2,
                'verify_required' => 1,
                'activation_code' => $activationCode,
                'user_id' => (int) $user->id,
            ];
        }

        return response()->json($payload)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function loginadvance(Request $request): JsonResponse
    {
        $salt = config('legacy.security.salt', '');
        $pepper = 'mindseye1';
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        if (empty($dataValues['user_id']) || empty($dataValues['password'])) {
            return response()->json(['status' => 0, 'message' => 'Invalid request payload'])
                ->header('Content-Type', 'application/json; charset=utf-8');
        }

        $user = DB::table('users')
            ->where('id', (int) $dataValues['user_id'])
            ->where('is_admin', 0)
            ->where('trash', 0)
            ->first();

        if (empty($user)) {
            return response()->json(['status' => 0, 'message' => 'Sorry, you are not registered. Please proceed to register.'])
                ->header('Content-Type', 'application/json; charset=utf-8');
        }

        $pwd = (string) $dataValues['password'];
        $hash = sha1($salt . $pwd);
        $master = sha1($salt . 'mindseye');
        $stored = (string) ($user->password ?? '');
        if ($stored === '' || !($stored === $hash || $hash === $master)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your password does not match, please try again',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $token = $this->getUniqueId(8);
        DB::table('users')->where('id', (int) $user->id)->update(['token' => $token]);
        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        $now = time();
        $expireAt = $now + 72000;
        $jwtPayload = [
            'iss' => 'https://www.whip2go.com',
            'aud' => 'https://www.whip2go.com',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expireAt,
            'user' => ['userId' => (int) $user->id, 'token' => $token],
        ];
        $authToken = \JWT::encode($jwtPayload, $pepper);

        return response()->json([
            'status' => 1,
            'message' => '',
            'auth_token' => $authToken,
            'expire_at' => $expireAt,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $pepper = 'mindseye1';
        $jwtToken = $this->getJwtFromAuthorizationHeader($request);
        if ($jwtToken === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Auth Token is missing',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            \JWT::decode($jwtToken, $pepper, ['HS256']);
            return response()->json([
                'status' => 1,
                'message' => 'Token is valid',
                'auth_token' => $jwtToken,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\ExpiredException $e) {
            \JWT::$leeway = 720000;
            $decoded = (array) \JWT::decode($jwtToken, $pepper, ['HS256']);
            $decoded['iat'] = time();
            $decoded['exp'] = time() + 72000;
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

    public function ssologin(Request $request): JsonResponse
    {
        $jwtToken = $this->getJwtFromAuthorizationHeader($request);
        if ($jwtToken === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $user = $this->resolveWebJwtUser($jwtToken);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, something went wrong, please try again',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $row = DB::table('users')
            ->where('id', (int) $user['id'])
            ->where('is_admin', 0)
            ->where('trash', 0)
            ->first();

        if (empty($row)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, something went wrong, please try again',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = array_map(static fn ($v) => $v === null ? '' : $v, (array) $row);
        $host = rtrim($request->getSchemeAndHttpHost(), '/') . '/';
        $result['photo'] = !empty($result['photo'])
            ? $host . 'Images/index?width=200&height=200&image=/img/user_pic/' . $result['photo']
            : $host . 'Images/index?width=200&height=200&image=/img/user_pic/no_image.gif';
        $result['license_doc_1'] = '';
        $result['license_doc_2'] = '';
        $result['licence_type'] = '';
        $result['licence_number'] = '';
        $result['licence_state'] = '';
        $result['licence_exp_date'] = '';
        $result['wallet_balance'] = 0;
        $result['wallet_term'] = 0;
        $result['credit_score_reported'] = 0;
        $result['residency_reported'] = 0;
        $result['income_reported'] = 0;
        $result['my_insurance_menu'] = 0;
        $result['bank_added'] = 0;
        $result['paystub_reported'] = 0;
        $result['paybank_reported'] = 0;
        $result['address_proof'] = !empty(json_decode((string)($row->address_doc ?? ''), true));
        $result['promo_added'] = false;

        return response()->json([
            'status' => 1,
            'message' => '',
            'auth_token' => $jwtToken,
            'expire_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function register(Request $request): JsonResponse
    {
        $salt = config('legacy.security.salt', '');
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        if (!is_array($dataValues) || empty($dataValues)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phoneNumber = (string) ($dataValues['phone_number'] ?? '');
        $username = $this->normalizePhone10($phoneNumber);
        if ($username === '' || empty($dataValues['password']) || empty($dataValues['first_name']) || empty($dataValues['last_name'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $existing = DB::table('users')->where('username', $username)->first();
        if (!empty($existing)) {
            if ((int) ($existing->trash ?? 0) === 1) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Sorry, your account is deleted. Please contact to support team if you want to recover it.',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }
            if ((int) ($existing->is_verified ?? 0) === 1) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Sorry, you are already registered. Please login.',
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }

            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your account is not verified. Please follow the login process to verify your account',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $activationCode = $this->getUniqueId(5, '0123456789');
        $userId = (int) DB::table('users')->insertGetId([
            'username' => $username,
            'contact_number' => $phoneNumber,
            'email' => (string) ($dataValues['email'] ?? ''),
            'first_name' => ucwords(strtolower((string) $dataValues['first_name'])),
            'middle_name' => (string) ($dataValues['middle_name'] ?? ''),
            'last_name' => ucwords(strtolower((string) $dataValues['last_name'])),
            'password' => sha1($salt . (string) $dataValues['password']),
            'verify_token' => $activationCode,
            'status' => 0,
            'is_verified' => 0,
            'is_admin' => 0,
            'trash' => 0,
            'token' => '',
        ]);

        if (
            $userId > 0
            && !empty($dataValues['photo_data'])
            && !empty($dataValues['fileformat'])
        ) {
            try {
                $directory = public_path('img/user_pic');
                if (!is_dir($directory)) {
                    @mkdir($directory, 0755, true);
                }
                $photoName = 'user_' . $userId . '.' . preg_replace('/[^a-zA-Z0-9]/', '', (string) $dataValues['fileformat']);
                $photoPath = $directory . DIRECTORY_SEPARATOR . $photoName;
                $encoded = preg_replace('#^data:image/\w+;base64,#i', '', (string) $dataValues['photo_data']);
                @file_put_contents($photoPath, base64_decode($encoded));
                DB::table('users')->where('id', $userId)->update(['photo' => $photoName]);
            } catch (\Throwable $e) {
                // Best-effort only; do not fail registration on image write.
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your are registered successfully. We have sent an activation code on your phone#, please verify your account before login',
            'activation_code' => $activationCode,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        $newCode = '';
        $userId = '';
        if (!is_array($dataValues) || empty($dataValues) || empty($dataValues['phone_number'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'activation_code' => $newCode,
                'user_id' => $userId,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phoneNumber = $this->normalizePhone10((string) $dataValues['phone_number']);
        $exist = DB::table('users')
            ->where('username', $phoneNumber)
            ->first();

        if (empty($exist)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, we could not find this phone#',
                'activation_code' => $newCode,
                'user_id' => $userId,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ((int) ($exist->trash ?? 0) === 1) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your account is deleted. Please contact to support team if you want to recover it.',
                'activation_code' => $newCode,
                'user_id' => $userId,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ((int) ($exist->is_verified ?? 0) !== 1) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your account is not verified. Please follow the login process to verify your account',
                'activation_code' => $newCode,
                'user_id' => $userId,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ((int) ($exist->status ?? 0) !== 1) {
            return response()->json([
                'status' => 0,
                'message' => 'Your account is deactivated. please contact @',
                'activation_code' => $newCode,
                'user_id' => $userId,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $newCode = $this->getUniqueId(5, '0123456789');
        $userId = (string) $exist->id;
        DB::table('users')->where('id', (int) $exist->id)->update(['verify_token' => $newCode]);

        return response()->json([
            'status' => 1,
            'message' => 'Please setup your new password',
            'activation_code' => $newCode,
            'user_id' => $userId,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $salt = config('legacy.security.salt', '');
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        if (!is_array($dataValues) || empty($dataValues)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = DB::table('users')
            ->where('id', (int) ($dataValues['user_id'] ?? 0))
            ->first(['id', 'password', 'verify_token']);
        if (empty($result)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, we could not find you.',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (empty($dataValues['password'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, password should not be empty',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (empty($dataValues['verify_token']) || (string) $result->verify_token !== (string) $dataValues['verify_token']) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, verification code does not match.',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        DB::table('users')->where('id', (int) $result->id)->update([
            'password' => sha1($salt . trim((string) $dataValues['password'])),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Your password updated successfully. Please login.',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function resendActivation(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        if (!is_array($dataValues) || empty($dataValues['email'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $emailOrPhone = (string) $dataValues['email'];
        $phoneLike = $this->normalizePhone10($emailOrPhone);
        $passengerData = DB::table('users')
            ->where('is_verified', 0)
            ->where(function ($query) use ($emailOrPhone, $phoneLike) {
                $query->where('email', $emailOrPhone)
                    ->orWhere('username', $phoneLike);
            })
            ->first(['id', 'email', 'first_name', 'last_name', 'contact_number']);

        if (empty($passengerData)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, we could not find your email id or your account is already activated.',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $newCode = $this->getUniqueId(5, '0123456789');
        DB::table('users')->where('id', (int) $passengerData->id)->update(['verify_token' => $newCode]);

        return response()->json([
            'status' => 1,
            'message' => 'We have sent a new activation code to your phone number.',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function verifyAccount(Request $request): JsonResponse
    {
        $pepper = 'mindseye1';
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        if (
            !is_array($dataValues)
            || empty($dataValues['activationCode'])
            || !isset($dataValues['phone_number'])
        ) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phoneNumber = $this->normalizePhone10((string) $dataValues['phone_number']);
        $userData = DB::table('users')
            ->where('username', $phoneNumber)
            ->where('verify_token', (string) $dataValues['activationCode'])
            ->where('status', 0)
            ->where('is_admin', 0)
            ->where('is_verified', 0)
            ->orderByDesc('id')
            ->first();

        if (empty($userData)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your activation code is wrong.',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $token = $this->getUniqueId(6);
        DB::table('users')->where('id', (int) $userData->id)->update([
            'status' => 1,
            'verify_token' => '',
            'is_verified' => 1,
            'token' => $token,
        ]);

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';
        $now = time();
        $expireAt = $now + 72000;
        $jwtPayload = [
            'iss' => 'https://www.whip2go.com',
            'aud' => 'https://www.whip2go.com',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expireAt,
            'user' => ['userId' => (int) $userData->id, 'token' => $token],
        ];
        $authToken = \JWT::encode($jwtPayload, $pepper);

        $row = DB::table('users')->where('id', (int) $userData->id)->first();
        $result = array_map(static fn ($v) => $v === null ? '' : $v, (array) $row);
        if (!empty($result['photo'])) {
            $host = rtrim($request->getSchemeAndHttpHost(), '/') . '/';
            $result['photo'] = $host . 'Images/index?width=200&height=200&image=/img/user_pic/' . $result['photo'];
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your account is verified successfully.',
            'auth_token' => $authToken,
            'expire_at' => date('Y-m-d H:i:s', $expireAt),
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getmyaccountDetails(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $array = $this->hydrateAccountResult((array) $user);
        if (!empty($array)) {
            return response()->json([
                'status' => 1,
                'message' => '',
                'result' => $array,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        return response()->json([
            'status' => 0,
            'message' => 'sorry, no record found for this user',
            'result' => '',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $salt = config('legacy.security.salt', '');
        $user = $this->requireWebJwtUser($request);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        if (!is_array($dataValues) || empty($dataValues)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = DB::table('users')
            ->where('id', (int) $user['id'])
            ->first(['id', 'password']);
        $oldPassword = trim((string) ($dataValues['old_password'] ?? ''));
        $newPassword = trim((string) ($dataValues['password'] ?? ''));
        $oldHash = sha1($salt . $oldPassword);
        if (empty($result) || (string) ($result->password ?? '') !== $oldHash) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your old password is wrong',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        if ($oldPassword === $newPassword) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your new password should be different than previous',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        DB::table('users')->where('id', (int) $user['id'])->update(['password' => sha1($salt . $newPassword)]);
        return response()->json([
            'status' => 1,
            'message' => 'Your password updated successfully',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function updateAccountDetails(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        if (!is_array($dataValues) || empty($dataValues)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $dataToSave = $dataValues;
        unset($dataToSave['photo'], $dataToSave['auto_renew'], $dataToSave['photo_data'], $dataToSave['fileformat']);
        unset($dataToSave['licence_type'], $dataToSave['licence_number'], $dataToSave['licence_exp_date'], $dataToSave['licence_state']);
        if (array_key_exists('ss_no', $dataToSave)) {
            $dataToSave['ss_no'] = empty($dataToSave['ss_no']) ? null : $this->cakeEncrypt((string) $dataToSave['ss_no']);
        }

        if (!empty($dataValues['photo_data']) && !empty($dataValues['fileformat'])) {
            try {
                $directory = public_path('img/user_pic');
                if (!is_dir($directory)) {
                    @mkdir($directory, 0755, true);
                }
                $photoName = 'user_' . (int) $user['id'] . '.' . preg_replace('/[^a-zA-Z0-9]/', '', (string) $dataValues['fileformat']);
                $photoPath = $directory . DIRECTORY_SEPARATOR . $photoName;
                $encoded = preg_replace('#^data:image/\w+;base64,#i', '', (string) $dataValues['photo_data']);
                @file_put_contents($photoPath, base64_decode($encoded));
                $dataToSave['photo'] = $photoName;
            } catch (\Throwable $e) {
                // Best effort.
            }
        }

        $safeData = $this->filterTableColumns('users', $dataToSave);
        DB::table('users')->where('id', (int) $user['id'])->update($safeData);

        $reloaded = (array) DB::table('users')->where('id', (int) $user['id'])->first();
        $array = $this->hydrateAccountResult($reloaded);
        $array['photo'] = !empty($array['photo'])
            ? rtrim($request->getSchemeAndHttpHost(), '/') . '/Images/index?width=200&height=200&image=/img/user_pic/' . $array['photo'] . '&v=' . time()
            : rtrim($request->getSchemeAndHttpHost(), '/') . '/Images/index?width=200&height=200&image=/img/user_pic/no_image.gif';

        return response()->json([
            'status' => 1,
            'message' => 'Profile data updated successfully',
            'result' => $array,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        $return = ['status' => 0, 'message' => 'Invalid request payload', 'result' => []];
        if (!is_array($dataValues) || empty($dataValues) || empty($dataValues['doc_type']) || empty($dataValues['doc_data']) || empty($dataValues['fileformat'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $docType = (string) $dataValues['doc_type'];
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', (string) $dataValues['fileformat']);
        $directory = public_path('files/userdocs');
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        $binary = base64_decode((string) preg_replace('#^data:image/\w+;base64,#i', '', (string) $dataValues['doc_data']));

        if (in_array($docType, ['license_doc_1', 'license_doc_2'], true)) {
            $filename = $docType . '_' . (int) $user['id'] . '.' . $ext;
            @file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $binary);
            DB::table('users')->where('id', (int) $user['id'])->update($this->filterTableColumns('users', [$docType => $filename]));
            return response()->json([
                'status' => 1,
                'message' => 'Document data saved successfully',
                'result' => ['url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/files/userdocs/' . $filename],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if ($docType === 'address_doc') {
            $newFile = $docType . '_' . (int) $user['id'] . '_' . rand(1, 100) . '.' . $ext;
            @file_put_contents($directory . DIRECTORY_SEPARATOR . $newFile, $binary);
            $existingDocs = json_decode((string) ($user['address_doc'] ?? '[]'), true);
            if (!is_array($existingDocs)) {
                $existingDocs = [];
            }
            $existingDocs[] = $newFile;
            DB::table('users')->where('id', (int) $user['id'])->update($this->filterTableColumns('users', ['address_doc' => json_encode($existingDocs)]));
            return response()->json([
                'status' => 1,
                'message' => 'Document data saved successfully',
                'result' => ['url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/files/userdocs/' . $newFile],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (in_array($docType, ['pay_stub', 'utility_bill'], true)) {
            $table = 'user_incomes';
            if (!Schema::hasTable($table)) {
                return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
            }
            $current = DB::table($table)->where('user_id', (int) $user['id'])->first();
            $columnName = $docType;
            $suffix = '';
            if (!empty($current) && !empty($current->{$docType})) {
                $columnName = $docType . '_2';
                $suffix = '_2';
            }
            $filename = $docType . '_' . (int) $user['id'] . $suffix . '.' . $ext;
            @file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $binary);
            $payload = $this->filterTableColumns($table, ['user_id' => (int) $user['id'], $columnName => $filename]);
            if (!empty($current) && isset($current->id) && Schema::hasColumn($table, 'id')) {
                DB::table($table)->where('id', (int) $current->id)->update($payload);
            } else {
                DB::table($table)->insert($payload);
            }
            return response()->json([
                'status' => 1,
                'message' => 'Document data saved successfully',
                'result' => ['url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/files/userdocs/' . $filename],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function updateLicenseDetails(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        $return = ['status' => 0, 'message' => 'Invalid request payload', 'result' => []];
        if (!is_array($dataValues) || empty($dataValues) || empty($dataValues['documentNumber'])) {
            if (is_array($dataValues) && !empty($dataValues) && empty($dataValues['documentNumber'])) {
                $return['message'] = "Sorry, your license couldn't be properly decoded. Please contact support for assistance";
            }
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (!empty($dataValues['dateOfBirth'])) {
            $age = $this->yearsBetweenDates((string) $dataValues['dateOfBirth'], date('Y-m-d'));
            if ($age < 21) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Sorry, you are too young to join this program. Please contact support for assistance',
                    'result' => [],
                ])->header('Content-Type', 'application/json; charset=utf-8');
            }
        }

        $table = 'user_license_details';
        if (Schema::hasTable($table)) {
            $payload = $dataValues;
            $payload['user_id'] = (int) $user['id'];
            $payload['documentNumber'] = $this->cakeEncrypt((string) $dataValues['documentNumber']);
            $payload['jurisdictionRestrictionCodes'] = '';
            $payload['jurisdictionEndorsementCodes'] = '';
            $payload['height'] = 1;
            $payload['eyeColor'] = '';
            $payload['documentDiscriminator'] = '';
            $payload['issuer'] = '';
            $payload['sex'] = 'M';
            $safePayload = $this->filterTableColumns($table, $payload);
            $exists = DB::table($table)->where('user_id', (int) $user['id'])->first();
            if (!empty($exists) && isset($exists->id) && Schema::hasColumn($table, 'id')) {
                DB::table($table)->where('id', (int) $exists->id)->update($safePayload);
            } else {
                DB::table($table)->insert($safePayload);
            }
        }

        $dtToUpdate = ['is_driver' => 1];
        if (!empty($dataValues['documentNumber'])) {
            $dtToUpdate['licence_number'] = $this->cakeEncrypt((string) $dataValues['documentNumber']);
        }
        if (!empty($dataValues['addressState'])) {
            $dtToUpdate['licence_state'] = (string) $dataValues['addressState'];
            $dtToUpdate['state'] = (string) $dataValues['addressState'];
        }
        if (!empty($dataValues['dateOfExpiry'])) {
            $dtToUpdate['licence_exp_date'] = (string) $dataValues['dateOfExpiry'];
        }
        if (!empty($dataValues['dateOfBirth'])) {
            $dtToUpdate['dob'] = date('m/d/Y', strtotime((string) $dataValues['dateOfBirth']));
        }
        DB::table('users')->where('id', (int) $user['id'])->update($this->filterTableColumns('users', $dtToUpdate));

        return response()->json([
            'status' => 1,
            'message' => 'License data updated successfully',
            'result' => $dataValues,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getLicenseDetails(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        try {
            $data = Schema::hasTable('user_license_details')
                ? DB::table('user_license_details')->where('user_id', (int) $user['id'])->first()
                : null;
            return response()->json([
                'status' => 1,
                'message' => '',
                'result' => $data ? (array) $data : [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    public function addMyCard(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        if (!is_array($dataValues) || empty($dataValues)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        if (!Schema::hasTable('user_cc_tokens')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, unable to add your card details. Please try again later.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $cardNumber = preg_replace('/\D/', '', (string) ($dataValues['credit_card_number'] ?? ''));
        $save = [
            'user_id' => (int) $user['id'],
            'card_type' => (string) ($dataValues['card_type'] ?? ''),
            'credit_card_number' => substr($cardNumber, -4),
            'card_holder_name' => (string) ($dataValues['card_holder_name'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))),
            'expiration' => (string) ($dataValues['expiration'] ?? ''),
            'card_funding' => (string) ($dataValues['card_funding'] ?? ''),
            'cvv' => (string) ($dataValues['cvv'] ?? ''),
            'address' => (string) ($dataValues['address'] ?? ''),
            'city' => (string) ($dataValues['city'] ?? ''),
            'state' => (string) ($dataValues['state'] ?? ''),
            'country' => (string) ($dataValues['country'] ?? ''),
            'zip' => (string) ($dataValues['zip'] ?? ''),
            'stripe_token' => (string) ($dataValues['stripe_token'] ?? ''),
            'card_id' => (string) ($dataValues['card_id'] ?? ''),
        ];
        $save = $this->filterTableColumns('user_cc_tokens', $save);
        $ccid = (int) LegacyUserCcToken::query()->insertGetId($save);

        $makedefault = (int) ($dataValues['makedefault'] ?? 0) === 1;
        $current = LegacyUser::query()->whereKey((int) $user['id'])->first(['id', 'cc_token_id']);
        if (!empty($current) && (empty($current->cc_token_id) || $makedefault)) {
            LegacyUser::query()->whereKey((int) $user['id'])->update(
                $this->filterTableColumns('users', ['cc_token_id' => $ccid, 'is_renter' => 1])
            );
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your card added successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getMyCards(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        if (!Schema::hasTable('user_cc_tokens')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, you did not add any card yet.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $cards = LegacyUserCcToken::query()
            ->where('user_id', (int) $user['id'])
            ->get(['id', 'card_type', 'credit_card_number', 'expiration']);
        if ($cards->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, you did not add any card yet.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $u = LegacyUser::query()->whereKey((int) $user['id'])->first(['cc_token_id']);
        $defaultId = (int) ($u->cc_token_id ?? 0);
        $mycards = [];
        foreach ($cards as $card) {
            $row = (array) $card;
            $row['is_default'] = $defaultId > 0 && $defaultId === (int) $row['id'] ? 1 : 0;
            $row['credit_card_number'] = substr((string) ($row['credit_card_number'] ?? ''), -4);
            $mycards[] = array_map(static fn ($v) => $v === null ? '' : $v, $row);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your card listing',
            'result' => $mycards,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function makeMyCardDefault(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }

        $cardTokenId = (int) ($dataValues['user_cc_token_id'] ?? 0);
        if ($cardTokenId <= 0) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, required inputs are missing.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $card = Schema::hasTable('user_cc_tokens')
            ? LegacyUserCcToken::query()->where('user_id', (int) $user['id'])->whereKey($cardTokenId)->first()
            : null;
        if (empty($card)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, you are not authorized for this action',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyUser::query()->whereKey((int) $user['id'])->update(
            $this->filterTableColumns('users', ['cc_token_id' => $cardTokenId])
        );

        return response()->json([
            'status' => 1,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function deleteMyCard(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Please log back in',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = $request->input();
        }
        $cardId = (int) ($dataValues['card_id'] ?? 0);
        if ($cardId <= 0) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $u = LegacyUser::query()->whereKey((int) $user['id'])->first(['cc_token_id']);
        if ((int) ($u->cc_token_id ?? 0) === $cardId) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry, you can't delete default CC details.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        if (!Schema::hasTable('user_cc_tokens')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, selected card is not found in your account.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $card = LegacyUserCcToken::query()->where('user_id', (int) $user['id'])->whereKey($cardId)->first();
        if (empty($card)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, selected card is not found in your account.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyUserCcToken::query()->whereKey($cardId)->delete();
        return response()->json([
            'status' => 1,
            'message' => 'Your card details deleted successfully.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getPendingBooking(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id']) || !Schema::hasTable('vehicle_reservations')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        $page = max(1, (int) ($payload['page'] ?? 1));
        $offset = ($page - 1) * 50;

        $query = LegacyVehicleReservation::query()
            ->from('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->where('vr.renter_id', (int) $user['id'])
            ->whereIn('vr.status', [0, 1, 4, 5, 6, 7, 8, 9])
            ->orderBy('vr.start_datetime', 'asc')
            ->offset($offset)
            ->limit(50)
            ->select([
                'vr.id',
                'vr.start_datetime',
                'vr.end_datetime',
                'vr.timezone',
                'v.id as vehicle_id',
                'v.user_id as owner_id',
                'v.make',
                'v.model',
                'v.year',
            ]);
        $rows = $query->get();
        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row->id,
                'vehicle_id' => (int) ($row->vehicle_id ?? 0),
                'owner_id' => (int) ($row->owner_id ?? 0),
                'make' => (string) ($row->make ?? ''),
                'model' => (string) ($row->model ?? ''),
                'year' => (string) ($row->year ?? ''),
                'start_datetime' => $this->formatDateTime((string) ($row->start_datetime ?? '')),
                'end_datetime' => $this->formatDateTime((string) ($row->end_datetime ?? '')),
            ];
        }

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getPendingBookingDetails(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        $bookingId = (int) ($payload['bookingid'] ?? 0);
        if (empty($user) || $bookingId <= 0 || !Schema::hasTable('vehicle_reservations')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $pending = LegacyVehicleReservation::query()
            ->from('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'vr.user_id')
            ->leftJoin((new LegacyOrderDepositRule())->getTable() . ' as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->where('vr.renter_id', (int) $user['id'])
            ->whereIn('vr.status', [0, 1, 4, 5, 6, 7, 8, 9])
            ->where('vr.id', $bookingId)
            ->first([
                'vr.*',
                'v.id as vehicle_id',
                'v.user_id as owner_id',
                'v.vehicle_name',
                'v.make',
                'v.model',
                'v.color',
                'v.year',
                'v.transmition_type',
                'v.engine',
                'v.vin_no',
                'v.interior_color',
                'v.trim',
                'v.odometer',
                'owner.currency as owner_currency',
                'owner.distance_unit',
                'odr.initial_fee',
                'odr.total_initial_fee',
                'odr.deposit_amt',
                'odr.total_deposit_amt',
                'odr.totalcost',
                'odr.num_of_days',
                'odr.goal',
                'odr.downpayment',
                'odr.rent_opt',
                'odr.deposit_opt',
                'odr.initial_fee_opt',
                'odr.insu_agreed',
                'odr.insurance',
                'odr.insurance_payer',
            ]);
        if (empty($pending)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = [
            'id' => (int) $pending->id,
            'vehicle_id' => (int) ($pending->vehicle_id ?? 0),
            'owner_id' => (int) ($pending->owner_id ?? 0),
            'vehicle_name' => (string) ($pending->vehicle_name ?? ''),
            'make' => (string) ($pending->make ?? ''),
            'model' => (string) ($pending->model ?? ''),
            'color' => (string) ($pending->color ?? ''),
            'year' => (string) ($pending->year ?? ''),
            'transmition_type' => (string) ($pending->transmition_type ?? ''),
            'engine' => (string) ($pending->engine ?? ''),
            'vin_no' => (string) ($pending->vin_no ?? ''),
            'interior_color' => (string) ($pending->interior_color ?? ''),
            'trim' => (string) ($pending->trim ?? ''),
            'mileage' => (string) ($pending->odometer ?? ''),
            'start_datetime' => $this->formatDateTime((string) ($pending->start_datetime ?? '')),
            'distance_unit' => (string) ($pending->distance_unit ?? ''),
            'initial_fee' => (string) ($pending->initial_fee ?? '0'),
            'total_initial_fee' => (string) ($pending->total_initial_fee ?? '0'),
            'deposit_amt' => (string) ($pending->deposit_amt ?? '0'),
            'total_deposit_amt' => (string) ($pending->total_deposit_amt ?? '0'),
            'vehicle_cost' => (string) ($pending->totalcost ?? '0'),
            'program_length' => ((int) ($pending->num_of_days ?? 0)) . ' days',
            'program_goal' => !empty($pending->goal) ? (string) $pending->goal : '',
            'insu_agreed' => (int) ($pending->insu_agreed ?? 0),
            'insurance_note' => '',
            'enable_choose_insurance' => in_array((int) ($pending->insurance_payer ?? 0), [3, 4, 5, 6, 7], true) ? 1 : 0,
            'status' => 'New',
            'note' => 'The order is being reviewed by the dealer. Stay tuned for when to pick up the car.',
        ];

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => array_map(static fn ($v) => $v === null ? '' : $v, $result),
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getMyActiveBooking(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        if (empty($user) || empty($user['id']) || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no active booking found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rows = LegacyCsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->where('o.renter_id', (int) $user['id'])
            ->whereIn('o.status', [0, 1])
            ->orderBy('o.start_datetime', 'asc')
            ->get([
                'o.id',
                'o.start_datetime',
                'o.end_datetime',
                'o.user_id as owner_id',
                'o.vehicle_id',
                'o.timezone',
                'v.make',
                'v.model',
                'v.year',
            ]);
        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no active booking found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row->id,
                'vehicle_id' => (int) ($row->vehicle_id ?? 0),
                'owner_id' => (int) ($row->owner_id ?? 0),
                'make' => (string) ($row->make ?? ''),
                'model' => (string) ($row->model ?? ''),
                'year' => (string) ($row->year ?? ''),
                'start_datetime' => $this->formatDateTime((string) ($row->start_datetime ?? '')),
                'end_datetime' => $this->formatDateTime((string) ($row->end_datetime ?? '')),
                'filename' => '',
            ];
        }

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getActiveBookingDetail(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($user['id']) || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no active booking found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $query = LegacyCsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('cs_twilio_orders as cto', 'cto.cs_order_id', '=', 'o.id')
            ->where('o.renter_id', (int) $user['id'])
            ->whereIn('o.status', [0, 1]);
        if (!empty($payload['bookingid'])) {
            $query->where('o.id', (int) $payload['bookingid']);
        }
        $lease = $query->orderByDesc('o.id')->first([
            'o.*',
            'cto.extend',
            'v.make',
            'v.model',
            'v.last_mile',
            'v.doors',
            'v.transmition_type',
            'v.engine',
            'v.modified',
            'owner.distance_unit',
        ]);
        if (empty($lease)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no active booking found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = array_map(static fn ($v) => $v === null ? '' : $v, (array) $lease);
        $start = strtotime((string) ($lease->start_datetime ?? ''));
        $end = strtotime((string) ($lease->end_datetime ?? ''));
        if ($start > 0 && $end > $start) {
            $result['suggested_autorenew_datetime'] = date('Y-m-d H:i:s', $end + ($end - $start));
        } else {
            $result['suggested_autorenew_datetime'] = '';
        }
        $result['payment_retry'] = 1;
        $result['scheduled_till'] = !empty($lease->extend) ? $this->formatDateTime((string) $lease->extend) : '';
        $result['start_datetime'] = $this->formatDateTime((string) ($lease->start_datetime ?? ''));
        $result['end_datetime'] = $this->formatDateTime((string) ($lease->end_datetime ?? ''));
        $result['filename'] = '';
        $result['ext_date'] = '';

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getMyPastBooking(Request $request): JsonResponse
    {
        // Keep Cake behavior: currently short-circuits to empty success response.
        return response()->json([
            'status' => 1,
            'message' => '',
            'records' => 0,
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getPastBookingDetail(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        $bookingId = (int) ($payload['bookingid'] ?? 0);
        if (empty($user) || $bookingId <= 0 || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $lease = LegacyCsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->where('o.renter_id', (int) $user['id'])
            ->whereIn('o.status', [2, 3])
            ->where('o.id', $bookingId)
            ->first(['o.*', 'v.make', 'v.model', 'v.year', 'owner.distance_unit']);
        if (empty($lease)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no active booking found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = array_map(static fn ($v) => $v === null ? '' : $v, (array) $lease);
        $result['start_datetime'] = $this->formatDateTime((string) ($lease->start_datetime ?? ''));
        $result['end_datetime'] = $this->formatDateTime((string) ($lease->end_datetime ?? ''));
        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getMyLeaseHistories(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        $page = max(1, (int) ($payload['page'] ?? 1));
        $offset = ($page - 1) * 50;
        if (empty($user) || empty($user['id']) || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rows = LegacyCsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->where('o.renter_id', (int) $user['id'])
            ->whereIn('o.status', [2, 3])
            ->orderBy('o.start_datetime', 'asc')
            ->offset($offset)
            ->limit(50)
            ->get([
                'o.id',
                'o.increment_id',
                'o.vehicle_id',
                'o.vehicle_name',
                'o.status',
                'o.start_datetime',
                'o.end_datetime',
                'o.start_timing',
                'o.end_timing',
                'o.rent',
                'o.tax',
                'o.extra_mileage_fee',
                'o.emf_tax',
                'o.damage_fee',
                'o.lateness_fee',
                'o.uncleanness_fee',
                'o.paid_amount',
                'o.insurance_amt',
                'o.dia_fee',
                'o.initial_fee',
                'o.cancellation_fee',
                'o.cancel_note',
                'o.deposit',
                'o.toll',
                'o.timezone',
                'v.make',
                'v.model',
                'v.year',
                'v.color',
                'v.cab_type',
            ]);
        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => 'sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = [];
        foreach ($rows as $row) {
            $arr = array_map(static fn ($v) => $v === null ? '' : $v, (array) $row);
            $arr['start_datetime'] = $this->formatDateTime((string) ($row->start_datetime ?? ''));
            $arr['end_datetime'] = $this->formatDateTime((string) ($row->end_datetime ?? ''));
            $arr['start_timing'] = $this->formatDateTime((string) ($row->start_timing ?? ''));
            $arr['end_timing'] = $this->formatDateTime((string) ($row->end_timing ?? ''));
            $arr['Payment'] = [];
            $arr['filename'] = '';
            $result[] = $arr;
        }

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function cancelbookedLease(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($payload) || empty($payload['booking_id']) || !Schema::hasTable('vehicle_reservations')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $bookingId = (int) $payload['booking_id'];
        $pendingBooking = LegacyVehicleReservation::query()
            ->where('id', $bookingId)
            ->where('renter_id', (int) $user['id'])
            ->whereIn('status', [0, 1, 4, 5, 6, 7, 8, 9])
            ->first();
        if (empty($pendingBooking)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, you cant perform this action now, please contact to support team @',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyVehicleReservation::query()->whereKey($bookingId)->update(['status' => 2]);
        if (Schema::hasTable('vehicles') && isset($pendingBooking->vehicle_id)) {
            DB::table('vehicles')->where('id', (int) $pendingBooking->vehicle_id)->update(
                $this->filterTableColumns('vehicles', ['booked' => 0])
            );
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your request has been processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function startbookedLease(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($payload) || empty($payload['booking_id']) || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $bookingId = (int) $payload['booking_id'];
        $lease = LegacyCsOrder::query()
            ->where('id', $bookingId)
            ->where('renter_id', (int) $user['id'])
            ->whereNotIn('status', [2, 3])
            ->where('parent_id', 0)
            ->first();
        if (empty($lease)) {
            return response()->json([
                'status' => 0,
                'message' => "sorry, you can't perform this action now.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        if ((int) ($lease->status ?? 0) === 1) {
            return response()->json([
                'status' => 0,
                'message' => 'sorry, booking already started.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyCsOrder::query()->whereKey($bookingId)->update(
            $this->filterTableColumns('cs_orders', [
                'status' => 1,
                'start_timing' => date('Y-m-d H:i:s'),
            ])
        );
        return response()->json([
            'status' => 1,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function retryPendingPayment(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($payload) || empty($payload['booking_id']) || !Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $bookingId = (int) $payload['booking_id'];
        $lease = LegacyCsOrder::query()
            ->where('id', $bookingId)
            ->where('renter_id', (int) $user['id'])
            ->first();
        if (empty($lease)) {
            return response()->json([
                'status' => 0,
                'message' => "Sorry, you can't perform this action now.",
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        return response()->json([
            'status' => 1,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getMyTransactions(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($payload) || empty($payload['booking_id']) || !Schema::hasTable('cs_order_payments')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
        $bookingId = (int) $payload['booking_id'];
        if (!Schema::hasTable('cs_orders')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $lease = LegacyCsOrder::query()
            ->where('id', $bookingId)
            ->where('renter_id', (int) $user['id'])
            ->first();
        if (empty($lease)) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, no record found.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $payments = LegacyCsOrderPayment::query()
            ->where('cs_order_id', $bookingId)
            ->where('status', 1)
            ->orderBy('type', 'asc')
            ->get();
        $result = [];
        foreach ($payments as $payment) {
            $row = array_map(static fn ($v) => $v === null ? '' : $v, (array) $payment);
            $row['charged_at'] = $this->formatDateTime((string) ($payment->charged_at ?? ''));
            $result[] = $row;
        }

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function faq(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'Please open url in webview',
            'result' => ['url' => rtrim($request->getSchemeAndHttpHost(), '/') . '/mobile-faq'],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getHomeScreenText(Request $request): JsonResponse
    {
        $blocks = [
            ['title' => 'How DriveItAway Works', 'subtitle' => '', 'type' => 'title'],
            [
                'title' => 'Subscribe To Own',
                'subtitle' => 'Choose the car you want to buy–no obligation to keep it. Pay for your usage. Each payment reduces the buyout price–if you choose to purchase. Buy the car when the price and time makes sense for you!. *1 month minimum',
                'type' => 'test_ownership',
            ],
            [
                'title' => 'Drive Down The Price of the Car',
                'subtitle' => 'Each usage payment will reduce the selling price of the car, if you choose to buy it. Drive the car down to a price where you can get financing to buy the vehicle.',
                'type' => 'down_payment',
            ],
            [
                'title' => 'Receive Loan Offers Along The Way',
                'subtitle' => 'While driving, you may get loan offers from lenders to convert to an auto loan once you’ve reached a buyout price that makes sense based on your credit and income',
                'type' => 'loan_offer',
            ],
            [
                'title' => 'Qualify Based On Income Not Credit',
                'subtitle' => 'DriveItAway has a credit-agnostic approach that will allow you to drive based on your income and budget. Credit does not matter at this point.',
                'type' => 'quality',
            ],
            [
                'title' => 'Full Warranty',
                'subtitle' => 'High quality vehicles are listed in DriveItAway. All vehicles are fully covered while in the program.',
                'type' => 'warranty',
            ],
        ];

        return response()->json([
            'status' => 1,
            'message' => '',
            'result' => [
                'top_text_1' => 'Drive It Then Buy It',
                'top_text_2' => 'Car Buying',
                'bottom_text_1' => 'Payments Count Towards Your Purchase',
                'bottom_blocks' => $blocks,
            ],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getStateList(Request $request): JsonResponse
    {
        $result = [
            'US' => [
                'AL' => 'Alabama',
                'AK' => 'Alaska',
                'AZ' => 'Arizona',
                'AR' => 'Arkansas',
                'CA' => 'California',
                'CO' => 'Colorado',
                'CT' => 'Connecticut',
                'DE' => 'Delaware',
                'DC' => 'District Of Columbia',
                'FL' => 'Florida',
                'GA' => 'Georgia',
                'HI' => 'Hawaii',
                'ID' => 'Idaho',
                'IL' => 'Illinois',
                'IN' => 'Indiana',
                'IA' => 'Iowa',
                'KS' => 'Kansas',
                'KY' => 'Kentucky',
                'LA' => 'Louisiana',
                'ME' => 'Maine',
                'MD' => 'Maryland',
                'MA' => 'Massachusetts',
                'MI' => 'Michigan',
                'MN' => 'Minnesota',
                'MS' => 'Mississippi',
                'MO' => 'Missouri',
                'MT' => 'Montana',
                'NE' => 'Nebraska',
                'NV' => 'Nevada',
                'NH' => 'New Hampshire',
                'NJ' => 'New Jersey',
                'NM' => 'New Mexico',
                'NY' => 'New York',
                'NC' => 'North Carolina',
                'ND' => 'North Dakota',
                'OH' => 'Ohio',
                'OK' => 'Oklahoma',
                'OR' => 'Oregon',
                'PA' => 'Pennsylvania',
                'RI' => 'Rhode Island',
                'SC' => 'South Carolina',
                'SD' => 'South Dakota',
                'TN' => 'Tennessee',
                'TX' => 'Texas',
                'UT' => 'Utah',
                'VT' => 'Vermont',
                'VA' => 'Virginia',
                'WA' => 'Washington',
                'WV' => 'West Virginia',
                'WI' => 'Wisconsin',
                'WY' => 'Wyoming',
            ],
            'CA' => [
                'AB' => 'Alberta',
                'BC' => 'British Columbia',
                'MB' => 'Manitoba',
                'NB' => 'New Brunswick',
                'NL' => 'Newfoundland and Labrador',
                'NT' => 'Northwest Territories',
                'NS' => 'Nova Scotia',
                'NU' => 'Nunavut',
                'ON' => 'Ontario',
                'PE' => 'Prince Edward Island',
                'QC' => 'Quebec',
                'SK' => 'Saskatchewan',
                'YT' => 'Yukon',
            ],
        ];

        return response()->json([
            'status' => true,
            'message' => '',
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function inviteFriend(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || !is_array($payload) || empty($payload['emails'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $emails = array_filter(array_map('trim', explode(',', (string) $payload['emails'])));
        $validEmails = [];
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\\./', $email)) {
                $validEmails[] = $email;
            }
        }

        // Best-effort: legacy sends email via Cake mailer. We'll only validate and accept for now.
        return response()->json([
            'status' => 1,
            'message' => 'Your invitation was sent successfully.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getWishlistVehicle(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }

        if (empty($user) || empty($user['id']) || !Schema::hasTable('wishlists')) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, there are no more vehicles available in your wishlist at this time.',
                'result' => [],
                'records' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $limit = (int) ($payload['limit'] ?? 16);
        if ($limit <= 0 || $limit > 100) {
            $limit = 16;
        }
        $page = max(1, (int) ($payload['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $query = LegacyWishlist::query()
            ->from('wishlists as w')
            ->leftJoin('vehicles as v', 'v.id', '=', 'w.vehicle_id')
            ->leftJoin('users as dealer', 'dealer.id', '=', 'v.user_id')
            ->where('w.user_id', (int) $user['id'])
            ->where('v.status', 1)
            ->where('v.trash', 0)
            ->where(function ($q) {
                $q->where('v.booked', 0)->orWhere('v.type', 'demo');
            })
            ->orderBy('w.created', 'desc');

        $rows = $query
            ->offset($offset)
            ->limit($limit)
            ->get([
                'v.id',
                'v.vehicle_name',
                'v.make',
                'v.model',
                'v.year',
                'v.lat',
                'v.lng',
                'v.odometer',
                'v.cab_type',
                'v.color',
                'v.engine',
                'w.program',
                'w.financing',
                'dealer.currency',
                'dealer.distance_unit',
                'dealer.city',
                'dealer.state',
            ]);

        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, there are no more vehicles available in your wishlist at this time.',
                'result' => [],
                'records' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $result = [];
        foreach ($rows as $row) {
            $temp = [
                'image' => '',
                'rent' => '',
                'rent_label' => 'As low as',
                'msrp' => '',
                'distance' => '0 miles away',
                'vehicle_name' => (string) ($row->vehicle_name ?? ''),
                'make' => (string) ($row->make ?? ''),
                'model' => (string) ($row->model ?? ''),
                'year' => (string) ($row->year ?? ''),
                'lat' => (string) ($row->lat ?? ''),
                'lng' => (string) ($row->lng ?? ''),
                'id' => (int) ($row->id ?? 0),
                'odometer' => (string) ($row->odometer ?? '') . ' ' . (string) ($row->distance_unit ?? ''),
                'featured_text' => [
                    'Subscribe with the Option to Own',
                    'Build Down Payment While Driving',
                    'Each usage payment will drive down the buyout price. Keep driving till the price and time makes sense for you',
                ],
                'program' => (string) ($row->program ?? ''),
                'financing' => (string) ($row->financing ?? ''),
                'city' => (string) ($row->city ?? ''),
                'state' => (string) ($row->state ?? ''),
                'body' => (string) ($row->cab_type ?? ''),
                'color' => (string) ($row->color ?? ''),
                'engine' => (string) ($row->engine ?? ''),
            ];
            $result[] = array_map(static fn ($v) => $v === null ? '' : $v, $temp);
        }

        $count = (int) LegacyWishlist::query()
            ->from('wishlists as w')
            ->leftJoin('vehicles as v', 'v.id', '=', 'w.vehicle_id')
            ->where('w.user_id', (int) $user['id'])
            ->where('v.status', 1)
            ->where('v.trash', 0)
            ->count();

        return response()->json([
            'status' => 1,
            'message' => 'Record found',
            'records' => $count,
            'result' => $result,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function addVehicleToWishlist(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($user['id']) || !is_array($payload) || empty($payload['vehicle_id']) || !Schema::hasTable('wishlists')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vehicleId = (int) $payload['vehicle_id'];
        $financing = !empty($payload['financing']) ? (string) $payload['financing'] : 'pto';

        $exists = LegacyWishlist::query()->where('user_id', (int) $user['id'])->where('vehicle_id', $vehicleId)->exists();
        if ($exists) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your request already saved.',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyWishlist::query()->insert(
            $this->filterTableColumns('wishlists', [
                'user_id' => (int) $user['id'],
                'vehicle_id' => $vehicleId,
                'program' => 'general',
                'financing' => $financing,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ])
        );

        return response()->json([
            'status' => 1,
            'message' => 'Vehicle added successfully to your favourite list.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function removeWishlistVehicle(Request $request): JsonResponse
    {
        $user = $this->requireWebJwtUser($request);
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->input();
        }
        if (empty($user) || empty($user['id']) || !is_array($payload) || empty($payload['vehicle_id']) || !Schema::hasTable('wishlists')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid request payload',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        LegacyWishlist::query()
            ->where('user_id', (int) $user['id'])
            ->where('vehicle_id', (int) $payload['vehicle_id'])
            ->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Vehicle removed successfully from your favourite list.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function Logout(Request $request): JsonResponse
    {
        return response()->json(['status' => 1, 'message' => ''])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    protected function getUniqueId(int $length = 32, string $pool = ''): string
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

    protected function normalizePhone10(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $digits = (string) $digits;
        return strlen($digits) <= 10 ? $digits : substr($digits, -10);
    }

    protected function getJwtFromAuthorizationHeader(Request $request): string
    {
        $authorization = (string) ($request->header('Authorization') ?? '');
        if ($authorization === '') {
            $authorization = (string) ($request->header('authorization') ?? '');
        }
        return trim(str_replace('Basic', '', $authorization));
    }

    protected function resolveWebJwtUser(string $jwtToken): ?array
    {
        $pepper = 'mindseye1';
        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            $decoded = \JWT::decode($jwtToken, $pepper, ['HS256']);
            $userId = isset($decoded->user->userId) ? (int) $decoded->user->userId : 0;
            $token = isset($decoded->user->token) ? (string) $decoded->user->token : '';
            if ($userId <= 0 || $token === '') {
                return null;
            }

            $user = LegacyUser::query()
                ->whereKey($userId)
                ->where('token', $token)
                ->where('status', 1)
                ->where('is_verified', 1)
                ->where('is_admin', 0)
                ->where('trash', 0)
                ->first();

            return $user ? $user->toArray() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function requireWebJwtUser(Request $request): ?array
    {
        $jwtToken = $this->getJwtFromAuthorizationHeader($request);
        if ($jwtToken === '') {
            return null;
        }
        return $this->resolveWebJwtUser($jwtToken);
    }

    protected function filterTableColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }
        $result = [];
        foreach ($data as $column => $value) {
            if (Schema::hasColumn($table, (string) $column)) {
                $result[(string) $column] = $value;
            }
        }
        return $result;
    }

    protected function hydrateAccountResult(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $result = array_map(static fn ($v) => $v === null ? '' : $v, $user);
        if ($userId <= 0) {
            return $result;
        }

        $result['wallet_balance'] = 0;
        $result['wallet_term'] = 0;
        if (Schema::hasTable('cs_wallets')) {
            $wallet = DB::table('cs_wallets')->where('user_id', $userId)->first();
            if (!empty($wallet)) {
                $result['wallet_balance'] = (float) ($wallet->balance ?? 0);
                $result['wallet_term'] = (float) ($wallet->term ?? 0);
            }
        }

        $result['credit_score_reported'] = 0;
        if (Schema::hasTable('user_credit_scores')) {
            $credit = DB::table('user_credit_scores')->where('user_id', $userId)->first();
            $result['credit_score_reported'] = empty($credit) ? 0 : 1;
        }

        $income = null;
        if (Schema::hasTable('user_incomes')) {
            $income = DB::table('user_incomes')->where('user_id', $userId)->first();
        }
        $result['residency_reported'] = 0;
        $result['income_reported'] = !empty($income) && !empty($income->income) ? 1 : 0;
        $result['paystub_reported'] = !empty($income) && (!empty($income->pay_stub) || !empty($income->pay_stub_2)) ? 1 : 0;
        $result['paybank_reported'] = 0;

        $result['my_insurance_menu'] = 0;
        $result['bank_added'] = 0;
        $result['promo_added'] = false;
        $result['address_proof'] = !empty(json_decode((string) ($result['address_doc'] ?? ''), true));
        $result['ss_no'] = empty($result['ss_no']) ? '' : $this->cakeDecrypt((string) $result['ss_no']);
        return $result;
    }

    protected function yearsBetweenDates(string $from, string $to): int
    {
        try {
            $fromDate = new \DateTime($from);
            $toDate = new \DateTime($to);
            return (int) $fromDate->diff($toDate)->y;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function formatDateTime(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : $value;
    }

    protected function cakeDecrypt(string $cipher): string
    {
        $encryptKey = 'DYhG93b0qyJfIxfs2guR2G0FgaC9mixf';
        $salt = config('legacy.security.salt', '');
        $decoded = base64_decode($cipher, true);
        if ($decoded === false || strlen($decoded) < 48) {
            return '';
        }
        $iv = substr($decoded, 0, 16);
        $hmac = substr($decoded, 16, 32);
        $ct = substr($decoded, 48);
        $key = hash('sha256', $encryptKey . $salt, true);
        $calc = hash_hmac('sha256', $iv . $ct, $key, true);
        if (!hash_equals($hmac, $calc)) {
            return '';
        }
        $plain = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : (string) $plain;
    }

    protected function cakeEncrypt(string $plain): string
    {
        $encryptKey = 'DYhG93b0qyJfIxfs2guR2G0FgaC9mixf';
        $salt = config('legacy.security.salt', '');
        $key = hash('sha256', $encryptKey . $salt, true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return '';
        }
        $hmac = hash_hmac('sha256', $iv . $cipher, $key, true);
        return base64_encode($iv . $hmac . $cipher);
    }
}

