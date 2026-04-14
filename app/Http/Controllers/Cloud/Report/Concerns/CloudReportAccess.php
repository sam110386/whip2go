<?php

namespace App\Http\Controllers\Cloud\Report\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait CloudReportAccess
{
    protected function guardCloudReportAccess(): ?RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (! empty($admin['administrator'])) {
            return redirect()->back()->with('error', 'Sorry, you are not authorized user for this action');
        }

        return null;
    }

    protected function cloudParentAdminId(): int
    {
        $admin = $this->getAdminUserid();

        return (int) ($admin['parent_id'] ?? $admin['admin_id'] ?? 0);
    }

    /**
     * Dealers (owners) linked to the cloud manager — matches legacy `User.is_owner` + `Set::combine` name.
     *
     * @return array{0: array<int|string, string>, 1: array<int>}
     */
    protected function managedOwnerDealersQuery(int $parentId): array
    {
        $dealers = DB::table('admin_user_associations')
            ->join('users', 'users.id', '=', 'admin_user_associations.user_id')
            ->where('admin_user_associations.admin_id', $parentId)
            ->where('users.is_owner', 1)
            ->select(
                'users.id',
                DB::raw("TRIM(CONCAT(COALESCE(users.first_name,''),' ',COALESCE(users.last_name,''))) as name")
            )
            ->pluck('name', 'id')
            ->toArray();

        $dealerIds = array_map('intval', array_keys($dealers));

        return [$dealers, $dealerIds];
    }

    /**
     * Dealer accounts for cashflow / portfolios — legacy `User.is_dealer` + first name label.
     *
     * @return array{0: array<int|string, string>, 1: array<int>}
     */
    protected function managedDealerAccountsQuery(int $parentId): array
    {
        $dealers = DB::table('admin_user_associations')
            ->join('users', 'users.id', '=', 'admin_user_associations.user_id')
            ->where('admin_user_associations.admin_id', $parentId)
            ->where('users.is_dealer', 1)
            ->select('users.id', 'users.first_name as name')
            ->pluck('name', 'id')
            ->toArray();

        return [$dealers, array_map('intval', array_keys($dealers))];
    }

    protected function getPageLimit(Request $request, string $sessKey, int $default = 25): int
    {
        if ($request->filled('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            session([$sessKey => $limit]);

            return $limit;
        }

        return (int) session($sessKey, $default);
    }
}
