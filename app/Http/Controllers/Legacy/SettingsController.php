<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\CsWorkingHour;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        if ($request->isMethod('post')) {
            $data = $request->input('CsSetting');
            $data['user_id'] = $userId;
            $data['locations'] = json_encode($request->input('VehicleLocation', []));
            
            $setting = CsSetting::updateOrCreate(['user_id' => $userId], $data);
            session()->flash('success', 'Settings updated successfully.');
            return back();
        }

        $setting = CsSetting::where('user_id', $userId)->first();
        $workingHour = CsWorkingHour::where('user_id', $userId)->first();
        
        return view('legacy.settings.index', compact('setting', 'workingHour'));
    }

    public function working_hours(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');

        if ($request->isMethod('post')) {
            $data = $request->input('CsWorkingHour');
            $data['user_id'] = $userId;
            
            // Reformat times
            $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
            foreach ($days as $day) {
                if (isset($data[$day . '_start'])) {
                    $data[$day . '_start'] = date('H:i', strtotime($data[$day . '_start']));
                }
                if (isset($data[$day . '_end'])) {
                    $data[$day . '_end'] = date('H:i', strtotime($data[$day . '_end']));
                }
            }

            CsWorkingHour::updateOrCreate(['user_id' => $userId], $data);
            session()->flash('success', 'Working hours updated successfully.');
        }

        return redirect()->route('legacy.settings.index');
    }

    public function syncVehicleAllowedMiles(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $miles = $request->input('allowed_miles');

        Vehicle::where('user_id', $userId)->update(['allowed_miles' => $miles]);

        return response()->json(['status' => 1, 'message' => "Vehicle updated successfully"]);
    }

    public function syncVehicleProgram(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $program = $request->input('program');

        Vehicle::where('user_id', $userId)->update(['program' => $program]);

        return response()->json(['status' => 1, 'message' => "Vehicle updated successfully"]);
    }
}
