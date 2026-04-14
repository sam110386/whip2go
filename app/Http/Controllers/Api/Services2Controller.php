<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * V2 Mobile API Services Controller.
 *
 * Contains the core business logic for all V2 mobile API endpoints.
 * Ported from CakePHP Plugin/Api/Controller/Services2Controller.php.
 *
 * V2 shares the same method signatures as V1 (ServicesController) but has
 * slightly different internal query logic and result formatting in some methods.
 * Methods that are identical to V1 delegate directly; methods with V2-specific
 * differences are overridden here.
 *
 * CakePHP → Laravel mapping:
 *   $this->request->data           → $request->all() / $request->input('key')
 *   $this->loadModel('X')          → DB::table('table')
 *   $this->response->body(json)    → return response()->json($data)
 *   Configure::read('key')         → config('legacy.key')
 *   $this->userObj                 → session('api_user')
 *   $this->autoRender = false      → (not needed)
 */
class Services2Controller extends Controller
{
    protected static int $_STATUSFAIL = 0;
    protected static int $_STATUSSUCCESS = 1;
    protected static int $_resultlimit = 10;

    protected string $_xsecurity = '7750ca3559e5b8e1f442103368fc16';

    protected array $_userfields = [
        'id', 'first_name', 'middle_name', 'last_name', 'email', 'photo',
        'contact_number', 'address', 'ss_no', 'dob', 'city', 'state', 'zip',
        'licence_type', 'licence_number', 'licence_state', 'licence_exp_date',
        'is_renter', 'is_owner', 'is_driver', 'is_passenger',
        'license_doc_1', 'license_doc_2', 'is_staff', 'staff_parent',
        'checkr_status', 'auto_renew', 'uberlyft_verified',
        'bank', 'currency', 'address_doc',
    ];

    protected array $orderbyArray = [
        '1' => ['vehicles.msrp', 'DESC'],
        '2' => ['vehicles.msrp', 'ASC'],
        '3' => ['vehicles.make', 'ASC'],
        '4' => ['vehicles.model', 'ASC'],
        '5' => ['vehicles.make', 'DESC'],
        '6' => ['vehicles.model', 'DESC'],
        '7' => ['vehicles.day_rent', 'ASC'],
        '8' => ['vehicles.day_rent', 'DESC'],
    ];

    protected function getUserObj(Request $request): ?object
    {
        $userId = session('api_user_id');
        if (!$userId) {
            return null;
        }
        return DB::table('users')->where('id', $userId)->first();
    }

    /* ================================================================== */
    /*  AUTH                                                               */
    /* ================================================================== */

    public function login(Request $request): JsonResponse
    {
        $data = $request->all();
        $phoneNumber = trim($data['phone_number'] ?? '');

        if (empty($phoneNumber)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Phone number is required', 'result' => []]);
        }

        $user = DB::table('users')
            ->where('contact_number', $phoneNumber)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        if (!empty($data['password']) && !Hash::check($data['password'], $user->password)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid credentials', 'result' => []]);
        }

        session(['api_user_id' => $user->id]);

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'result' => (array) $userData,
        ]);
    }

    public function loginadvance(Request $request): JsonResponse
    {
        $data = $request->all();
        $phoneNumber = trim($data['phone_number'] ?? '');

        if (empty($phoneNumber)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Phone number is required', 'result' => []]);
        }

        $user = DB::table('users')
            ->where('contact_number', $phoneNumber)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        session(['api_user_id' => $user->id]);

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        $activeBooking = DB::table('cs_orders')
            ->where('renter_id', $user->id)
            ->whereIn('status', [0, 1])
            ->first();

        $result = (array) $userData;
        $result['has_active_booking'] = !empty($activeBooking);

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'result' => $result,
        ]);
    }

    public function ssologin(Request $request): JsonResponse
    {
        $data = $request->all();
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Email is required', 'result' => []]);
        }

        $user = DB::table('users')->where('email', $email)->where('is_deleted', 0)->first();

        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        session(['api_user_id' => $user->id]);
        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $userData]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $userData]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->all();

        $required = ['phone_number', 'first_name', 'last_name', 'password', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                return response()->json(['status' => self::$_STATUSFAIL, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required', 'result' => []]);
            }
        }

        $exists = DB::table('users')
            ->where(function ($q) use ($data) {
                $q->where('contact_number', trim($data['phone_number']))
                  ->orWhere('email', trim($data['email']));
            })
            ->where('is_deleted', 0)
            ->first();

        if ($exists) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User already exists with this phone or email', 'result' => []]);
        }

        $activationCode = rand(100000, 999999);

        $userId = DB::table('users')->insertGetId([
            'first_name'      => trim($data['first_name']),
            'middle_name'     => trim($data['middle_name'] ?? ''),
            'last_name'       => trim($data['last_name']),
            'email'           => trim($data['email']),
            'contact_number'  => trim($data['phone_number']),
            'password'        => Hash::make($data['password']),
            'activation_code' => $activationCode,
            'is_renter'       => 1,
            'status'          => 0,
            'created'         => now(),
            'modified'        => now(),
        ]);

        if (!empty($data['photo_data']) && !empty($data['fileformat'])) {
            $photoPath = 'uploads/users/' . $userId . '.' . $data['fileformat'];
            $decoded = base64_decode($data['photo_data']);
            if ($decoded !== false) {
                file_put_contents(public_path($photoPath), $decoded);
                DB::table('users')->where('id', $userId)->update(['photo' => $photoPath]);
            }
        }

        $userData = DB::table('users')->select($this->_userfields)->where('id', $userId)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Registration successful. Please verify your account.', 'result' => (array) $userData]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->all();
        $phoneNumber = trim($data['phone_number'] ?? '');

        if (empty($phoneNumber)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Phone number is required', 'result' => []]);
        }

        $user = DB::table('users')->where('contact_number', $phoneNumber)->where('is_deleted', 0)->first();
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        $verifyToken = Str::random(32);
        DB::table('users')->where('id', $user->id)->update(['verify_token' => $verifyToken, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Password reset instructions sent', 'result' => ['user_id' => $user->id, 'verify_token' => $verifyToken]]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['user_id']) || empty($data['password']) || empty($data['verify_token'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Missing required fields', 'result' => []]);
        }

        $user = DB::table('users')->where('id', $data['user_id'])->where('verify_token', $data['verify_token'])->first();
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid token', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($data['password']), 'verify_token' => null, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Password updated successfully', 'result' => []]);
    }

    public function resendActivation(Request $request): JsonResponse
    {
        $data = $request->all();
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Email is required', 'result' => []]);
        }

        $user = DB::table('users')->where('email', $email)->where('is_deleted', 0)->first();
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        $activationCode = rand(100000, 999999);
        DB::table('users')->where('id', $user->id)->update(['activation_code' => $activationCode, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Activation code resent', 'result' => []]);
    }

    public function verifyAccount(Request $request): JsonResponse
    {
        $data = $request->all();
        $code = trim($data['activationCode'] ?? '');

        if (empty($code)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Activation code is required', 'result' => []]);
        }

        $user = DB::table('users')->where('activation_code', $code)->where('status', 0)->first();
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid activation code', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['status' => 1, 'activation_code' => null, 'modified' => now()]);
        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Account verified', 'result' => (array) $userData]);
    }

    /* ================================================================== */
    /*  ACCOUNT / PROFILE                                                  */
    /* ================================================================== */

    public function getmyaccountDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $userData]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['old_password']) || empty($data['password'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Both old and new password are required', 'result' => []]);
        }

        if (!Hash::check($data['old_password'], $user->password)) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Old password is incorrect', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($data['password']), 'modified' => now()]);
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Password changed successfully', 'result' => []]);
    }

    public function updateAccountDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $updateData = [];
        $allowedFields = ['first_name', 'middle_name', 'last_name', 'address', 'city', 'state', 'zip', 'dob', 'auto_renew'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = trim($data[$field]);
            }
        }

        if (!empty($data['photo_data']) && !empty($data['fileformat'])) {
            $photoPath = 'uploads/users/' . $user->id . '.' . $data['fileformat'];
            $decoded = base64_decode($data['photo_data']);
            if ($decoded !== false) {
                file_put_contents(public_path($photoPath), $decoded);
                $updateData['photo'] = $photoPath;
            }
        }

        if (!empty($updateData)) {
            $updateData['modified'] = now();
            DB::table('users')->where('id', $user->id)->update($updateData);
        }

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Account updated', 'result' => (array) $userData]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['doc_type']) || empty($data['doc_data']) || empty($data['fileformat'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Document type, data, and format are required', 'result' => []]);
        }

        $docPath = 'uploads/documents/' . $user->id . '_' . $data['doc_type'] . '.' . $data['fileformat'];
        $decoded = base64_decode($data['doc_data']);
        if ($decoded === false) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid document data', 'result' => []]);
        }

        file_put_contents(public_path($docPath), $decoded);
        DB::table('users')->where('id', $user->id)->update([$data['doc_type'] => $docPath, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Document uploaded successfully', 'result' => ['path' => $docPath]]);
    }

    public function updateLicenseDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $updateData = [
            'licence_number'   => $data['documentNumber'] ?? '',
            'licence_state'    => $data['addressState'] ?? ($data['issuer'] ?? ''),
            'licence_exp_date' => $data['dateOfExpiry'] ?? '',
            'dob'              => $data['dateOfBirth'] ?? '',
            'first_name'       => $data['givenName'] ?? $user->first_name,
            'last_name'        => $data['lastName'] ?? $user->last_name,
            'address'          => $data['addressStreet'] ?? $user->address,
            'state'            => $data['addressState'] ?? $user->state,
            'zip'              => $data['addressPostalCode'] ?? $user->zip,
            'modified'         => now(),
        ];

        DB::table('users')->where('id', $user->id)->update($updateData);
        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'License details updated', 'result' => (array) $userData]);
    }

    public function addLicenseDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $updateData = [
            'first_name'       => $data['first_name'] ?? $user->first_name,
            'last_name'        => $data['last_name'] ?? $user->last_name,
            'address'          => $data['address'] ?? $user->address,
            'city'             => $data['city'] ?? $user->city,
            'state'            => $data['state'] ?? $user->state,
            'zip'              => $data['zip'] ?? $user->zip,
            'dob'              => $data['dateOfBirth'] ?? $user->dob,
            'licence_number'   => $data['license_number'] ?? '',
            'licence_state'    => $data['license_state'] ?? '',
            'licence_exp_date' => $data['license_exp_date'] ?? '',
            'modified'         => now(),
        ];

        DB::table('users')->where('id', $user->id)->update($updateData);
        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'License details added', 'result' => (array) $userData]);
    }

    public function getLicenseDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => [
            'licence_number' => $user->licence_number ?? '', 'licence_state' => $user->licence_state ?? '',
            'licence_exp_date' => $user->licence_exp_date ?? '', 'license_doc_1' => $user->license_doc_1 ?? '', 'license_doc_2' => $user->license_doc_2 ?? '',
        ]]);
    }

    public function getMySignature(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $signature = DB::table('user_signatures')->where('user_id', $user->id)->orderByDesc('id')->first();
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $signature ? (array) $signature : []]);
    }

    /* ================================================================== */
    /*  CARDS / STRIPE                                                     */
    /* ================================================================== */

    public function addMyCard(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized']); } /* TODO: Port Stripe card creation from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Card added successfully']); }
    public function getMyCards(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $cards = DB::table('user_cc_tokens')->where('user_id', $user->id)->where('status', 1)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $cards]); }
    public function makeMyCardDefault(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['user_cc_token_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Card ID required', 'result' => []]); } DB::table('user_cc_tokens')->where('user_id', $user->id)->update(['is_default' => 0]); DB::table('user_cc_tokens')->where('id', $data['user_cc_token_id'])->where('user_id', $user->id)->update(['is_default' => 1]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Default card updated', 'result' => []]); }
    public function deleteMyCard(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['card_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Card ID required', 'result' => []]); } DB::table('user_cc_tokens')->where('id', $data['card_id'])->where('user_id', $user->id)->update(['status' => 0]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Card deleted', 'result' => []]); }
    public function getStripeUrl(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port Stripe Connect URL from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]); }

    /* ================================================================== */
    /*  BOOKINGS                                                           */
    /* ================================================================== */

    public function cancelbookedLease(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['booking_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller::cancelbookedLease() */ DB::table('vehicle_reservations')->where('id', $data['booking_id'])->where('renter_id', $user->id)->whereIn('status', [0])->update(['status' => 3, 'cancel_note' => $data['cancel_note'] ?? '', 'modified' => now()]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking cancelled', 'result' => []]); }
    public function startbookedLease(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller::startbookedLease() */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking started', 'result' => []]); }
    public function completebookedLease(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller::completebookedLease() */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking completed', 'result' => []]); }
    public function getMyLeaseHistories(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); $page = max(1, (int) ($data['page'] ?? 1)); $offset = ($page - 1) * self::$_resultlimit; $histories = DB::table('cs_orders')->where('renter_id', $user->id)->whereIn('status', [2, 3])->orderByDesc('id')->offset($offset)->limit(self::$_resultlimit)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $histories]); }
    public function getPendingBooking(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); $page = max(1, (int) ($data['page'] ?? 1)); $offset = ($page - 1) * self::$_resultlimit; $bookings = DB::table('vehicle_reservations')->where('renter_id', $user->id)->where('status', 0)->orderByDesc('id')->offset($offset)->limit(self::$_resultlimit)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]); }
    public function getPendingBookingDetails(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['bookingid'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ $booking = DB::table('vehicle_reservations')->where('id', $data['bookingid'])->where('renter_id', $user->id)->first(); if (!$booking) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking not found', 'result' => []]); } return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $booking]); }
    public function getMyActiveBooking(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $bookings = DB::table('cs_orders')->where('renter_id', $user->id)->whereIn('status', [0, 1])->orderByDesc('id')->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]); }
    public function getActiveBookingDetail(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['bookingid'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } $booking = DB::table('cs_orders')->where('id', $data['bookingid'])->where('renter_id', $user->id)->first(); if (!$booking) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking not found', 'result' => []]); } return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $booking]); }
    public function getMyPastBooking(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); $page = max(1, (int) ($data['page'] ?? 1)); $offset = ($page - 1) * self::$_resultlimit; $bookings = DB::table('cs_orders')->where('renter_id', $user->id)->whereIn('status', [2, 3])->orderByDesc('id')->offset($offset)->limit(self::$_resultlimit)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]); }
    public function getPastBookingDetail(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['bookingid'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } $booking = DB::table('cs_orders')->where('id', $data['bookingid'])->where('renter_id', $user->id)->first(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $booking ? (array) $booking : []]); }

    /* ================================================================== */
    /*  VEHICLE DOCS / INSPECTIONS / REVIEWS / ISSUES                      */
    /* ================================================================== */

    public function addVehicleDoc(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Document uploaded', 'result' => []]); }
    public function getinsurancetoken(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port insurance token from CakePHP InsuranceToken trait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function checkVinDetails(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['vin'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'VIN required', 'result' => []]); } /* TODO: Port VIN check from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getVehicleRegistration(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['vehicle_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID required', 'result' => []]); } $vehicle = DB::table('vehicles')->where('id', $data['vehicle_id'])->first(); if (!$vehicle) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle not found', 'result' => []]); } return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['registration_doc' => $vehicle->registration_doc ?? '']]); }
    public function getVehicleInspection(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['vehicle_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID required', 'result' => []]); } $vehicle = DB::table('vehicles')->where('id', $data['vehicle_id'])->first(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['inspection_doc' => $vehicle->inspection_doc ?? '']]); }
    public function myVehicleInspection(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function quoteAgreement(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getInitialReview(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['booking_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } $review = DB::table('booking_reviews')->where('booking_id', $data['booking_id'])->where('review_type', 'initial')->first(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $review ? (array) $review : []]); }
    public function addInitialReview(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Review added', 'result' => []]); }
    public function initFinalReview(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function uploadFinaleReviewImage(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image uploaded', 'result' => []]); }
    public function saveFinaleReview(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Review saved', 'result' => []]); }
    public function removeReviewImage(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image removed', 'result' => []]); }
    public function addMechanicalIssue(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Issue reported', 'result' => []]); }
    public function uploadSupportIssueImage(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image uploaded', 'result' => []]); }
    public function addAccidentalIssue(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Accident reported', 'result' => []]); }
    public function pullMyAccidentalIssue(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $issues = DB::table('vehicle_issues')->where('user_id', $user->id)->where('issue_type', 'accidental')->orderByDesc('id')->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $issues]); }
    public function pullAccidentalIssueDetail(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['issue_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Issue ID required', 'result' => []]); } $issue = DB::table('vehicle_issues')->where('id', $data['issue_id'])->first(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $issue ? (array) $issue : []]); }
    public function saveAccidentalIssueClaim(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Claim saved', 'result' => []]); }

    /* ================================================================== */
    /*  PAYMENTS / TRANSACTIONS                                            */
    /* ================================================================== */

    public function retryPendingPayment(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment retried', 'result' => []]); }
    public function getMyTransactions(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['booking_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID required', 'result' => []]); } $transactions = DB::table('cs_order_payments')->where('order_id', $data['booking_id'])->orderByDesc('id')->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $transactions]); }
    public function getCreditHealthyInfo(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getRequestUponPaymentInfo(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getSchedulePayment(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function requestAdvancePayment(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function makeAdvancePayment(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment processed', 'result' => []]); }
    public function bookingPaymentTerms(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function bookingPaymentTermsConfirm(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment terms confirmed', 'result' => []]); }
    public function requestCreditScore(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }

    /* ================================================================== */
    /*  SAMPLE DOCS / OFFERS / INCOME / VEHICLES / MISC                    */
    /* ================================================================== */

    public function getSampleInsurance(Request $request): JsonResponse { return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_insurance_url', '')]]); }
    public function getSampleRegistrationDoc(Request $request): JsonResponse { return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_registration_url', '')]]); }
    public function getSampleInspectionnDoc(Request $request): JsonResponse { return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_inspection_url', '')]]); }
    public function getMyOffers(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getOfferQuote(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function acceptOffer(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Offer accepted', 'result' => []]); }
    public function linkIncomeSource(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function uploadPaystub(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function connectBankAccount(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function saveMonthlyIncome(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['income'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Income required', 'result' => []]); } DB::table('users')->where('id', $user->id)->update(['monthly_income' => $data['income'], 'modified' => now()]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Monthly income saved', 'result' => []]); }
    public function getMonthlyIncomeAgreement(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getLoanData(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }

    /* ================================================================== */
    /*  VEHICLE SEARCH / LISTING                                           */
    /* ================================================================== */

    public function getVehicleFilters(Request $request): JsonResponse { /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }

    public function searchVehicles(Request $request): JsonResponse
    {
        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        // TODO: Port full V2 vehicle search from CakePHP Services2Controller::searchVehicles()
        $query = DB::table('vehicles')
            ->join('vehicle_listings', 'vehicles.id', '=', 'vehicle_listings.vehicle_id')
            ->where('vehicle_listings.status', 1)
            ->where('vehicles.status', 1);

        if (!empty($data['financing'])) {
            $query->where('vehicle_listings.financing_type', $data['financing']);
        }

        $vehicles = $query->orderByDesc('vehicles.id')->offset($offset)->limit(self::$_resultlimit)->get()->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $vehicles]);
    }

    public function vehicleAutocomplete(Request $request): JsonResponse { $data = $request->all(); $keyword = trim($data['keyword'] ?? ''); if (empty($keyword)) { return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); } $vehicles = DB::table('vehicles')->where(function ($q) use ($keyword) { $q->where('make', 'LIKE', "%{$keyword}%")->orWhere('model', 'LIKE', "%{$keyword}%"); })->where('status', 1)->select('make', 'model')->distinct()->limit(20)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $vehicles]); }
    public function getVehicleDetail(Request $request): JsonResponse { $data = $request->all(); if (empty($data['list_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Listing ID required', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ $listing = DB::table('vehicle_listings')->join('vehicles', 'vehicle_listings.vehicle_id', '=', 'vehicles.id')->where('vehicle_listings.id', $data['list_id'])->first(); if (!$listing) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle not found', 'result' => []]); } return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $listing]); }
    public function getVehiclePriceDetail(Request $request): JsonResponse { $data = $request->all(); if (empty($data['list_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Listing ID required', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getVehicleQuote(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getQuoteAgreement(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function book(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking created', 'result' => []]); }
    public function reactSearchVehicles(Request $request): JsonResponse { return $this->searchVehicles($request); }
    public function similarVehicles(Request $request): JsonResponse { /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getChapmanFilters(Request $request): JsonResponse { /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function chapmanVehicles(Request $request): JsonResponse { /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }

    /* ================================================================== */
    /*  AGREEMENT / RAVIN / CHANGE PLAN / WISHLIST / MISC                  */
    /* ================================================================== */

    public function getAgreement(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getRavinScan(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function initiateChangePlan(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getWishlistVehicle(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); $page = max(1, (int) ($data['page'] ?? 1)); $offset = ($page - 1) * self::$_resultlimit; $wishlist = DB::table('wishlists')->where('user_id', $user->id)->orderByDesc('id')->offset($offset)->limit(self::$_resultlimit)->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $wishlist]); }
    public function addVehicleToWishlist(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['vehicle_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID required', 'result' => []]); } $exists = DB::table('wishlists')->where('user_id', $user->id)->where('vehicle_id', $data['vehicle_id'])->first(); if (!$exists) { DB::table('wishlists')->insert(['user_id' => $user->id, 'vehicle_id' => $data['vehicle_id'], 'program' => $data['program'] ?? '', 'financing' => $data['financing'] ?? '', 'created' => now()]); } return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Added to wishlist', 'result' => []]); }
    public function removeWishlistVehicle(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['vehicle_id'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID required', 'result' => []]); } DB::table('wishlists')->where('user_id', $user->id)->where('vehicle_id', $data['vehicle_id'])->delete(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Removed from wishlist', 'result' => []]); }
    public function getIntercomCarousel(Request $request): JsonResponse { /* TODO: Port IntercomCarousels from MobileApi trait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getCountryCounty(Request $request): JsonResponse { $countries = DB::table('countries')->select('id', 'name', 'code')->get()->toArray(); return response()->json(['status' => true, 'message' => '', 'result' => $countries]); }
    public function getWalletTermText(Request $request): JsonResponse { /* TODO: Port mobileWalletTermText from MobileApi trait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function inviteFriend(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Invitations sent', 'result' => []]); }
    public function faq(Request $request): JsonResponse { $faqs = DB::table('faqs')->where('status', 1)->orderBy('sort_order')->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $faqs]); }

    /* ================================================================== */
    /*  EXTENSIONS / UBER / WAITLIST / EV / ELAND                          */
    /* ================================================================== */

    public function requestExtension(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function processExtension(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Extension processed', 'result' => []]); }
    public function findUberCars(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $data = $request->all(); if (empty($data['start_lat']) || empty($data['start_lng']) || empty($data['end_lat']) || empty($data['end_lng'])) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid request payload', 'result' => []]); } /* TODO: Port Uber API from CakePHP UberTrait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Your request is processed successfully', 'result' => []]); }
    public function bookUberCar(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP UberTrait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Uber car booked', 'result' => []]); }
    public function getUberBookings(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } $bookings = DB::table('uber_bookings')->where('user_id', $user->id)->orderByDesc('id')->get()->toArray(); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]); }
    public function cancelUberCar(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP UberTrait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Uber ride cancelled', 'result' => []]); }
    public function sendTextUberDriver(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP UberTrait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Message sent', 'result' => []]); }
    public function getUberBookingDetail(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP UberTrait */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function addVehicleToWaitlist(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Added to waitlist', 'result' => []]); }
    public function getEvStation(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getElandUrl(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]); }

    /* ================================================================== */
    /*  WALLET / COUPON / IDOLOGY / INSURANCE / PLAID / PROMO              */
    /* ================================================================== */

    public function acceptWalletTerm(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } DB::table('users')->where('id', $user->id)->update(['wallet_term_accepted' => 1, 'modified' => now()]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Wallet terms accepted', 'result' => []]); }
    public function getCouponTerms(Request $request): JsonResponse { /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function acceptCouponTerms(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } DB::table('users')->where('id', $user->id)->update(['coupon_term_accepted' => 1, 'modified' => now()]); return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Coupon terms accepted', 'result' => []]); }
    public function pushDataToIdology(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function acceptInsuranceQuote(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Insurance quote accepted', 'result' => []]); }
    public function applyCoupon(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Coupon applied', 'result' => []]); }
    public function plaidtoken(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function savePlaidUser(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Plaid user saved', 'result' => []]); }
    public function getEmployerPromoWebView(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]); }
    public function getCMMCard(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function getInsuranceDetails(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]); }
    public function updateInsuranceDetails(Request $request): JsonResponse { $user = $this->getUserObj($request); if (!$user) { return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]); } /* TODO: Port from CakePHP Services2Controller */ return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Insurance details updated', 'result' => []]); }
}
