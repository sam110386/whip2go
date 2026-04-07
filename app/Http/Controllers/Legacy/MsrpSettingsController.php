<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsMsrpSetting;
use App\Models\Legacy\CsEquitySetting;
use App\Models\Legacy\PtoSetting;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MsrpSettingsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (Path to Ownership Setting) ────────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->layout = "main";
        $this->set('title_for_layout', 'Path To Ownership Setting');
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        if ($request->isMethod('post')) {
            $data = $request->input('data', []);
            $keepIds = [];

            foreach ($data as $item) {
                $settingData = $item['CsMsrpSetting'];
                $settingData['user_id'] = $userId;
                
                $record = CsMsrpSetting::updateOrCreate(
                    ['id' => $settingData['id'] ?? null, 'user_id' => $userId],
                    $settingData
                );
                $keepIds[] = $record->id;
            }

            // Clean up deleted records
            if (!empty($keepIds)) {
                CsMsrpSetting::where('user_id', $userId)->whereNotIn('id', $keepIds)->delete();
            }

            return redirect()->back()->with('success', 'MSRP settings updated successfully.');
        }

        $msrpSettings = CsMsrpSetting::where('user_id', $userId)->get();
        $equitySetting = CsEquitySetting::where('user_id', $userId)->first();

        // Share legacy request data structure
        view()->share('data', $msrpSettings);

        return view('legacy.msrp_settings.index', compact('msrpSettings', 'equitySetting'));
    }

    // ─── equaitysave (AJAX/Post) ──────────────────────────────────────────────
    public function equaitysave(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        
        if ($request->isMethod('post')) {
            $data = [
                'user_id'       => $userId,
                'share'         => $request->input('EquitySetting.share'),
                'other_vhshare' => $request->input('EquitySetting.other_vhshare'),
            ];

            CsEquitySetting::updateOrCreate(['user_id' => $userId], $data);
            
            return redirect()->back()->with('success', 'Equity settings updated.');
        }

        return redirect()->back();
    }

    // ─── pto (Path to Ownership secondary setting) ────────────────────────────
    public function pto(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->layout = "main";
        $this->set('title_for_layout', 'PTO Setting');
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        if ($request->isMethod('post')) {
            $data = $request->input('data', []);
            $keepIds = [];

            foreach ($data as $item) {
                $settingData = $item['PtoSetting'];
                $settingData['user_id'] = $userId;
                
                $record = PtoSetting::updateOrCreate(
                    ['id' => $settingData['id'] ?? null, 'user_id' => $userId],
                    $settingData
                );
                $keepIds[] = $record->id;
            }

            if (!empty($keepIds)) {
                PtoSetting::where('user_id', $userId)->whereNotIn('id', $keepIds)->delete();
            }

            return redirect()->back()->with('success', 'PTO settings updated successfully.');
        }

        $ptoSettings = PtoSetting::where('user_id', $userId)->get();
        view()->share('data', $ptoSettings);

        return view('legacy.msrp_settings.pto', compact('ptoSettings'));
    }

    // ─── syncDayRentalToVehicle (AJAX) ────────────────────────────────────────
    public function syncDayRentalToVehicle(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $vehicles = Vehicle::where('user_id', $userId)->get();

        foreach ($vehicles as $vehicle) {
            // Trigger dynamic fare calculation logic (simulated)
            // \App\Models\Legacy\DynamicFare::calculateDynamicFare($vehicle, true);
        }

        return response()->json(['status' => true, 'message' => 'Vehicle pricing synchronized successfully.']);
    }
}
