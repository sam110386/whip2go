<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;

class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function cloud_dashboard()
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return view('cloud.homes.dashboard', [
            'title_for_layout' => 'Dashboard',
        ]);
    }
}

