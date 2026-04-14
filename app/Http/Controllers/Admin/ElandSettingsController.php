<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Elandlib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElandSettingsController extends LegacyAppController
{
    public function index(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = base64_decode($id);
        $formData = null;

        if ($request->isMethod('post') && $request->filled('ElandSetting')) {
            $dataToSave = $request->input('ElandSetting');
            $dataToSave['user_id'] = $userid;

            $token = Elandlib::createJwtToken($dataToSave['jwt_sub'], $dataToSave['jwt_secret']);
            $dataToSave['token'] = $token;

            $existing = DB::table('eland_settings')->where('id', $dataToSave['id'] ?? 0)->first();
            if (!empty($existing)) {
                DB::table('eland_settings')->where('id', $existing->id)->update($dataToSave);
            } else {
                DB::table('eland_settings')->insert($dataToSave);
            }

            return redirect()->back()->with('success', 'Dealer data saved successfully.');
        }

        if (!empty($userid)) {
            $formData = DB::table('eland_settings')->where('user_id', $userid)->first();
        }

        return view('admin.eland.settings_index', compact('userid', 'formData'));
    }
}
