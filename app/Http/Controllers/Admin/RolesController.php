<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission as LegacyAdminPermission;
use App\Models\Legacy\AdminRole as LegacyAdminRole;
use App\Models\Legacy\AdminRoleMenu as LegacyAdminRoleMenu;
use App\Models\Legacy\AdminRolePermission as LegacyAdminRolePermission;
use App\Models\Legacy\AdminUserRole as LegacyAdminUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RolesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // CakePHP: app/Controller/RolesController.php::admin_index()
    // URL: /admin/roles/index
    public function index(Request $request)
    {
        $keyword = trim((string)($request->query('keyword') ?? ''));

        $q = LegacyAdminRole::query()
            ->with('permissions')
            ->orderBy('id', 'asc');

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('slug', 'like', $like)
                    ->orWhere('name', 'like', $like);
            });
        }

        $roles = $q->get()->map(function (LegacyAdminRole $role) {
            $row = $role->toArray();
            $row['permission_names'] = $role->permissions
                ->pluck('name')
                ->sort()
                ->implode(', ');
            return (object) $row;
        });

        return view('admin.roles.index', [
            'roles' => $roles,
            'keyword' => $keyword,
        ]);
    }

    // CakePHP: app/Controller/RolesController.php::admin_add($id=null)
    // URL:
    // - /admin/roles/add
    // - /admin/roles/admin_add/{id}
    public function add(Request $request, $id = null)
    {
        $isEditing = !empty($id);
        $roleId = null;
        if ($isEditing && is_numeric($id)) {
            $roleId = (int)$id;
        }

        if (!$request->isMethod('POST')) {
            $role = null;
            $selectedPermissionIds = [];
            $selectedMenuIds = [];

            if ($isEditing && $roleId !== null) {
                $role = LegacyAdminRole::query()->with(['rolePermissions', 'roleMenus'])->find($roleId);
                if ($role) {
                    $selectedPermissionIds = $role->rolePermissions->pluck('permission_id')->all();
                    $selectedMenuIds = $role->roleMenus->pluck('menu_id')->all();
                }
            }

            $parentRoles = LegacyAdminRole::query()
                ->where('parent_id', 0)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn ($r) => [(string) $r->id => $r->name])
                ->toArray();

            $permissions = LegacyAdminPermission::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])
                ->toArray();

            // Minimal menu picker (flat). Cake has nested/tree UI.
            $menus = DB::table('admin_modules')
                ->where('status', 1)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->get();

            return view('admin.roles.add', [
                'listTitle' => $isEditing ? 'Update Role' : 'Add Role',
                'role' => $role,
                'parentRoles' => $parentRoles,
                'permissions' => $permissions,
                'menus' => $menus,
                'selectedPermissionIds' => $selectedPermissionIds,
                'selectedMenuIds' => $selectedMenuIds,
            ]);
        }

        $payload = $request->input('AdminRole', []);

        $slug = trim((string)($payload['slug'] ?? ''));
        $name = trim((string)($payload['name'] ?? ''));
        $parentId = $payload['parent_id'] ?? 0;
        $parentId = ($parentId === '' || $parentId === null) ? 0 : (int)$parentId;

        if ($slug === '' || $name === '') {
            return back()->withInput()->with('error', 'Please enter role name and slug.');
        }

        $selectedPermissionIds = $payload['permissions'] ?? [];
        if (!is_array($selectedPermissionIds)) {
            $selectedPermissionIds = [];
        }
        $selectedPermissionIds = array_values(array_filter(array_map('intval', $selectedPermissionIds)));

        $selectedMenuIds = $payload['menu_id'] ?? [];
        if (!is_array($selectedMenuIds)) {
            // Support comma-separated input.
            if (is_string($selectedMenuIds)) {
                $selectedMenuIds = array_filter(array_map('trim', explode(',', $selectedMenuIds)));
            } else {
                $selectedMenuIds = [];
            }
        }
        $selectedMenuIds = array_values(array_filter(array_map('intval', $selectedMenuIds)));

        $candidateRole = [
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
        ];

        // Only update columns that exist (defensive).
        $roleTable = 'admin_roles';
        $filteredRole = [];
        foreach ($candidateRole as $col => $val) {
            if (Schema::hasColumn($roleTable, $col)) {
                $filteredRole[$col] = $val;
            }
        }

        if ($isEditing && $roleId !== null) {
            LegacyAdminRole::query()->whereKey($roleId)->update($filteredRole);
        } else {
            $role = LegacyAdminRole::query()->create($filteredRole);
            $roleId = $role->id;
        }

        // Permissions mapping.
        LegacyAdminRolePermission::query()->where('role_id', (int) $roleId)->delete();
        foreach ($selectedPermissionIds as $pid) {
            LegacyAdminRolePermission::query()->create([
                'role_id' => (int) $roleId,
                'permission_id' => (int) $pid,
            ]);
        }

        // Menu mapping.
        LegacyAdminRoleMenu::query()->where('role_id', (int) $roleId)->delete();
        foreach ($selectedMenuIds as $mid) {
            LegacyAdminRoleMenu::query()->create([
                'role_id' => (int) $roleId,
                'menu_id' => (int) $mid,
            ]);
        }

        return redirect('/admin/roles/index');
    }

    // CakePHP: app/Controller/RolesController.php::admin_delete($id=null)
    public function delete(Request $request, $id = null)
    {
        if (empty($id) || !is_numeric($id)) {
            return redirect('/admin/roles/index');
        }

        $roleId = (int)$id;

        LegacyAdminRolePermission::query()->where('role_id', $roleId)->delete();
        LegacyAdminRoleMenu::query()->where('role_id', $roleId)->delete();
        LegacyAdminRole::query()->whereKey($roleId)->delete();

        return redirect('/admin/roles/index');
    }

    // CakePHP: app/Controller/RolesController.php::admin_getsubrole()
    // URL: /admin/roles/getsubrole (jQuery posts roleid + userid)
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
            $selected = in_array((string)$r->id, array_map('strval', $related), true);
            if ($selected) {
                $return .= "<option value='{$r->id}' selected='selected'>{$r->name}</option>";
            } else {
                $return .= "<option value='{$r->id}'>{$r->name}</option>";
            }
        }

        return response($return, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }
}

