<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\InspectionSetting;
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
            $setting = InspectionSetting::findOrNew(1);
            $setting->fill($input)->save();
            return redirect()->back()->with('success', 'Request saved successfully');
        }

        $settingData = InspectionSetting::find(1);

        return view('admin.inspection_settings.index', [
            'listTitle' => $listTitle,
            'settingData' => $settingData,
            'scheduels' => $this->schedules,
        ]);
    }
}
