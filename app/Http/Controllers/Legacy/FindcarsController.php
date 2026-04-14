<?php

namespace App\Http\Controllers\Legacy;

/**
 * Cake `FindcarsController` — DriveItAway marketing pages.
 */
class FindcarsController extends LegacyAppController
{
    public function index()
    {
        return view('findcars.index', [
            'title_for_layout' => 'DriveItAway',
        ]);
    }

    public function details()
    {
        return view('findcars.details', [
            'title_for_layout' => 'DriveItAway',
        ]);
    }
}
