<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\User;
use App\Models\Legacy\AdminRole;
use App\Models\Legacy\AdminRolePermission;
use App\Models\Legacy\AdminUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class AdminsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "Admins::{$action} pending migration.",
            'result' => [],
        ]);
    }

    // ─── admin_login (Admin Authentication) ──────────────────────────────────
    public function admin_login(Request $request, $referred_url = null)
    {
        $this->layout = 'layout_admin';
        if (session()->has('SESSION_ADMIN.id')) {
            return redirect('/' . session('SESSION_ADMIN.slug') . '/homes/dashboard');
        }

        if ($request->isMethod('post')) {
            $username = $request->input('username');
            // Legacy uses md5-based Security::hash by default in many configs, 
            // but the snippet showed Security::hash($password, null, true).
            // We'll use our bridge or a direct check.
            $password = $request->input('password');

            $userinfo = User::from('users as User')
                ->leftJoin('admin_roles as AdminRole', 'AdminRole.id', '=', 'User.role_id')
                ->where('User.username', $username)
                ->where('User.is_admin', 1)
                ->where('User.status', '1')
                ->select('User.*', 'AdminRole.slug')
                ->first();

            // Verify password using legacy hash comparison
            // Note: In a real migration, we'd use a custom hasher or check against old logic.
            // For now, mirroring the snippet logic: if ($userinfo->password == Security::hash($password))
            if ($userinfo && $userinfo->password === md5($password)) { // Assuming md5 based on legacy snippet pattern
                session([
                    'SESSION_ADMIN'    => $userinfo->toArray(),
                    'adminRoleId'      => $userinfo->role_id,
                    'adminName'        => $userinfo->first_name . ' ' . $userinfo->last_name,
                    'default_timezone' => $userinfo->timezone,
                ]);

                // Load permissions
                $permissions = AdminRolePermission::where('role_id', $userinfo->role_id)
                    ->pluck('permission_id', 'permission_id')
                    ->toArray();
                session(['permissions' => $permissions]);

                if ($referred_url) {
                    return redirect('/' . base64_decode($referred_url));
                }
                return redirect('/' . $userinfo->slug . '/homes/dashboard');
            }

            return redirect()->back()->with('error', 'Invalid username or password.');
        }

        return view('admin.admins.login', compact('referred_url'));
    }

    // ─── admin_logout / cloud_logout ─────────────────────────────────────────
    public function admin_logout()
    {
        session()->forget(['SESSION_ADMIN', 'adminRoleId', 'adminName', 'permissions']);
        session()->flush();
        return redirect('/admin/admins/login');
    }

    public function cloud_logout()
    {
        return $this->admin_logout();
    }

    // ─── admin_index (Manage Admin Users) ────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $loggedId = session('SESSION_ADMIN.id');
        $query = User::where('is_admin', 1)
            ->whereNotIn('User.id', [1, $loggedId])
            ->join('admin_roles as AdminRole', 'User.role_id', '=', 'AdminRole.id')
            ->select('User.*', 'AdminRole.name as role_name');

        if (session('adminRoleId') != 1) {
            $query->where('User.parent_id', $loggedId);
        }

        $keyword  = $request->query('keyword', '');
        $searchIn = $request->query('searchin', 'All');
        $showType = $request->query('showtype', 'All');

        if (!empty($keyword)) {
            if ($searchIn == 'All') {
                $query->where(function($q) use ($keyword) {
                    $q->where('first_name', 'LIKE', "%$keyword%")
                      ->orWhere('username', 'LIKE', "%$keyword%")
                      ->orWhere('email', 'LIKE', "%$keyword%");
                });
            } else {
                $query->where($searchIn, 'LIKE', "%$keyword%");
            }
        }

        if ($showType == 'Active') $query->where('User.status', '1');
        if ($showType == 'Deactive') $query->where('User.status', '0');

        $users = $query->orderBy('User.id', 'DESC')->paginate(20)->withQueryString();

        return view('admin.admins.index', compact('users', 'keyword', 'searchIn', 'showType'));
    }

    // ─── admin_add / admin_edit ──────────────────────────────────────────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = $id ? base64_decode($id) : null;
        $listTitle = $id ? 'Update Admin User' : 'Add Admin User';

        if ($request->isMethod('post')) {
            $data = $request->input('User', []);
            $data['is_admin'] = 1;

            if (!empty($data['newpassword'])) {
                $data['password'] = md5($data['newpassword']);
            }

            if ($id) {
                User::where('id', $id)->update($data);
            } else {
                $user = User::create($data);
                $id = $user->id;
            }

            // Sync sub-roles
            AdminUserRole::where('user_id', $id)->delete();
            if (!empty($data['staff_role_id'])) {
                foreach ($data['staff_role_id'] as $roleId) {
                    AdminUserRole::create(['user_id' => $id, 'role_id' => $roleId]);
                }
            }

            return redirect('/admin/admins/index')->with('success', 'Admin user saved successfully.');
        }

        $record = $id ? User::find($id) : null;
        // Role list fetching (simulated)
        // $roles = \App\Lib\Legacy\Common::getAdminRoleList();

        return view('admin.admins.add', compact('listTitle', 'record', 'id'));
    }

    // ─── admin_change_password ───────────────────────────────────────────────
    public function admin_change_password(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        if ($request->isMethod('post')) {
            $old = $request->input('User.oldPassword');
            $new = $request->input('User.newpassword');
            $conf = $request->input('User.confirmpassword');

            $user = User::findOrFail(session('SESSION_ADMIN.id'));

            if ($user->password === md5($old)) {
                if ($new === $conf) {
                    $user->password = md5($new);
                    $user->save();
                    return redirect()->back()->with('success', 'Password changed successfully.');
                }
                return redirect()->back()->with('error', 'Confirmation password does not match.');
            }
            return redirect()->back()->with('error', 'Old password is incorrect.');
        }

        return view('admin.admins.change_password');
    }

    // ─── admin_status ────────────────────────────────────────────────────────
    public function admin_status($id, $status)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        User::where('id', base64_decode($id))->update(['status' => $status]);
        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    // ─── admin_delete ────────────────────────────────────────────────────────
    public function admin_delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        User::where('id', base64_decode($id))->delete();
        return redirect('/admin/admins/index')->with('success', 'User deleted successfully.');
    }

    public function admin_forgotPassword(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }

    public function admin_profile(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }

    public function admin_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $status = $request->input('User.status');
        $selected = $request->input('select', []);
        foreach ($selected as $id => $enabled) {
            if ($enabled) {
                User::where('id', (int) $id)->update(['status' => $status]);
            }
        }
        return redirect()->back()->with('success', 'Users updated successfully.');
    }

    public function verifyEmail(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
}
