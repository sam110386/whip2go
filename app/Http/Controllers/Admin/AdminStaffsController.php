<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminUserRole;
use App\Models\Legacy\User as LegacyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStaffsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;
    protected function staffBasePath(): string
    {
        return '/admin/admin_staffs';
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        $currentUserId = (int) ($admin['admin_id'] ?? 0);

        $limit = $this->resolveLimit($request, 'admin_staffs_limit');

        $keyword = $this->searchInput($request, 'keyword');
        $searchin = $this->searchInput($request, 'searchin');
        $show = $this->searchInput($request, 'show') ?? $this->searchInput($request, 'showtype');
        $fieldname = $searchin === '' ? 'All' : $searchin;

        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'desc');
        $allowedSort = ['id', 'created', 'status'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $q = LegacyUser::query()
            ->select('users.*', 'admin_roles.name as role_name')
            ->join('admin_roles', 'users.role_id', '=', 'admin_roles.id')
            ->where('users.is_admin', 1)
            ->where('users.id', '!=', $currentUserId)
            ->orderBy($sort, $direction);

        $this->applyStaffScope($q, $admin);

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($fieldname === '' || strcasecmp($fieldname, 'All') === 0) {
                $q->where(function ($qq) use ($like) {
                    $qq->where('users.first_name', 'like', $like)
                        ->orWhere('users.username', 'like', $like)
                        ->orWhere('users.email', 'like', $like);
                });
            } else {
                $allowed = ['username', 'first_name', 'email'];
                $col = in_array($fieldname, $allowed, true) ? $fieldname : 'username';
                $q->where('users.' . $col, 'like', $like);
            }
        }

        if ($show !== '' && $show !== null && strcasecmp((string) $show, 'All') !== 0) {
            if (strcasecmp((string) $show, 'Active') === 0) {
                $q->where('users.status', 1);
            } elseif (strcasecmp((string) $show, 'Deactive') === 0 || strcasecmp((string) $show, 'Inactive') === 0) {
                $q->where('users.status', 0);
            }
        }

        $users = $q->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.admin_staffs._index_table', [
                'users' => $users,
                'limit' => $limit,
            ]);
        }

        return view('admin.admin_staffs.index', [
            'users' => $users,
            'keyword' => $keyword,
            'show' => $show ?? '',
            'fieldname' => $fieldname,
            'limit' => $limit,
            'options' => [
                'username' => 'Username',
                'first_name' => 'Firstname',
                'email' => 'Email',
            ],
            'basePath' => $this->staffBasePath(),
        ]);
    }

    public function multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $status = (string) $request->input('User.status', $request->input('data.User.status', ''));
        $select = $request->input('select', $request->input('data.select', []));
        if (!is_array($select)) {
            $select = [];
        }
        $ids = array_values(array_filter(array_map('intval', $select)));

        if ($ids !== []) {
            $q = LegacyUser::query()->whereIn('id', $ids)->where('is_admin', 1);
            $this->applyStaffScope($q, $admin);
            $scopedIds = $q->pluck('id')->all();

            if ($status === 'active') {
                LegacyUser::query()->whereIn('id', $scopedIds)->update(['status' => 1]);
            } elseif ($status === 'inactive') {
                LegacyUser::query()->whereIn('id', $scopedIds)->update(['status' => 0]);
            } elseif ($status === 'del') {
                foreach ($scopedIds as $id) {
                    AdminUserRole::query()->where('user_id', $id)->delete();
                    LegacyUser::query()->whereKey($id)->delete();
                }
            }
        }

        $kw = trim((string) $request->input('Search.keyword', $request->input('data.Search.keyword', '')));
        $si = trim((string) $request->input('Search.searchin', $request->input('data.Search.searchin', '')));
        $st = trim((string) $request->input('Search.show', $request->input('data.Search.show', '')));

        if ($kw !== '' && $si !== '' && $st !== '') {
            return redirect($this->adminsSearchRedirectUrl($kw, $si, $st));
        }

        return redirect('/admin/admin_staffs/index');
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/admin_staffs/index')->with('error', 'Sorry, you are not authorized user for this action');
        }
        $parentId = (int) ($admin['parent_id'] ?? 0);

        $decodedId = $this->decodeIdParam($id);
        $user = $decodedId
            ? LegacyUser::query()
                ->whereKey($decodedId)
                ->where('is_admin', 1)
                ->first()
            : null;

        if ($user && !$this->staffRowAllowed($user, $parentId)) {
            return redirect('/admin/admin_staffs/index')->with('error', 'Staff user not found.');
        }

        $roles = $this->rolesForParent($parentId);

        if ($request->isMethod('POST')) {
            $payload = (array) $request->input('User', []);
            $saved = $this->saveStaffUser($payload, $parentId, $user);
            if ($saved instanceof \Illuminate\Http\RedirectResponse) {
                return $saved;
            }

            return view('admin.admin_staffs.add', [
                'listTitle' => $user ? 'Update Staff User' : 'Add Staff User',
                'user' => $saved,
                'roles' => $roles,
                'basePath' => $this->staffBasePath(),
                'formAction' => $user
                    ? '/admin/admin_staffs/add/' . base64_encode((string) $user->id)
                    : '/admin/admin_staffs/add',
            ]);
        }

        return view('admin.admin_staffs.add', [
            'listTitle' => $user ? 'Update Staff User' : 'Add Staff User',
            'user' => $user,
            'roles' => $roles,
            'basePath' => $this->staffBasePath(),
            'formAction' => $user
                ? '/admin/admin_staffs/add/' . base64_encode((string) $user->id)
                : '/admin/admin_staffs/add',
        ]);
    }

    public function status(Request $request, $id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $decoded = $this->decodeIdParam($id);
        if ($decoded) {
            $user = LegacyUser::query()->whereKey($decoded)->where('is_admin', 1)->first();
            if ($user && $this->staffRowAllowed($user, (int) ($admin['parent_id'] ?? 0))) {
                $newStatus = ((string) $status === '1') ? 1 : 0;
                LegacyUser::query()->whereKey($decoded)->update(['status' => $newStatus]);
            }
        }

        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }

        return redirect('/admin/admin_staffs/index')->with('success', 'Status updated.');
    }

    public function delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        $decoded = $this->decodeIdParam($id) ?? (is_numeric($id) ? (int) $id : null);
        if ($decoded) {
            $user = LegacyUser::query()->whereKey($decoded)->where('is_admin', 1)->first();
            if ($user && $this->staffRowAllowed($user, (int) ($admin['parent_id'] ?? 0))) {
                AdminUserRole::query()->where('user_id', $decoded)->delete();
                LegacyUser::query()->whereKey($decoded)->delete();
            }
        }

        return redirect('/admin/admin_staffs/index')->with('success', 'User deleted.');
    }

    protected function applyStaffScope($query, array $admin): void
    {
        if (!empty($admin['administrator'])) {
            $query->where('users.parent_id', '!=', 0);
        } else {
            $query->where('users.parent_id', (int) ($admin['parent_id'] ?? 0));
        }
    }

    protected function staffRowAllowed(?LegacyUser $user, int $parentId): bool
    {
        if (!$user) {
            return false;
        }
        if ($parentId <= 0) {
            return false;
        }

        return (int) $user->parent_id === $parentId;
    }

    private function rolesForParent(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        return DB::table('admin_user_roles as aur')
            ->join('admin_roles as ar', 'ar.id', '=', 'aur.role_id')
            ->where('aur.user_id', $parentId)
            ->orderBy('ar.name')
            ->pluck('ar.name', 'ar.id')
            ->mapWithKeys(fn($name, $id) => [(string) $id => $name])
            ->toArray();
    }

    /**
     * @return RedirectResponse|object Staff row for re-render on validation error
     */
    private function saveStaffUser(array $payload, int $parentId, ?LegacyUser $existing)
    {
        $roleId = $payload['role_id'] ?? null;
        $firstName = ucwords(strtolower(trim((string) ($payload['first_name'] ?? ''))));
        $lastName = ucwords(strtolower(trim((string) ($payload['last_name'] ?? ''))));
        $email = trim((string) ($payload['email'] ?? ''));
        $username = trim((string) ($payload['username'] ?? ''));
        $status = ((string) ($payload['status'] ?? '1') === '0') ? 0 : 1;
        $address1 = trim((string) ($payload['address1'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));
        $otherState = trim((string) ($payload['other_state'] ?? ''));
        $contact = trim((string) ($payload['contact_number'] ?? ''));

        if ($roleId === null || $roleId === '' || $firstName === '' || $lastName === '' || $email === '') {
            session()->flash('error', 'Please fill required fields.');

            return (object) array_merge($payload, ['id' => $existing ? $existing->id : null]);
        }

        $roleIdInt = (int) $roleId;
        if (!$this->roleAllowedForParent($parentId, $roleIdInt)) {
            session()->flash('error', 'Invalid role selection.');

            return (object) array_merge($payload, ['id' => $existing ? $existing->id : null]);
        }

        $now = [
            'role_id' => $roleIdInt,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'parent_id' => $parentId,
            'is_admin' => 1,
            'status' => $status,
            'address1' => $address1,
            'city' => $city,
            'other_state' => $otherState,
            'contact_number' => $contact,
        ];

        if ($existing) {
            $newPassword = (string) ($payload['newpassword'] ?? '');
            $cnfPassword = (string) ($payload['cnfpassword'] ?? '');
            if ($newPassword !== '' || $cnfPassword !== '') {
                if ($newPassword !== $cnfPassword) {
                    session()->flash('error', 'Passwords do not match.');

                    return (object) array_merge($existing->toArray(), $payload);
                }
                $now['password'] = sha1(config('legacy.security.salt', '') . $newPassword);
            }
            LegacyUser::query()->whereKey((int) $existing->id)->update($now);

            return redirect('/admin/admin_staffs/index')->with('success', 'Staff user updated.');
        }

        if ($username === '') {
            session()->flash('error', 'Username is required.');

            return (object) array_merge($payload, ['id' => null]);
        }

        $passwordPlain = (string) ($payload['npwd'] ?? '');
        $confirmPlain = (string) ($payload['conpwd'] ?? '');
        if ($passwordPlain === '' || $confirmPlain === '' || $passwordPlain !== $confirmPlain) {
            session()->flash('error', 'Password/confirm password mismatch.');

            return (object) array_merge($payload, ['id' => null]);
        }

        $now['username'] = $username;
        $now['password'] = sha1(config('legacy.security.salt', '') . $passwordPlain);
        $now['created'] = now()->toDateTimeString();

        LegacyUser::query()->create($now);

        return redirect('/admin/admin_staffs/index')->with('success', 'Admin user created successfully');
    }

    private function roleAllowedForParent(int $parentId, int $roleId): bool
    {
        return DB::table('admin_user_roles')
            ->where('user_id', $parentId)
            ->where('role_id', $roleId)
            ->exists();
    }

    protected function searchInput(Request $request, string $key): string
    {
        $v = $request->query($key);
        if ($v === null) {
            $v = $request->input($key);
        }
        if ($v === null && $request->has("Search.$key")) {
            $v = $request->input("Search.$key");
        }

        return trim((string) $v);
    }

    protected function resolveLimit(Request $request, string $sessionKey): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put($sessionKey, $lim);

                return $lim;
            }
        }

        $sess = (int) session()->get($sessionKey, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }

    protected function decodeIdParam($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (is_string($id)) {
            $tmp = base64_decode($id, true);
            if ($tmp !== false && ctype_digit((string) $tmp)) {
                return (int) $tmp;
            }
        }
        if (is_numeric($id)) {
            return (int) $id;
        }

        return null;
    }

    protected function adminsSearchRedirectUrl(string $keyword, string $searchin, string $show): string
    {
        return '/admin/admins/index?keyword=' . rawurlencode($keyword) . '&searchin=' . rawurlencode($searchin) . '&showtype=' . rawurlencode($show);
    }
}
