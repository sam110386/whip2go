<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Legacy\Security;
use App\Models\Legacy\AdminUserRole;
use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PerformsSessionLogout;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\AdminRole as LegacyAdminRole;
use App\Models\Legacy\AdminRolePermission as LegacyAdminRolePermission;
use App\Models\Legacy\AdminUserRole as LegacyAdminUserRole;
use App\Models\Legacy\EmailTemplate as LegacyEmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class AdminsController extends LegacyAppController
{
    use PerformsSessionLogout;

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

    public function dashboard()
    {
        return redirect('/admin/homes/dashboard');
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

    public function index(Request $request)
    {
        $title = "Admin Users";
        $sessLimitName = "admins_limit";
        $userId = Session::get('SESSION_ADMIN.id');
        $roleId = Session::get('adminRoleId');
        $showArr = $this->getStatus();
        $options = [
            'username' => 'Username',
            'first_name' => 'Firstname',
            'email' => 'Email'
        ];

        $query = LegacyUser::with('role:id,name')
            ->where('is_admin', 1)
            ->whereNotIn('id', [1, $userId]);

        if ($roleId != 1) {
            $query->where('parent_id', $userId);
        }

        $searchIn = $request->input('searchin', $request->query('searchin', ''));
        $keyword = $request->input('keyword', $request->query('keyword', ''));
        $showtype = $request->input('showtype', $request->query('showtype', ''));
        $searchin = empty($searchIn) ? 'All' : $searchIn;

        if (!empty($keyword)) {
            $query->where(function ($q) use ($searchin, $keyword) {
                if ($searchin === 'All') {
                    $q->where('first_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('username', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%");
                } elseif (in_array($searchin, ['username', 'first_name', 'email'])) {
                    $q->where($searchin, 'LIKE', "%{$keyword}%");
                }
            });
        }

        if (!empty($showtype) && $showtype !== 'All') {
            $matchShow = ($showtype === 'Active') ? 1 : 0;
            $query->where('status', $matchShow);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessLimitName, $limit);
        } else {
            $limit = Session::get($sessLimitName, $this->recordsPerPage);
        }

        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $users = $query->orderBy($sort, $direction)->paginate($limit);

        if ($request->ajax()) {
            return view('admin.admins.elements.index', compact('users', 'limit'));
        }

        return view('admin.admins.index', compact('title', 'users', 'keyword', 'showtype', 'searchin', 'showArr', 'options', 'limit'));
    }
    public function add(Request $request, $id = null)
    {
        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Admin User' : 'Add Admin User';
        $message = !empty($id) ? 'Admin user updated successfully.' : 'Admin user created successfully';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $rules = [
                'User.first_name' => ['required', 'regex:/^[a-zA-Z0-9 ]{1,50}$/'],
                'User.email' => ['required', 'email', 'unique:users,email,' . $id],
                'User.username' => ['required', 'unique:users,username,' . $id],
                'User.contact_number' => [
                    'required',
                    'unique:users,contact_number,' . $id,
                    function ($attribute, $value, $fail) {
                        $clean = preg_replace("/[^0-9]/", "", $value);
                        if (strlen($clean) < 10) {
                            $fail('Please enter a valid phone number.');
                        }
                    },
                ],
            ];

            $messages = [
                'User.first_name.required' => 'Please enter your first name.',
                'User.first_name.regex' => 'Please enter a valid first name.',
                'User.email.required' => 'Please enter your email.',
                'User.email.email' => 'Please enter a valid email address.',
                'User.email.unique' => 'Email already exists, please choose another email.',
                'User.username.required' => 'Please enter a username',
                'User.username.unique' => 'Username already exists, please choose another.',
                'User.contact_number.required' => 'Please enter phone number.',
                'User.contact_number.unique' => 'Phone number already exists, please choose another.',
            ];
            $request->validate($rules, $messages);

            $userData = $request->input('User');
            $userData['first_name'] = Str::title(strtolower($userData['first_name']));
            $userData['last_name'] = Str::title(strtolower($userData['last_name']));
            $userData['is_admin'] = 1;

            if (!empty($userData['npwd'])) {
                $userData['password'] = Security::hash($userData['npwd'], null, true);
            }

            if (!empty($userData['newpassword']) && !empty($userData['cnfpassword'])) {
                $userData['password'] = Security::hash($userData['newpassword'], null, true);
            }

            $user = LegacyUser::updateOrCreate(
                ['id' => $id],
                $userData
            );

            AdminUserRole::where('user_id', $user->id)->delete();

            if (!empty($userData['staff_role_id'])) {
                foreach ($userData['staff_role_id'] as $roleId) {
                    AdminUserRole::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                    ]);
                }
            }

            return redirect('admin/admins/index')->with('success', $message);
        }

        $user = $id ? LegacyUser::findOrFail($id) : new LegacyUser();
        $roles = $this->commonService->getAdminRoleList();
        return view('admin.admins.add', compact('title', 'user', 'roles'));
    }
    public function profile(Request $request)
    {
        $id = Session::get('SESSION_ADMIN.id');
        $title = 'Update Profile';

        if (!$id) {
            return redirect('admin/admins/login')->with('error', 'Unauthorized access.');
        }

        $user = LegacyUser::findOrFail($id);

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $userPayload = $request->input('User', []);

            if (!isset($userPayload['contact_number']) && isset($userPayload['phone1'])) {
                $userPayload['contact_number'] = implode('', (array) $userPayload['phone1']);
            }
            if (!isset($userPayload['address']) && isset($userPayload['address1'])) {
                $userPayload['address'] = trim(($userPayload['address1'] ?? '') . ' ' . ($userPayload['address2'] ?? ''));
            }
            if (!isset($userPayload['state']) && isset($userPayload['state_id'])) {
                $userPayload['state'] = $userPayload['state_id'] == '1110' ? ($userPayload['other_state'] ?? '') : $userPayload['state_id'];
            }

            $request->merge(['User' => $userPayload]);

            $rules = [
                'User.first_name' => ['required', 'regex:/^[a-zA-Z0-9 ]{1,50}$/'],
                'User.email' => ['required', 'email', 'unique:users,email,' . $id],
                'User.username' => ['required', 'unique:users,username,' . $id],
                'User.contact_number' => [
                    'required',
                    'unique:users,contact_number,' . $id,
                    function ($attribute, $value, $fail) {
                        $clean = preg_replace("/[^0-9]/", "", $value);
                        if (strlen($clean) < 10) {
                            $fail('Please enter a valid phone number.');
                        }
                    },
                ],
            ];

            $messages = [
                'User.first_name.required' => 'Please enter your first name.',
                'User.first_name.regex' => 'Please enter a valid first name.',
                'User.email.required' => 'Please enter your email.',
                'User.email.email' => 'Please enter a valid email address.',
                'User.email.unique' => 'Email already exists, please choose another email.',
                'User.username.required' => 'Please enter a username',
                'User.username.unique' => 'Username already exists, please choose another.',
                'User.contact_number.required' => 'Please enter phone number.',
                'User.contact_number.unique' => 'Phone number already exists, please choose another.',
            ];

            $request->validate($rules, $messages);
            $userData = $request->input('User');

            $userData['first_name'] = ucwords(strtolower($userData['first_name']));
            $userData['last_name'] = ucwords(strtolower($userData['last_name']));
            $user->updateOrFail($userData);
            Session::put('SESSION_ADMIN', $user->toArray());

            return redirect('admin/admins/index')->with('success', 'Admin profile updated successfully.');
        }

        if ($user->contact_number) {
            $clean = preg_replace("/[^0-9]/", "", $user->contact_number);
            if (strlen($clean) >= 10) {
                $user->setAttribute('phone1', [
                    substr($clean, 0, 3),
                    substr($clean, 3, 3),
                    substr($clean, 6)
                ]);
            }
        }
        $user->setAttribute('address1', $user->address);
        $user->setAttribute('state_id', '1110');
        $user->setAttribute('other_state', $user->state);

        return view('admin.admins.profile', compact('title', 'user'));
    }
    public function status($id = null, $status = null)
    {
        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            $newStatus = ($status == 1) ? 1 : 0;
            LegacyUser::where('id', $decodedId)->update(['status' => $newStatus]);
        }

        return back()->with('success', 'Status updated successfully.');
    }
    public function delete($id = null)
    {
        LegacyUser::where('id', $id)->deleteOrFail();
        return redirect('/admin/admins/index')->with('success', 'Admin user deleted successfully.');
    }
}

