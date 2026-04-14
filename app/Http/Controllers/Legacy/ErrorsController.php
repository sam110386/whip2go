<?php

namespace App\Http\Controllers\Legacy;

/**
 * Cake `ErrorsController` — public error pages (DriveItAway layout).
 */
class ErrorsController extends LegacyAppController
{
    public function error404()
    {
        return response()->view('errors.error404', [
            'title_for_layout' => 'Page not found',
        ], 404);
    }

    public function error500()
    {
        return response()->view('errors.error500', [
            'title_for_layout' => 'Server error',
        ], 500);
    }

    public function fatal_error()
    {
        return response()->view('errors.fatal_error', [
            'title_for_layout' => 'Error',
        ], 500);
    }
}
