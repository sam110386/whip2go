<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait DepositTemplatesTrait
{
    /**
     * Shared logic for index(), admin_index(), cloud_index()
     */
    protected function processDepositTemplateIndex(Request $request, int $userId, string $listTitle, string $viewName, ?string $redirectRoute = null)
    {
        if ($request->isMethod('post')) {
            $dataToSave = $request->input('DepositTemplate', []);
            
            if (empty($dataToSave['id'])) {
                $dataToSave['user_id'] = $userId;
            }
            if (($dataToSave['deposit_event'] ?? '') === 'N') {
                $dataToSave['deposit_amt'] = 0;
            }

            // Filter incentives exactly as Cake PHP did
            $incentives = [];
            if (isset($dataToSave['incentives']) && is_array($dataToSave['incentives'])) {
                $incentives = array_filter($dataToSave['incentives'], function ($item) {
                    return isset($item['amount']) && (float)$item['amount'] != 0;
                });
            }

            // Process deposit_amt_opt truncations
            $total_deposit_amt = (float)($dataToSave['deposit_amt'] ?? 0);
            $depositAmtOpt = is_array($dataToSave['deposit_amt_opt'] ?? null) ? $dataToSave['deposit_amt_opt'] : [];
            $total_depositamtOpt = array_sum(array_column($depositAmtOpt, 'amount'));
            $dataToSave['total_deposit_amt'] = $total_deposit_amt + $total_depositamtOpt;
            $dataToSave['deposit_amt_opt'] = $total_depositamtOpt > 0 ? json_encode($this->truncateDeposit($depositAmtOpt)) : null;

            // Process initial_fee_opt truncations
            $total_initial_fee = (float)($dataToSave['initial_fee'] ?? 0);
            $initialFeeOpt = is_array($dataToSave['initial_fee_opt'] ?? null) ? $dataToSave['initial_fee_opt'] : [];
            $total_initialfeeOpt = array_sum(array_column($initialFeeOpt, 'amount'));
            $dataToSave['total_initial_fee'] = $total_initial_fee + $total_initialfeeOpt;
            $dataToSave['initial_fee_opt'] = $total_initialfeeOpt > 0 ? json_encode($this->truncateDeposit($initialFeeOpt)) : null;

            // Process prepaid_initial_fee
            $prepaidDay = $dataToSave['prepaid_initial_fee_data']['day'] ?? null;
            $prepaidAmt = $dataToSave['prepaid_initial_fee_data']['amount'] ?? null;
            if (!empty($dataToSave['prepaid_initial_fee']) && !empty($prepaidAmt) && !empty($prepaidDay)) {
                $dataToSave['prepaid_initial_fee_data'] = json_encode($dataToSave['prepaid_initial_fee_data']);
                $dataToSave['prepaid_initial_fee'] = 1;
            } else {
                $dataToSave['prepaid_initial_fee_data'] = null;
                $dataToSave['prepaid_initial_fee'] = 0;
            }

            $dataToSave['incentives'] = json_encode($incentives);

            $idToSave = $dataToSave['id'] ?? null;
            $templateModel = $idToSave ? DepositTemplate::find($idToSave) : new DepositTemplate();
            
            if ($templateModel) {
                // Remove arrays that may conflict with fillable fields since we JSON encoded them in specific variables
                unset($dataToSave['id']); 

                // Cake PHP compatibility fallback if 'deposit_amt_opt' string throws exceptions in fill()
                foreach ($dataToSave as $k => $v) {
                    $templateModel->{$k} = $v;
                }
                
                $templateModel->save();

                $msg = empty($idToSave) ? "Rule has been added successfully." : "Rule has been updated successfully.";
                if ($redirectRoute) {
                    return redirect($redirectRoute)->with('success', $msg);
                } else {
                    return redirect()->back()->with('success', $msg);
                }
            }
        }

        // Handle GET Request payload mimic
        $depositTemplate = DepositTemplate::where('user_id', $userId)->first();
        $viewData = $depositTemplate ? $depositTemplate->toArray() : [];
        if (!empty($viewData)) {
            $viewData['deposit_amt_opt'] = !empty($viewData['deposit_amt_opt']) ? json_decode($viewData['deposit_amt_opt'], true) : [];
            $viewData['initial_fee_opt'] = !empty($viewData['initial_fee_opt']) ? json_decode($viewData['initial_fee_opt'], true) : [];
            $viewData['prepaid_initial_fee_data'] = !empty($viewData['prepaid_initial_fee_data']) ? json_decode($viewData['prepaid_initial_fee_data'], true) : ['day' => '', 'amount' => ''];
            $viewData['incentives'] = !empty($viewData['incentives']) ? json_decode($viewData['incentives'], true) : [];
        }

        // Map Make & Model queries natively
        $makesRaw = DB::table('vehicles')->whereIn('status', [0, 1])->whereNotNull('make')->where('make', '!=', '')->distinct()->pluck('make')->toArray();
        $makes = array_combine($makesRaw, $makesRaw) ?: [];

        $models = [];
        foreach ($makes as $make) {
            $modelsRaw = DB::table('vehicles')->where('make', $make)->whereIn('status', [0, 1])->whereNotNull('model')->where('model', '!=', '')->distinct()->pluck('model')->toArray();
            $models[$make] = array_combine($modelsRaw, $modelsRaw) ?: [];
        }

        return view($viewName, [
            'listTitle' => $listTitle,
            'userId' => $userId,
            'requestData' => ['DepositTemplate' => $viewData],
            'makes' => $makes,
            'models' => $models
        ]);
    }

    /**
     * Shared logic for syncToVehicle(), admin_syncToVehicle(), cloud_syncToVehicle()
     */
    protected function processSyncToVehicle(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $dataToSave = $request->input('DepositTemplate', []);
        
        $total_deposit_amt = (float)($dataToSave['deposit_amt'] ?? 0);
        $depositAmtOpt = is_array($dataToSave['deposit_amt_opt'] ?? null) ? $dataToSave['deposit_amt_opt'] : [];
        $total_depositamtOpt = array_sum(array_column($depositAmtOpt, 'amount'));
        $dataToSave['total_deposit_amt'] = $total_deposit_amt + $total_depositamtOpt;
        $dataToSave['deposit_amt_opt'] = $total_depositamtOpt > 0 ? json_encode($this->truncateDepositArray(array_values($depositAmtOpt))) : null;

        $total_initial_fee = (float)($dataToSave['initial_fee'] ?? 0);
        $initialFeeOpt = is_array($dataToSave['initial_fee_opt'] ?? null) ? $dataToSave['initial_fee_opt'] : [];
        $total_initialfeeOpt = array_sum(array_column($initialFeeOpt, 'amount'));
        $dataToSave['total_initial_fee'] = $total_initial_fee + $total_initialfeeOpt;
        $dataToSave['initial_fee_opt'] = $total_initialfeeOpt > 0 ? json_encode($this->truncateDepositArray(array_values($initialFeeOpt))) : null;

        $prepaidDay = $dataToSave['prepaid_initial_fee_data']['day'] ?? null;
        $prepaidAmt = $dataToSave['prepaid_initial_fee_data']['amount'] ?? null;
        if (!empty($dataToSave['prepaid_initial_fee']) && !empty($prepaidAmt) && !empty($prepaidDay)) {
            $dataToSave['prepaid_initial_fee_data'] = json_encode($dataToSave['prepaid_initial_fee_data']);
            $dataToSave['prepaid_initial_fee'] = 1;
        } else {
            $dataToSave['prepaid_initial_fee_data'] = null;
            $dataToSave['prepaid_initial_fee'] = 0;
        }

        $userId = $dataToSave['user_id'] ?? null;
        if (empty($userId)) {
            return response()->json(['status' => false, 'message' => 'User ID is missing']);
        }

        $allDeposits = DepositRule::where('user_id', $userId)->get(['id']);
        foreach ($allDeposits as $deposit) {
            $ruleUpdates = $dataToSave;
            $ruleUpdates['id'] = $deposit->id;
            unset($ruleUpdates['fare_type'], $ruleUpdates['buy_fee']);
            
            $ruleTemplate = DepositRule::find($deposit->id);
            if ($ruleTemplate) {
                // Cake `callbacks=>false` meaning no event dispatches
                foreach ($ruleUpdates as $k => $v) {
                    if ($k !== 'id') $ruleTemplate->{$k} = $v;
                }
                $ruleTemplate->save();
            }
        }

        $sellingPremium = (float)($dataToSave['selling_premium'] ?? 0);
        $vehicles = Vehicle::where('user_id', $userId)->get(['id', 'msrp']);
        foreach ($vehicles as $vehicle) {
            $vehicle->premium_msrp = (float)$vehicle->msrp + $sellingPremium;
            $vehicle->save();
        }

        return response()->json(['status' => true, 'message' => 'Vehicle synched successfully']);
    }

    /**
     * Shared logic for updateVehicleSetting(), admin_updateVehicleSetting()
     */
    protected function processUpdateVehicleSetting(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        $data = $request->all();
        $depositTemplateData = $data['DepositTemplate'] ?? [];
        $userId = $depositTemplateData['user_id'] ?? ($data['user_id'] ?? null);

        if (isset($data['incentive']) && $userId) {
            $rawIncentives = $depositTemplateData['incentives'] ?? [];
            $incentives = array_filter($rawIncentives, function ($item) {
                return isset($item['amount']) && (float)$item['amount'] != 0;
            });

            // Perform leftJoin mimicking Cake PHP's joins feature
            $allDeposits = DB::table('cs_deposit_rules')
                ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_deposit_rules.vehicle_id')
                ->where('cs_deposit_rules.user_id', $userId)
                ->select([
                    'cs_deposit_rules.id as deposit_rule_id',
                    'cs_deposit_rules.vehicle_id',
                    'vehicles.make',
                    'vehicles.model',
                    'vehicles.year',
                    'vehicles.trim'
                ])
                ->get();

            foreach ($allDeposits as $deposit) {
                $amount = 0;
                foreach ($incentives as $incentive) {
                    if (strcasecmp($deposit->make, $incentive['make'] ?? '') !== 0) continue;
                    
                    $incModels = $incentive['model'] ?? [];
                    if (!is_array($incModels)) $incModels = [$incModels];
                    
                    if (!in_array($deposit->model, $incModels)) continue;

                    if (!empty($incentive['year']) && $deposit->year != $incentive['year']) continue;
                    if (!empty($incentive['trim']) && $deposit->trim != $incentive['trim']) continue;

                    $amount = $incentive['amount'];
                    break;
                }

                if ($amount == 0) {
                    continue;
                }

                DepositRule::where('id', $deposit->deposit_rule_id)->update(['incentive' => $amount]);
                
                if (class_exists('\Free2Move')) {
                    \Free2Move::fetchDynamicFare($deposit->vehicle_id, true);
                }
            }

            return response()->json(["status" => true, "message" => "Vehicle synched successfully"]);
        }

        if ($userId) {
            $allDeposits = DepositRule::where('user_id', $userId)->get(['id', 'vehicle_id']);
            foreach ($allDeposits as $deposit) {
                $rule = DepositRule::find($deposit->id);
                if ($rule) {
                    foreach ($depositTemplateData as $k => $v) {
                        $rule->{$k} = $v;
                    }
                    $rule->save();
                }

                if (class_exists('\Free2Move')) {
                    \Free2Move::fetchDynamicFare($deposit->vehicle_id, true);
                }
            }
        }

        return response()->json(["status" => true, "message" => "Vehicle synched successfully"]);
    }

    private function truncateDeposit(array $arr): array
    {
        $return = [];
        foreach ($arr as $key => $a) {
            if (!empty($a['after_day_date']) && !empty($a['amount'])) {
                $return[$key] = $a;
            }
            if (!empty($a['after_day']) && !empty($a['amount'])) {
                $return[$key] = $a;
            }
        }
        return $return;
    }

    private function truncateDepositArray(array $arr): array
    {
        $return = [];
        foreach ($arr as $a) {
            if (!empty($a['after_day_date']) && !empty($a['amount'])) {
                $return[] = $a;
            }
            if (!empty($a['after_day']) && !empty($a['amount'])) {
                $return[] = $a;
            }
        }
        return $return;
    }
}
