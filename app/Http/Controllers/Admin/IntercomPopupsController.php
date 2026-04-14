<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;

class IntercomPopupsController extends LegacyAppController
{
    public function loadpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = base64_decode(trim($request->input('userid')));
        $xtoken = '7750ca3559e5b8e1f442103368fcgc';

        return view('admin.intercom_popups._popup', compact('userid', 'xtoken'));
    }
}
