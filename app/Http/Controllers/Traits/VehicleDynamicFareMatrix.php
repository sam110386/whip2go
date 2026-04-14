<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/VehicleDynamicFareMatrix.php
 *
 * Complex fare matrix calculation trait used by booking and reservation flows.
 */
trait VehicleDynamicFareMatrix
{
    public function _getVehicleDynamicFareMatrix($offer, $renter = null)
    {
        $vehicleid = $offer['vehicle_id'];
        $vehicleData = DB::table('vehicles')
            ->where('id', $vehicleid)
            ->select('msrp', 'user_id', 'vehicleCostInclRecon', 'allowed_miles', 'premium_msrp', 'homenet_msrp', 'fare_type')
            ->first();

        $depositRule = DB::table('deposit_rules')->where('vehicle_id', $vehicleid)->first();

        $price = $offer['totalcost'] ? $offer['totalcost'] : $vehicleData->msrp;
        $homenet_msrp = $vehicleData->homenet_msrp ? $vehicleData->homenet_msrp : 10000;
        $ownerid = $vehicleData->user_id;

        $downpaymentRate = $offer['goal'];
        if ($depositRule->write_down_allocation > 0) {
            $downpaymentRate = $depositRule->write_down_allocation;
        }
        $isPto = $offer['pto'] ? 1 : 0;

        $program_length = $depositRule->program_length;
        $initial_fee = $offer['total_initial_fee'];
        $deposit = $offer['total_deposit_amt'];
        $emf = $dia_insu = $allowedMiles = 0;
        $miles_options = [];
        $vehicleCostInclRecon = ($vehicleData->msrp - $initial_fee);
        $allowedMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;

        $miles = $offer['miles'];

        if (!empty($depositRule)) {
            $emf = $depositRule->emf ? $depositRule->emf : 0;
            $dia_insu = $depositRule->emf_insu ? $depositRule->emf_insu : 0;
            $insurance = $depositRule->insurance_fee;
        }
        $goalLength = !empty($offer['target_days']) ? $offer['target_days'] : $program_length;

        $maintenance = $depositRule->monthly_maintenance;
        $financing = $depositRule->financing;
        $financing_type = $depositRule->financing_type;
        $dispositionfee = $depositRule->disposition_fee;
        $capitalize_starting_fee = (bool) $depositRule->capitalize_starting_fee;

        $free2MoveData = json_decode($depositRule->free_two_move, true);
        if ($vehicleData->fare_type == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * $free2MoveData['residual_value'])));
        } else {
            $totalWriteDownPayment = $offer['downpayment'] ? $offer['downpayment'] : sprintf('%0.4f', (($price * $downpaymentRate / 100) * $program_length / 365));
        }

        $RevSetting = DB::table('rev_settings')->where('user_id', $ownerid)->first();
        $revshare = !empty($RevSetting->rental_rev) ? $RevSetting->rental_rev : config('app.owner_part', 100);
        $diAFee = $revshare * 1;
        $diaRate = $RevSetting->dia_fee * 1;

        $toalMaintenance = ($maintenance * 12 * ($goalLength / 365));

        if ($vehicleData->fare_type == 'L') {
            $leasePaymentsToLessor = sprintf('%0.4f', ($free2MoveData['monthly_price'] * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleData->vehicleCostInclRecon - $depositRule->incentive + $depositRule->doc_fee)));
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
        if ($depositRule->insurance_payer == 1 || $depositRule->insurance_payer == 2) {
            $insurance_payer = true;
        }
        if ($depositRule->insurance_payer == 3 || $depositRule->insurance_payer == 4) {
            $insurance_payer = false;
            $insurance = $dia_insu = 0;
        }

        $return = [];
        $insu = ($insurance + (($miles - $allowedMiles) * 12 / 365) * $dia_insu);
        $return["emf"] = sprintf('%0.2f', ((($miles - $allowedMiles) * 12 / 365) * $emf));
        $return["dayInsurance"] = sprintf('%0.2f', $insu);
        if ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L') {
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
        }
        if ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L') {
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
        $return['initial_fee_tax'] = sprintf('%0.4f', ($initial_fee * $depositRule->tax / 100));
        $return['miles_options'] = $miles_options;
        $return['dia_insu'] = $dia_insu;
        $return['tax_rate'] = $depositRule->tax / 100;
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
        $return['finance_per_year'] = ($financing_type == 'P' ? ((($vehicleCostInclRecon * $financing / 100) / 365)) : $financing);
        $return['total_maintenance_allocation'] = $toalMaintenance;
        return $return;
    }

    public function _vehicleReservationVehicleDynamicFareMatrix($offer)
    {
        $offer['deposit_amt'] = !empty($offer['deposit_amt']) ? $offer['deposit_amt'] : 0;
        $total_deposit_amt = $offer['deposit_amt'];
        $total_deposit_amt_sum = collect($offer['deposit_opt'])->sum('amount');
        $offer['total_deposit_amt'] = $total_deposit_amt + $total_deposit_amt_sum;
        $offer['deposit_opt'] = $total_deposit_amt_sum ? json_encode(array_values($offer['deposit_opt'])) : "";
        $offer['initial_fee'] = !empty($offer['initial_fee']) ? $offer['initial_fee'] : 0;
        $total_initial_fee = $offer['initial_fee'];
        $total_initial_fee_sum = collect($offer['initial_fee_opt'])->sum('amount');
        $offer['total_initial_fee'] = $total_initial_fee + $total_initial_fee_sum;
        $offer['initial_fee_opt'] = $total_initial_fee_sum ? json_encode(array_values($offer['initial_fee_opt'])) : "";

        $OrderDepositRule = DB::table('order_deposit_rules')->where('id', $offer['id'])->first();

        $VehicleReservation = DB::table('vehicle_reservations')
            ->where('id', $OrderDepositRule->vehicle_reservation_id)
            ->select('renter_id', 'vehicle_id', 'initial_discount')
            ->first();

        if ($VehicleReservation->initial_discount > 0 && $offer['clear_promo'] == 1) {
            $offer['total_initial_fee'] = $total_initial_fee + $total_initial_fee_sum + $VehicleReservation->initial_discount;
        }
        $vehicleid = $VehicleReservation->vehicle_id;
        $vehicleData = DB::table('vehicles')
            ->where('id', $VehicleReservation->vehicle_id)
            ->select('msrp', 'user_id', 'vehicleCostInclRecon', 'allowed_miles', 'premium_msrp', 'homenet_msrp', 'fare_type')
            ->first();

        $depositRule = DB::table('deposit_rules')->where('vehicle_id', $vehicleid)->first();

        $price = $offer['totalcost'] ? $offer['totalcost'] : $vehicleData->msrp;
        $homenet_msrp = $vehicleData->homenet_msrp ? $vehicleData->homenet_msrp : 10000;
        $ownerid = $vehicleData->user_id;
        $vehicleCostInclRecon = ($vehicleData->msrp - $offer['total_initial_fee']);

        $downpaymentRate = $offer['goal'];
        $program_length = $depositRule->program_length;

        $initial_fee = $offer['total_initial_fee'];
        $deposit = $offer['total_deposit_amt'];
        $emf = $dia_insu = $allowedMiles = $age = 0;
        $miles_options = [];

        $allowedMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;

        $miles = $offer['miles'];
        $emf = $OrderDepositRule->emf_rate;
        $dia_insu = $OrderDepositRule->emf_insu_rate;
        $insurance = $OrderDepositRule->insurance;
        $goalLength = !empty($offer['target_days']) ? $offer['target_days'] : $program_length;
        $maintenance = $depositRule->monthly_maintenance;
        $financing = $depositRule->financing;
        $financing_type = $depositRule->financing_type;
        $dispositionfee = $depositRule->disposition_fee;
        $capitalize_starting_fee = (bool) $depositRule->capitalize_starting_fee;

        $free2MoveData = json_decode($depositRule->free_two_move, true);
        if ($vehicleData->fare_type == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * $free2MoveData['residual_value'])));
        } else {
            $totalWriteDownPayment = $offer['downpayment'] ? $offer['downpayment'] : sprintf('%0.4f', (($price * $downpaymentRate / 100) * $program_length / 365));
        }

        $RevSetting = DB::table('rev_settings')->where('user_id', $ownerid)->first();
        $revshare = !empty($RevSetting->rental_rev) ? $RevSetting->rental_rev : config('app.owner_part', 100);
        $diAFee = $revshare * 1;
        $diaRate = $RevSetting->dia_fee * 1;
        $toalMaintenance = ($maintenance * 12 * ($goalLength / 365));

        if ($vehicleData->fare_type == 'L') {
            $leasePaymentsToLessor = sprintf('%0.4f', ($free2MoveData['monthly_price'] * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleData->vehicleCostInclRecon - $depositRule->incentive + $depositRule->doc_fee)));
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
        if ($OrderDepositRule->insurance_payer != 3) {
            $insurance_payer = true;
        }
        $return = [];
        $insu = ($insurance + (($miles - $allowedMiles) * 12 / 365) * $dia_insu);

        $return["emf"] = sprintf('%0.2f', ((($miles - $allowedMiles) * 12 / 365) * $emf));
        $return["dayInsurance"] = sprintf('%0.2f', $insu);

        if ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L') {
            $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / ($goalLength));
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
        } else {
            $dailyFee = $offer['day_rent'];
            $equityShare = 100;
        }

        $newDailyFee = sprintf('%0.2f', ($dailyFee + $return['emf']));
        $newGoallenghth = $return['emf'] > 0 ? floor((($totalProgramFeeWithDia - $initial_fee) / $newDailyFee)) : $goalLength;
        $return["days"] = $newGoallenghth;
        $return["dayRent"] = $dailyFee;
        $return["dayEmfRent"] = sprintf('%0.2f', ($return["dayRent"] + $return["emf"]));
        $return['total_insurance'] = $insurance_payer ? sprintf('%0.2f', ($insu * $newGoallenghth)) : 0;
        $return['total_program_cost'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return["program_fee"] = sprintf('%0.4f', ($return['total_program_cost'] - $totalWriteDownPayment));
        $return["month_miles"] = $miles;
        $return["month_emf"] = sprintf('%0.2f', ((($miles - $allowedMiles)) * $emf));
        $return["weekRent"] = sprintf('%0.2f', ($return["dayRent"] * 7));
        $return["weekkEmfRent"] = sprintf('%0.2f', (($return["dayRent"] + $return["emf"]) * 7));
        $return['downpayment'] = $totalWriteDownPayment;
        $return['initial_fee'] = $initial_fee;
        $return['miles_options'] = $miles_options;
        $return['dia_insu'] = $dia_insu;
        $return['tax_rate'] = $depositRule->tax;
        $return['dia_rate'] = $diaRate;
        $return['deposit'] = $deposit;
        $return['equityshare'] = $equityShare;
        $return['write_down_allocation'] = sprintf('%0.4f', (100 * $totalWriteDownPayment / $totalProgramFeeWithDia));
        $return['finance_allocation'] = sprintf('%0.4f', $finance_allocation);
        $return['maintenance_allocation'] = sprintf('%0.4f', $maintenance_allocation);
        $return['financing_total'] = sprintf('%0.4f', $totalFinancing);
        $return['disposition_fee'] = sprintf('%0.4f', $dispositionfee);
        $return['total_program_fee_without_dia'] = sprintf('%0.4f', $totalProgramFee);
        $return['total_program_fee_with_dia'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return['maintenance_per_month'] = sprintf('%0.4f', $maintenance);
        $return['fixed_program_cost'] = 0;
        $return['finance_per_year'] = ($financing_type == 'P' ? sprintf('%0.4f', (($vehicleCostInclRecon * $financing / 100) / 365)) : $financing);
        $return['total_initial_fee'] = $initial_fee;
        $return['total_maintenance_allocation'] = $toalMaintenance;
        return $return;
    }

    public function _bookingVehicleDynamicFareMatrix($offer)
    {
        $offer['deposit_amt'] = !empty($offer['deposit_amt']) ? $offer['deposit_amt'] : 0;
        $total_deposit_amt = $offer['deposit_amt'];
        $total_deposit_amt_sum = collect($offer['deposit_opt'])->sum('amount');
        $offer['total_deposit_amt'] = $total_deposit_amt + $total_deposit_amt_sum;
        $offer['deposit_opt'] = $total_deposit_amt_sum ? json_encode(array_values($offer['deposit_opt'])) : "";
        $offer['initial_fee'] = !empty($offer['initial_fee']) ? $offer['initial_fee'] : 0;
        $total_initial_fee = $offer['initial_fee'];
        $total_initial_fee_sum = collect($offer['initial_fee_opt'])->sum('amount');
        $offer['total_initial_fee'] = $total_initial_fee + $total_initial_fee_sum;
        $offer['initial_fee_opt'] = $total_initial_fee_sum ? json_encode(array_values($offer['initial_fee_opt'])) : "";

        $OrderDepositRule = DB::table('order_deposit_rules')->where('id', $offer['id'])->first();

        $CsOrder = DB::table('cs_orders')
            ->where('id', $OrderDepositRule->cs_order_id)
            ->select('renter_id', 'vehicle_id')
            ->first();

        $vehicleid = $CsOrder->vehicle_id;
        $vehicleData = DB::table('vehicles')
            ->where('id', $CsOrder->vehicle_id)
            ->select('msrp', 'user_id', 'vehicleCostInclRecon', 'allowed_miles', 'premium_msrp', 'homenet_msrp', 'fare_type')
            ->first();

        $depositRule = DB::table('deposit_rules')->where('vehicle_id', $vehicleid)->first();

        $price = $offer['totalcost'] ? $offer['totalcost'] : $vehicleData->msrp;
        $homenet_msrp = $vehicleData->homenet_msrp ? $vehicleData->homenet_msrp : 10000;
        $ownerid = $vehicleData->user_id;
        $vehicleCostInclRecon = ($vehicleData->msrp - $offer['total_initial_fee']);

        $downpaymentRate = $offer['goal'];
        $program_length = $depositRule->program_length;
        $initial_fee = $offer['total_initial_fee'];
        $deposit = $offer['total_deposit_amt'];
        $emf = $dia_insu = $allowedMiles = $age = 0;
        $miles_options = [];

        $allowedMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;

        $miles = $offer['miles'];
        $emf = $OrderDepositRule->emf_rate;
        $dia_insu = $OrderDepositRule->emf_insu_rate;
        $insurance = $OrderDepositRule->insurance;

        $goalLength = !empty($offer['target_days']) ? $offer['target_days'] : $program_length;

        $maintenance = $depositRule->monthly_maintenance;
        $financing = $depositRule->financing;
        $financing_type = $depositRule->financing_type;
        $dispositionfee = $depositRule->disposition_fee;
        $capitalize_starting_fee = (bool) $depositRule->capitalize_starting_fee;

        $free2MoveData = json_decode($depositRule->free_two_move, true);
        if ($vehicleData->fare_type == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * $free2MoveData['residual_value'])));
        } else {
            $totalWriteDownPayment = $offer['downpayment'] ? $offer['downpayment'] : sprintf('%0.4f', (($price * $downpaymentRate / 100) * $program_length / 365));
        }

        $RevSetting = DB::table('rev_settings')->where('user_id', $ownerid)->first();
        $revshare = !empty($RevSetting->rental_rev) ? $RevSetting->rental_rev : config('app.owner_part', 100);
        $diAFee = $revshare * 1;
        $diaRate = $RevSetting->dia_fee * 1;

        $toalMaintenance = ($maintenance * 12 * ($goalLength / 365));

        if ($vehicleData->fare_type == 'L') {
            $leasePaymentsToLessor = sprintf('%0.4f', ($free2MoveData['monthly_price'] * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleData->vehicleCostInclRecon - $depositRule->incentive + $depositRule->doc_fee)));
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
        if ($OrderDepositRule->insurance_payer != 3) {
            $insurance_payer = true;
        }

        $return = [];
        $insu = ($insurance + (($miles - $allowedMiles) * 12 / 365) * $dia_insu);

        $return["emf"] = sprintf('%0.2f', ((($miles - $allowedMiles) * 12 / 365) * $emf));
        $return["dayInsurance"] = sprintf('%0.2f', $insu);
        if ($offer['fare_type'] == 'D' || $offer['fare_type'] == 'L') {
            $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / $goalLength);
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
        } else {
            $dailyFee = $offer['day_rent'];
            $equityShare = 100;
        }

        $newDailyFee = sprintf('%0.2f', ($dailyFee + $return['emf']));
        $newGoallenghth = $return['emf'] > 0 ? floor((($totalProgramFeeWithDia - $initial_fee) / $newDailyFee)) : $goalLength;
        $return["days"] = $newGoallenghth;
        $return["dayRent"] = $dailyFee;
        $return["dayEmfRent"] = sprintf('%0.2f', ($return["dayRent"] + $return["emf"]));
        $return['total_insurance'] = $insurance_payer ? sprintf('%0.2f', ($insu * $newGoallenghth)) : 0;
        $return['total_program_cost'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return["program_fee"] = sprintf('%0.4f', ($return['total_program_cost'] - $totalWriteDownPayment));
        $return["month_miles"] = $miles;
        $return["month_emf"] = sprintf('%0.2f', ((($miles - $allowedMiles)) * $emf));
        $return["weekRent"] = sprintf('%0.2f', ($return["dayRent"] * 7));
        $return["weekkEmfRent"] = sprintf('%0.2f', (($return["dayRent"] + $return["emf"]) * 7));
        $return['downpayment'] = $totalWriteDownPayment;
        $return['initial_fee'] = $initial_fee;
        $return['miles_options'] = $miles_options;
        $return['dia_insu'] = $dia_insu;
        $return['tax_rate'] = $depositRule->tax;
        $return['dia_rate'] = $diaRate;
        $return['deposit'] = $deposit;
        $return['equityshare'] = $equityShare;
        $return['write_down_allocation'] = sprintf('%0.4f', (100 * $totalWriteDownPayment / $totalProgramFeeWithDia));
        $return['finance_allocation'] = sprintf('%0.4f', $finance_allocation);
        $return['maintenance_allocation'] = sprintf('%0.4f', $maintenance_allocation);
        $return['financing_total'] = sprintf('%0.4f', $totalFinancing);
        $return['disposition_fee'] = sprintf('%0.4f', $dispositionfee);
        $return['total_program_fee_without_dia'] = sprintf('%0.4f', $totalProgramFee);
        $return['total_program_fee_with_dia'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return['maintenance_per_month'] = sprintf('%0.4f', $maintenance);
        $return['fixed_program_cost'] = 0;
        $return['finance_per_year'] = ($financing_type == 'P' ? sprintf('%0.4f', (($vehicleCostInclRecon * $financing / 100) / 365)) : $financing);
        $return['total_initial_fee'] = $initial_fee;
        $return['total_maintenance_allocation'] = $toalMaintenance;
        return $return;
    }
}
