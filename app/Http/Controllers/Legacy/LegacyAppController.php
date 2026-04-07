<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use App\Http\Controllers\Controller;

/**
 * CakePHP `AppController` equivalent (subset).
 *
 * This base focuses on request lifecycle pieces that are needed across
 * most controllers: session checks and module/permission data loading.
 */
class LegacyAppController extends Controller
{
    protected bool $shouldLoadLegacyModules = true;

    public function __construct()
    {
        if ($this->shouldLoadLegacyModules) {
            $this->loadModulesForViews();
        }
    }

    // Cake compatibility methods
    protected function beforeFilter()
    {
        return null;
    }

    protected function beforeRender()
    {
        return null;
    }

    protected function appError($message = 'Application error')
    {
        return response($message, 500);
    }

    protected function _setErrorLayout()
    {
        return 'error';
    }

    protected function ensureUserSession(): ?RedirectResponse
    {
        if (!session()->has('userid')) {
            return redirect('/logins/index');
        }

        return null;
    }

    protected function ensureAdminSession(): ?RedirectResponse
    {
        $admin = session()->get('SESSION_ADMIN');
        if (empty($admin) || ($admin['slug'] ?? null) === 'cloud') {
            return redirect('/admin/admins/login');
        }

        return null;
    }

    protected function ensureCloudAdminSession(): ?RedirectResponse
    {
        $admin = session()->get('SESSION_ADMIN');
        if (empty($admin) || ($admin['slug'] ?? null) !== 'cloud') {
            return redirect('/admin/admins/login');
        }

        return null;
    }

    protected function getAdminUserid(): array
    {
        $admin = session()->get('SESSION_ADMIN');
        if (empty($admin)) {
            return [];
        }

        return [
            'admin_id' => $admin['id'] ?? null,
            'administrator' => (($admin['role_id'] ?? null) == 1),
            'parent_id' => !empty($admin['parent_id'])
                ? ($admin['parent_id'])
                : ($admin['id'] ?? null),
            'timezone' => $admin['timezone'] ?? null,
        ];
    }

    /**
     * Replicates Cake's `checkUserPermission()` + `loadAdminModule()` data wiring.
     * These view variables are used by legacy Blade ports.
     */
    protected function loadModulesForViews(): void
    {
        // User modules
        if (session()->has('userid')) {
            $topModules = DB::table('cs_user_modules')
                ->where('status', 1)
                ->where('parent_id', 0)
                ->orderBy('order')
                ->get();

            $subModules = DB::table('cs_user_modules')
                ->where('status', 1)
                ->where('parent_id', '!=', 0)
                ->orderBy('order')
                ->get();

            // Keyed by parent_id: { [parent_id]: [rows...] }
            $userSubModules = [];
            foreach ($subModules as $row) {
                $userSubModules[(int) $row->parent_id][] = (array) $row;
            }

            ViewFacade::share('userModules', $topModules->map(fn ($r) => (array) $r)->all());
            ViewFacade::share('userSubModules', $userSubModules);
        }

        // Admin modules
        if (session()->has('SESSION_ADMIN')) {
            $roleId = session()->get('adminRoleId');
            $menuids = [];
            if (!empty($roleId)) {
                $menuids = DB::table('admin_role_menu')
                    ->where('role_id', $roleId)
                    ->pluck('menu_id')
                    ->toArray();
            }

            $adminModulesQuery = DB::table('admin_modules')
                ->where('status', 1)
                ->where('parent_id', 0);
            if (!empty($menuids)) {
                $adminModulesQuery->whereIn('id', $menuids);
            }
            $adminModules = $adminModulesQuery
                ->orderBy('order')
                ->get();

            $adminSubModulesQuery = DB::table('admin_modules')
                ->where('status', 1)
                ->where('parent_id', '!=', 0);
            if (!empty($menuids)) {
                $adminSubModulesQuery->whereIn('id', $menuids);
            }
            $adminSubModulesRows = $adminSubModulesQuery
                ->orderBy('order')
                ->get();

            $adminSubModules = [];
            foreach ($adminSubModulesRows as $row) {
                $adminSubModules[(int) $row->parent_id][] = (array) $row;
            }

            ViewFacade::share('adminModules', $adminModules->map(fn ($r) => (array) $r)->all());
            ViewFacade::share('adminSubModules', $adminSubModules);
            ViewFacade::share('adminUser', $this->getAdminUserid());
        }
    }

    protected function checkUserSession(): ?RedirectResponse
    {
        return $this->ensureUserSession();
    }

    protected function checkSessionAdmin(): ?RedirectResponse
    {
        return $this->ensureAdminSession();
    }

    protected function checkSessionCloud(): ?RedirectResponse
    {
        return $this->ensureCloudAdminSession();
    }

    protected function loadAdminModule(): void
    {
        $this->loadModulesForViews();
    }

    protected function checkUserPermission(): bool
    {
        return true;
    }

    protected function checkPermission($permissionId = null): bool
    {
        if ($permissionId === null) {
            return true;
        }
        $permissions = session('permissions', []);
        return isset($permissions[$permissionId]);
    }

    protected function getPermissionOfAdmin($roleId = null): array
    {
        $roleId = $roleId ?? session('adminRoleId');
        if (empty($roleId)) {
            return [];
        }
        return DB::table('admin_role_menu')
            ->where('role_id', $roleId)
            ->pluck('menu_id')
            ->toArray();
    }

    protected function getClientUserIp(?Request $request = null): string
    {
        $request = $request ?: request();
        $cf = (string) $request->header('HTTP_CF_CONNECTING_IP', '');
        if ($cf !== '') return $cf;
        $xff = (string) $request->header('X-Forwarded-For', '');
        if ($xff !== '') {
            $parts = explode(',', $xff);
            return trim(end($parts));
        }
        return (string) $request->ip();
    }

    protected function getStatus($status = null): array|string
    {
        $map = [
            0 => 'Inactive',
            1 => 'Active',
            2 => 'Cancelled',
            3 => 'Completed',
        ];
        if ($status === null) {
            return $map;
        }
        return $map[$status] ?? 'Unknown';
    }

}

