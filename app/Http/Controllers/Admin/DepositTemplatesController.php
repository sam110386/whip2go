<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\DepositTemplatesController as LegacyDepositTemplatesController;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

class DepositTemplatesController extends LegacyDepositTemplatesController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index (Admin view for a specific dealer's template) ───────────
    public function admin_index(Request $request, $userId)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = base64_decode($userId);
        $this->layout = 'admin';
        $this->set('listTitle', 'Update Rental Fee Template');

        if ($request->isMethod('post')) {
            $data = $request->input('DepositTemplate', []);
            $data['user_id'] = $userId;

            if (($data['deposit_event'] ?? 'N') == 'N') {
                $data['deposit_amt'] = 0;
            }

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

        $vehicleModel = new Vehicle();
        $makes = $vehicleModel->getMake([0, 1]);
        $models = $vehicleModel->getMakeModel($makes, [0, 1]);

        return view('admin.deposit_templates.index', compact('userId', 'depositTemplate', 'makes', 'models'));
    }

    // ─── admin_syncToVehicle (AJAX Wrapper) ──────────────────────────────────
    public function admin_syncToVehicle(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $userId = $request->input('DepositTemplate.user_id');
        // Legacy sync logic works the same but uses passed userId
        return $this->syncToVehicle($request);
    }

    // ─── admin_updateVehicleSetting (AJAX Wrapper) ──────────────────────────
    public function admin_updateVehicleSetting(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $userId = $request->input('DepositTemplate.user_id') ?? $request->input('user_id');
        return $this->updateVehicleSetting($request);
    }

    // ─── admin_updateFareType (Specialty admin sync) ────────────────────────
    public function admin_updateFareType(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $userId = $request->input('user_id');
        $field = $request->input('field');
        $vehicles = Vehicle::where('user_id', $userId)->get();

        foreach ($vehicles as $vehicle) {
            if ($field == 'fare_type') {
                $vehicle->fare_type = $request->input('fare_type');
                $vehicle->day_rent = ($vehicle->fare_type == 'L') ? 0 : $vehicle->day_rent;
            } else {
                $vehicle->{$field} = $request->input($field);
            }
            $vehicle->save();
        }

        return response()->json(['status' => true, 'message' => 'Vehicles synchronized successfully.']);
    }
}
