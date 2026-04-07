<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        return response()->json([
            'legacy' => true,
            'controller' => 'homes',
            'action' => 'index',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}

