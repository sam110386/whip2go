<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

trait InsuranceTemplatesTrait
{
    /**
     * Shared logic for index(), admin_index()
     */
    protected function processInsuranceTemplateIndex(Request $request, int $userId, string $viewName, ?string $redirectRoute = null)
    {
        if ($request->isMethod('post')) {
            $dataToSave = $request->input('CsInsuranceTemplate', []);
            $dataToSave['user_id'] = $userId;

            $template = CsInsuranceTemplate::where('user_id', $userId)->first() ?: new CsInsuranceTemplate();
            $template->fill($dataToSave);
            $template->save();

            $msg = 'Record saved successfully';
            if ($redirectRoute) {
                return redirect($redirectRoute)->with('success', $msg);
            } else {
                return redirect()->back()->with('success', $msg);
            }
        }

        $templateData = CsInsuranceTemplate::where('user_id', $userId)->first();
        
        return view($viewName, [
            'listTitle' => 'Insurance',
            'userId' => $userId,
            'requestData' => ['CsInsuranceTemplate' => $templateData ? $templateData->toArray() : []]
        ]);
    }

    /**
     * Shared logic for syncVehicleInsurance(), admin_syncVehicleInsurance()
     */
    protected function processSyncVehicleInsurance(Request $request, ?int $userId = null)
    {
        $templateId = $request->input('templateid');
        $return = ['status' => false, 'message' => 'Sorry, something went wrong, please try again later'];

        // Cake PHP admin endpoint didn't restrict template lookup by user_id
        $query = CsInsuranceTemplate::where('id', $templateId);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $templateObj = $query->first();

        if (!empty($templateObj)) {
            try {
                Vehicle::where('user_id', $templateObj->user_id)->update([
                    'insurance_policy_no' => $templateObj->insurance_policy_no,
                    'insurance_company' => $templateObj->insurance_company,
                    'insurance_policy_date' => $templateObj->insurance_policy_date,
                    'insurance_policy_exp_date' => $templateObj->insurance_policy_exp_date,
                ]);
                $return['status'] = true;
                $return['message'] = "Vehicle records updated successfully.";
            } catch (\Exception $e) {
                $return['message'] = $e->getMessage();
            }
        }

        return response()->json($return);
    }
}
