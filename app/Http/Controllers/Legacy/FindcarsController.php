<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class FindcarsController extends LegacyAppController
{
    // The legacy component logic (like AppController::beforeFilter session checking)
    // is maintained in the LegacyAppController constructor.
    
    // We only need the legacy modules if they are actually communicating heavily with DBs
    // Assuming FindCars needs the session helpers:
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        return view('legacy.findcars.index', [
            'title_for_layout' => 'DriveItAway'
        ]);
    }
    
    public function details(Request $request)
    {
        return view('legacy.findcars.details', [
            'title_for_layout' => 'DriveItAway'
        ]);
    }
}
