<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

class DepositTemplatesController extends LegacyAppController
{
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Update Rental Fee Template';
        $userid = $this->decodeId($userid);

        if (!$userid) {
            return redirect('/admin/users/index')->with('error', 'Invalid dealer ID.');
        }

        if ($request->isMethod('POST')) {
            $dataToSave = $request->input('DepositTemplate', []);
            $depositTemplateId = $dataToSave['id'] ?? null;

            $incentives = array_filter($dataToSave['incentives'] ?? [], function ($item) {
                return isset($item['amount']) && ($item['amount'] != 0);
            });

            if (empty($depositTemplateId)) {
                $dataToSave['user_id'] = $userid;
            }

            if (($dataToSave['deposit_event'] ?? '') == 'N') {
                $dataToSave['deposit_amt'] = 0;
            }

            $total_deposit_amt = $dataToSave['deposit_amt'] ?? 0;
            $depositAmtOpt = $dataToSave['deposit_amt_opt'] ?? [];
            $total_depositamt = collect($depositAmtOpt)->sum('amount');
            $dataToSave['total_deposit_amt'] = $total_deposit_amt + $total_depositamt;
            $dataToSave['deposit_amt_opt'] = $total_depositamt > 0 ? json_encode($this->truncateData($depositAmtOpt)) : '';
            $total_initial_fee = $dataToSave['initial_fee'] ?? 0;
            $initialFeeOpt = $dataToSave['initial_fee_opt'] ?? [];
            $total_initialfee = collect($initialFeeOpt)->sum('amount');
            $dataToSave['total_initial_fee'] = $total_initial_fee + $total_initialfee;
            $dataToSave['initial_fee_opt'] = $total_initialfee > 0 ? json_encode($this->truncateData($initialFeeOpt)) : '';
            $prepaidData = $dataToSave['prepaid_initial_fee_data'] ?? [];

            if (!empty($dataToSave['prepaid_initial_fee']) && !empty($prepaidData['amount']) && !empty($prepaidData['day'])) {
                $dataToSave['prepaid_initial_fee_data'] = json_encode($prepaidData);
                $dataToSave['prepaid_initial_fee'] = 1;
            } else {
                $dataToSave['prepaid_initial_fee_data'] = null;
                $dataToSave['prepaid_initial_fee'] = 0;
            }

            $dataToSave['incentives'] = json_encode($incentives);

            try {
                DepositTemplate::updateOrCreate(
                    ['id' => $depositTemplateId, 'user_id' => $userid],
                    $dataToSave
                );

                $message = empty($depositTemplateId) ? "Rule has been added successfully." : "Rule has been updated successfully.";
                return redirect()->back()->with('success', $message);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

        }

        $template = DepositTemplate::where('user_id', $userid)->first();
        $depositTemplateData = [];

        if ($template) {
            $depositTemplateData = $template->toArray();
            $depositTemplateData['deposit_amt_opt'] = !empty($template->deposit_amt_opt) ? json_decode($template->deposit_amt_opt, true) : [];
            $depositTemplateData['initial_fee_opt'] = !empty($template->initial_fee_opt) ? json_decode($template->initial_fee_opt, true) : [];
            $depositTemplateData['prepaid_initial_fee_data'] = !empty($template->prepaid_initial_fee_data) ? json_decode($template->prepaid_initial_fee_data, true) : ["day" => "", "amount" => ""];
            $depositTemplateData['incentives'] = !empty($template->incentives) ? json_decode($template->incentives, true) : [];
        }

        $makes = Vehicle::getMake([0, 1]);
        $models = Vehicle::getMakeModel($makes, [0, 1]);

        return view('admin.deposit_templates.index', compact('title', 'userid', 'depositTemplateData', 'makes', 'models'));
    }

    protected function truncateInput($arr)
    {
        $return = [];
        foreach ($arr as $key => $a) {
            if ((isset($a['after_day_date']) && !empty($a['after_day_date'])) || (!empty($a['after_day']))) {
                if (!empty($a['amount']))
                    $return[$key] = $a;
            }
        }
        return $return;
    }

    public function updateFareType(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $field = (string) $request->input('field');
        if ($userId <= 0 || $field === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $allowedScalar = ['roadside_assistance_included', 'maintenance_included_fee'];

        $vehicles = LegacyVehicle::query()
            ->where('user_id', $userId)
            ->get(['id', 'fare_type', 'day_rent']);

        foreach ($vehicles as $v) {
            if ($field === 'fare_type') {
                $fare = (string) $request->input('fare_type', '');
                $updates = ['fare_type' => $fare];
                if ($fare === 'L') {
                    $updates['day_rent'] = 0;
                }
                LegacyVehicle::query()->whereKey((int) $v->id)->update($updates);
            } elseif (in_array($field, $allowedScalar, true)) {
                $val = $request->input($field);
                LegacyVehicle::query()->whereKey((int) $v->id)->update([$field => $val]);
            } else {
                return response()->json(['status' => false, 'message' => 'Invalid field']);
            }
        }

        return response()->json(['status' => true, 'message' => 'Vehicle synched successfully']);
    }
}
