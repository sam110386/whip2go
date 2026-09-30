<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Exception;
class InsuranceTemplatesController extends LegacyAppController
{
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Insurance';
        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect()->back()->with('error', 'Invalid user ID');
        }

        if ($request->isMethod('post') && $request->has('CsInsuranceTemplate')) {
            $dataToSave = $request->input('CsInsuranceTemplate');
            $dataToSave['user_id'] = $userid;

            CsInsuranceTemplate::updateOrCreate(
                ['user_id' => $userid],
                $dataToSave
            );

            return redirect()->back()->with('success', 'Record saved successfully');
        }

        $csInsuranceTemplate = CsInsuranceTemplate::where('user_id', $userid)->first();

        return view('admin.insurance_templates.index', compact('title', 'csInsuranceTemplate', 'userid'));
    }
    public function syncVehicleInsurance(Request $request)
    {
        if ($this->ensureAdminSession() !== null) {
            return response()->json([
                'status' => false,
                'message' => 'Session expired.',
            ], 401);
        }

        $return = [
            'status' => false,
            'message' => 'Sorry, something went wrong, please try again later',
        ];

        $templateid = $request->input('templateid');
        $csInsuranceTemplate = CsInsuranceTemplate::find($templateid);

        if (!empty($csInsuranceTemplate)) {
            try {
                Vehicle::where('user_id', $csInsuranceTemplate->user_id)->update([
                    'insurance_policy_no' => $csInsuranceTemplate->insurance_policy_no,
                    'insurance_company' => $csInsuranceTemplate->insurance_company,
                    'insurance_policy_date' => $csInsuranceTemplate->insurance_policy_date,
                    'insurance_policy_exp_date' => $csInsuranceTemplate->insurance_policy_exp_date,
                ]);

                $return['status'] = true;
                $return['message'] = "Vehicle records updated successfully.";
            } catch (Exception $e) {
                $return['message'] = $e->getMessage();
            }
        }

        return response()->json($return);
    }
}
