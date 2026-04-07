<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminRole;
use App\Models\Legacy\AdminPermission;
use App\Models\Legacy\AdminModule;
use App\Models\Legacy\AdminRolePermission;
use App\Models\Legacy\AdminRoleMenu;
use App\Models\Legacy\AdminUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index (List all roles) ──────────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $this->set('title_for_layout', 'Manage Roles');
        
        $sessionLimitKey  = 'Roles_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        // In legacy, recursive => 2 was used to get permissions and maybe parents.
        // In Laravel, we use with()
        $roles = AdminRole::with(['permissions', 'menus', 'parent'])
            ->orderBy('id', 'ASC')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.roles.index', [
            'keyword' => '',
            'roles'   => $roles,
        ]);
    }

    // ─── admin_add / admin_edit (Add or update roles and permissions) ─────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $this->set('id', $id);
        $mypermissions = [];
        $selectedMenu = [];
        $listTitle = empty($id) ? 'Add Role' : 'Update Role';

        // Get parents for dropdown
        $parentRole = AdminRole::where('parent_id', 0)->pluck('name', 'id')->toArray();

        if ($request->isMethod('post')) {
            $data = $request->input('AdminRole', []);
            $data['parent_id'] = $data['parent_id'] ?? 0;

            DB::transaction(function () use ($data, $id) {
                if (!empty($id)) {
                    $role = AdminRole::findOrFail($id);
                    $role->update($data);
                } else {
                    $role = AdminRole::create($data);
                }

                $roleId = $role->id;

                // Update Role Permissions
                AdminRolePermission::where('role_id', $roleId)->delete();
                if (!empty($data['permissions'])) {
                    foreach ($data['permissions'] as $perid) {
                        AdminRolePermission::create([
                            'role_id' => $roleId,
                            'permission_id' => $perid
                        ]);
                    }
                }

                // Update Role Menu
                AdminRoleMenu::where('role_id', $roleId)->delete();
                if (!empty($data['menu_id'])) {
                    $menuids = explode(',', $data['menu_id']);
                    $menuids = array_filter(array_unique($menuids));
                    foreach ($menuids as $menuid) {
                        AdminRoleMenu::create([
                            'role_id' => $roleId,
                            'menu_id' => $menuid
                        ]);
                    }
                }
            });

            return redirect('/admin/roles/index')->with('success', 'Role data saved successfully.');
        }

        if (!empty($id)) {
            $role = AdminRole::with(['rolePermissions', 'roleMenus'])->findOrFail($id);
            $mypermissions = $role->rolePermissions->pluck('permission_id', 'permission_id')->toArray();
            $selectedMenu = $role->roleMenus->pluck('menu_id', 'menu_id')->toArray();
            // Simulating CakePHP $this->data
            view()->share('data', $role);
        }

        $permissions = AdminPermission::pluck('name', 'id')->toArray();
        
        // Build threaded menu for side-by-side management
        $rawMenus = AdminModule::orderBy('id', 'ASC')->get();
        $menuTree = $this->buildMenuTree($rawMenus);

        return view('admin.roles.add', compact(
            'listTitle', 'id', 'mypermissions', 'permissions', 
            'menuTree', 'selectedMenu', 'parentRole'
        ));
    }

    // ─── admin_delete ─────────────────────────────────────────────────────────
    public function admin_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!empty($id)) {
            AdminRole::where('id', $id)->delete();
            // Foreign keys usually handle AdminRolePermission/AdminRoleMenu but we can be explicit
            AdminRolePermission::where('role_id', $id)->delete();
            AdminRoleMenu::where('role_id', $id)->delete();
        }

        return redirect('/admin/roles/index')->with('success', 'Role deleted successfully.');
    }

    // ─── admin_getsubrole (AJAX) ──────────────────────────────────────────────
    public function admin_getsubrole(Request $request)
    {
        $roleId = $request->input('roleid');
        $userId = $request->input('userid');
        
        $related = [];
        if (!empty($userId)) {
            $related = AdminUserRole::where('user_id', $userId)->pluck('role_id')->toArray();
        }

        $roles = [];
        if (!empty($roleId)) {
            $roles = AdminRole::where('parent_id', $roleId)->pluck('name', 'id')->toArray();
        }

        $html = '';
        foreach ($roles as $key => $role) {
            $selected = in_array($key, $related) ? ' selected="selected"' : '';
            $html .= "<option value='$key'$selected>$role</option>";
        }

        return response($html);
    }

    /**
     * Helper to build recursive menu tree from flat modules list (replaces find('threaded'))
     */
    protected function buildMenuTree($menus, $parentId = 0)
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $children = $this->buildMenuTree($menus, $menu->id);
                if ($children) {
                    $menu->children = $children;
                }
                $branch[] = $menu;
            }
        }

        return $branch;
    }
}
