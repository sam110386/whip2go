<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class DashboardController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        // Minimal placeholder while we port the real dashboard logic.
        return response()->json([
            'legacy' => true,
            'route' => 'dashboard/index',
            'user' => [
                'userid' => session()->get('userid'),
                'userParentId' => session()->get('userParentId'),
                'userfullname' => session()->get('userfullname'),
            ],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}

