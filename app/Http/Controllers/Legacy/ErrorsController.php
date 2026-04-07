<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class ErrorsController extends LegacyAppController
{
    // These views don't need the legacy modules
    protected bool $shouldLoadLegacyModules = false;

    public function error404(Request $request)
    {
        return response()->view('legacy.errors.error404', [], 404);
    }
    
    public function error500(Request $request)
    {
        return response()->view('legacy.errors.error500', [], 500);
    }
    
    public function fatal_error(Request $request)
    {
        return response()->view('legacy.errors.fatal_error', [], 500);
    }
}
