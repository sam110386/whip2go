<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PerformsSessionLogout;
use Illuminate\Http\Request;

class AdminsController extends LegacyAppController
{
    use PerformsSessionLogout;

    protected bool $shouldLoadLegacyModules = false;

    public function cloud_logout(Request $request)
    {
        return $this->performSessionLogout('/admin/admins/login');
    }
}

