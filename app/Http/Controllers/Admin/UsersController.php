<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\ArgyleUser;
use App\Models\Legacy\ArgyleUserRecord;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\User as LegacyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UsersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $keyword = trim((string) ($request->query('keyword') ?? ''));
        $show = trim((string) ($request->query('show') ?? ''));
        $type = trim((string) ($request->query('type') ?? ''));

        $q = LegacyUser::query()
            ->where('is_admin', 0)
            ->orderByDesc('id');

        if ($show === 'Active') {
            $q->where('status', 1);
        } elseif ($show === 'Deactive') {
            $q->where('status', 0);
        }

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
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

        $users = $q->limit(50)->get();

        return view('admin.users.index', [
            'listTitle' => 'Manage Users',
            'users' => $users,
            'keyword' => $keyword,
            'show' => $show,
            'type' => $type,
        ]);
    }

    public function status(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['status' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function trash(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['trash' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function verify(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update([
                'is_verified' => 1,
                'verify_token' => '',
            ]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function driverstatus(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['is_driver' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
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
        return redirect('/admin/users/index');
    }

    public function add(Request $request, $id = null)
    {
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.users.add', [
                'listTitle' => $user ? 'Update User' : 'Add User',
                'user' => $user,
                'error' => null,
                'formAction' => $user ? '/admin/users/add/' . $id : '/admin/users/add',
            ]);
        }

        $payload = $request->input('User', []);
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $contact = trim((string) ($payload['contact_number'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $contact === '') {
            return view('admin.users.add', [
                'listTitle' => $user ? 'Update User' : 'Add User',
                'user' => $user,
                'error' => 'Please fill required fields.',
                'formAction' => $user ? '/admin/users/add/' . $id : '/admin/users/add',
            ]);
        }

        $data = [
            'first_name' => ucwords(strtolower($firstName)),
            'last_name' => ucwords(strtolower($lastName)),
            'email' => $email,
            'contact_number' => $contact,
        ];

        if (!$user) {
            $usernameDigits = preg_replace('/[^0-9]/', '', $contact);
            $data['username'] = (string) $usernameDigits;
            $data['is_verified'] = 1;
            $data['status'] = 1;
            $pwd = (string) ($payload['pwd'] ?? '');
            if ($pwd !== '') {
                $data['password'] = sha1($salt . $pwd);
            }
            LegacyUser::query()->create($data);
        } else {
            $pwd = (string) ($payload['pwd'] ?? '');
            if ($pwd !== '') {
                $data['password'] = sha1($salt . $pwd);
            }
            LegacyUser::query()->whereKey((int) $user->id)->update($data);
        }

        return redirect('/admin/users/index');
    }

    public function bankdetails(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;
        if (!$user) {
            return redirect('/admin/users/index');
        }
        if ((int)($user->is_owner ?? 0) !== 1) {
            return redirect('/admin/users/index');
        }

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('User', []);
            LegacyUser::query()->whereKey($userId)->update([
                'business_type' => (string)($payload['business_type'] ?? ($user->business_type ?? 'individual')),
                'ss_no' => (string)($payload['ss_no'] ?? ''),
                'ein_no' => (string)($payload['ein_no'] ?? ''),
                'is_owner' => 1,
            ]);

            return redirect('/admin/users/index')->with('success', 'Bank Account Details updated successfully.');
        }

        return view('admin.users.bankdetails', [
            'listTitle' => 'Connect with Stripe',
            'user' => $user,
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
        return view('admin.users.load_payout_schedule', [
            'token' => (string)$request->input('stripekey', ''),
            'Loadeddata' => [],
            'migrationGap' => 'Payout schedule management via Stripe is not migrated in Laravel yet.',
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
        $userId = $this->decodeId($id);
        if (!$userId) {
            return redirect('/admin/users/index');
        }

        $revSetting = RevSetting::query()->where('user_id', $userId)->first();

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('RevSetting', []);
            $save = [
                'id' => isset($payload['id']) && $payload['id'] !== '' ? (int)$payload['id'] : ($revSetting->id ?? null),
                'user_id' => $userId,
                'rev' => isset($payload['rev']) ? (float)$payload['rev'] : 0,
                'transfer_rev' => isset($payload['transfer_rev']) ? (int)$payload['transfer_rev'] : 0,
                'transfer_insu' => isset($payload['transfer_insu']) ? (int)$payload['transfer_insu'] : 0,
                'rental_rev' => isset($payload['rental_rev']) ? (float)$payload['rental_rev'] : 0,
                'tax_included' => isset($payload['tax_included']) ? (int)$payload['tax_included'] : 0,
                'dia_fee' => isset($payload['dia_fee']) ? (float)$payload['dia_fee'] : 0,
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
            $payload = (array)$request->input('User', []);
            $contact = trim((string)($payload['contact_number'] ?? ''));
            $oldUsername = (string)($payload['old_username'] ?? ($user->username ?? ''));
            $username = substr(preg_replace('/[^0-9]/', '', $contact), -10);

            if ($username === '') {
                return redirect('/admin/users/change_phone/' . base64_encode((string)$userId))
                    ->with('error', 'Please enter a valid phone number.');
            }

            $save = [
                'id' => $userId,
                'contact_number' => $username,
                'username' => $username,
            ];

            if ($username !== $oldUsername) {
                $save['is_verified'] = 0;
                $save['status'] = 0;
                $save['verify_token'] = (string)random_int(10000, 99999);
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
        $encoded = (string)$request->input('userid', '');
        $userId = $this->decodeId($encoded);
        $argyleUser = null;
        $records = collect();

        if ($userId) {
            $argyleUser = ArgyleUser::query()->where('user_id', $userId)->first();
            if ($argyleUser) {
                $records = ArgyleUserRecord::query()
                    ->where('argyle_user_id', (int)$argyleUser->id)
                    ->orderBy('account')
                    ->get(['account', 'account_id']);
            }
        }

        return view('admin.users.showargyldetails', [
            'argyleUser' => $argyleUser,
            'argyleRecords' => $records,
        ]);
    }

    public function address_proof_popup(Request $request)
    {
        $userId = $this->decodeId((string)$request->input('userid', ''));
        if (!$userId) {
            return response('Invalid user id', 400);
        }

        return view('admin.users._addresspopup', ['userid' => $userId]);
    }

    public function saveaddressproof(Request $request): JsonResponse
    {
        $userId = (int)$request->input('userid', 0);
        $file = $request->file('proofimage');
        if ($userId <= 0 || !$file) {
            return response()->json(['error' => 'No files were uploaded.']);
        }
        if (!$file->isValid()) {
            return response()->json(['error' => 'Upload Error #' . (int)$file->getError()]);
        }
        if ($file->getSize() === 0) {
            return response()->json(['error' => 'File is empty.']);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return response()->json(['error' => 'File is too large.', 'preventRetry' => true]);
        }

        $ext = strtolower((string)$file->getClientOriginalExtension());
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
            $decoded = json_decode((string)$user->address_doc, true);
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
        $userId = $this->decodeId((string)$request->input('userid', ''));
        $pick = (int)$request->input('pick', 1);
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
            $file = (string)($user->license_doc_1 ?? '');
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
            $file = (string)($user->license_doc_2 ?? '');
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
                'is_dealer' => ((string)$status === '1') ? 1 : 2,
            ]);
        }

        return $this->redirectBackOr('/admin/users/index', $request)
            ->with('success', 'Dealer status is changed successfully.');
    }

    public function checkr_status(Request $request, $id = null)
    {
        $redirect = $this->ensureAdminSession();
        if ($redirect) {
            return $redirect;
        }

        $userId = $this->decodeId((string) $id);
        if (!$userId) {
            return redirect('/admin/users/index');
        }

        $user = DB::table('users')
            ->where('id', $userId)
            ->first(['id', 'first_name', 'last_name', 'checkr_status', 'email']);

        if (!$user) {
            return redirect('/admin/users/index');
        }

        $reports = DB::table('user_reports')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        return view('admin.users.checkr_status', [
            'user' => $user,
            'reports' => $reports,
            'basePath' => '/admin/users',
        ]);
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

