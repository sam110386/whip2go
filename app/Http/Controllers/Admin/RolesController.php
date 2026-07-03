<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission as LegacyAdminPermission;
use App\Models\Legacy\AdminRole as LegacyAdminRole;
use App\Models\Legacy\AdminUserRole as LegacyAdminUserRole;
use App\Models\Legacy\AdminModule;
use Illuminate\Http\Request;

class RolesController extends LegacyAppController
{
    public function index(Request $request)
    {
        $title = 'Manage Roles';
        $sessLimitName = "admin_roles_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage;
        }

        $roles = LegacyAdminRole::with('permissions')->orderBy('id', 'asc')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.roles.elements.index', compact('roles', 'limit'));
        }

        return view('admin.roles.index', compact('title', 'roles', 'limit'));
    }
    public function delete($id = null)
    {
        LegacyAdminRole::where('id', $id)->delete();
        return redirect('admin/roles/index')->with('success', 'Role deleted successfully.');
    }
    public function add(Request $request, $id = null)
    {
        $mypermissions = [];
        $selectedMenu = [];
        $role = null;
        $title = !empty($id) ? 'Update Role' : 'Add Role';

        $parentRoles = LegacyAdminRole::where('parent_id', 0)->pluck('name', 'id');

        if ($request->isMethod('post')) {
            $request->validate(
                [
                    'AdminRole.name' => 'required',
                    'AdminRole.slug' => 'required',
                ],
                [
                    'AdminRole.name.required' => 'Please enter role name',
                    'AdminRole.slug.required' => 'Please enter slug',
                ]
            );

            $roleData = $request->input('AdminRole');
            $roleData['parent_id'] ??= 0;
            $role = LegacyAdminRole::updateOrCreate(['id' => $id], $roleData);

            if ($request->has('AdminRole.permissions')) {
                $role->permissions()->sync($request->input('AdminRole.permissions'));
            } else {
                $role->permissions()->detach();
            }

            if ($request->filled('AdminRole.menu_id')) {
                $menuIds = array_filter(array_unique(explode(',', $request->input('AdminRole.menu_id'))));
                $role->menus()->sync($menuIds);
            } else {
                $role->menus()->detach();
            }

            return redirect('admin/roles/index')->with('success', 'Role data saved successfully.');
        }

        if (!empty($id)) {
            $role = LegacyAdminRole::with(['permissions', 'menus'])->findOrFail($id);
            $mypermissions = $role->permissions->pluck('id')->toArray();
            $selectedMenu = $role->menus->pluck('id')->toArray();
        }

        $permissions = LegacyAdminPermission::pluck('name', 'id');
        $menu = AdminModule::with('children')->get();

        return view('admin.roles.add', compact('id', 'title', 'parentRoles', 'mypermissions', 'permissions', 'menu', 'selectedMenu', 'role'));
    }
    public function getsubrole(Request $request)
    {
        $role_d = $request->input('roleid');
        $user_d = $request->input('userid');

        $related = [];
        if ($user_d !== null && $user_d !== '' && is_numeric($user_d)) {
            $related = LegacyAdminUserRole::query()
                ->where('user_id', (int) $user_d)
                ->pluck('role_id')
                ->toArray();
        }

        $childRoles = [];
        if ($role_d !== null && $role_d !== '' && is_numeric($role_d)) {
            $childRoles = LegacyAdminRole::query()
                ->where('parent_id', (int) $role_d)
                ->get(['id', 'name']);
        }

        $return = '';
        foreach ($childRoles as $r) {
            $selected = in_array((string) $r->id, array_map('strval', $related), true);
            if ($selected) {
                $return .= "<option value='{$r->id}' selected='selected'>{$r->name}</option>";
            } else {
                $return .= "<option value='{$r->id}'>{$r->name}</option>";
            }
        }

        return response($return, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }
}

