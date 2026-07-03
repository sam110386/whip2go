<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

class PermissionsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        $title = 'Manage Permissions';
        $sessLimitName = "admin_permission_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage;
        }

        $permissions = AdminPermission::orderBy('id', 'asc')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.permissions.elements.index', compact('permissions', 'limit'));
        }

        return view('admin.permissions.index', compact('permissions', 'title', 'limit'));
    }
    public function delete($id = null)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        $permission = AdminPermission::findOrFail($id);
        $permission->delete();

        return redirect('admin/permissions/index')->with('success', 'Permission deleted successfully.');
    }
    public function add(Request $request, $id = null)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        $title = !empty($id) ? 'Update Permission' : 'Add Permission';
        $msg = !empty($id) ? 'Permission updated successfully.' : 'Permission added successfully.';
        $selectedMenu = '';
        $permission = collect();

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $request->validate([
                'AdminPermission.name' => 'required',
                'AdminPermission.type' => 'required',
            ], [
                'AdminPermission.name.required' => 'Please enter permission name',
                'AdminPermission.type.required' => 'Please enter type',
            ]);

            $dataToSave = $request->input('AdminPermission');
            $user = AdminPermission::updateOrCreate(
                ['id' => $id],
                $dataToSave
            );

            return redirect('admin/permissions/index')->with('success', $msg);
        } elseif (!empty($id)) {
            $permission = AdminPermission::findOrFail($id);
            $selectedMenu = ($permission->type === 'all') ? '*' : $permission->permissions;
        }

        $actions = $this->detectPermissions();

        return view('admin.permissions.add', compact('id', 'title', 'selectedMenu', 'actions', 'permission'));
    }
    public function detectPermissions(): array
    {
        $permissions = [];
        $controllerFiles = glob(app_path('Http/Controllers/Admin/*Controller.php'));

        foreach ($controllerFiles as $fullPath) {
            $className = pathinfo($fullPath, PATHINFO_FILENAME);
            $fullClassName = "App\\Http\\Controllers\\Admin\\" . $className;

            if (!class_exists($fullClassName)) {
                continue;
            }

            $reflection = new ReflectionClass($fullClassName);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            $actions = [];
            foreach ($methods as $method) {
                if ($method->class !== $fullClassName) {
                    continue;
                }

                if (in_array($method->name, ['__construct', 'middleware', 'getMiddleware', 'callAction', 'missingMethod'])) {
                    continue;
                }

                $actions[] = $method->name;
            }

            if (!empty($actions)) {
                $permissions[$className] = $actions;
            }
        }

        return $permissions;
    }
}

