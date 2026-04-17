<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Legacy\PermissionNestedTree;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission as LegacyAdminPermission;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

class PermissionsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        $keyword = trim((string) ($request->query('keyword') ?? ''));

        $q = LegacyAdminPermission::query()->orderBy('id', 'asc');
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)
                    ->orWhere('type', 'like', $like);
            });
        }

        $permissions = $q->paginate(20);

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'keyword' => $keyword,
            'title_for_layout' => 'Manage Permissions',
        ]);
    }

    public function delete(Request $request, $id = null)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        if (empty($id) || !is_numeric($id)) {
            return redirect('/admin/permissions/index');
        }

        LegacyAdminPermission::query()->whereKey((int) $id)->delete();

        return redirect('/admin/permissions/index')->with('success', 'Permission deleted successfully.');
    }

    public function add(Request $request, $id = null)
    {
        if ($resp = $this->ensureAdminSession()) {
            return $resp;
        }

        $isEditing = !empty($id) && is_numeric($id);
        $permission = $isEditing ? LegacyAdminPermission::query()->find((int) $id) : null;

        if (!$request->isMethod('POST')) {
            $actions = $this->detectPermissions();
            $selectedMenu = $permission ? ($permission->type === 'all' ? '*' : $permission->permissions) : '';
            $selectedMenuArr = json_decode((string) $selectedMenu, true);

            return view('admin.permissions.add', [
                'listTitle' => $isEditing ? 'Update Permission' : 'Add Permission',
                'permission' => $permission,
                'id' => $id,
                'actions' => $actions,
                'selectedMenu' => $selectedMenu,
                'treeData' => PermissionNestedTree::getMenuNameTree($actions, $selectedMenuArr),
            ]);
        }

        $payload = $request->input('AdminPermission', []);
        $name = trim((string) ($payload['name'] ?? ''));
        $type = trim((string) ($payload['type'] ?? 'all'));
        $permissionsValue = $payload['permissions'] ?? '';

        if ($name === '') {
            return back()->withInput()->with('error', 'Permission name is required.');
        }

        if ($type === 'all') {
            $permissionsValue = '*';
        }

        $data = [
            'name' => $name,
            'type' => $type,
            'permissions' => $permissionsValue,
        ];

        if ($isEditing && $permission) {
            $permission->update($data);
        } else {
            LegacyAdminPermission::query()->create($data);
        }

        return redirect('/admin/permissions/index')->with('success', 'Permission saved successfully.');
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

