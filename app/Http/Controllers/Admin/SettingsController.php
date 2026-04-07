<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\DepositTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => 0,
            'message' => "AdminSettings::{$action} pending migration",
            'result' => (object)[],
        ]);
    }

    public function index(Request $request, $userId)
    {
        $userId = base64_decode($userId);
        $setting = CsSetting::where('user_id', $userId)->first();
        $depositTemplate = DepositTemplate::where('user_id', $userId)->first();

        if ($request->isMethod('post')) {
            $data = $request->input('CsSetting');
            $data['user_id'] = $userId;
            $data['booking_validation'] = json_encode($request->input('CsSetting.booking_validation', []));
            $data['locations'] = json_encode($request->input('VehicleLocation', []));
            
            $setting = CsSetting::updateOrCreate(['user_id' => $userId], $data);
            
            // If deposit template data is provided
            if ($request->has('DepositTemplate.id')) {
                $depData = $request->input('DepositTemplate');
                DepositTemplate::where('id', $depData['id'])->update([
                    'roadside_assistance_included' => $depData['roadside_assistance_included'],
                    'maintenance_included_fee' => $depData['maintenance_included_fee']
                ]);
            }
            
            session()->flash('success', 'Settings updated successfully.');
            return back();
        }

        return view('admin.settings.index', compact('setting', 'depositTemplate', 'userId'));
    }

    public function validateGeotab(Request $request)
    {
        // Placeholder for Geotab/GPS validation logic
        return response()->json(['status' => false, 'message' => "GPS Provider logic pending Lib migration."]);
    }

    public function admin_index(Request $request, $userId) { return $this->index($request, $userId); }
    public function admin_pullDevicesFromAutoPi(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_syncDeviceWithGeotab(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_syncVehicleAddress(Request $request) { return $this->_syncVehicleAddress($request); }
    public function admin_syncVehicleAllowedMiles(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_syncVehicleDefaultAddress(Request $request) { return $this->_syncVehicleDefaultAddress($request); }
    public function admin_syncVehicleFinancing(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_syncVehicleProgram(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_syncVehicleWithOnestep(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_validateGeotab(Request $request) { return $this->validateGeotab($request); }
    public function admin_validateOneStepGPSKey(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function tdk_setting(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    protected function _syncVehicleAddress(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    protected function _syncVehicleDefaultAddress(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
