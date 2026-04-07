<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\InsuranceTemplatesController as LegacyInsuranceTemplatesController;
use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

class InsuranceTemplatesController extends LegacyInsuranceTemplatesController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index (Admin view for a specific dealer's insurance) ───────────
    public function admin_index(Request $request, $userId)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = base64_decode($userId);
        $this->layout = "admin";
        $this->set('title_for_layout', 'Insurance');

        if ($request->isMethod('post')) {
            $data = $request->input('CsInsuranceTemplate', []);
            $data['user_id'] = $userId;
            
            CsInsuranceTemplate::updateOrCreate(['user_id' => $userId], $data);
            
            return redirect()->back()->with('success', 'Insurance template saved successfully.');
        }

        $record = CsInsuranceTemplate::where('user_id', $userId)->first();
        view()->share('data', $record);

        return view('admin.insurance_templates.index', compact('userId', 'record'));
    }

    // ─── admin_syncVehicleInsurance (AJAX Wrapper) ───────────────────────────
    public function admin_syncVehicleInsurance(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $templateId = $request->input('templateid');
        $template = CsInsuranceTemplate::where('id', $templateId)->first();

        if ($template) {
            Vehicle::where('user_id', $template->user_id)->update([
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
