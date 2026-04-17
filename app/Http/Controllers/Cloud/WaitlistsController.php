<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\WaitlistsController as AdminWaitlistsController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WaitlistsController extends AdminWaitlistsController
{
    protected function waitlistGuard(): ?RedirectResponse
    {
        return $this->ensureCloudAdminSession();
    }

    protected function waitlistBasePath(): string
    {
        return '/cloud/waitlists';
    }

    protected function waitlistViewNamespace(): string
    {
        return 'cloud';
    }

    protected function waitlistLayout(): string
    {
        return 'layouts.main';
    }

    protected function vehicleAjaxUrl(): string
    {
        return '/vehicles/getVehicle';
    }

    /**
     * Cloud query scopes waitlist records to the logged-in dealer's vehicles.
     */
    protected function waitlistQuery(): \Illuminate\Database\Query\Builder
    {
        $userid = (int) session('userParentId', 0);
        if ($userid === 0) {
            $userid = (int) session('userid', 0);
        }

        return parent::waitlistQuery()
            ->where('Vehicle.user_id', $userid);
    }
}
