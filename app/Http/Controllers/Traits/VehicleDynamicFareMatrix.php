<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Support\Facades\Config;

trait VehicleDynamicFareMatrix
{
    /**
     * @param array $offer
     * @param mixed $renter
     * @return array
     */
    public function _getVehicleDynamicFareMatrix($offer, $renter = null)
    {
        $vehicleid = $offer['vehicle_id'];
        $vehicleData = Vehicle::select("msrp", 'user_id', 'vehicleCostInclRecon', 'allowed_miles', 'premium_msrp', 'homenet_msrp', 'fare_type')
            ->find($vehicleid);

        if (!$vehicleData) {
            return [];
        }

        $depositRuleObj = DepositRule::where('vehicle_id', $vehicleid)->first();
        $price = !empty($offer['totalcost']) ? $offer['totalcost'] : $vehicleData->msrp;
        $homenet_msrp = $vehicleData->homenet_msrp ?: 10000;
        $ownerid = $vehicleData->user_id;

        $downpaymentRate = $offer['goal'];
        if ($depositRuleObj && $depositRuleObj->write_down_allocation > 0) {
            $downpaymentRate = $depositRuleObj->write_down_allocation;
        }

        $isPto = !empty($offer['pto']) ? 1 : 0;
        $program_length = $depositRuleObj ? $depositRuleObj->program_length : 0;
        $initial_fee = $offer['total_initial_fee'];
        $deposit = $offer['total_deposit_amt'];
        $emf = $dia_insu = $allowedMiles = 0;
        $miles_options = [];
        
        $allowedMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;
        $miles = $offer['miles'];

        if ($depositRuleObj) {
            $emf = $depositRuleObj->emf ?: 0;
            $dia_insu = $depositRuleObj->emf_insu ?: 0;
            $insurance = $depositRuleObj->insurance_fee;
        } else {
            $insurance = 0;
        }

        $goalLength = !empty($offer['target_days']) ? $offer['target_days'] : $program_length;
        $maintenance = $depositRuleObj ? $depositRuleObj->monthly_maintenance : 0;
        $financing = $depositRuleObj ? $depositRuleObj->financing : 0;
        $financing_type = $depositRuleObj ? $depositRuleObj->financing_type : '';
        $dispositionfee = $depositRuleObj ? $depositRuleObj->disposition_fee : 0;
        $capitalize_starting_fee = $depositRuleObj ? (bool)$depositRuleObj->capitalize_starting_fee : false;

        $free2MoveData = $depositRuleObj ? json_decode($depositRuleObj->free_two_move, true) : null;
        if ($vehicleData->fare_type == 'L' && $free2MoveData) {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * $free2MoveData['residual_value'])));
        } else {
            $totalWriteDownPayment = !empty($offer['downpayment']) ? $offer['downpayment'] : sprintf('%0.4f', (($price * $downpaymentRate / 100) * $program_length / 365));
        }

        $revSetting = RevSetting::where('user_id', $ownerid)->first();
        $revshare = !empty($revSetting->rental_rev) ? $revSetting->rental_rev : config('app.owner_part', 15);
        $diAFee = $revshare * 1;
        $diaRate = $revSetting ? $revSetting->dia_fee * 1 : 0;

        $toalMaintenance = ($maintenance * 12 * ($goalLength / 365));

        if ($vehicleData->fare_type == 'L' && $free2MoveData) {
            $leasePaymentsToLessor = sprintf('%0.4f', ($free2MoveData['monthly_price'] * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            // Recalculate financing rate for lease
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleData->vehicleCostInclRecon - $depositRuleObj->incentive + $depositRuleObj->doc_fee)));
        } else {
            if ($capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = ((($price - $initial_fee) * $financing / 100) / 365) * $goalLength;
            } elseif (!$capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = (($price * $financing / 100) / 365) * $goalLength;
            } else {
                $totalFinancing = ($financing / 365) * $goalLength;
            }
        }

        $totalProgramFee = $totalWriteDownPayment + $toalMaintenance + $dispositionfee + $totalFinancing;
        $totalProgramFeeWithDia = $diAFee ? sprintf('%0.4f', ($totalProgramFee * 100 / $diAFee)) : $totalProgramFee;

        $finance_allocation = (100 * $totalFinancing) / $totalProgramFeeWithDia;
        $maintenance_allocation = (100 * $toalMaintenance) / $totalProgramFeeWithDia;

        $insurance_payer = false;
        if ($depositRuleObj && in_array($depositRuleObj->insurance_payer, [1, 2])) {
            $insurance_payer = true;
        }

        if ($depositRuleObj && in_array($depositRuleObj->insurance_payer, [3, 4])) {
            $insurance_payer = false;
            $insurance = $dia_insu = 0;
        }

        $return = [];
        $insu = ($insurance + (($miles - $allowedMiles) * 12 / 365) * $dia_insu);
        $return["emf"] = sprintf('%0.2f', ((($miles - $allowedMiles) * 12 / 365) * $emf));
        $return["dayInsurance"] = sprintf('%0.2f', $insu);

        if ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L') {
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
            $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / ($goalLength));
        } else {
            $dailyFee = $offer['day_rent'];
            $equityShare = 100;
        }

        $newDailyFee = sprintf('%0.2f', ($dailyFee + $return['emf']));
        $newGoallenghth = $return['emf'] > 0 ? floor((($totalProgramFeeWithDia - $initial_fee) / $newDailyFee)) : $goalLength;
        
        $return["days"] = $newGoallenghth;
        $return["dayRent"] = sprintf('%0.2f', ($dailyFee + ($insurance_payer ? $insu : 0)));
        $return["dayEmfRent"] = sprintf('%0.2f', ($return["dayRent"] + $return["emf"]));
        $return['total_insurance'] = $insurance_payer ? sprintf('%0.2f', ($insu * $newGoallenghth)) : 0;
        $return['total_program_cost'] = sprintf('%0.4f', ($totalProgramFeeWithDia + $return['total_insurance']));
        $return["program_fee"] = sprintf('%0.4f', ($return['total_program_cost'] - $totalWriteDownPayment));
        $return["month_miles"] = $miles;
        $return["month_emf"] = sprintf('%0.2f', ((($miles - $allowedMiles)) * $emf));
        $return["weekRent"] = sprintf('%0.2f', ($return["dayRent"] * 7));
        $return["weekkEmfRent"] = sprintf('%0.2f', (($return["dayRent"] + $return["emf"]) * 7));

        $return['downpayment'] = $totalWriteDownPayment;
        $return['initial_fee'] = $initial_fee;
        $return['initial_fee_tax'] = sprintf('%0.4f', ($initial_fee * ($depositRuleObj ? $depositRuleObj->tax : 0) / 100));
        $return['miles_options'] = $miles_options;
        $return['dia_insu'] = $dia_insu;
        $return['tax_rate'] = ($depositRuleObj ? $depositRuleObj->tax : 0) / 100;
        $return['dia_rate'] = $diaRate;
        $return['deposit'] = $deposit;
        $return['equityshare'] = $equityShare;
        $return['write_down_allocation'] = sprintf('%0.4f', (100 * $totalWriteDownPayment / $totalProgramFeeWithDia));
        $return['finance_allocation'] = $finance_allocation;
        $return['maintenance_allocation'] = $maintenance_allocation;
        $return['financing_total'] = $totalFinancing;
        $return['disposition_fee'] = $dispositionfee;
        $return['total_program_fee_without_dia'] = $totalProgramFee;
        $return['total_program_fee_with_dia'] = $totalProgramFeeWithDia;
        $return['maintenance_per_month'] = $maintenance;
        $return['fixed_program_cost'] = 0;
        $return['finance_per_year'] = ($financing_type == 'P' ? sprintf('%0.4f', (($vehicleData->vehicleCostInclRecon * $financing / 100) / 365)) : $financing);
        $return['total_maintenance_allocation'] = $toalMaintenance;

        return $return;
    }

    public function _vehicleReservationVehicleDynamicFareMatrix($offer)
    {
        $offer['deposit_amt'] = !empty($offer['deposit_amt']) ? $offer['deposit_amt'] : 0;
        $total_deposit_amt_sum = collect($offer['deposit_opt'] ?: [])->sum('amount');
        $offer['total_deposit_amt'] = $offer['deposit_amt'] + $total_deposit_amt_sum;
        $offer['deposit_opt'] = $total_deposit_amt_sum ? json_encode(array_values($offer['deposit_opt'])) : "";

        $offer['initial_fee'] = !empty($offer['initial_fee']) ? $offer['initial_fee'] : 0;
        $total_initial_fee_sum = collect($offer['initial_fee_opt'] ?: [])->sum('amount');
        $offer['total_initial_fee'] = $offer['initial_fee'] + $total_initial_fee_sum;
        $offer['initial_fee_opt'] = $total_initial_fee_sum ? json_encode(array_values($offer['initial_fee_opt'])) : "";

        $orderDepositRule = OrderDepositRule::find($offer['id']);
        if (!$orderDepositRule) return [];

        $vehicleReservation = VehicleReservation::find($orderDepositRule->vehicle_reservation_id);
        if ($vehicleReservation && $vehicleReservation->initial_discount > 0 && @$offer['clear_promo'] == 1) {
            $offer['total_initial_fee'] += $vehicleReservation->initial_discount;
        }

        $vehicleid = $vehicleReservation ? $vehicleReservation->vehicle_id : null;
        $vehicleData = Vehicle::select("msrp", 'user_id', 'vehicleCostInclRecon', 'allowed_miles', 'premium_msrp', 'homenet_msrp', 'fare_type')
            ->find($vehicleid);

        if (!$vehicleData) return [];

        $depositRuleObj = DepositRule::where('vehicle_id', $vehicleid)->first();
        $price = !empty($offer['totalcost']) ? $offer['totalcost'] : $vehicleData->msrp;
        $homenet_msrp = $vehicleData->homenet_msrp ?: 10000;
        $ownerid = $vehicleData->user_id;

        $downpaymentRate = $offer['goal'];
        $program_length = $depositRuleObj ? $depositRuleObj->program_length : 0;
        $initial_fee = $offer['total_initial_fee'];
        $miles = $offer['miles'];

        $allowedMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;
        $emf = $orderDepositRule->emf_rate;
        $dia_insu = $orderDepositRule->emf_insu_rate;
        $insurance = $orderDepositRule->insurance;
        $goalLength = !empty($offer['target_days']) ? $offer['target_days'] : $program_length;

        $maintenance = $depositRuleObj ? $depositRuleObj->monthly_maintenance : 0;
        $financing = $depositRuleObj ? $depositRuleObj->financing : 0;
        $financing_type = $depositRuleObj ? $depositRuleObj->financing_type : '';
        $dispositionfee = $depositRuleObj ? $depositRuleObj->disposition_fee : 0;
        $capitalize_starting_fee = $depositRuleObj ? (bool)$depositRuleObj->capitalize_starting_fee : false;

        $free2MoveData = $depositRuleObj ? json_decode($depositRuleObj->free_two_move, true) : null;
        if ($vehicleData->fare_type == 'L' && $free2MoveData) {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * $free2MoveData['residual_value'])));
        } else {
            $totalWriteDownPayment = !empty($offer['downpayment']) ? $offer['downpayment'] : sprintf('%0.4f', (($price * $downpaymentRate / 100) * $program_length / 365));
        }

        $revSetting = RevSetting::where('user_id', $ownerid)->first();
        $revshare = !empty($revSetting->rental_rev) ? $revSetting->rental_rev : config('app.owner_part', 15);
        $diAFee = $revshare * 1;
        $diaRate = $revSetting ? $revSetting->dia_fee * 1 : 0;

        $toalMaintenance = ($maintenance * 12 * ($goalLength / 365));

        if ($vehicleData->fare_type == 'L' && $free2MoveData) {
            $leasePaymentsToLessor = sprintf('%0.4f', ($free2MoveData['monthly_price'] * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleData->vehicleCostInclRecon - $depositRuleObj->incentive + $depositRuleObj->doc_fee)));
        } else {
            if ($capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = ((($price - $initial_fee) * $financing / 100) / 365) * $goalLength;
            } elseif (!$capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = (($price * $financing / 100) / 365) * $goalLength;
            } else {
                $totalFinancing = ($financing / 365) * $goalLength;
            }
        }

        $totalProgramFee = $totalWriteDownPayment + $toalMaintenance + $dispositionfee + $totalFinancing;
        $totalProgramFeeWithDia = $diAFee ? sprintf('%0.4f', ($totalProgramFee * 100 / $diAFee)) : $totalProgramFee;

        $return = [];
        $insu = ($insurance + (($miles - $allowedMiles) * 12 / 365) * $dia_insu);
        $return["emf"] = sprintf('%0.2f', ((($miles - $allowedMiles) * 12 / 365) * $emf));
        $return["dayInsurance"] = sprintf('%0.2f', $insu);

        $dailyFee = ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L')
            ? sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / ($goalLength))
            : $offer['day_rent'];

        $equityShare = ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L')
            ? sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100)
            : 100;

        $newDailyFee = sprintf('%0.2f', ($dailyFee + $return['emf']));
        $newGoallenghth = $return['emf'] > 0 ? floor((($totalProgramFeeWithDia - $initial_fee) / $newDailyFee)) : $goalLength;

        $return["days"] = $newGoallenghth;
        $return["dayRent"] = $dailyFee;
        $return["dayEmfRent"] = sprintf('%0.2f', ($return["dayRent"] + $return["emf"]));
        $return['total_insurance'] = ($orderDepositRule->insurance_payer != 3) ? sprintf('%0.2f', ($insu * $newGoallenghth)) : 0;
        $return['total_program_cost'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return["program_fee"] = sprintf('%0.4f', ($return['total_program_cost'] - $totalWriteDownPayment));
        $return["month_miles"] = $miles;
        $return["month_emf"] = sprintf('%0.2f', ((($miles - $allowedMiles)) * $emf));
        $return["weekRent"] = sprintf('%0.2f', ($return["dayRent"] * 7));
        $return["weekkEmfRent"] = sprintf('%0.2f', (($return["dayRent"] + $return["emf"]) * 7));

        $return['downpayment'] = $totalWriteDownPayment;
        $return['initial_fee'] = $initial_fee;
        $return['dia_insu'] = $dia_insu;
        $return['tax_rate'] = $depositRuleObj ? $depositRuleObj->tax : 0;
        $return['dia_rate'] = $diaRate;
        $return['equityshare'] = $equityShare;
        $return['write_down_allocation'] = sprintf('%0.4f', (100 * $totalWriteDownPayment / $totalProgramFeeWithDia));
        $return['finance_allocation'] = sprintf('%0.4f', (100 * $totalFinancing) / $totalProgramFeeWithDia);
        $return['maintenance_allocation'] = sprintf('%0.4f', (100 * $toalMaintenance) / $totalProgramFeeWithDia);
        $return['financing_total'] = sprintf('%0.4f', $totalFinancing);
        $return['disposition_fee'] = sprintf('%0.4f', $dispositionfee);
        $return['total_program_fee_without_dia'] = sprintf('%0.4f', $totalProgramFee);
        $return['total_program_fee_with_dia'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return['maintenance_per_month'] = sprintf('%0.4f', $maintenance);
        $return['finance_per_year'] = ($financing_type == 'P' ? sprintf('%0.4f', (($vehicleData->vehicleCostInclRecon * $financing / 100) / 365)) : $financing);
        $return['total_maintenance_allocation'] = $toalMaintenance;

        return $return;
    }

    // _bookingVehicleDynamicFareMatrix is identical to _vehicleReservationVehicleDynamicFareMatrix 
    // but works on CsOrder instead of VehicleReservation.
    public function _bookingVehicleDynamicFareMatrix($offer)
    {
        $offer['id'] = $offer['id']; // OrderDepositRule ID
        return $this->_vehicleReservationVehicleDynamicFareMatrix($offer);
    }
}
