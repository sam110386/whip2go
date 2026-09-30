<?php

namespace App\Observers;

use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleVariation;

class VehicleObserver
{
    public function saving(Vehicle $vehicle)
    {
        if (
            !empty($vehicle->id)
            || !empty($vehicle->insurance_company)
            || empty($vehicle->user_id)
        ) {
            return;
        }

        $insuranceTemplate = CsInsuranceTemplate::where('user_id', $vehicle->user_id)->first();

        if ($insuranceTemplate) {
            $vehicle->insurance_company = $insuranceTemplate->insurance_company;
            $vehicle->insurance_policy_no = $insuranceTemplate->insurance_policy_no;
            $vehicle->insurance_policy_date = $insuranceTemplate->insurance_policy_date;
            $vehicle->insurance_policy_exp_date = $insuranceTemplate->insurance_policy_exp_date;
        }

        if (empty($vehicle->fare_type)) {
            $savedTemplate = DepositTemplate::where('user_id', $vehicle->user_id)
                ->select(['fare_type', 'roadside_assistance_included', 'maintenance_included_fee'])
                ->first();

            $vehicle->fare_type = $savedTemplate->fare_type ?? 'S';
            $vehicle->roadside_assistance_included = $savedTemplate->roadside_assistance_included ?? 0;
            $vehicle->maintenance_included_fee = $savedTemplate->maintenance_included_fee ?? 0;
        }
    }
    public function created(Vehicle $vehicle)
    {
        $savedTemplate = DepositTemplate::where('user_id', $vehicle->user_id)->first();

        DepositRule::create([
            'user_id' => $vehicle->user_id,
            'vehicle_id' => $vehicle->id,
            'title' => !empty($vehicle->vehicle_unique_id) ? $vehicle->vehicle_unique_id : $vehicle->vehicle_name,
            'deposit_amt' => $savedTemplate->deposit_amt ?? 200,
            'deposit_event' => $savedTemplate->deposit_event ?? 'P',
            'deposit_type' => $savedTemplate->deposit_type ?? 'C',
            'charge_rent' => $savedTemplate->charge_rent ?? 'S',
            'emf' => $savedTemplate->emf ?? 0.5,
            'emf_insu' => $savedTemplate->emf_insu ?? 0.15,
            'tax' => $savedTemplate->tax ?? 0,
            'lateness_fee' => $savedTemplate->lateness_fee ?? 5,
            'cancellation_fee' => $savedTemplate->cancellation_fee ?? 10,
            'insurance_fee' => $savedTemplate->insurance_fee ?? 0.25,
            'insurance_event' => $savedTemplate->insurance_event ?? 'S',
            'initial_event' => $savedTemplate->initial_event ?? 'P',
            'initial_fee' => $savedTemplate->initial_fee ?? 0,
            'total_deposit_amt' => $savedTemplate->total_deposit_amt ?? 0,
            'deposit_amt_opt' => $savedTemplate->deposit_amt_opt ?? '',
            'initial_fee_opt' => $savedTemplate->initial_fee_opt ?? '',
            'total_initial_fee' => $savedTemplate->total_initial_fee ?? 0,
            'depreciation_rate' => $savedTemplate->depreciation_rate ?? 0,
            'financing' => $savedTemplate->financing ?? 0,
            'financing_type' => $savedTemplate->financing_type ?? 0,
            'monthly_maintenance' => $savedTemplate->monthly_maintenance ?? 0,
            'disposition_fee' => $savedTemplate->disposition_fee ?? 0,
            'write_down_allocation' => $savedTemplate->write_down_allocation ?? 0,
            'prepaid_initial_fee' => $savedTemplate->prepaid_initial_fee ?? 0,
            'prepaid_initial_fee_data' => $savedTemplate->prepaid_initial_fee_data ?? null,
            'program_length' => $savedTemplate->program_length ?? 365,
            'capitalize_starting_fee' => $savedTemplate->capitalize_starting_fee ?? null,
            'insurance_payer' => $savedTemplate->insurance_payer ?? null,
            'return_fee' => $savedTemplate->return_fee ?? null,
        ]);
    }
    public function deleted(Vehicle $vehicle)
    {
        VehicleVariation::where('variant_id', $vehicle->id)->delete();
    }
}
