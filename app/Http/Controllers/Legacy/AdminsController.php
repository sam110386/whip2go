<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

/**
 * Migration stub for CakePHP `AdminsController`.
 */
class AdminsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function login(Request $request)
    {
        return response()->json([
            'legacy' => true,
            'controller' => 'admins',
            'action' => 'login',
            'message' => 'AdminsController stub (port full Cake logic next).',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}

