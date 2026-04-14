<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Migrated from: app/Plugin/TinnyUrl/Controller/TinnyUrlsController.php
 *
 * Short-URL redirect handler.
 */
class TinnyUrlsController extends Controller
{
    public function index(Request $request, string $key): RedirectResponse
    {
        $fallback = 'http://app.driveitaway.com';

        if (empty($key)) {
            return redirect()->away($fallback);
        }

        $row = DB::table('tinny_urls')->where('ukey', $key)->first();

        if (empty($row)) {
            return redirect()->away($fallback);
        }

        return redirect()->away($fallback . $row->target);
    }
}
