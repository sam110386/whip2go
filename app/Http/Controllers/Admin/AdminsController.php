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

/**
 * Admin end stub: CakePHP `AdminsController`.
 * Stored under `App\Http\Controllers\Admin` per migration requirement.
 */
class AdminsController extends LegacyAppController
{
    use PerformsSessionLogout;

    protected bool $shouldLoadLegacyModules = false;

    public function login(Request $request)
    {
        return $this->admin_login($request);
    }

    // CakePHP action name under `/admin` prefix.
    public function admin_login(Request $request)
    {
        // If already logged in, redirect to correct dashboard based on role slug.
        $sessionAdmin = session()->get('SESSION_ADMIN', []);
        $adminId = is_array($sessionAdmin) ? ($sessionAdmin['id'] ?? null) : null;
        if (!empty($adminId)) {
            $slug = is_array($sessionAdmin) ? ($sessionAdmin['slug'] ?? null) : null;
            if (!empty($slug)) {
                return redirect('/' . $slug . '/homes/dashboard');
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

        $userName = (string)($request->input('username') ?? $request->input('User.username') ?? '');
        $passwordPlain = (string)($request->input('password') ?? $request->input('User.password') ?? '');

        if ($userName === '' || $passwordPlain === '') {
            return view('admin.admins.login', [
                'referred_url' => $referredUrl,
                'error' => 'Username/password required.',
                'username' => $userName,
            ]);
        }

        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        $passwordNew = sha1($salt . $passwordPlain);

        $userinfo = LegacyUser::query()
            ->with('role')
            ->where('username', $userName)
            ->where('is_admin', 1)
            ->first();

        $userinfoArr = $userinfo ? $userinfo->toArray() : [];
        if (!empty($userinfoArr) && !empty($userinfo) && !empty($userinfo->role) && !empty($userinfo->role->slug)) {
            $userinfoArr['slug'] = $userinfo->role->slug;
        }

        if (empty($userinfoArr) || empty($userinfoArr['password']) || (string)$userinfoArr['password'] !== (string)$passwordNew || ((string)($userinfoArr['status'] ?? '') !== '1' && (int)($userinfoArr['status'] ?? 0) !== 1)) {
            return view('admin.admins.login', [
                'referred_url' => $referredUrl,
                'error' => 'Invalid username/password.',
                'username' => $userName,
            ]);
        }

        // session payload mimics Cake: write full user row + slug.
        $sessionAdminPayload = $userinfoArr;

        session()->put('SESSION_ADMIN', $sessionAdminPayload);
        session()->put('adminRoleId', (int)($userinfoArr['role_id'] ?? 0));

        $fullName = trim((string)($userinfoArr['first_name'] ?? '') . ' ' . (string)($userinfoArr['last_name'] ?? ''));
        session()->put('adminName', $fullName);
        session()->put('default_timezone', $userinfoArr['timezone'] ?? null);

        // Permissions
        $roleId = (int)($userinfoArr['role_id'] ?? 0);
        $permissionIds = LegacyAdminRolePermission::query()
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();
        session()->put('permissions', $permissionIds);

        if (!empty($referredUrl)) {
            return redirect('/' . $referredUrl);
        }

        $slug = (string)($userinfoArr['slug'] ?? '');
        if ($slug === '') {
            return redirect('/admin/admins/login');
        }
        return redirect('/' . $slug . '/homes/dashboard');
    }

    // CakePHP: app/Controller/AdminsController.php::admin_logout()
    public function admin_logout(Request $request)
    {
        return $this->performSessionLogout('/admin/admins/login');
    }

    // CakePHP: app/Controller/AdminsController.php::admin_index()
    // URL: /admin/admins/index
    public function admin_index(Request $request)
    {
        // Cake supports search/filter via request params; we accept query params for now.
        $keyword = trim((string)($request->query('keyword') ?? ''));
        $searchin = trim((string)($request->query('searchin') ?? ''));
        $showtype = trim((string)($request->query('showtype') ?? ''));

        $status = null;
        if ($showtype !== '') {
            // Cake: 'Active' => status 1, 'Deactive' => status 0
            if (strcasecmp($showtype, 'Active') === 0) {
                $status = 1;
            } elseif (strcasecmp($showtype, 'Deactive') === 0) {
                $status = 0;
            }
        }

        $q = LegacyUser::query()
            ->with('role')
            ->where('is_admin', 1)
            ->orderByDesc('id');

        if ($status !== null) {
            $q->where('status', $status);
        }

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
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

        $users = $q->limit(50)->get()->map(function (LegacyUser $u) {
            $row = $u->toArray();
            $row['role_name'] = (!empty($u->role) && isset($u->role->name)) ? $u->role->name : '';
            return (object) $row;
        });

        return view('admin.admins.index', [
            'users' => $users,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'showtype' => $showtype,
            'showArr' => ['Active' => 'Active', 'Deactive' => 'Inactive'],
            'options' => [
                'username' => 'Username',
                'first_name' => 'Firstname',
                'email' => 'Email',
            ],
        ]);
    }

    // CakePHP: app/Controller/AdminsController.php::admin_status($id, $status)
    // URL: /admin/admins/admin_status/{base64_user_id}/{status}
    public function admin_status(Request $request, $id = null, $status = null)
    {
        // Cake sends base64-encoded user id in the URL.
        $decodedId = null;
        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string)$id;
        }

        if ($decodedId !== null && $decodedId !== '') {
            $newStatus = ((string)$status === '1') ? 1 : 0;
            LegacyUser::query()
                ->whereKey((int) $decodedId)
                ->where('is_admin', 1)
                ->update(['status' => $newStatus]);
        }

        // Keep navigation parity with Cake's `$this->redirect($this->referer())`.
        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }

        return redirect('/admin/admins/index');
    }

    // CakePHP: app/Controller/AdminsController.php::admin_add($id = null)
    // URL examples:
    // - /admin/admins/admin_add
    // - /admin/admins/admin_add/{base64_user_id}
    public function admin_add(Request $request, $id = null)
    {
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $isEditing = !empty($id);
        $decodedId = null;
        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string)$id;
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
                'formAction' => $isEditing ? '/admin/admins/admin_add/' . $id : '/admin/admins/admin_add',
            ]);
        }

        $payload = $request->input('User', []);
        $username = trim((string)($payload['username'] ?? ''));
        $firstName = trim((string)($payload['first_name'] ?? ''));
        $lastName = trim((string)($payload['last_name'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $contact = trim((string)($payload['contact_number'] ?? ''));
        $roleId = $payload['role_id'] ?? null;
        $status = ((string)($payload['status'] ?? '1') === '0') ? 0 : 1;
        $staffRoleIds = $payload['staff_role_id'] ?? [];

        if ($username === '' || $firstName === '' || $lastName === '' || $email === '') {
            return view('admin.admins.add', [
                'listTitle' => $isEditing ? 'Update Admin User' : 'Add Admin User',
                'user' => $user,
                'roles' => $this->getAdminRolesForForm(),
                'userStaffRoleIds' => $staffRoleIds,
                'error' => 'Please fill required fields.',
                'formAction' => $isEditing ? '/admin/admins/admin_add/' . $id : '/admin/admins/admin_add',
            ]);
        }

        // Normalize names like Cake does.
        $firstName = ucwords(strtolower($firstName));
        $lastName = ucwords(strtolower($lastName));

        $userId = $isEditing && $decodedId !== null ? (int)$decodedId : null;
        if (!$userId && !empty($payload['id']) && is_numeric($payload['id'])) {
            $userId = (int)$payload['id'];
        }

        $nowUser = [
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'contact_number' => $contact,
            'role_id' => $roleId,
            'is_admin' => 1,
            'status' => $status,
        ];

        // Password handling follows Cake’s legacy sha1(salt+password).
        if ($isEditing) {
            $newPassword = (string)($payload['newpassword'] ?? '');
            $cnfPassword = (string)($payload['cnfpassword'] ?? '');
            if ($newPassword !== '' || $cnfPassword !== '') {
                if ($newPassword !== $cnfPassword) {
                    return back()->withInput()->with('error', 'Passwords do not match.');
                }
                $nowUser['password'] = sha1($salt . $newPassword);
            }
        } else {
            $passwordPlain = (string)($payload['npwd'] ?? '');
            $confirmPlain = (string)($payload['conpwd'] ?? '');
            if ($passwordPlain === '' || $confirmPlain === '' || $passwordPlain !== $confirmPlain) {
                return view('admin.admins.add', [
                    'listTitle' => 'Add Admin User',
                    'user' => $user,
                    'roles' => $this->getAdminRolesForForm(),
                    'userStaffRoleIds' => $staffRoleIds,
                    'error' => 'Password/confirm password mismatch.',
                    'formAction' => '/admin/admins/admin_add',
                ]);
            }
            $nowUser['password'] = sha1($salt . $passwordPlain);
        }

        if ($userId) {
            LegacyUser::query()->whereKey((int) $userId)->update($nowUser);
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
                'user_id' => (int)$userId,
                'role_id' => (int)$rid,
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

    // CakePHP: app/Controller/AdminsController.php::admin_change_password()
    public function admin_change_password(Request $request)
    {
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        $admin = session()->get('SESSION_ADMIN');
        $adminId = is_array($admin) ? ($admin['id'] ?? null) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => null,
            ]);
        }

        $payload = $request->input('User', []);
        $oldPassword = (string)($payload['oldPassword'] ?? '');
        $newPassword = (string)($payload['newpassword'] ?? '');
        $confirmPassword = (string)($payload['confirmpassword'] ?? '');

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

        if ($stored === null || (string)$stored !== (string)$expectedOldHash) {
            return view('admin.admins.change_password', [
                'title_for_layout' => 'Change Password',
                'error' => 'Old password is incorrect.',
            ]);
        }

        LegacyUser::query()
            ->whereKey((int) $adminId)
            ->update(['password' => sha1($salt . $newPassword)]);

        return redirect('/admin/homes/dashboard');
    }

    // CakePHP: app/Controller/AdminsController.php::admin_profile()
    // URL: /admin/admins/admin_profile
    public function admin_profile(Request $request)
    {
        $admin = session()->get('SESSION_ADMIN');
        $adminId = is_array($admin) ? ($admin['id'] ?? null) : null;
        if (empty($adminId)) {
            return redirect('/admin/admins/login');
        }

        if (!$request->isMethod('POST')) {
            $user = LegacyUser::query()
                ->whereKey((int) $adminId)
                ->where('is_admin', 1)
                ->first();

            return view('admin.admins.profile', [
                'listTitle' => 'Update Profile',
                'user' => $user,
                'error' => null,
            ]);
        }

        $payload = $request->input('User', []);

        $firstName = trim((string)($payload['first_name'] ?? ''));
        $lastName = trim((string)($payload['last_name'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $contact = trim((string)($payload['contact_number'] ?? ''));
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
            // Optional address/profile fields (if present in schema).
            'address1' => isset($payload['address1']) ? (string)$payload['address1'] : null,
            'address2' => isset($payload['address2']) ? (string)$payload['address2'] : null,
            'city' => isset($payload['city']) ? (string)$payload['city'] : null,
            'state_id' => isset($payload['state_id']) ? (string)$payload['state_id'] : null,
            'timezone' => isset($payload['timezone']) ? (string)$payload['timezone'] : null,
            'status' => $status !== null && $status !== '' ? (int)$status : null,
        ];

        $update = $this->filterExistingUserColumns($candidateUpdate);

        if (!empty($update)) {
            LegacyUser::query()
                ->whereKey((int) $adminId)
                ->where('is_admin', 1)
                ->update($update);
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
}

