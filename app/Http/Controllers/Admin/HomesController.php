<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;

class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_dashboard()
    {
        if (!session()->has('SESSION_ADMIN')) {
            return redirect('/admin/admins/login');
        }

        return view('admin.homes.dashboard');
    }
}
