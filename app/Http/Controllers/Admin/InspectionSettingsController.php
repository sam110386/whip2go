<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionSettingsController extends LegacyAppController
{
    public array $schedules = [
        '1' => 'Everyday',
        '2' => 'Weekly',
        '3' => 'Bi-Weekly',
        '4' => 'Monthly',
    ];

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $listTitle = 'Update Inspection Scan Setting';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $input = $request->input('InspectionSetting', []);
            if (!empty($input['id'])) {
                DB::table('inspection_settings')->where('id', $input['id'])->update([
                    'status'   => $input['status'] ?? 0,
                    'schedule' => $input['schedule'] ?? 1,
                ]);
            } else {
                DB::table('inspection_settings')->updateOrInsert(
                    ['id' => 1],
                    [
                        'status'   => $input['status'] ?? 0,
                        'schedule' => $input['schedule'] ?? 1,
                    ]
                );
            }

            return redirect()->back()->with('success', 'Request saved successfully');
        }

        $setting = DB::table('inspection_settings')->where('id', 1)->first();
        $settingData = $setting ? (array) $setting : ['id' => '', 'status' => 1, 'schedule' => 1];

        return view('admin.inspection_settings.index', [
            'listTitle'   => $listTitle,
            'scheduels'   => $this->schedules,
            'settingData' => $settingData,
        ]);
    }
}
