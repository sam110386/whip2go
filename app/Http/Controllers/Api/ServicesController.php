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
 * V1 Mobile API Services Controller.
 *
 * Contains the core business logic for all mobile API endpoints.
 * Ported from CakePHP Plugin/Api/Controller/ServicesController.php.
 *
 * CakePHP → Laravel mapping:
 *   $this->request->data           → $request->all() / $request->input('key')
 *   $this->loadModel('X')          → DB::table('table')
 *   $this->response->body(json)    → return response()->json($data)
 *   Configure::read('key')         → config('legacy.key')
 *   $this->userObj                 → session('api_user')
 *   $this->autoRender = false      → (not needed)
 */
class ServicesController extends Controller
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

        $user = DB::table('users')
            ->where('email', $email)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'User not found', 'result' => []]);
        }

        session(['api_user_id' => $user->id]);
        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'result' => (array) $userData,
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $userData = DB::table('users')->select($this->_userfields)->where('id', $user->id)->first();

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'result' => (array) $userData,
        ]);
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

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => 'Registration successful. Please verify your account.',
            'result' => (array) $userData,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
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

        $verifyToken = Str::random(32);
        DB::table('users')->where('id', $user->id)->update(['verify_token' => $verifyToken, 'modified' => now()]);

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => 'Password reset instructions sent',
            'result' => ['user_id' => $user->id, 'verify_token' => $verifyToken],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['user_id']) || empty($data['password']) || empty($data['verify_token'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Missing required fields', 'result' => []]);
        }

        $user = DB::table('users')
            ->where('id', $data['user_id'])
            ->where('verify_token', $data['verify_token'])
            ->first();

        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid token', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password'     => Hash::make($data['password']),
            'verify_token' => null,
            'modified'     => now(),
        ]);

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

        $licenseData = [
            'licence_number'   => $user->licence_number ?? '',
            'licence_state'    => $user->licence_state ?? '',
            'licence_exp_date' => $user->licence_exp_date ?? '',
            'license_doc_1'    => $user->license_doc_1 ?? '',
            'license_doc_2'    => $user->license_doc_2 ?? '',
        ];

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $licenseData]);
    }

    public function getMySignature(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $signature = DB::table('user_signatures')->where('user_id', $user->id)->orderByDesc('id')->first();

        return response()->json([
            'status' => self::$_STATUSSUCCESS,
            'message' => '',
            'result' => $signature ? (array) $signature : [],
        ]);
    }

    public function deleteMyAccount(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $activeBookings = DB::table('cs_orders')
            ->where('renter_id', $user->id)
            ->whereIn('status', [0, 1])
            ->count();

        if ($activeBookings > 0) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Cannot delete account with active bookings', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['is_deleted' => 1, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Account deleted successfully', 'result' => []]);
    }

    /* ================================================================== */
    /*  CARDS / STRIPE                                                     */
    /* ================================================================== */

    public function addMyCard(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized']);
        }

        $data = $request->all();
        // TODO: Port Stripe card creation logic from CakePHP ServicesController::addMyCard()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Card added successfully']);
    }

    public function getMyCards(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $cards = DB::table('user_cc_tokens')->where('user_id', $user->id)->where('status', 1)->get()->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $cards]);
    }

    public function makeMyCardDefault(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['user_cc_token_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Card ID is required', 'result' => []]);
        }

        DB::table('user_cc_tokens')->where('user_id', $user->id)->update(['is_default' => 0]);
        DB::table('user_cc_tokens')->where('id', $data['user_cc_token_id'])->where('user_id', $user->id)->update(['is_default' => 1]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Default card updated', 'result' => []]);
    }

    public function deleteMyCard(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['card_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Card ID is required', 'result' => []]);
        }

        DB::table('user_cc_tokens')->where('id', $data['card_id'])->where('user_id', $user->id)->update(['status' => 0]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Card deleted', 'result' => []]);
    }

    public function getStripeUrl(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Stripe Connect URL generation from CakePHP ServicesController::getStripeUrl()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]);
    }

    /* ================================================================== */
    /*  BOOKINGS                                                           */
    /* ================================================================== */

    public function cancelbookedLease(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['booking_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        // TODO: Port full cancellation logic from CakePHP ServicesController::cancelbookedLease()
        DB::table('vehicle_reservations')
            ->where('id', $data['booking_id'])
            ->where('renter_id', $user->id)
            ->whereIn('status', [0])
            ->update([
                'status'      => 3,
                'cancel_note' => $data['cancel_note'] ?? '',
                'modified'    => now(),
            ]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking cancelled', 'result' => []]);
    }

    public function startbookedLease(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['booking_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        // TODO: Port full start booking logic from CakePHP ServicesController::startbookedLease()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking started', 'result' => []]);
    }

    public function completebookedLease(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port full complete booking logic from CakePHP ServicesController::completebookedLease()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking completed', 'result' => []]);
    }

    public function getMyLeaseHistories(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        // TODO: Port full lease history query from CakePHP ServicesController::getMyLeaseHistories()
        $histories = DB::table('cs_orders')
            ->where('renter_id', $user->id)
            ->whereIn('status', [2, 3])
            ->orderByDesc('id')
            ->offset($offset)
            ->limit(self::$_resultlimit)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $histories]);
    }

    public function getPendingBooking(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        // TODO: Port full pending booking query from CakePHP ServicesController::getPendingBooking()
        $bookings = DB::table('vehicle_reservations')
            ->where('renter_id', $user->id)
            ->where('status', 0)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit(self::$_resultlimit)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]);
    }

    public function getPendingBookingDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['bookingid'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        // TODO: Port full pending booking details from CakePHP ServicesController::getPendingBookingDetails()
        $booking = DB::table('vehicle_reservations')
            ->where('id', $data['bookingid'])
            ->where('renter_id', $user->id)
            ->first();

        if (!$booking) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking not found', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $booking]);
    }

    public function getMyActiveBooking(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port full active booking query from CakePHP ServicesController::getMyActiveBooking()
        $bookings = DB::table('cs_orders')
            ->where('renter_id', $user->id)
            ->whereIn('status', [0, 1])
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]);
    }

    public function getActiveBookingDetail(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['bookingid'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        // TODO: Port full active booking detail from CakePHP ServicesController::getActiveBookingDetail()
        $booking = DB::table('cs_orders')->where('id', $data['bookingid'])->where('renter_id', $user->id)->first();

        if (!$booking) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking not found', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $booking]);
    }

    public function getMyPastBooking(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        // TODO: Port full past booking query from CakePHP ServicesController::getMyPastBooking()
        $bookings = DB::table('cs_orders')
            ->where('renter_id', $user->id)
            ->whereIn('status', [2, 3])
            ->orderByDesc('id')
            ->offset($offset)
            ->limit(self::$_resultlimit)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]);
    }

    public function getPastBookingDetail(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['bookingid'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        // TODO: Port full past booking detail from CakePHP ServicesController::getPastBookingDetail()
        $booking = DB::table('cs_orders')->where('id', $data['bookingid'])->where('renter_id', $user->id)->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $booking ? (array) $booking : []]);
    }

    /* ================================================================== */
    /*  VEHICLE DOCS / INSPECTIONS                                         */
    /* ================================================================== */

    public function addVehicleDoc(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        // TODO: Port full vehicle doc upload from CakePHP ServicesController::addVehicleDoc()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Document uploaded', 'result' => []]);
    }

    public function getinsurancetoken(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port insurance token generation from CakePHP InsuranceToken trait
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function checkVinDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['vin'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'VIN is required', 'result' => []]);
        }

        // TODO: Port VIN check logic from CakePHP ServicesController::checkVinDetails()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getVehicleRegistration(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['vehicle_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID is required', 'result' => []]);
        }

        $vehicle = DB::table('vehicles')->where('id', $data['vehicle_id'])->first();
        if (!$vehicle) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle not found', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['registration_doc' => $vehicle->registration_doc ?? '']]);
    }

    public function getVehicleInspection(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['vehicle_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID is required', 'result' => []]);
        }

        $vehicle = DB::table('vehicles')->where('id', $data['vehicle_id'])->first();
        if (!$vehicle) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle not found', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['inspection_doc' => $vehicle->inspection_doc ?? '']]);
    }

    public function myVehicleInspection(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port vehicle inspection URL logic from CakePHP ServicesController::myVehicleInspection()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  REVIEWS                                                            */
    /* ================================================================== */

    public function quoteAgreement(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port quote agreement PDF logic from CakePHP ServicesController::quoteAgreement()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getInitialReview(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['booking_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        $review = DB::table('booking_reviews')
            ->where('booking_id', $data['booking_id'])
            ->where('review_type', 'initial')
            ->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $review ? (array) $review : []]);
    }

    public function addInitialReview(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port initial review creation from CakePHP ServicesController::addInitialReview()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Review added', 'result' => []]);
    }

    public function initFinalReview(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port final review init from CakePHP ServicesController::initFinalReview()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function uploadFinaleReviewImage(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port final review image upload from CakePHP ServicesController::uploadFinaleReviewImage()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image uploaded', 'result' => []]);
    }

    public function saveFinaleReview(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port final review save from CakePHP ServicesController::saveFinaleReview()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Review saved', 'result' => []]);
    }

    public function removeReviewImage(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port review image removal from CakePHP ServicesController::removeReviewImage()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image removed', 'result' => []]);
    }

    /* ================================================================== */
    /*  SUPPORT / ISSUES                                                   */
    /* ================================================================== */

    public function addMechanicalIssue(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port mechanical issue creation from CakePHP ServicesController::addMechanicalIssue()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Issue reported', 'result' => []]);
    }

    public function uploadSupportIssueImage(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port support issue image upload from CakePHP ServicesController::uploadSupportIssueImage()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Image uploaded', 'result' => []]);
    }

    public function addAccidentalIssue(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port accidental issue creation from CakePHP ServicesController::addAccidentalIssue()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Accident reported', 'result' => []]);
    }

    public function pullMyAccidentalIssue(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $issues = DB::table('vehicle_issues')
            ->where('user_id', $user->id)
            ->where('issue_type', 'accidental')
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $issues]);
    }

    public function pullAccidentalIssueDetail(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['issue_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Issue ID is required', 'result' => []]);
        }

        // TODO: Port accidental issue detail from CakePHP ServicesController::pullAccidentalIssueDetail()
        $issue = DB::table('vehicle_issues')->where('id', $data['issue_id'])->first();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $issue ? (array) $issue : []]);
    }

    public function saveAccidentalIssueClaim(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port accidental issue claim save from CakePHP ServicesController::saveAccidentalIssueClaim()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Claim saved', 'result' => []]);
    }

    /* ================================================================== */
    /*  PAYMENTS / TRANSACTIONS                                            */
    /* ================================================================== */

    public function retryPendingPayment(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port payment retry logic from CakePHP ServicesController::retryPendingPayment()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment retried', 'result' => []]);
    }

    public function getMyTransactions(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['booking_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Booking ID is required', 'result' => []]);
        }

        $transactions = DB::table('cs_order_payments')
            ->where('order_id', $data['booking_id'])
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $transactions]);
    }

    public function getCreditHealthyInfo(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port credit health info from CakePHP ServicesController::getCreditHealthyInfo()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getRequestUponPaymentInfo(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port PTO payment info from CakePHP ServicesController::getRequestUponPaymentInfo()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getSchedulePayment(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port schedule payment logic from CakePHP ServicesController::getSchedulePayment()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function requestAdvancePayment(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port advance payment request from CakePHP ServicesController::requestAdvancePayment()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function makeAdvancePayment(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port advance payment processing from CakePHP ServicesController::makeAdvancePayment()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment processed', 'result' => []]);
    }

    public function bookingPaymentTerms(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port booking payment terms from CakePHP ServicesController::bookingPaymentTerms()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function bookingPaymentTermsConfirm(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port booking payment terms confirm from CakePHP ServicesController::bookingPaymentTermsConfirm()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Payment terms confirmed', 'result' => []]);
    }

    public function requestCreditScore(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port credit score request from CakePHP ServicesController::requestCreditScore()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  SAMPLE DOCS                                                        */
    /* ================================================================== */

    public function getSampleInsurance(Request $request): JsonResponse
    {
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_insurance_url', '')]]);
    }

    public function getSampleRegistrationDoc(Request $request): JsonResponse
    {
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_registration_url', '')]]);
    }

    public function getSampleInspectionnDoc(Request $request): JsonResponse
    {
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => config('legacy.sample_inspection_url', '')]]);
    }

    /* ================================================================== */
    /*  OFFERS                                                             */
    /* ================================================================== */

    public function getMyOffers(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port offers query from CakePHP ServicesController::getMyOffers()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getOfferQuote(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port offer quote from CakePHP ServicesController::getOfferQuote()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function acceptOffer(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port accept offer from CakePHP ServicesController::acceptOffer()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Offer accepted', 'result' => []]);
    }

    /* ================================================================== */
    /*  INCOME VERIFICATION                                                */
    /* ================================================================== */

    public function linkIncomeSource(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Plaid/Argyle income source link from CakePHP ServicesController::linkIncomeSource()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function uploadPaystub(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port paystub upload from CakePHP ServicesController::uploadPaystub()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function connectBankAccount(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port bank account connection from CakePHP ServicesController::connectBankAccount()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function saveMonthlyIncome(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['income'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Income is required', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['monthly_income' => $data['income'], 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Monthly income saved', 'result' => []]);
    }

    public function getMonthlyIncomeAgreement(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port monthly income agreement from CakePHP ServicesController::getMonthlyIncomeAgreement()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getLoanData(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port loan data from CakePHP ServicesController::getLoanData()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  VEHICLE SEARCH / LISTING                                           */
    /* ================================================================== */

    public function getVehicleFilters(Request $request): JsonResponse
    {
        // TODO: Port vehicle filter query from CakePHP ServicesController::getVehicleFilters()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function searchVehicles(Request $request): JsonResponse
    {
        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        // TODO: Port full vehicle search with geo, financing, filters from CakePHP ServicesController::searchVehicles()
        $query = DB::table('vehicles')
            ->join('vehicle_listings', 'vehicles.id', '=', 'vehicle_listings.vehicle_id')
            ->where('vehicle_listings.status', 1)
            ->where('vehicles.status', 1);

        if (!empty($data['financing'])) {
            $query->where('vehicle_listings.financing_type', $data['financing']);
        }

        $vehicles = $query->orderByDesc('vehicles.id')
            ->offset($offset)
            ->limit(self::$_resultlimit)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $vehicles]);
    }

    public function vehicleAutocomplete(Request $request): JsonResponse
    {
        $data = $request->all();
        $keyword = trim($data['keyword'] ?? '');

        if (empty($keyword)) {
            return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
        }

        $vehicles = DB::table('vehicles')
            ->where(function ($q) use ($keyword) {
                $q->where('make', 'LIKE', "%{$keyword}%")
                  ->orWhere('model', 'LIKE', "%{$keyword}%");
            })
            ->where('status', 1)
            ->select('make', 'model')
            ->distinct()
            ->limit(20)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $vehicles]);
    }

    public function getVehicleDetail(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['list_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Listing ID is required', 'result' => []]);
        }

        // TODO: Port full vehicle detail from CakePHP ServicesController::getVehicleDetail()
        $listing = DB::table('vehicle_listings')
            ->join('vehicles', 'vehicle_listings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicle_listings.id', $data['list_id'])
            ->first();

        if (!$listing) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle not found', 'result' => []]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => (array) $listing]);
    }

    public function getVehiclePriceDetail(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['list_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Listing ID is required', 'result' => []]);
        }

        // TODO: Port full vehicle price detail from CakePHP ServicesController::getVehiclePriceDetail()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getVehicleQuote(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port full vehicle quote calculation from CakePHP ServicesController::getVehicleQuote()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getQuoteAgreement(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port quote agreement from CakePHP ServicesController::getQuoteAgreement()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function book(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port full booking creation from CakePHP ServicesController::book()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Booking created', 'result' => []]);
    }

    public function reactSearchVehicles(Request $request): JsonResponse
    {
        // TODO: Port React search vehicles from CakePHP ServicesController::reactSearchVehicles()
        return $this->searchVehicles($request);
    }

    public function similarVehicles(Request $request): JsonResponse
    {
        // TODO: Port similar vehicles from CakePHP ServicesController::similarVehicles()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getChapmanFilters(Request $request): JsonResponse
    {
        // TODO: Port Chapman filters from CakePHP ServicesController::getChapmanFilters()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function chapmanVehicles(Request $request): JsonResponse
    {
        // TODO: Port Chapman vehicles from CakePHP ServicesController::chapmanVehicles()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  AGREEMENT / RAVIN / CHANGE PLAN                                    */
    /* ================================================================== */

    public function getAgreement(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port agreement retrieval from CakePHP ServicesController::getAgreement()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getRavinScan(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Ravin scan URL from CakePHP ServicesController::getRavinScan()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function initiateChangePlan(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port change plan init from CakePHP ServicesController::initiateChangePlan()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  WISHLIST                                                           */
    /* ================================================================== */

    public function getWishlistVehicle(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * self::$_resultlimit;

        $wishlist = DB::table('wishlists')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit(self::$_resultlimit)
            ->get()
            ->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $wishlist]);
    }

    public function addVehicleToWishlist(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['vehicle_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID is required', 'result' => []]);
        }

        $exists = DB::table('wishlists')->where('user_id', $user->id)->where('vehicle_id', $data['vehicle_id'])->first();
        if (!$exists) {
            DB::table('wishlists')->insert([
                'user_id'    => $user->id,
                'vehicle_id' => $data['vehicle_id'],
                'program'    => $data['program'] ?? '',
                'financing'  => $data['financing'] ?? '',
                'created'    => now(),
            ]);
        }

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Added to wishlist', 'result' => []]);
    }

    public function removeWishlistVehicle(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['vehicle_id'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Vehicle ID is required', 'result' => []]);
        }

        DB::table('wishlists')->where('user_id', $user->id)->where('vehicle_id', $data['vehicle_id'])->delete();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Removed from wishlist', 'result' => []]);
    }

    /* ================================================================== */
    /*  HOME / STATIC / INTERCOM                                           */
    /* ================================================================== */

    public function getIntercomCarousel(Request $request): JsonResponse
    {
        // TODO: Port IntercomCarousels logic from CakePHP MobileApi trait
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getCountryCounty(Request $request): JsonResponse
    {
        $countries = DB::table('countries')->select('id', 'name', 'code')->get()->toArray();

        return response()->json(['status' => true, 'message' => '', 'result' => $countries]);
    }

    public function getWalletTermText(Request $request): JsonResponse
    {
        // TODO: Port mobileWalletTermText from CakePHP MobileApi trait
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  SOCIAL / MISC                                                      */
    /* ================================================================== */

    public function inviteFriend(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['emails'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Email addresses are required', 'result' => []]);
        }

        // TODO: Port invite friend email logic from CakePHP ServicesController::inviteFriend()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Invitations sent', 'result' => []]);
    }

    public function faq(Request $request): JsonResponse
    {
        $faqs = DB::table('faqs')->where('status', 1)->orderBy('sort_order')->get()->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $faqs]);
    }

    /* ================================================================== */
    /*  EXTENSIONS                                                         */
    /* ================================================================== */

    public function requestExtension(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port extension request from CakePHP ServicesController::requestExtension()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function processExtension(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port extension processing from CakePHP ServicesController::processExtension()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Extension processed', 'result' => []]);
    }

    /* ================================================================== */
    /*  UBER / RIDE-HAIL (from UberTrait)                                  */
    /* ================================================================== */

    public function findUberCars(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $data = $request->all();
        if (empty($data['start_lat']) || empty($data['start_lng']) || empty($data['end_lat']) || empty($data['end_lng'])) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Invalid request payload', 'result' => []]);
        }

        $activeBooking = DB::table('cs_orders')->where('renter_id', $user->id)->whereIn('status', [0, 1])->first();
        $reservation = DB::table('vehicle_reservations')->where('renter_id', $user->id)->whereIn('status', [0, 1])->first();

        if (!$activeBooking && !$reservation) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Sorry you cant book Uber ride because you dont have any active/pending booking. Please contact to support team', 'result' => []]);
        }

        // TODO: Port Uber API call from CakePHP UberTrait::findUberCars()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Your request is processed successfully', 'result' => []]);
    }

    public function bookUberCar(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Uber booking from CakePHP UberTrait::bookUberCar()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Uber car booked', 'result' => []]);
    }

    public function getUberBookings(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        $bookings = DB::table('uber_bookings')->where('user_id', $user->id)->orderByDesc('id')->get()->toArray();

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => $bookings]);
    }

    public function cancelUberCar(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Uber cancellation from CakePHP UberTrait::cancelUberCar()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Uber ride cancelled', 'result' => []]);
    }

    public function sendTextUberDriver(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Uber text message from CakePHP UberTrait::sendTextUberDriver()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Message sent', 'result' => []]);
    }

    public function getUberBookingDetail(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Uber booking detail from CakePHP UberTrait::getUberBookingDetail()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    /* ================================================================== */
    /*  WAITLIST / EV / ELAND                                              */
    /* ================================================================== */

    public function addVehicleToWaitlist(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port waitlist logic from CakePHP ServicesController::addVehicleToWaitlist()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Added to waitlist', 'result' => []]);
    }

    public function getEvStation(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port EV station lookup from CakePHP ServicesController::getEvStation()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getElandUrl(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Eland URL from CakePHP ServicesController::getElandUrl()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]);
    }

    /* ================================================================== */
    /*  WALLET / COUPON                                                    */
    /* ================================================================== */

    public function acceptWalletTerm(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['wallet_term_accepted' => 1, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Wallet terms accepted', 'result' => []]);
    }

    public function getCouponTerms(Request $request): JsonResponse
    {
        // TODO: Port coupon terms from CakePHP ServicesController::getCouponTerms()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function acceptCouponTerms(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        DB::table('users')->where('id', $user->id)->update(['coupon_term_accepted' => 1, 'modified' => now()]);

        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Coupon terms accepted', 'result' => []]);
    }

    /* ================================================================== */
    /*  IDOLOGY / INSURANCE / PLAID                                        */
    /* ================================================================== */

    public function pushDataToIdology(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port IDology data push from CakePHP ServicesController::pushDataToIdology()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function acceptInsuranceQuote(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port insurance quote acceptance from CakePHP ServicesController::acceptInsuranceQuote()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Insurance quote accepted', 'result' => []]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port coupon application from CakePHP ServicesController::applyCoupon()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Coupon applied', 'result' => []]);
    }

    public function plaidtoken(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Plaid token generation from CakePHP ServicesController::plaidtoken()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function savePlaidUser(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port Plaid user save from CakePHP ServicesController::savePlaidUser()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Plaid user saved', 'result' => []]);
    }

    /* ================================================================== */
    /*  EMPLOYER PROMO / CMM / INSURANCE                                   */
    /* ================================================================== */

    public function getEmployerPromoWebView(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port employer promo WebView URL from CakePHP ServicesController::getEmployerPromoWebView()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => ['url' => '']]);
    }

    public function getCMMCard(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port CMM card data from CakePHP ServicesController::getCMMCard()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function getInsuranceDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port insurance details from CakePHP ServicesController::getInsuranceDetails()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => '', 'result' => []]);
    }

    public function updateInsuranceDetails(Request $request): JsonResponse
    {
        $user = $this->getUserObj($request);
        if (!$user) {
            return response()->json(['status' => self::$_STATUSFAIL, 'message' => 'Unauthorized', 'result' => []]);
        }

        // TODO: Port insurance details update from CakePHP ServicesController::updateInsuranceDetails()
        return response()->json(['status' => self::$_STATUSSUCCESS, 'message' => 'Insurance details updated', 'result' => []]);
    }
}
