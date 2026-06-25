<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\CsTrackVehicle;
use App\Models\Legacy\Passtime;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ActiveBookingTotalPendingTrait
{
    /**
     * Ported from CakePHP ActiveBookingTotalPending trait.
     * Calculates future fees for scheduled extensions/renewals.
     */
    private function getNextScheduleFee($OrderDepositRuleObj, $CsOrder, $autorenew = false)
    {
        $return = [
            "damage_fee" => $CsOrder['damage_fee'] ?? 0,
            "uncleanness_fee" => $CsOrder['uncleanness_fee'] ?? 0,
            "initial_fee" => $CsOrder['initial_fee'] ?? 0
        ];

        if ($autorenew) {
            $timeDiff = (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_datetime']));
        } else {
            $timeDiff = (time() - strtotime($CsOrder['start_datetime']));
        }

        $totalHours = abs($timeDiff / 3600);
        if ($totalHours > 6) {
            $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $insurance_hours = $insurance_hours > 6 ? $insurance_hours : 0;
            $totldays = $insurance_days + ($insurance_hours > 0 ? 1 : 0);
        } else {
            $totldays = $insurance_hours = $insurance_days = 0;
        }

        if (!empty($OrderDepositRuleObj['OrderDepositRule'])) {
            $return['insurance_amt'] = number_format(($totldays * ($OrderDepositRuleObj['OrderDepositRule']['insurance'] ?? 0)), 2, '.', '');
        } else {
            $return['insurance_amt'] = 0;
        }

        $depositRuleModel = new DepositRule();
        $day_rent = 0;
        if (!empty($OrderDepositRuleObj) && method_exists($depositRuleModel, 'getDayRentFromTierData')) {
            $day_rent = $depositRuleModel->getDayRentFromTierData(
                $OrderDepositRuleObj['OrderDepositRule']['rental_opt'] ?? 0, 
                $insurance_days, 
                $OrderDepositRuleObj['OrderDepositRule']['rental'] ?? 0, 
                $CsOrder['start_datetime']
            );
        } else {
            $day_rent = $OrderDepositRuleObj['OrderDepositRule']['rental'] ?? 0;
        }

        $time_feeDays = ($insurance_days * $day_rent);
        $time_feehours = $dia_insu = 0;
        if ($insurance_hours) {
            $time_feehours = $day_rent;
        }
        $time_fee = number_format(($time_feeDays + $time_feehours), 2, '.', '');

        $taxRate = !empty($OrderDepositRuleObj) ? ($OrderDepositRuleObj['OrderDepositRule']['tax'] ?? 0) : 0;
        $return['rent'] = number_format($time_fee, 2, '.', '');
        
        $rent_discount = 0;
        if (class_exists('App\Services\Legacy\PromoService')) {
            $promo = app('App\Services\Legacy\PromoService');
            if (method_exists($promo, 'useRentalPromoCode')) {
                $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $CsOrder['renter_id'] ?? 0);
                $rent_discount = $discounts['rent_discount'] ?? 0;
            }
        }
        
        $return['rent'] = ($time_fee - $rent_discount);
        $return['discount'] = $rent_discount;

        $return['extra_mileage_fee'] = 0;
        $allowed_miles = $OrderDepositRuleObj['OrderDepositRule']['miles'] ?? 0;
        
        $scheduledEndodometer = 0;
        if ($autorenew && strtotime($CsOrder['end_datetime']) < time()) {
            $csTrackVehicle = new CsTrackVehicle();
            if (method_exists($csTrackVehicle, 'getLastMileFromHistory')) {
                $scheduledEndodometer = $csTrackVehicle->getLastMileFromHistory($CsOrder['vehicle_id'], $CsOrder['end_datetime']);
            }
        }
        if (isset($CsOrder['last_mile']) && $CsOrder['last_mile'] > 0) {
            $scheduledEndodometer = $CsOrder['last_mile'];
        }
        if ($scheduledEndodometer == 0) {
            $passtime = new Passtime();
            if (method_exists($passtime, 'getPasstimeMiles')) {
                $milesData = $passtime->getPasstimeMiles($CsOrder['vehicle_id']);
                $scheduledEndodometer = $milesData['miles'] ?? 0;
            }
        }

        $return['end_odometer'] = $scheduledEndodometer;
        if ($scheduledEndodometer > 0 && $allowed_miles > 0) {
            $multiplier = isset($CsOrder['durdays']) ? $CsOrder['durdays'] : $totldays;
            $billableMileage = (int)(($return['end_odometer'] - ($CsOrder['start_odometer'] ?? 0)) - ($allowed_miles * $multiplier));
            $return['billable_mileage'] = $billableMileage;
            if ($billableMileage > 0) {
                $depositTemplate = DepositTemplate::where('user_id', $CsOrder['user_id'])->first(['max_extramile_fee']);
                $maxExtraMileFee = ($depositTemplate && $depositTemplate->max_extramile_fee) ? $depositTemplate->max_extramile_fee : 500;
                
                $extra_mileage_feeTotal = sprintf('%0.2f', ($billableMileage * ($OrderDepositRuleObj['OrderDepositRule']['emf_rate'] ?? 0)));
                $extra_mileage_feeTotal += $return['extra_mileage_fee'];
                $return['extra_mileage_fee'] = min($maxExtraMileFee, (float)$extra_mileage_feeTotal);
                
                $dia_insu = sprintf('%0.2f', ($billableMileage * ($OrderDepositRuleObj['OrderDepositRule']['emf_insu_rate'] ?? 0)));
                $dia_insu = min($maxExtraMileFee, (float)$dia_insu);
            }
        }
        
        $dia_fee = method_exists($depositRuleModel, 'calculateDIAFee') ? $depositRuleModel->calculateDIAFee($return['rent'], $CsOrder['user_id']) : 0;
        $tax = (($return['rent'] + $dia_fee) * $taxRate) / 100;
        $return['tax'] = number_format($tax, 2, '.', '');
        $return['emf_tax'] = number_format((($return['extra_mileage_fee'] * $taxRate) / 100), 2, '.', '');
        $return['dia_fee'] = $dia_fee;
        $return['dia_insu'] = $dia_insu;
        return $return;
    }

    private function getActiveBookingTotalPending($Lease, $CsOrder = null)
    {
        $depositRuleModel = new DepositRule();
        $orderId = !empty($Lease['CsOrder']['parent_id']) ? $Lease['CsOrder']['parent_id'] : $Lease['CsOrder']['id'];
        $dbRule = OrderDepositRule::where('cs_order_id', $orderId)->first();
        if (!$dbRule) return $Lease;
        
        $OrderDepositRuleObj = ['OrderDepositRule' => $dbRule->toArray()];
        $extramilefee = $OrderDepositRuleObj['OrderDepositRule']['emf_rate'] ?? 0;
        $diainsufee = $OrderDepositRuleObj['OrderDepositRule']['emf_insu_rate'] ?? 0;

        if (($OrderDepositRuleObj['OrderDepositRule']['insurance_payer'] ?? 0) == 7) {
            $OrderDepositRuleObj['OrderDepositRule']['insurance'] = 0;
        }

        $endtime = $Lease['CsOrder']['end_datetime'];
        $starttime = $Lease['CsOrder']['start_datetime'];
        $daysGap = (strtotime($endtime) - strtotime($starttime)) / 3600;
        $durationdays = $daysGap < 24 ? 1 : floor($daysGap / 24);
        if (($daysGap % 24) > 6) $durationdays++;

        $scheduledEndodometer = 0;
        if (strtotime($Lease['CsOrder']['end_datetime']) < time()) {
            $csTrackVehicle = new CsTrackVehicle();
            if (method_exists($csTrackVehicle, 'getLastMileFromHistory')) {
                $scheduledEndodometer = $csTrackVehicle->getLastMileFromHistory($Lease['CsOrder']['vehicle_id'], $Lease['CsOrder']['end_datetime']);
            }
        }

        $paidInsurancePayments = $paidRentalPayments = 0;
        $paidPayments = CsOrderPayment::where('cs_order_id', $Lease['CsOrder']['id'])->whereIn('type', [3, 2, 16, 19, 4, 14])->where('status', 1)->get();
        foreach ($paidPayments as $p) {
            if ($p->type == 4 || $p->type == 14) $paidInsurancePayments += $p->amount;
            if (in_array($p->type, [2, 16, 19])) $paidRentalPayments += $p->amount;
        }

        $start_mileage = $Lease['CsOrder']['start_odometer'] ?? 0;
        $end_mileage = $scheduledEndodometer ?: ($Lease['Vehicle']['last_mile'] ?? 0);
        $total_mileage = max(0, $end_mileage - $start_mileage);
        $mileage_checked = Carbon::parse($Lease['Vehicle']['modified'] ?? now())->timezone($Lease['CsOrder']['timezone'] ?? 'UTC')->format('Y-m-d H:i:s');

        $currency = $Lease['CsOrder']['currency'] ?? 'USD';
        $distanceUnit = $Lease['Owner']['distance_unit'] ?? 'mi';
        $billabemiles = (int)($total_mileage - ($durationdays * ($OrderDepositRuleObj['OrderDepositRule']['miles'] ?? 0)));
        $current_emf = ($billabemiles > 0) ? sprintf('%0.2f', ($extramilefee * $billabemiles)) : '0.00';
        $emf_tax = sprintf('%0.2f', ($current_emf * ($OrderDepositRuleObj['OrderDepositRule']['tax'] ?? 0) / 100));
        $dia_insu = ($billabemiles > 0) ? sprintf('%0.2f', ($diainsufee * $billabemiles)) : 0;
        $dia_insu = max($dia_insu, (float)($Lease['CsOrder']['dia_insu'] ?? 0));

        $totalCalculatedAmount = sprintf('%0.2f', (($Lease['CsOrder']['rent'] ?? 0) + ($Lease['CsOrder']['tax'] ?? 0) + $current_emf + $emf_tax + ($Lease['CsOrder']['damage_fee'] ?? 0) + ($Lease['CsOrder']['lateness_fee'] ?? 0) + ($Lease['CsOrder']['uncleanness_fee'] ?? 0) + ($Lease['CsOrder']['dia_fee'] ?? 0) + ($Lease['CsOrder']['pending_toll'] ?? 0)));
        
        $Lease['CsOrder']['carsharing_fee_total'] = $totalCalculatedAmount;
        $Lease['CsOrder']['tax'] = sprintf('%0.2f', (($Lease['CsOrder']['tax'] ?? 0) + $emf_tax));
        $Lease['CsOrder']['carsharing_fee_paid'] = $Lease['CsOrder']['paid_amount'] = $paidRentalPayments;
        $Lease['CsOrder']['total_rental_calculated'] = $totalCalculatedAmount;
        $Lease['CsOrder']['total_rental_remaining'] = max(0, (float)$totalCalculatedAmount - $paidRentalPayments);
        $Lease['CsOrder']['carsharing_fee_pending'] = $paidRentalPayments ? sprintf('%0.2f', (float)$totalCalculatedAmount - $paidRentalPayments) : $totalCalculatedAmount;

        $totalCalculatedInsurance = sprintf('%0.2f', (($Lease['CsOrder']['insurance_amt'] ?? 0) + $dia_insu));
        $Lease['CsOrder']['total_insurance_paid'] = $paidInsurancePayments;
        $Lease['CsOrder']['total_insurance_calculated'] = $totalCalculatedInsurance;
        $Lease['CsOrder']['total_insurance_remaining'] = max(0, (float)$totalCalculatedInsurance - $paidInsurancePayments);

        $total_initial_fee_paid_arr = method_exists(new CsOrderPayment(), 'getTotalInitialFee') ? (new CsOrderPayment())->getTotalInitialFee($Lease['CsOrder']['id']) : ['initial_fee' => 0, 'initial_fee_tax' => 0];
        $Lease['CsOrder']['total_initial_fee_paid'] = $total_initial_fee_paid_arr['initial_fee'] + $total_initial_fee_paid_arr['initial_fee_tax'];
        $Lease['CsOrder']['total_initial_fee_calculated'] = ($Lease['CsOrder']['initial_fee'] ?? 0) + ($Lease['CsOrder']['initial_fee_tax'] ?? 0);
        $Lease['CsOrder']['total_initial_fee_remaining'] = max(0, (float)$Lease['CsOrder']['total_initial_fee_calculated'] - $Lease['CsOrder']['total_initial_fee_paid']);

        $pendingForCurrentCycle = $Lease['CsOrder']['total_initial_fee_remaining'] + $Lease['CsOrder']['total_insurance_remaining'] + $Lease['CsOrder']['total_rental_remaining'];
        $Lease['CsOrder']['total_remaining_autorenew'] = ["hint" => "*Due On " . Carbon::parse($starttime)->format('m/d/Y'), "amount" => "$" . number_format($pendingForCurrentCycle, 2)];

        $discountedRent = 0;
        if (class_exists('App\Services\Legacy\PromoService')) {
            $pService = app('App\Services\Legacy\PromoService');
            $dRent = $pService->useRentalPromoCode(["rent" => ($durationdays * $OrderDepositRuleObj['OrderDepositRule']['rental'])], $Lease['CsOrder']['renter_id']);
            $discountedRent = $dRent['rent_discount'] ?? 0;
        }

        $futurerent = sprintf('%0.2f', (($durationdays * ($OrderDepositRuleObj['OrderDepositRule']['rental'] ?? 0)) - $discountedRent));
        $futurerent_withTax = $futurerent + ($futurerent * ($OrderDepositRuleObj['OrderDepositRule']['tax'] ?? 0) / 100);
        $futurerent_dia_fee = method_exists($depositRuleModel, 'calculateDIAFee') ? $depositRuleModel->calculateDIAFee($futurerent, $Lease['CsOrder']['user_id']) : 0;
        
        $Lease['CsOrder']["total_remaining_nextschedule"] = [
            "hint" => "*Due On " . date('m/d/Y', strtotime($starttime . " +$durationdays days")),
            "amount" => "$" . number_format(($pendingForCurrentCycle + $futurerent_withTax + $futurerent_dia_fee + ($durationdays * ($OrderDepositRuleObj['OrderDepositRule']['insurance'] ?? 0))), 2)
        ];

        $Lease['CsOrder']["total_remaining_close"] = ["hint" => "*Due On " . date('m/d/Y'), "amount" => $Lease['CsOrder']["total_remaining_autorenew"]['amount']];
        
        $totalCalForCycle = ($futurerent_withTax + $futurerent_dia_fee + ($durationdays * ($OrderDepositRuleObj['OrderDepositRule']['insurance'] ?? 0)));
        
        // Multi-cycle logic
        $daysGap1 = (time() - strtotime($Lease['CsOrder']['end_datetime'])) / 3600;
        $durdays1 = $daysGap1 < 6 ? 0 : floor($daysGap1 / 24);
        if (($daysGap1 % 24) > 6) $durdays1++;

        $nextdurtaion = method_exists($dbRule, 'getFromTierData') ? $dbRule->getFromTierData($OrderDepositRuleObj['OrderDepositRule']['duration_opt'], $starttime, $endtime) : $durationdays;
        $nextdurtaion = $nextdurtaion ?: $durationdays;

        if ($daysGap1 > 0) {
            $cycles = $durdays1 < $durationdays ? 1 : floor($durdays1 / $durationdays);
            if ($durdays1 > $durationdays && ($durdays1 % $durationdays) > 0) $cycles++;

            $CsOrderTmp = ['CsOrder' => $Lease['CsOrder']];
            $CsOrderTmp['CsOrder']['start_datetime'] = $Lease['CsOrder']['end_datetime'];
            $CsOrderTmp['CsOrder']['end_datetime'] = date('Y-m-d H:i:s', strtotime($Lease['CsOrder']['end_datetime'] . " +$nextdurtaion days"));
            $CsOrderTmp['CsOrder']['start_odometer'] = $end_mileage;

            $resp1 = $this->getNextScheduleFee($OrderDepositRuleObj, $CsOrderTmp['CsOrder'], true);
            $totalCalForCycle = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt']));
            $totalCalForCycle = sprintf('%0.2f', (($totalCalForCycle * $cycles) + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));

            $Lease['CsOrder']["total_remaining_nextschedule"] = [
                "hint" => "*Due On " . date('m/d/Y', strtotime($starttime . " +$nextdurtaion days")),
                "amount" => "$" . number_format(($pendingForCurrentCycle + $totalCalForCycle), 2)
            ];

            $CsOrderTmp['CsOrder']['start_odometer'] = $start_mileage;
            $CsOrderTmp['CsOrder']['last_mile'] = $resp1['end_odometer'];
            $CsOrderTmp['CsOrder']['end_datetime'] = date('Y-m-d H:i:s');
            $CsOrderTmp['CsOrder']['durdays'] = ($durdays1 + $durationdays);
            $resp1 = $this->getNextScheduleFee($OrderDepositRuleObj, $CsOrderTmp['CsOrder'], true);
            $totalCalForClose = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt'] + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));
            if ($durdays1 == 0) $totalCalForClose = sprintf('%0.2f', ($totalCalForClose - (float)$current_emf));
            
            $Lease['CsOrder']["total_remaining_close"] = ["hint" => "*Due On " . date('m/d/Y'), "amount" => "$" . number_format(($pendingForCurrentCycle + $totalCalForClose), 2)];
        }

        $Lease['CsOrder']['least_advance_payment'] = "$" . number_format(max(0, ($OrderDepositRuleObj['OrderDepositRule']['rental'] + $OrderDepositRuleObj['OrderDepositRule']['insurance']) * 2), 2);
        if ($OrderDepositRuleObj['OrderDepositRule']['minimum_payment'] != 0) {
            $minPayExp = $OrderDepositRuleObj['OrderDepositRule']['minimum_payment_exp_date'];
            if (empty($minPayExp) || date('Y-m-d', strtotime($minPayExp)) >= date('Y-m-d')) {
                $Lease['CsOrder']['least_advance_payment'] = "$" . number_format($OrderDepositRuleObj['OrderDepositRule']['minimum_payment'], 2);
            }
        }

        return $Lease;
    }
}
