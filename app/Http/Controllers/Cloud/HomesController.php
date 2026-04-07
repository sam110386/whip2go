<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;

class HomesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function cloud_dashboard(Request $request)
    {
        return response()->json([
            'legacy' => true,
            'route' => 'cloud/homes/dashboard',
            'user' => [
                'SESSION_ADMIN' => session()->get('SESSION_ADMIN'),
            ],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}

