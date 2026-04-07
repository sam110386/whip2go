<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\User;
use App\Models\Legacy\AdminUserRole;
use App\Models\Legacy\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStaffsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index (List Staff Users) ──────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $loggedId    = session('SESSION_ADMIN.id');
        $parentId    = session('SESSION_ADMIN.parent_id', 0);
        $isAdminRole = session('adminRoleId') == 1; // Administrator

        $query = User::from('users as User')
            ->join('admin_roles as AdminRole', 'User.role_id', '=', 'AdminRole.id')
            ->where('User.is_admin', 1)
            ->where('User.id', '!=', $loggedId)
            ->select('User.*', 'AdminRole.name as role_name');

        if ($isAdminRole) {
            $query->where('User.parent_id', '!=', 0);
        } else {
            $query->where('User.parent_id', $parentId);
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

        return view('admin.admin_staffs.index', compact('users', 'keyword', 'searchIn', 'showType'));
    }

    // ─── admin_add (Add or Edit Staff User) ──────────────────────────────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $loggedId    = session('SESSION_ADMIN.id');
        $parentId    = session('SESSION_ADMIN.parent_id', 0);
        $isAdminRole = session('adminRoleId') == 1;

        if ($isAdminRole) {
            return redirect('/admin/admin_staffs/index')->with('error', 'Administrators cannot manage sub-staff here.');
        }

        $id = $id ? base64_decode($id) : null;
        $listTitle = $id ? 'Update Staff User' : 'Add Staff User';

        if ($request->isMethod('post')) {
            $data = $request->input('User', []);
            $data['parent_id'] = $parentId;
            $data['is_admin']  = 1;

            if (!empty($data['newpassword'])) {
                $data['password'] = md5($data['newpassword']);
            }

            if ($id) {
                User::where('id', $id)->update($data);
            } else {
                User::create($data);
            }

            return redirect('/admin/admin_staffs/index')->with('success', 'Staff user saved successfully.');
        }

        $record = $id ? User::find($id) : null;
        
        // Fetch roles available for this parent
        $roles = AdminRole::from('admin_roles as AdminRole')
            ->join('admin_user_roles as AdminUserRole', 'AdminUserRole.role_id', '=', 'AdminRole.id')
            ->where('AdminUserRole.user_id', $parentId)
            ->pluck('AdminRole.name', 'AdminRole.id');

        return view('admin.admin_staffs.add', compact('listTitle', 'record', 'id', 'roles'));
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
        return redirect('/admin/admin_staffs/index')->with('success', 'Staff user deleted successfully.');
    }
}
