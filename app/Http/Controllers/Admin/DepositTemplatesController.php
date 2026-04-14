<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    /**
     * Admin Index: Edit a specific dealer's deposit template.
     */
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $uid = $this->decodeId($userid);
        if (!$uid) {
            return redirect('/admin/users/index')->with('error', 'Invalid dealer ID.');
        }
        $userId = $uid;
        $useridB64 = base64_encode((string)$uid);

        if ($request->isMethod('POST')) {
            $data = $request->input('DepositTemplate', []);
            $data['user_id'] = $userId;
            
            if (($data['deposit_event'] ?? 'N') == 'N') {
                $data['deposit_amt'] = 0;
            }

            // Serialize optional fees and incentives
            $incentives = array_filter($data['incentives'] ?? [], fn($item) => ($item['amount'] ?? 0) != 0);
            $data['incentives'] = json_encode($incentives);

            $totalDepositAmt = $data['deposit_amt'] ?? 0;
            $optDepositAmt   = collect($data['deposit_amt_opt'] ?? [])->sum('amount');
            $data['total_deposit_amt'] = $totalDepositAmt + $optDepositAmt;
            $data['deposit_amt_opt']   = $optDepositAmt > 0 ? json_encode($this->truncateInput($data['deposit_amt_opt'])) : '';

            $totalInitialFee = $data['initial_fee'] ?? 0;
            $optInitialFee   = collect($data['initial_fee_opt'] ?? [])->sum('amount');
            $data['total_initial_fee'] = $totalInitialFee + $optInitialFee;
            $data['initial_fee_opt']   = $optInitialFee > 0 ? json_encode($this->truncateInput($data['initial_fee_opt'])) : '';

            if (!empty($data['prepaid_initial_fee']) && !empty($data['prepaid_initial_fee_data']['amount']) && !empty($data['prepaid_initial_fee_data']['day'])) {
                $data['prepaid_initial_fee_data'] = json_encode($data['prepaid_initial_fee_data']);
                $data['prepaid_initial_fee'] = 1;
            } else {
                $data['prepaid_initial_fee_data'] = null;
                $data['prepaid_initial_fee'] = 0;
            }

            \App\Models\Legacy\DepositTemplate::updateOrCreate(['user_id' => $userId], $data);
            
            return redirect()->back()->with('success', 'Rental Fee Template updated successfully.');
        }

        $depositTemplate = \App\Models\Legacy\DepositTemplate::where('user_id', $userId)->first();
        $data = $depositTemplate ? $depositTemplate->toArray() : [];
        if (!empty($data)) {
            $data['deposit_amt_opt'] = !empty($data['deposit_amt_opt']) ? json_decode($data['deposit_amt_opt'], true) : [];
            $data['initial_fee_opt'] = !empty($data['initial_fee_opt']) ? json_decode($data['initial_fee_opt'], true) : [];
            $data['incentives']      = !empty($data['incentives']) ? json_decode($data['incentives'], true) : [];
        }

        $vehicleModel = new \App\Models\Legacy\Vehicle();
        $makes = $vehicleModel->getMake([0, 1]);
        $models = $vehicleModel->getMakeModel($makes, [0, 1]);

        return view('admin.deposit_templates.index', [
            'listTitle' => 'Update Rental Fee Template',
            'depositTemplate' => $depositTemplate,
            'data' => $data,
            'makes' => $makes,
            'models' => $models,
            'userid' => $userId,
            'useridB64' => $useridB64
        ]);
    }

    protected function truncateInput($arr) {
        $return = [];
        foreach ($arr as $key => $a) {
            if ((isset($a['after_day_date']) && !empty($a['after_day_date'])) || (!empty($a['after_day']))) {
                if (!empty($a['amount'])) $return[$key] = $a;
            }
        }
        return $return;
    }

    /**
     * Cake DepositTemplatesController::admin_updateFareType (subset: settings sync buttons).
     */
    public function updateFareType(Request $request): JsonResponse
    {
        $userId = (int)$request->input('user_id');
        $field = (string)$request->input('field');
        if ($userId <= 0 || $field === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $allowedScalar = ['roadside_assistance_included', 'maintenance_included_fee'];

        $vehicles = LegacyVehicle::query()
            ->where('user_id', $userId)
            ->get(['id', 'fare_type', 'day_rent']);

        foreach ($vehicles as $v) {
            if ($field === 'fare_type') {
                $fare = (string)$request->input('fare_type', '');
                $updates = ['fare_type' => $fare];
                if ($fare === 'L') {
                    $updates['day_rent'] = 0;
                }
                LegacyVehicle::query()->whereKey((int)$v->id)->update($updates);
            } elseif (in_array($field, $allowedScalar, true)) {
                $val = $request->input($field);
                LegacyVehicle::query()->whereKey((int)$v->id)->update([$field => $val]);
            } else {
                return response()->json(['status' => false, 'message' => 'Invalid field']);
            }
        }

        return response()->json(['status' => true, 'message' => 'Vehicle synched successfully']);
    }
}
