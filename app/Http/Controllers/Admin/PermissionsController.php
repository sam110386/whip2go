<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminPermission;
use Illuminate\Http\Request;

class PermissionsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessionLimitKey  = 'Permissions_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $permissions = AdminPermission::orderBy('id', 'ASC')->paginate($limit)->withQueryString();

        return view('admin.permissions.index', [
            'title_for_layout' => 'Manage Permissions',
            'keyword'          => '',
            'permissions'      => $permissions,
        ]);
    }

    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $listTitle    = empty($id) ? 'Add Permission' : 'Update Permission';
        $selectedMenu = '';

        if ($request->isMethod('post')) {
            $data = $request->input('AdminPermission', []);
            if (!empty($id)) {
                AdminPermission::where('id', $id)->update($data);
            } else {
                AdminPermission::create($data);
            }
            return redirect('/admin/permissions/index')->with('success', 'Record updated successfully');
        }

        $record = !empty($id) ? AdminPermission::find($id) : null;
        if ($record) {
            $selectedMenu = $record->type === 'all' ? '*' : ($record->permissions ?? '');
        }

        $actions = $this->detectPermissions();

        return view('admin.permissions.add', compact('listTitle', 'id', 'record', 'selectedMenu', 'actions'));
    }

    public function admin_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        AdminPermission::where('id', $id)->delete();

        return redirect('/admin/permissions/index')->with('success', 'Record deleted successfully');
    }

    /**
     * Scans Laravel Admin/Cloud controller directories and returns all admin_/cloud_ methods.
     * Replaces CakePHP's glob(APP.'Controller') approach.
     */
    public function detectPermissions(): array
    {
        $permissions = [];

        $map = [
            app_path('Http/Controllers/Admin')  => 'App\\Http\\Controllers\\Admin\\',
            app_path('Http/Controllers/Cloud')  => 'App\\Http\\Controllers\\Cloud\\',
            app_path('Http/Controllers/Legacy') => 'App\\Http\\Controllers\\Legacy\\',
        ];

        foreach ($map as $dir => $namespace) {
            if (!is_dir($dir)) continue;

            foreach (glob($dir . '/*Controller.php') as $fullPath) {
                $className = pathinfo($fullPath, PATHINFO_FILENAME);

                if (str_contains($className, 'Api') || str_contains($className, 'LegacyApp')) {
                    continue;
                }

                $fqcn = $namespace . $className;
                if (!class_exists($fqcn)) {
                    @require_once $fullPath;
                }

                $actions = [];
                if (class_exists($fqcn)) {
                    foreach (get_class_methods($fqcn) as $method) {
                        if (preg_match('/^(admin_|cloud_)\w+$/', $method)) {
                            $actions[] = $method;
                        }
                    }
                }

                $permissions[$className] = $actions;
            }
        }

        return $permissions;
    }
}
