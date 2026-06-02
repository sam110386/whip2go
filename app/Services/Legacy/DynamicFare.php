<?php

namespace App\Services\Legacy;

use App\Models\Legacy\DepositRule;
use App\Models\Legacy\PtoSetting;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\Vehicle;

class DynamicFare
{
    public static function calculateDynamicFare($vehicleData, bool $force = false): array
    {
        $vehicleData = is_array($vehicleData) ? $vehicleData : $vehicleData->toArray();

        if (!$force && !empty($vehicleData['day_rent'])) {
            $rentOpt = !empty($vehicleData['rent_opt']) ? json_decode($vehicleData['rent_opt'], true) : [];

            if (!empty($rentOpt) && count($rentOpt) === 2) {
                $tier1Obj = $rentOpt[array_key_first($rentOpt)];
                $tier2Obj = $rentOpt[array_key_last($rentOpt)];

                return [
                    "day_rent" => $vehicleData['day_rent'],
                    "rent_opt" => [
                        ["after_day" => $tier1Obj['after_day'], "amount" => $tier1Obj['amount']],
                        ["after_day" => $tier2Obj['after_day'], "amount" => $tier2Obj['amount']]
                    ],
                    "rent_opt_des" => [
                        "0 to " . sprintf("%d", $tier1Obj['after_day'] / 30) . " months $" . $vehicleData['day_rent'] . " per day",
                        "20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime"
                    ]
                ];
            } else {
                return [
                    "day_rent" => $vehicleData['day_rent'],
                    "rent_opt" => [],
                    "rent_opt_des" => [
                        "0 to 1 months $" . $vehicleData['day_rent'] . " per day",
                        "20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime"
                    ]
                ];
            }
        }

        $price = $vehicleData['msrp'] ?: 10000;
        $ownerId = $vehicleData['user_id'];
        $vehicleCostInclRecon = $vehicleData['vehicleCostInclRecon'];

        $depositRuleObj = DepositRule::where('vehicle_id', $vehicleData['id'])->first();

        if (isset($depositRuleObj->write_down_allocation) && $depositRuleObj->write_down_allocation > 0) {
            $downpaymentRate = $depositRuleObj->write_down_allocation;
        } else {
            $downpaymentData = PtoSetting::where('user_id', $ownerId)
                ->where('msrp_from', '<=', $price)
                ->where('msrp_to', '>=', $price)
                ->where('credit_score_from', '<=', 650)
                ->where('credit_score_to', '>=', 650)
                ->first();

            $downpaymentRate = $downpaymentData ? $downpaymentData->downpayment : 60;
        }

        $goalLength = $depositRuleObj->program_length ?? 0;
        $maintenance = ($depositRuleObj->monthly_maintenance ?? 0) / 30;
        $financing = $depositRuleObj->financing ?? 0;
        $financing_type = $depositRuleObj->financing_type ?? '';
        $dispositionfee = $depositRuleObj->disposition_fee ?? 0;

        $downpayment = sprintf('%0.2f', ($price * $downpaymentRate / 100));

        $revSetting = RevSetting::where('user_id', $ownerId)->first();
        $revshare = !empty($revSetting->rental_rev) ? $revSetting->rental_rev : config('legacy.OWNER_PART', 85);
        $diAFee = $revshare * 1;

        $financingCost = ($financing_type === 'P')
            ? ((($vehicleCostInclRecon * $financing / 100) / 365))
            : $financing;

        $totalProgramFee = $downpayment + ($maintenance * $goalLength) + $dispositionfee + ($financingCost * $goalLength);
        $financingBonus = ($vehicleData['financing'] == 1) ? 105 : 0;
        $totalProgramFeeWithDia = $diAFee
            ? sprintf('%0.2f', ($financingBonus + ($totalProgramFee * 100 / $diAFee)))
            : ($totalProgramFee + $financingBonus);

        if ($vehicleData['fare_type'] === 'D') {
            $dailyFee = sprintf('%0.2f', $totalProgramFeeWithDia / $goalLength);
        } else {
            $dailyFee = $vehicleData['day_rent'];
        }

        $return = [
            "day_rent" => $dailyFee,
            "downpayment" => sprintf('%0.2f', ($price * $downpaymentRate / 100)),
            "rent_opt" => [],
            "rent_opt_des" => [
                "0 to " . sprintf("%d", $goalLength) . " days $" . $dailyFee . " per day",
                "20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime"
            ]
        ];

        if (!empty($vehicleData['id'])) {
            Vehicle::where('id', $vehicleData['id'])->update([
                "day_rent" => $return['day_rent'],
                "rent_opt" => ""
            ]);
        }

        return $return;
    }

    public static function getDynamicFare($vehicleData, $depositRule, $pto = 1): bool
    {
        return false;
    }
}
