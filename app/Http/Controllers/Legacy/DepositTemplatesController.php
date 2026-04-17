<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DepositTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (Update Rental Fee Template) ──────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->layout = 'main';
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        
        $this->set('listTitle', 'Update Rental Fee Template');
        
        if ($request->isMethod('post')) {
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
            $data['deposit_amt_opt']   = $optDepositAmt > 0 ? json_encode($this->truncate($data['deposit_amt_opt'])) : '';

            $totalInitialFee = $data['initial_fee'] ?? 0;
            $optInitialFee   = collect($data['initial_fee_opt'] ?? [])->sum('amount');
            $data['total_initial_fee'] = $totalInitialFee + $optInitialFee;
            $data['initial_fee_opt']   = $optInitialFee > 0 ? json_encode($this->truncate($data['initial_fee_opt'])) : '';

            if (!empty($data['prepaid_initial_fee']) && !empty($data['prepaid_initial_fee_data']['amount']) && !empty($data['prepaid_initial_fee_data']['day'])) {
                $data['prepaid_initial_fee_data'] = json_encode($data['prepaid_initial_fee_data']);
                $data['prepaid_initial_fee'] = 1;
            } else {
                $data['prepaid_initial_fee_data'] = null;
                $data['prepaid_initial_fee'] = 0;
            }

            DepositTemplate::updateOrCreate(['user_id' => $userId], $data);
            
            return redirect()->back()->with('success', 'Rental Fee Template updated successfully.');
        }

        $depositTemplate = DepositTemplate::where('user_id', $userId)->first();
        $data = $depositTemplate ? $depositTemplate->toArray() : [];
        if (!empty($data)) {
            $data['deposit_amt_opt'] = !empty($data['deposit_amt_opt']) ? json_decode($data['deposit_amt_opt'], true) : [];
            $data['initial_fee_opt'] = !empty($data['initial_fee_opt']) ? json_decode($data['initial_fee_opt'], true) : [];
            $data['incentives']      = !empty($data['incentives']) ? json_decode($data['incentives'], true) : [];
        }

        view()->share('data', $data);

        // Fetch makes/models for incentives dropdown
        $vehicleModel = new Vehicle();
        $makes = $vehicleModel->getMake([0, 1]);
        $models = $vehicleModel->getMakeModel($makes, [0, 1]);

        return view('legacy.deposit_templates.index', compact('depositTemplate', 'makes', 'models'));
    }

    // ─── syncToVehicle (AJAX) ────────────────────────────────────────────────
    public function syncToVehicle(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $data = $request->input('DepositTemplate', []);
        
        $totalDepositSum = collect($data['deposit_amt_opt'] ?? [])->sum('amount');
        $data['total_deposit_amt'] = ($data['deposit_amt'] ?? 0) + $totalDepositSum;
        $data['deposit_amt_opt']   = $totalDepositSum > 0 ? json_encode($this->truncateArray(collect($data['deposit_amt_opt'])->toArray())) : '';

        $totalInitialSum = collect($data['initial_fee_opt'] ?? [])->sum('amount');
        $data['total_initial_fee'] = ($data['initial_fee'] ?? 0) + $totalInitialSum;
        $data['initial_fee_opt']   = $totalInitialSum > 0 ? json_encode($this->truncateArray(collect($data['initial_fee_opt'])->toArray())) : '';

        if (!empty($data['prepaid_initial_fee']) && !empty($data['prepaid_initial_fee_data']['amount']) && !empty($data['prepaid_initial_fee_data']['day'])) {
            $data['prepaid_initial_fee_data'] = json_encode($data['prepaid_initial_fee_data']);
            $data['prepaid_initial_fee'] = 1;
        } else {
            $data['prepaid_initial_fee_data'] = null;
            $data['prepaid_initial_fee'] = 0;
        }

        // Apply rules to all dealer vehicles
        $depositRuleData = $data;
        unset($depositRuleData['fare_type'], $depositRuleData['buy_fee']);

        DepositRule::where('user_id', $userId)->update($depositRuleData);

        // Update vehicle MSRP-based pricing if selling premium is defined
        $vehicles = Vehicle::where('user_id', $userId)->select('id', 'msrp')->get();
        foreach ($vehicles as $vehicle) {
            $vehicle->premium_msrp = ($vehicle->msrp + ($data['selling_premium'] ?? 0));
            $vehicle->save();
        }

        return response()->json(['status' => true, 'message' => 'Vehicles synchronized successfully.']);
    }

    // ─── updateVehicleSetting (AJAX) ────────────────────────────────────────
    public function updateVehicleSetting(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $data = $request->all();

        // If updating incentives
        if (isset($data['incentive'])) {
            $incentives = array_filter($data['DepositTemplate']['incentives'] ?? [], fn($item) => ($item['amount'] ?? 0) != 0);
            
            $allRules = DepositRule::from('deposit_rules as DepositRule')
                ->join('vehicles as Vehicle', 'Vehicle.id', '=', 'DepositRule.vehicle_id')
                ->where('DepositRule.user_id', $userId)
                ->select('DepositRule.id', 'DepositRule.vehicle_id', 'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.trim')
                ->get();

            foreach ($allRules as $rule) {
                $amount = 0;
                foreach ($incentives as $incentive) {
                    if (strcasecmp($rule->make, $incentive['make']) !== 0) continue;
                    if (!in_array($rule->model, (array)($incentive['model'] ?? []))) continue;
                    if (!empty($incentive['year']) && $rule->year != $incentive['year']) continue;
                    if (!empty($incentive['trim']) && $rule->trim != $incentive['trim']) continue;
                    
                    $amount = $incentive['amount'];
                    break;
                }
                
                if ($amount > 0) {
                    DepositRule::where('id', $rule->id)->update(['incentive' => $amount]);
                    // Trigger price update logic (simulated)
                    // \App\Lib\Legacy\Free2Move::fetchDynamicFare($rule->vehicle_id, true);
                }
            }
        } else {
            // General record update
            DepositRule::where('user_id', $userId)->update($data['DepositRule'] ?? []);
        }

        return response()->json(['status' => true, 'message' => 'Vehicle settings updated.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function truncate($arr) {
        $return = [];
        foreach ($arr as $key => $a) {
            if ((isset($a['after_day_date']) && !empty($a['after_day_date'])) || (!empty($a['after_day']))) {
                if (!empty($a['amount'])) $return[$key] = $a;
            }
        }
        return $return;
    }

    protected function truncateArray($arr) {
        $return = [];
        foreach ($arr as $a) {
            if ((isset($a['after_day_date']) && !empty($a['after_day_date'])) || (!empty($a['after_day']))) {
                if (!empty($a['amount'])) $return[] = $a;
            }
        }
        return $return;
    }
}
