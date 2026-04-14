<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;

/**
 * Admin end stub: CakePHP `HomesController` (admin_dashboard action).
 */
class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function dashboard(Request $request)
    {
        if (!session()->has('SESSION_ADMIN')) {
            return redirect('/admin/admins/login');
        }

        return view('admin.homes.dashboard');
    }
}

