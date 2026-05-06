<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use App\Http\Controllers\Controller;
use App\Services\Legacy\Common as CommonService;

/**
 * CakePHP `AppController` equivalent (subset).
 *
 * This base focuses on request lifecycle pieces that are needed across
 * most controllers: session checks and module/permission data loading.
 */
class LegacyAppController extends Controller
{
    protected bool $shouldLoadLegacyModules = true;
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
        ViewFacade::share('commonService', $this->commonService);

        if ($this->shouldLoadLegacyModules) {
            $this->loadModulesForViews();
        }
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
        $AdminUser = session()->get('SESSION_ADMIN');

        if (empty($AdminUser)) {
            return [];
        }

        $id = $admin['id'] ?? null;

        return [
            'admin_id' => $id,
            'administrator' => ($AdminUser['role_id'] ?? null) == 1,
            'parent_id' => $AdminUser['parent_id'] ?? $id,
            'timezone' => $AdminUser['timezone'] ?? null,
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

            ViewFacade::share('userModules', $topModules->map(fn($r) => (array) $r)->all());
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

            ViewFacade::share('adminModules', $adminModules->map(fn($r) => (array) $r)->all());
            ViewFacade::share('adminSubModules', $adminSubModules);
            ViewFacade::share('adminUser', $this->getAdminUserid());
        }
    }

    protected function decodeId($id): ?int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }
        if (is_string($id) && $id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int) $decoded;
            }
        }

        return null;
    }

    protected function encodeId(?int $id): string
    {
        return base64_encode((string) ((int) $id));
    }
}

