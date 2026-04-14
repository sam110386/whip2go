<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\ArgyleUser;
use App\Models\Legacy\ArgyleUserRecord;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\User as LegacyUser;
use App\Helpers\Legacy\Security as LegacySecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Helpers\Legacy\Number as LegacyNumber;

class UsersController extends LegacyAppController
{
    use UsersTrait, DriverBackgroundReport;

    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $keyword = trim((string) ($request->query('keyword') ?? ''));
        $show = trim((string) ($request->query('show') ?? ''));
        $type = trim((string) ($request->query('type') ?? ''));
        
        // Respect the "Records per page" limit from the request
        $limit = (int) $request->input('Record.limit', $request->input('limit', 50));
        
        // Ensure limit is within a reasonable range
        if (!in_array($limit, [25, 50, 100, 200])) {
            $limit = 50;
        }

        // Handle sorting
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        // Allowed sortable columns
        $allowedSort = [
            'id', 'first_name', 'last_name', 'email', 'contact_number', 
            'created', 'status', 'is_verified', 'is_renter', 
            'is_driver', 'is_dealer', 'checkr_status', 'trash'
        ];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }

        $q = LegacyUser::query()
            ->where('is_admin', 0)
            ->orderBy($sort, $direction);

        if ($show === 'Active') {
            $q->where('status', 1);
        } elseif ($show === 'Deactive') {
            $q->where('status', 0);
        }

        if ($keyword !== '') {
            $like = "%{$keyword}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('business_name', 'like', $like);
            });
        }

        if ($type === '1') {
            $q->where('is_verified', 1);
        } elseif ($type === '2') {
            $q->where('is_verified', 0);
        } elseif ($type === '3') {
            $q->where('is_renter', 1);
        } elseif ($type === '4') {
            $q->where('is_driver', 1);
        } elseif ($type === '5') {
            $q->where('is_dealer', 1);
        } elseif ($type === '6') {
            $q->where('is_dealer', 2);
        }

        $users = $q->paginate($limit);

        if (request()->ajax()) {
            return view('admin.elements.users.index', [
                'users' => $users,
                'keyword' => $keyword,
                'show' => $show,
                'type' => $type,
                'limit' => $limit,
            ]);
        }

        return view('admin.users.index', [
            'listTitle' => 'Manage Users',
            'users' => $users,
            'keyword' => $keyword,
            'show' => $show,
            'type' => $type,
            'limit' => $limit,
        ]);
    }

    public function status(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['status' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'User status has been changed.');
    }

    public function trash(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['trash' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'User status has been changed.');
    }

    public function verify(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update([
                'is_verified' => 1,
                'verify_token' => '',
            ]);
            // Mirror CakePHP admin_verify: save lead association on verification
            $user = LegacyUser::query()->whereKey($userId)->first(['username']);
            if ($user && !empty($user->username)) {
                AdminUserAssociation::saveLeadAssociation($user->username, $userId);
            }
        }
        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'User status has been changed.');
    }

    public function driverstatus(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['is_driver' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'User status has been changed.');
    }

    public function view(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;
        if (!$user) {
            return redirect('/admin/users/index');
        }
        return view('admin.users.view', [
            'listTitle' => 'View User',
            'user' => $user,
        ]);
    }

    public function delete(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->delete();
        }
        return redirect('/admin/users/index')
            ->with('success', 'User has been deleted successfully.');
    }

    public function add(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        $user   = $userId ? LegacyUser::query()->with('userLicenseDetail')->find($userId) : null;

        // GET: render the form
        if (!$request->isMethod('POST')) {
            return $this->renderAddForm($user);
        }

        // POST: delegate all validation, saving, uploads, and relations
        //       to _saveUser() defined in UsersTrait
        $result = $this->_saveUser($request, $userId ?: null);

        if (!($result['status'] ?? false)) {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'An error occurred.');
        }

        return redirect('/admin/users/index')
            ->with('success', $result['message']);
    }

    /**
     * Shared view-data builder for the add/edit form.
     */
    private function renderAddForm(?LegacyUser $user)
    {
        return view('admin.users.add', [
            'listTitle'  => $user ? 'Update User' : 'Add User',
            'user'       => $user,
            'currencies' => LegacyNumber::getCurrencies(),
        ]);
    }

    public function bankdetails(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;
        if (!$user) {
            return redirect('/admin/users/index');
        }
        if ((int) ($user->is_owner ?? 0) !== 1) {
            return redirect('/admin/users/index');
        }

        if ($request->isMethod('POST')) {
            // Flat field names match admin_bankdetails.blade.php
            $businessType = (string) $request->input('business_type', $user->business_type ?? 'individual');
            $ssNo  = trim((string) $request->input('ss_no', ''));
            $einNo = trim((string) $request->input('ein_no', ''));

            LegacyUser::query()->whereKey($userId)->update([
                'business_type' => $businessType,
                // Encrypt sensitive fields to match CakePHP admin_bankdetails
                'ss_no'         => $ssNo  !== '' ? LegacySecurity::encrypt($ssNo)  : '',
                'ein_no'        => $einNo !== '' ? LegacySecurity::encrypt($einNo) : '',
                'is_owner'      => 1,
            ]);

            return redirect('/admin/users/index')->with('success', 'Bank Account Details updated successfully.');
        }

        return view('admin.users.bankdetails', [
            'listTitle' => 'Connect with Stripe',
            'user'      => $user,
            'id'        => $userId,
        ]);
    }

    public function getmystripeurl(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Stripe connect flow is not migrated in Laravel yet. Use legacy flow.',
            'result' => [],
        ]);
    }

    public function getstripeloginurl(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Stripe login link is not migrated in Laravel yet. Use legacy flow.',
            'result' => [],
        ]);
    }

    public function loadPayoutSchedule(Request $request)
    {
        // Use admin_load_payout_schedule — a modal partial with full payout form
        return view('admin.users.load_payout_schedule', [
            'token'      => (string) $request->input('stripekey', ''),
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
                'user_id'      => $userId,
                'rev'          => (float) $request->input('rev', 0),
                'transfer_rev' => (int)   $request->input('transfer_rev', 0),
                'transfer_insu'=> (int)   $request->input('transfer_insu', 0),
                'rental_rev'   => (float) $request->input('rental_rev', 0),
                'tax_included' => (int)   $request->input('tax_included', 0),
                'dia_fee'      => (float) $request->input('dia_fee', 0),
            ];

            RevSetting::query()->updateOrCreate(
                ['user_id' => $userId],
                $save
            );

            return redirect('/admin/users/index')->with('success', 'Revenue setting updated successfully.');
        }

        return view('admin.users.revsetting', [
            'user_id'    => $userId,
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
            $contact     = trim((string) $request->input('contact_number', ''));
            $oldUsername = trim((string) $request->input('old_username', $user->username ?? ''));
            $username    = substr(preg_replace('/[^0-9]/', '', $contact), -10);

            if ($username === '') {
                return redirect('/admin/users/change_phone/' . base64_encode((string) $userId))
                    ->with('error', 'Please enter a valid phone number.');
            }

            $save = [
                'contact_number' => $username,
                'username'       => $username,
            ];

            if ($username !== $oldUsername) {
                $save['is_verified']  = 0;
                $save['status']       = 0;
                $save['verify_token'] = (string) random_int(10000, 99999);
            }

            LegacyUser::query()->whereKey($userId)->update($save);

            return redirect('/admin/users/index')->with('success', 'Phone number updated successfully.');
        }

        return view('admin.users.change_phone', [
            'listTitle' => 'Change Phone#',
            'id'        => $userId,
            'user'      => $user,
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
            'argyleUser'    => $argyleUser,
            'argyleRecords' => $records,
            'ArgyleUser'    => $argyleUser ? $argyleUser->toArray() : [],
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
        } elseif (!empty($userReport) && (int)$userReport->status === 0) {
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

