<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Traits\PerformsSessionLogout;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\AdminRole as LegacyAdminRole;
use App\Models\Legacy\AdminRolePermission as LegacyAdminRolePermission;
use App\Models\Legacy\AdminUserRole as LegacyAdminUserRole;
use App\Models\Legacy\EmailTemplate as LegacyEmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminsController extends LegacyAppController
{
    use PerformsSessionLogout;

    protected bool $shouldLoadLegacyModules = true;

    public function login(Request $request)
    {
        $sessionAdmin = session()->get('SESSION_ADMIN', []);
        $adminId = is_array($sessionAdmin) ? ($sessionAdmin['id'] ?? null) : null;

        if (!empty($adminId)) {
            $slug = is_array($sessionAdmin) ? ($sessionAdmin['slug'] ?? null) : null;

            if (!empty($slug)) {
                return redirect("/{$slug}/homes/dashboard");
            }

            return redirect('/admin/admins/login');
        }

        $referredUrl = $request->input('referred_url') ?? $request->query('referred_url');

        if (!empty($referredUrl) && is_string($referredUrl)) {
            $referredUrl = base64_decode(trim($referredUrl), true) ?: $referredUrl;
        }

        if (!$request->isMethod('POST')) {
            return view('admin.admins.login', [
                'referred_url' => $referredUrl,
                'error' => null,
            ]);
        }

        $userName = (string) ($request->input('username') ?? $request->input('User.username') ?? '');
        $passwordPlain = (string) ($request->input('password') ?? $request->input('User.password') ?? '');

        if ($userName === '' || $passwordPlain === '') {
            return view('admin.admins.login', [
                'referred_url' => $referredUrl,
                'error' => 'Username/password required.',
                'username' => $userName,
            ]);
        }

        $salt = config('legacy.security.salt', '');
        $passwordNew = sha1("{$salt}{$passwordPlain}");

        $userinfo = LegacyUser::query()
            ->with('role')
            ->where('username', $userName)
            ->where('is_admin', 1)
            ->first();

        $userinfoArr = $userinfo ? $userinfo->toArray() : [];

        if (!empty($userinfoArr) && !empty($userinfo) && !empty($userinfo->role) && !empty($userinfo->role->slug)) {
            $userinfoArr['slug'] = $userinfo->role->slug;
        }

        if (empty($userinfoArr) || empty($userinfoArr['password']) || (string) $userinfoArr['password'] !== (string) $passwordNew || ((string) ($userinfoArr['status'] ?? '') !== '1' && (int) ($userinfoArr['status'] ?? 0) !== 1)) {
            return view('admin.admins.login', [
                'referred_url' => $referredUrl,
                'error' => 'Invalid username/password.',
                'username' => $userName,
            ]);
        }

        $sessionAdminPayload = $userinfoArr;

        session()->put('SESSION_ADMIN', $sessionAdminPayload);
        session()->put('adminRoleId', (int) ($userinfoArr['role_id'] ?? 0));

        $fullName = trim((string) ($userinfoArr['first_name'] ?? '') . ' ' . (string) ($userinfoArr['last_name'] ?? ''));

        session()->put('adminName', $fullName);
        session()->put('default_timezone', $userinfoArr['timezone'] ?? null);

        $roleId = (int) ($userinfoArr['role_id'] ?? 0);
        $permissionIds = LegacyAdminRolePermission::query()
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();

        session()->put('permissions', $permissionIds);

        if (!empty($referredUrl)) {
            return redirect("/{$referredUrl}");
        }

        $slug = (string) ($userinfoArr['slug'] ?? '');

        if ($slug === '') {
            return redirect('/admin/admins/login');
        }

        return redirect("/{$slug}/homes/dashboard");
    }

    public function logout(Request $request)
    {
        return $this->performSessionLogout('/admin/admins/login');
    }

    public function index(Request $request)
    {
        $sessionAdmin = session()->get('SESSION_ADMIN', []);
        $adminRoleId = session()->get('adminRoleId');
        $currentAdminId = is_array($sessionAdmin) ? ($sessionAdmin['id'] ?? null) : null;
        $keyword = trim((string) ($request->query('keyword') ?? ''));
        $searchin = trim((string) ($request->query('searchin') ?? ''));
        $showtype = trim((string) ($request->query('showtype') ?? ''));
        $limit = (int) $request->input('limit', 50);

        if (!in_array($limit, [10, 20, 50, 100, 200, 500])) {
            $limit = 50;
        }

        $status = null;
        if ($showtype !== '') {
            // Cake: 'Active' => status 1, 'Deactive' => status 0
            if (strcasecmp($showtype, 'Active') === 0) {
                $status = 1;
            } elseif (strcasecmp($showtype, 'Deactive') === 0) {
                $status = 0;
            }
        }

        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'desc');
        $allowedSort = ['id', 'created', 'status'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $q = LegacyUser::query()
            ->with('role')
            ->where('is_admin', 1)
            ->whereNotIn('id', array_filter([1, $currentAdminId]))
            ->orderBy($sort, $direction);

        if ($adminRoleId != 1) {
            $q->where('parent_id', $currentAdminId);
        }

        if ($status !== null) {
            $q->where('status', $status);
        }

        if ($keyword !== '') {
            $like = "%{$keyword}%";

            $q->where(function ($qq) use ($like, $searchin) {
                if ($searchin === '' || strcasecmp($searchin, 'All') === 0) {
                    $qq->where('username', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like);
                } else {
                    $fieldMap = [
                        'username' => 'username',
                        'first_name' => 'first_name',
                        'email' => 'email',
                        'contact_number' => 'contact_number',
                    ];
                    $field = $fieldMap[$searchin] ?? 'username';
                    $qq->where($field, 'like', $like);
                }
            });
        }

        $users = $q->paginate($limit);

        // Map role name for use in view
        $users->getCollection()->transform(function (LegacyUser $u) {
            $u->role_name = (!empty($u->role) && isset($u->role->name)) ? $u->role->name : '';
            return $u;
        });

        if ($request->ajax()) {
            return view('admin.admins._index_table', [
                'users' => $users,
                'limit' => $limit,
            ]);
        }

        return view('admin.admins.index', [
            'users' => $users,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'showtype' => $showtype,
            'limit' => $limit,
            'showArr' => ['Active' => 'Active', 'Deactive' => 'Inactive'],
            'options' => [
                'username' => 'Username',
                'first_name' => 'Firstname',
                'email' => 'Email',
            ],
        ]);
    }

    public function dashboard()
    {
        return redirect('/admin/homes/dashboard');
    }

    public function status(Request $request, $id = null, $status = null)
    {
        $decodedId = null;

        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string) $id;
        }

        if ($decodedId !== null && $decodedId !== '') {
            $newStatus = ((string) $status === '1') ? 1 : 0;
            $user = LegacyUser::query()
                ->whereKey((int) $decodedId)
                ->where('is_admin', 1)
                ->first();

            if ($user) {
                $user->update(['status' => $newStatus]);
            }

            session()->flash('success', 'Status updated successfully.');
        }

        $referer = $request->headers->get('referer');

        if (!empty($referer)) {
            return redirect()->to($referer);
        }

        return redirect('/admin/admins/index');
    }

    public function add(Request $request, $id = null)
    {
        $salt = config('legacy.security.salt', '');
        $isEditing = !empty($id);
        $decodedId = null;

        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string) $id;
        }

        $user = null;

        if ($isEditing && $decodedId !== null) {
            $user = LegacyUser::query()
                ->whereKey((int) $decodedId)
                ->where('is_admin', 1)
                ->first();
        }

        if (!$request->isMethod('POST')) {
            $roles = LegacyAdminRole::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn($r) => [(string) $r->id => $r->name])
                ->toArray();

            $userStaffRoleIds = [];

            if ($isEditing && $user) {
                $userStaffRoleIds = LegacyAdminUserRole::query()
                    ->where('user_id', (int) $user->id)
                    ->pluck('role_id')
                    ->toArray();
            }

            return view('admin.admins.add', [
                'listTitle' => $isEditing ? 'Update Admin User' : 'Add Admin User',
                'user' => $user,
                'roles' => $roles,
                'userStaffRoleIds' => $userStaffRoleIds,
                'formAction' => $isEditing ? "/admin/admins/add/{$id}" : "/admin/admins/add",
            ]);
        }

        $payload = $request->input('User', []);
        $username = trim((string) ($payload['username'] ?? ''));
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $contact = trim((string) ($payload['contact_number'] ?? ''));
        $roleId = $payload['role_id'] ?? null;
        $status = ((string) ($payload['status'] ?? '1') === '0') ? 0 : 1;
        $staffRoleIds = $payload['staff_role_id'] ?? [];

        $userId = $isEditing && $decodedId !== null ? (int) $decodedId : null;
        if (!$userId && !empty($payload['id']) && is_numeric($payload['id'])) {
            $userId = (int) $payload['id'];
        }

        if ($firstName === '' || $lastName === '' || $email === '' || (!$userId && $username === '')) {
            return view('admin.admins.add', [
                'listTitle' => $isEditing ? 'Update Admin User' : 'Add Admin User',
                'user' => $user,
                'roles' => $this->getAdminRolesForForm(),
                'userStaffRoleIds' => $staffRoleIds,
                'error' => 'Please fill required fields.',
                'formAction' => $isEditing ? "/admin/admins/add/{$id}" : "/admin/admins/add",
            ]);
        }

        // Normalize names like Cake does.
        $firstName = ucwords(strtolower($firstName));
        $lastName = ucwords(strtolower($lastName));

        $nowUser = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'contact_number' => $contact,
            'address' => trim((string) ($payload['address'] ?? '')),
            'city' => trim((string) ($payload['city'] ?? '')),
            'state' => trim((string) ($payload['state'] ?? '')),
            'role_id' => $roleId,
            'is_admin' => 1,
            'status' => $status,
        ];

        if (!$userId) {
            $nowUser['username'] = $username;
        }

        // Password handling follows Cake’s legacy sha1(salt+password).
        if ($isEditing) {
            $newPassword = (string) ($payload['newpassword'] ?? '');
            $cnfPassword = (string) ($payload['cnfpassword'] ?? '');
            if ($newPassword !== '' || $cnfPassword !== '') {
                if ($newPassword !== $cnfPassword) {
                    return back()->withInput()->with('error', 'Passwords do not match.');
                }
                $nowUser['password'] = sha1($salt . $newPassword);
            }
        } else {
            $passwordPlain = (string) ($payload['npwd'] ?? '');
            $confirmPlain = (string) ($payload['conpwd'] ?? '');
            if ($passwordPlain === '' || $confirmPlain === '' || $passwordPlain !== $confirmPlain) {
                return view('admin.admins.add', [
                    'listTitle' => 'Add Admin User',
                    'user' => $user,
                    'roles' => $this->getAdminRolesForForm(),
                    'userStaffRoleIds' => $staffRoleIds,
                    'error' => 'Password/confirm password mismatch.',
                    'formAction' => '/admin/admins/add',
                ]);
            }
            $nowUser['password'] = sha1($salt . $passwordPlain);
        }

        if ($userId) {
            $existingUser = LegacyUser::query()->whereKey((int) $userId)->first();
            if ($existingUser) {
                $existingUser->update($nowUser);
            }
        } else {
            $userId = LegacyUser::query()->create($nowUser)->id;
        }

        // Update staff role mappings (admin_user_roles).
        if (!is_array($staffRoleIds)) {
            $staffRoleIds = [];
        }

        LegacyAdminUserRole::query()
            ->where('user_id', (int) $userId)
            ->delete();

        foreach ($staffRoleIds as $rid) {
            if ($rid === null || $rid === '') {
                continue;
            }
            LegacyAdminUserRole::query()->create([
                'user_id' => (int) $userId,
                'role_id' => (int) $rid,
            ]);
        }

        return redirect('/admin/admins/index');
    }

    private function getAdminRolesForForm(): array
    {
        return LegacyAdminRole::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn($r) => [(string) $r->id => $r->name])
            ->toArray();
    }

    public function change_password(Request $request)
    {
        $salt = config('legacy.security.salt', '');
        $admin = session()->get('SESSION_ADMIN');
        $adminId = is_array($admin) ? ($admin['id'] ?? null) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => null,
            ]);
        }

        $payload = $request->input('User', []);
        $oldPassword = (string) ($payload['oldPassword'] ?? '');
        $newPassword = (string) ($payload['newpassword'] ?? '');
        $confirmPassword = (string) ($payload['confirmpassword'] ?? '');

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => 'All fields are required.',
            ]);
        }

        if ($newPassword !== $confirmPassword) {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => 'Passwords do not match.',
            ]);
        }

        if (empty($adminId)) {
            return redirect('/admin/admins/login');
        }

        $expectedOldHash = sha1($salt . $oldPassword);
        $stored = LegacyUser::query()
            ->whereKey((int) $adminId)
            ->where('is_admin', 1)
            ->value('password');

        if ($stored === null || (string) $stored !== (string) $expectedOldHash) {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => 'Old password is incorrect.',
            ]);
        }

        $user = LegacyUser::query()->whereKey((int) $adminId)->where('is_admin', 1)->first();
        if ($user) {
            $user->update(['password' => sha1($salt . $newPassword)]);
        }

        return redirect('/admin/homes/dashboard');
    }

    public function profile(Request $request)
    {
        $admin = session()->get('SESSION_ADMIN');
        $adminId = is_array($admin) ? ($admin['id'] ?? null) : null;
        if (empty($adminId)) {
            return redirect('/admin/admins/login');
        }

        $user = LegacyUser::query()
            ->whereKey((int) $adminId)
            ->where('is_admin', 1)
            ->first();

        if (!$request->isMethod('POST')) {
            return view('admin.admins.profile', [
                'listTitle' => 'Update Profile',
                'user' => $user,
                'error' => null,
            ]);
        }

        $payload = $request->input('User', []);

        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $contact = trim((string) ($payload['contact_number'] ?? ''));
        $status = $payload['status'] ?? null;

        // Cake normalizes names.
        if ($firstName !== '') {
            $firstName = ucwords(strtolower($firstName));
        }
        if ($lastName !== '') {
            $lastName = ucwords(strtolower($lastName));
        }

        $candidateUpdate = [
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => $email !== '' ? $email : null,
            'contact_number' => $contact !== '' ? $contact : null,
            'address' => isset($payload['address']) ? (string) $payload['address'] : null,
            'city' => isset($payload['city']) ? (string) $payload['city'] : null,
            'state' => isset($payload['state']) ? (string) $payload['state'] : null,
            'timezone' => isset($payload['timezone']) ? (string) $payload['timezone'] : null,
            'status' => $status !== null && $status !== '' ? (int) $status : null,
        ];

        $update = $this->filterExistingUserColumns($candidateUpdate);

        if (!empty($update)) {
            $user->update($update);
        }

        // Keep session payload roughly in sync for anything consuming these fields.
        $updatedRow = LegacyUser::query()
            ->whereKey((int) $adminId)
            ->first();

        if (!empty($updatedRow)) {
            session()->put('SESSION_ADMIN', $updatedRow->toArray());
        }

        return redirect('/admin/admins/index');
    }

    private function filterExistingUserColumns(array $candidate): array
    {
        $table = 'users';
        $filtered = [];
        foreach ($candidate as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (Schema::hasColumn($table, $col)) {
                $filtered[$col] = $val;
            }
        }
        return $filtered;
    }

    public function delete(Request $request, $id = null)
    {
        $decodedId = null;

        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string) $id;
        }

        if ($decodedId !== null && $decodedId !== '') {
            LegacyUser::query()
                ->whereKey((int) $decodedId)
                ->where('is_admin', 1)
                ->delete();

            session()->flash('success', 'Admin user deleted successfully.');
        }

        return redirect('/admin/admins/index');
    }

    public function multiplAction(Request $request)
    {
        $payload = $request->input('User', []);
        $action = $payload['status'] ?? '';
        $selectedIds = $request->input('select', []);

        if (!empty($selectedIds) && is_array($selectedIds)) {
            if ($action === 'active') {
                LegacyUser::query()->whereIn('id', $selectedIds)->where('is_admin', 1)->update(['status' => 1]);
                session()->flash('success', 'Selected users activated.');
            } elseif ($action === 'inactive') {
                LegacyUser::query()->whereIn('id', $selectedIds)->where('is_admin', 1)->update(['status' => 0]);
                session()->flash('success', 'Selected users deactivated.');
            } elseif ($action === 'del') {
                LegacyUser::query()->whereIn('id', $selectedIds)->where('is_admin', 1)->delete();
                session()->flash('success', 'Selected users deleted.');
            }
        }

        return redirect('/admin/admins/index');
    }

    public function forgotPassword(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return view('admin.admins.forgot_password');
        }

        $email = $request->input('User.email');
        $user = LegacyUser::query()->where('email', $email)->where('is_admin', 1)->first();

        if ($user) {
            $newPassword = Str::random(8);
            $salt = config('legacy.security.salt', '');
            $user->password = sha1($salt . $newPassword);
            $user->save();

            $template = LegacyEmailTemplate::find(2);
            if ($template) {
                $body = $template->description;
                $body = str_replace('[USERNAME]', $user->username, $body);
                $body = str_replace('[PASSWORD]', $newPassword, $body);
                $body = str_replace('[FIRST_NAME]', $user->first_name, $body);
                $body = str_replace('[LAST_NAME]', $user->last_name, $body);
                $body = str_replace('[DATE]', date('m-d-Y'), $body);
                $body = str_replace('[LOGIN_LINK]', url('/admin/admins/login'), $body);

                Mail::html($body, function ($message) use ($user, $template) {
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                        ->subject($template->subject)
                        ->from($template->from_email ?: config('mail.from.address'));
                });

                return redirect('/admin/admins/login')->with('success', 'New password sent to your email.');
            }
        }

        return back()->with('error', 'Email not found or invalid.');
    }
}

