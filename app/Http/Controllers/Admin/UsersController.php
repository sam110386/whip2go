<?php

namespace App\Http\Controllers\Admin;

use App\Models\Legacy\UserLicenseDetail;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\ArgyleUser;
use App\Models\Legacy\ArgyleUserRecord;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\User as LegacyUser;
use App\Helpers\Legacy\Security as LegacySecurity;
use App\Helpers\Legacy\Number as LegacyNumber;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Http\Controllers\Traits\DriverBackgroundReport;

class UsersController extends LegacyAppController
{
    use UsersTrait, DriverBackgroundReport;

    public function index(Request $request)
    {
        $adminUser = $this->getAdminUserid();

        if (empty($adminUser['administrator'])) {
            return redirect('admin/linked_users/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $title = 'Manage Users';
        $sessionLimitKey = 'users_limit';
        $keyword = $request->input('.Search.keyword', $request->input('keyword', ''));
        $show = $request->input('Search.show', $request->input('show', ''));
        $type = $request->input('Search.type', $request->input('type', ''));

        if ($request->has('Search.ClearFilter')) {
            $request->request->remove('Search');
            return redirect('admin/users/index');
        }

        $query = LegacyUser::where('is_admin', 0);

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere('username', 'LIKE', "%{$keyword}%")
                    ->orWhere('business_name', 'LIKE', "%{$keyword}%");
            });
        }

        if ($show === 'Active') {
            $query->where('status', 1);
        } elseif ($show === 'Deactive') {
            $query->where('status', 0);
        }

        switch ($type) {
            case 1:
                $query->where('is_verified', 1);
                break;
            case 2:
                $query->where('is_verified', 0);
                break;
            case 3:
                $query->where('is_renter', 1);
                break;
            case 4:
                $query->where('is_driver', 1);
                break;
            case 5:
                $query->where('is_dealer', 1);
                break;
            case 6:
                $query->where('is_dealer', 2);
                break;
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $allowedSort = [
            'id',
            'first_name',
            'last_name',
            'email',
            'contact_number',
            'created',
            'status',
            'is_verified',
            'is_renter',
            'is_driver',
            'is_dealer',
            'checkr_status',
            'trash'
        ];

        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }

        $users = $query->orderBy($sort, $direction)->paginate($limit);

        if (request()->ajax()) {
            return view('admin.users.elements.index', compact('users', 'keyword', 'show', 'type', 'limit', ));
        }

        return view('admin.users.index', compact('title', 'users', 'keyword', 'show', 'type', 'limit', ));
    }
    public function status($id = null, $status = null)
    {
        $userId = $this->decodeId($id);

        if (!empty($userId)) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $user->status = ((string) $status === '1') ? 1 : 0;
                $user->save();
            }
        }

        return redirect()->back()->with('success', 'User status has been changed.');
    }
    public function trash($id = null, $status = null)
    {
        $userId = $this->decodeId($id);

        if (!empty($userId)) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $user->trash = ((string) $status === '1') ? 1 : 0;
                $user->save();
            }
        }

        return redirect()->back()->with('success', 'User status has been changed.');
    }
    public function verify($id = null)
    {
        $userId = $this->decodeId($id);

        if ($userId) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $user->is_verified = 1;
                $user->verify_token = '';
                $user->save();

                if (!empty($user->username)) {
                    AdminUserAssociation::saveLeadAssociation($user->username, $userId);
                }
            }

        }

        return redirect()->back()->with('success', 'User status has been changed.');
    }
    public function driverstatus($id = null, $status = null)
    {
        $userId = $this->decodeId($id);

        if (!empty($userId)) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $user->is_driver = ((string) $status === '1') ? 1 : 0;
                $user->save();
            }
        }

        return redirect()->back()->with('success', 'User status has been changed.');
    }
    public function view($id = null)
    {
        $title = 'View User';
        $userId = $this->decodeId($id);
        $user = LegacyUser::find($userId);

        if (!$user) {
            return redirect('/admin/users/index');
        }

        return view('admin.users.view', compact('title', 'user'));
    }
    public function delete($id = null)
    {
        $userId = $this->decodeId($id);

        if ($userId) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $user->delete();
            }

        }

        return redirect()->back()->with('success', 'User has been deleted successfully.');
    }
    public function add(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        $title = $userId ? 'Update User' : 'Add User';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $data = $request->input('User', []);

            $validator = Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contact_number' => $userId ? 'nullable' : 'required',
                'pwd' => $userId ? 'nullable|min:6' : 'required|min:6',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            if (empty($userId)) {
                $data['username'] = preg_replace("/[^0-9]/", "", $data['contact_number'] ?? '');
                $data['is_verified'] = 1;
                $data['status'] = 1;
            }

            if (!empty($data['licence_number'])) {
                $data['is_driver'] = 1;
                $data['licence_number'] = LegacySecurity::encrypt($data['licence_number']);
            }

            if (!empty($data['ss_no'])) {
                $data['ss_no'] = LegacySecurity::encrypt($data['ss_no']);
            }

            if (!empty($data['pwd'])) {
                $data['password'] = LegacySecurity::hash($data['pwd'], null, true);
            } else {
                unset($data['pwd']);
            }

            $address = ($data['address'] ?? '') . " " . ($data['city'] ?? '') . ' ' . ($data['state'] ?? '') . ' ' . ($data['zip'] ?? '');
            $latlng = $this->commonService->toCoordinates($address);

            if (!empty($latlng['lat']) && !empty($latlng['lng'])) {
                $data['address_lat'] = $latlng['lat'];
                $data['address_lng'] = $latlng['lng'];
            }

            $user = LegacyUser::updateOrCreate(
                ['id' => $userId],
                collect($data)->except(['photo', 'updatelicense'])->toArray()
            );

            $userId = $user->id;
            $uploadPhotoPath = public_path('img/user_pic');

            if (!file_exists($uploadPhotoPath)) {
                mkdir($uploadPhotoPath, 0755, true);
            }

            if ($request->hasFile('User.photo')) {
                $photoFile = $request->file('User.photo');
                $photoName = $photoFile->getClientOriginalName();
                $photoFile->move($uploadPhotoPath, $photoName);
                $user->photo = $photoName;
                $user->save();
            }

            $docFields = ['tmp_doc_1' => 'license_doc_1', 'representative_sign' => 'representative_sign', 'tmp_doc_2' => 'license_doc_2'];
            $allowedExtensions = ['jpeg', 'jpg', 'png'];
            $uploadFilePath = public_path('files/userdocs');

            if (!file_exists($uploadFilePath)) {
                mkdir($uploadFilePath, 0755, true);
            }

            foreach ($docFields as $inputKey => $dbColumn) {
                if ($request->hasFile($inputKey)) {
                    $file = $request->file($inputKey);
                    $ext = strtolower($file->getClientOriginalExtension());

                    if (in_array($ext, $allowedExtensions)) {
                        $filename = "{$dbColumn}_{$userId}.{$ext}";
                        $file->move($uploadFilePath, $filename);
                        $user->$dbColumn = $filename;
                        $user->save();
                    }
                }
            }

            if (isset($data['is_dealer']) && $data['is_dealer'] != 1) {
                $licenseInput = $request->input('UserLicenseDetail', []);
                $licenseData = [
                    'dateOfExpiry' => $data['licence_exp_date'] ?? null,
                    'documentNumber' => $user->licence_number,
                    'user_id' => $userId,
                    'lastName' => $licenseInput['lastName'] ?? null,
                    'givenName' => $licenseInput['givenName'] ?? null,
                    'dateOfBirth' => $licenseInput['dateOfBirth'] ?? null,
                    'addressStreet' => $licenseInput['addressStreet'] ?? null,
                    'addressCity' => $licenseInput['addressCity'] ?? null,
                    'addressState' => $licenseInput['addressState'] ?? null,
                    'addressPostalCode' => $licenseInput['addressPostalCode'] ?? null,
                    'jurisdictionRestrictionCodes' => $licenseInput['jurisdictionRestrictionCodes'] ?? '',
                    'jurisdictionEndorsementCodes' => $licenseInput['jurisdictionEndorsementCodes'] ?? '',
                    'eyeColor' => $licenseInput['eyeColor'] ?? '',
                    'height' => $licenseInput['height'] ?? '',
                    'documentDiscriminator' => $licenseInput['documentDiscriminator'] ?? '',
                    'issuer' => $licenseInput['issuer'] ?? '',
                ];

                try {
                    $userLicenseDetail = UserLicenseDetail::updateOrCreate(
                        ['user_id' => $userId],
                        $licenseData
                    );
                } catch (QueryException $e) {
                    // dd($e->getMessage());
                }

                if (!empty($data['updatelicense'])) {
                    $checkrStatus = $this->updateCandidateToDriverBackgroundReport($userId);
                    if ($checkrStatus['status']) {
                        $user->checkr_status = 2;
                        $user->save();
                    } else {
                        $user->checkr_status = 4;
                        $user->save();
                        return redirect()->back()->with('error', $checkrStatus['message']);
                    }
                }
            }

            if (empty($userId)) {
                AdminUserAssociation::saveLeadAssociation($user->username, $userId);
                return redirect('admin/users/index')->with('success', "User has been added successfully.");
            }

            return redirect()->back()->with('success', "User has been updated successfully.");
        }

        $user = null;

        if (!empty($userId)) {
            $user = LegacyUser::with('userLicenseDetail')->find($userId);
            if ($user) {
                if ($user->licence_number) {
                    $user->licence_number = LegacySecurity::decrypt($user->licence_number);
                }
                if ($user->userLicenseDetail && $user->userLicenseDetail->documentNumber) {
                    $user->userLicenseDetail->documentNumber = LegacySecurity::decrypt($user->userLicenseDetail->documentNumber);
                }
            }
        }

        $currencies = LegacyNumber::getCurrencies();
        return view('admin.users.add', compact('user', 'title', 'currencies'));
    }
    public function bankdetails(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Connect with Stripe';
        $userId = $this->decodeId($id);

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $data = $request->input('User', []);
            $user = LegacyUser::find($userId);

            if ($user) {
                $data['is_owner'] = 1;
                $validator = Validator::make($data, [
                    'business_type' => 'required',
                    'ss_no' => 'required_if:business_type,individual|nullable|string|max:50',
                    'ein_no' => 'required_if:business_type,company|nullable|string|max:50',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $user->fill($data);
                $user->save();

                return redirect('admin/users/index')->with('success', 'Bank Account Details updated successfully.');
            }
        }

        $user = null;

        if (!empty($userId)) {
            $user = LegacyUser::find($userId);

            if (!$user) {
                return redirect('admin/users/index')->with('error', 'User not found.');
            }

            if (!$user->is_owner) {
                return redirect()->back();
            }
        }

        return view('admin.users.bankdetails', compact('user', 'title'));
    }
    public function getmystripeurl(Request $request)
    {
        $return = [
            'status' => false,
            'message' => "Something went wrong",
            'result' => []
        ];

        $userId = $request->input('User.id');
        $businessType = $request->input('User.business_type');

        if (!empty($userId)) {
            $user = LegacyUser::find($userId);

            if ($user) {
                $base64Id = $this->decodeId($userId);
                $siteUrl = config('app.url');
                $oauth_url = config('legacy.STRIPE.oauth_url');
                $clientId = config('legacy.STRIPE.client_id');
                $queryParams = [
                    'response_type' => 'code',
                    'client_id' => $clientId,
                    'scope' => 'read_write',
                    'state' => $base64Id,
                    'stripe_user' => [
                        'business_type' => $businessType,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'country' => 'US',
                        'phone_number' => $user->contact_number,
                        'street_address' => $user->address,
                        'city' => $user->city,
                        'state' => $user->state,
                        'zip' => $user->zip,
                    ],
                ];

                if (app()->environment('local')) {
                    $queryParams['redirect_uri'] = "{$siteUrl}/StripeAuths/index";
                } else {
                    $queryParams['stripe_user']['business_name'] = "{$user->first_name} {$user->last_name}";
                }

                $url = "{$oauth_url}?" . http_build_query($queryParams);

                $einInput = $request->input('User.ein_no', '');
                $ssnInput = $request->input('User.ss_no', '');
                $ein = !empty($einInput) ? LegacySecurity::encrypt($einInput) : '';
                $ssn = !empty($ssnInput) ? LegacySecurity::encrypt($ssnInput) : '';

                if (
                    $businessType == 'individual'
                    || $businessType == 'company'
                ) {
                    // $user->update([
                    //     'ss_no' => $businessType == 'individual' ? $ssn : null,
                    //     'ein_no' => $businessType == 'company' ? $ein : null,
                    // ]);

                    $return = [
                        'status' => true,
                        'message' => "You will be redirected to Stripe portal",
                        'result' => ["url" => $url]
                    ];
                }
            }
        }

        return response()->json($return);
    }
    public function getstripeloginurl(Request $request)
    {
        $return = [
            'status' => false,
            'message' => "Something went wrong",
            'result' => []
        ];

        $stripeKey = $request->input('stripekey');

        if (!empty($stripeKey)) {
            $paymentProcessorObj = new PaymentProcessor();
            $return = $paymentProcessorObj->createLoginLink($stripeKey);
        }

        return response()->json($return);
    }





    public function loadPayoutSchedule(Request $request)
    {
        // Use admin_load_payout_schedule — a modal partial with full payout form
        return view('admin.users.load_payout_schedule', [
            'token' => (string) $request->input('stripekey', ''),
            'Loadeddata' => [],  // Stripe payout schedule not migrated yet
        ]);
    }

    public function updatePayoutSchedule(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Payout schedule update is not migrated in Laravel yet. Use legacy flow.',
        ]);
    }

    public function revsetting(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = $this->decodeId($id);
        if (!$userId) {
            return redirect('/admin/users/index');
        }

        $revSetting = RevSetting::query()->where('user_id', $userId)->first();

        if ($request->isMethod('POST')) {
            // admin_revsetting.blade.php uses flat field names (rev, transfer_rev, etc.)
            $save = [
                'user_id' => $userId,
                'rev' => (float) $request->input('rev', 0),
                'transfer_rev' => (int) $request->input('transfer_rev', 0),
                'transfer_insu' => (int) $request->input('transfer_insu', 0),
                'rental_rev' => (float) $request->input('rental_rev', 0),
                'tax_included' => (int) $request->input('tax_included', 0),
                'dia_fee' => (float) $request->input('dia_fee', 0),
            ];

            RevSetting::query()->updateOrCreate(
                ['user_id' => $userId],
                $save
            );

            return redirect('/admin/users/index')->with('success', 'Revenue setting updated successfully.');
        }

        return view('admin.users.revsetting', [
            'user_id' => $userId,
            'revSetting' => $revSetting,
        ]);
    }

    public function change_phone(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if (!$userId) {
            return redirect('/admin/users/index')->with('error', 'Sorry, you are not authorize user for this action.');
        }

        $user = LegacyUser::query()->find($userId);
        if (!$user) {
            return redirect('/admin/users/index')->with('error', 'Sorry, you are not authorize user for this action.');
        }

        if ($request->isMethod('POST')) {
            // admin_change_phone.blade.php uses flat field names (contact_number, old_username)
            $contact = trim((string) $request->input('contact_number', ''));
            $oldUsername = trim((string) $request->input('old_username', $user->username ?? ''));
            $username = substr(preg_replace('/[^0-9]/', '', $contact), -10);

            if ($username === '') {
                return redirect('/admin/users/change_phone/' . base64_encode((string) $userId))
                    ->with('error', 'Please enter a valid phone number.');
            }

            $save = [
                'contact_number' => $username,
                'username' => $username,
            ];

            if ($username !== $oldUsername) {
                $save['is_verified'] = 0;
                $save['status'] = 0;
                $save['verify_token'] = (string) random_int(10000, 99999);
            }

            LegacyUser::query()->whereKey($userId)->update($save);

            return redirect('/admin/users/index')->with('success', 'Phone number updated successfully.');
        }

        return view('admin.users.change_phone', [
            'listTitle' => 'Change Phone#',
            'id' => $userId,
            'user' => $user,
        ]);
    }

    public function showargyldetails(Request $request)
    {
        $encoded = (string) $request->input('userid', '');
        $userId = $this->decodeId($encoded);
        $argyleUser = null;
        $records = collect();

        if ($userId) {
            $argyleUser = ArgyleUser::query()->where('user_id', $userId)->first();
            if ($argyleUser) {
                $records = ArgyleUserRecord::query()
                    ->where('argyle_user_id', (int) $argyleUser->id)
                    ->orderBy('account')
                    ->get(['account', 'account_id']);
            }
        }

        // admin_showargyldetails uses $ArgyleUser array structure — pass both for compatibility
        return view('admin.users.showargyldetails', [
            'argyleUser' => $argyleUser,
            'argyleRecords' => $records,
            'ArgyleUser' => $argyleUser ? $argyleUser->toArray() : [],
        ]);
    }

    public function address_proof_popup(Request $request)
    {
        $userId = $this->decodeId((string) $request->input('userid', ''));
        if (!$userId) {
            return response('Invalid user id', 400);
        }

        return view('admin.users._addresspopup', ['userid' => $userId]);
    }

    public function saveaddressproof(Request $request): JsonResponse
    {
        $userId = (int) $request->input('userid', 0);
        $file = $request->file('proofimage');
        if ($userId <= 0 || !$file) {
            return response()->json(['error' => 'No files were uploaded.']);
        }
        if (!$file->isValid()) {
            return response()->json(['error' => 'Upload Error #' . (int) $file->getError()]);
        }
        if ($file->getSize() === 0) {
            return response()->json(['error' => 'File is empty.']);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return response()->json(['error' => 'File is too large.', 'preventRetry' => true]);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['jpeg', 'jpg', 'png'], true)) {
            return response()->json(['error' => 'File has an invalid extension, it should be one of jpeg, jpg, png.']);
        }

        $dir = public_path('files/userdocs');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filename = 'address_doc_' . $userId . '_' . random_int(1, 100) . '.' . $ext;
        $file->move($dir, $filename);

        $user = LegacyUser::query()->find($userId);
        if (!$user) {
            return response()->json(['error' => 'Invalid user id.']);
        }
        $docs = [];
        if (!empty($user->address_doc)) {
            $decoded = json_decode((string) $user->address_doc, true);
            if (is_array($decoded)) {
                $docs = $decoded;
            }
        }
        $docs[] = $filename;
        LegacyUser::query()->whereKey($userId)->update(['address_doc' => json_encode($docs)]);

        return response()->json(['success' => true, 'key' => $userId]);
    }

    public function getDriverLicense(Request $request): JsonResponse
    {
        $userId = $this->decodeId((string) $request->input('userid', ''));
        $pick = (int) $request->input('pick', 1);
        $return = ['status' => false, 'message' => 'Invalid User ID', 'result' => []];

        if (!$userId) {
            return response()->json($return);
        }

        $user = LegacyUser::query()
            ->whereKey($userId)
            ->first(['license_doc_1', 'license_doc_2']);

        if (!$user) {
            return response()->json($return);
        }

        if ($pick === 1) {
            $file = (string) ($user->license_doc_1 ?? '');
            if ($file !== '' && File::exists(public_path('files/userdocs/' . $file))) {
                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                    'result' => ['file' => url('files/userdocs/' . $file)],
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'sorry, document not exists',
                'result' => [],
            ]);
        }

        if ($pick === 2) {
            $file = (string) ($user->license_doc_2 ?? '');
            if ($file !== '' && File::exists(public_path('files/userdocs/' . $file))) {
                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                    'result' => ['file' => url('files/userdocs/' . $file)],
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'sorry, document not exists',
                'result' => [],
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid pick value',
            'result' => [],
        ]);
    }

    public function dealer_approve(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update([
                'is_dealer' => ((string) $status === '1') ? 1 : 2,
            ]);
        }

        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'Dealer status is changed successfully.');
    }

    public function checkr_status(Request $request, $id = null)
    {
        $userId = $this->decodeId((string) $id);
        if (!$userId) {
            return redirect('/admin/users/index');
        }

        // Mirror CakePHP admin_checkr_status action logic
        $userReport = DB::table('user_reports')->where('user_id', $userId)->first();

        if (empty($userReport)) {
            $CheckrStatus = $this->addCandidateToDriverBackgroundReport($userId);
            if ($CheckrStatus['status']) {
                session()->flash('success', "User is added to Checkr API for processing");
            } else {
                session()->flash('error', $CheckrStatus['message']);
            }
        } elseif (!empty($userReport->status) && !empty($userReport->checkr_reportid)) {
            $report = $this->pullBackgroundReport($userId);
            if ($report['status']) {
                session()->flash('success', "User Report is Ready");
            } else {
                session()->flash('error', $report['message']);
            }
        } elseif (!empty($userReport) && (int) $userReport->status === 0) {
            $CheckrReport = $this->createBackgroundReport($userId);
            if ($CheckrReport['status']) {
                session()->flash('success', "User Report is requested");
            } else {
                session()->flash('error', $CheckrReport['message']);
            }
        }

        // Match CakePHP exactly: always redirect back after triggering/pulling report
        return redirect()->back();
    }

    public function checkrreport(Request $request): JsonResponse
    {
        $redirect = $this->ensureAdminSession();
        if ($redirect) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }

        $reportId = (int) $request->input('id', 0);
        if (!$reportId) {
            return response()->json(['status' => false, 'message' => 'Invalid report ID']);
        }

        $report = DB::table('user_reports')->where('id', $reportId)->first();
        if (!$report) {
            return response()->json(['status' => false, 'message' => 'Report not found']);
        }

        $reportData = !empty($report->report) ? json_decode($report->report, true) : [];

        return response()->json([
            'status' => true,
            'report' => $reportData,
            'user_id' => $report->user_id,
            'checkr_id' => $report->checkr_id ?? '',
            'motor_vehicle_report_id' => $report->motor_vehicle_report_id ?? '',
        ]);
    }

    private function redirectBackOr(string $fallback, Request $request)
    {
        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }
        return redirect($fallback);
    }
}

