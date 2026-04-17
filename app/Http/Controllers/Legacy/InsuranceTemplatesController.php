<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

class InsuranceTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (Insurance Template settings) ──────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->layout = "main";
        $this->set('title_for_layout', 'Insurance');
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        if ($request->isMethod('post')) {
            $data = $request->input('CsInsuranceTemplate', []);
            $data['user_id'] = $userId;
            
            CsInsuranceTemplate::updateOrCreate(['user_id' => $userId], $data);
            
            return redirect()->back()->with('success', 'Insurance template saved successfully.');
        }

        $record = CsInsuranceTemplate::where('user_id', $userId)->first();
        view()->share('data', $record);

        return view('legacy.insurance_templates.index', compact('record'));
    }

    // ─── syncVehicleInsurance (AJAX) ──────────────────────────────────────────
    public function syncVehicleInsurance(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $templateId = $request->input('templateid');

        $template = CsInsuranceTemplate::where('id', $templateId)->where('user_id', $userId)->first();

        if ($template) {
            Vehicle::where('user_id', $userId)->update([
                'insurance_policy_no'       => $template->insurance_policy_no,
                'insurance_company'         => $template->insurance_company,
                'insurance_policy_date'     => $template->insurance_policy_date,
                'insurance_policy_exp_date' => $template->insurance_policy_exp_date,
            ]);

            return response()->json(['status' => true, 'message' => 'Vehicle insurance records updated successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Template not found.']);
    }
}
