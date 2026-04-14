<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission as LegacyAdminPermission;
use Illuminate\Http\Request;

class PermissionsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $keyword = trim((string)($request->query('keyword') ?? ''));

        $q = LegacyAdminPermission::query()->orderBy('id', 'asc');
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)
                    ->orWhere('type', 'like', $like);
            });
        }

        $permissions = $q->limit(100)->get();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'keyword' => $keyword,
        ]);
    }

    public function delete(Request $request, $id = null)
    {
        if (empty($id) || !is_numeric($id)) {
            return redirect('/admin/permissions/index');
        }

        LegacyAdminPermission::query()->whereKey((int)$id)->delete();

        return redirect('/admin/permissions/index');
    }

    public function add(Request $request, $id = null)
    {
        $isEditing = !empty($id) && is_numeric($id);
        $permission = $isEditing ? LegacyAdminPermission::query()->find((int)$id) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.permissions.add', [
                'listTitle' => $isEditing ? 'Update Permission' : 'Add Permission',
                'permission' => $permission,
            ]);
        }

        $payload = $request->input('AdminPermission', []);
        $name = trim((string)($payload['name'] ?? ''));
        $type = trim((string)($payload['type'] ?? 'all'));
        $permissions = $payload['permissions'] ?? '';

        if ($name === '') {
            return back()->withInput()->with('error', 'Permission name is required.');
        }

        $permissionsValue = $type === 'all' ? '*' : (is_string($permissions) ? trim($permissions) : '');
        if ($type !== 'all' && $permissionsValue === '') {
            return back()->withInput()->with('error', 'Permissions are required for custom type.');
        }

        $data = [
            'name' => $name,
            'type' => $type,
            'permissions' => $permissionsValue,
        ];

        if ($isEditing && $permission) {
            LegacyAdminPermission::query()->whereKey((int)$permission->id)->update($data);
        } else {
            LegacyAdminPermission::query()->create($data);
        }

        return redirect('/admin/permissions/index');
    }
}

