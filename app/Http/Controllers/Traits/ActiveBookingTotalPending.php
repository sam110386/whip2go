<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsTrackVehicle;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PromoService;
use App\Helpers\Legacy\Number as LegacyNumber;
use Carbon\Carbon;

trait ActiveBookingTotalPending
{
    private function getNextScheduleFee(OrderDepositRule $orderDepositRule, CsOrder $CsOrder, $autorenew = false)
    {
        $damageFee = $CsOrder->damage_fee ?? 0;
        $uncleannessFee = $CsOrder->uncleanness_fee ?? 0;
        $initialFee = $CsOrder->initial_fee ?? 0;
        $startDatetime = $CsOrder->start_datetime ?? null;
        $endDatetime = $CsOrder->end_datetime ?? null;
        $renterId = $CsOrder->renter_id ?? 0;
        $vehicleId = $CsOrder->vehicle_id ?? 0;
        $userId = $CsOrder->user_id ?? 0;
        $return = [
            "damage_fee" => $damageFee,
            "uncleanness_fee" => $uncleannessFee,
            "initial_fee" => $initialFee
        ];

        if ($autorenew) {
            $timeDiff = (strtotime($endDatetime) - strtotime($startDatetime));
        } else {
            $timeDiff = (time() - strtotime($startDatetime));
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

        if ($orderDepositRule) {
            $ruleInsurance = $orderDepositRule->insurance ?? 0;
            $return['insurance_amt'] = number_format(($totldays * $ruleInsurance), 2, '.', '');
        } else {
            $return['insurance_amt'] = '0.00';
        }

        $depositRuleModel = new DepositRule();
        $day_rent = $orderDepositRule ? $depositRuleModel->getDayRentFromTierData(($orderDepositRule->rental_opt ?? 0), $insurance_days, ($orderDepositRule->rental ?? 0), $startDatetime) : 0;
        $time_feeDays = ($insurance_days * $day_rent);
        $time_feehours = $dia_insu = 0;

        if ($insurance_hours) {
            $time_feehours = $day_rent;
        }

        $time_fee = number_format(($time_feeDays + $time_feehours), 2, '.', '');
        $taxRate = !empty($orderDepositRule) ? ($orderDepositRule->tax ?? 0) : 0;
        $return['rent'] = number_format($time_fee, 2, '.', '');
        $promoService = new PromoService();
        $discounts = $promoService->useRentalPromoCode(["rent" => $time_fee], $renterId);
        $rentDiscount = $discounts['rent_discount'] ?? 0;
        $return['rent'] = ($time_fee - $rentDiscount);
        $return['discount'] = $rentDiscount;
        $return['extra_mileage_fee'] = 0;
        $allowed_miles = $orderDepositRule->miles ?? 0;
        $scheduledEndodometer = 0;

        if ($autorenew && strtotime($endDatetime) < time()) {
            $scheduledEndodometer = CsTrackVehicle::getLastMileFromHistory($vehicleId, $endDatetime);
        }

        $lastMile = $CsOrder->last_mile ?? null;

        if ($lastMile !== null) {
            $scheduledEndodometer = $lastMile;
        }

        if ($scheduledEndodometer == 0 && $vehicleId) {
            $passtimeModel = new Passtime();
            $milesData = $passtimeModel->getPasstimeMiles($vehicleId);
            $scheduledEndodometer = $milesData['miles'] ?? 0;
        }

        $return['end_odometer'] = $scheduledEndodometer;
        $startOdometer = $CsOrder->start_odometer ?? 0;
        $durDays = $CsOrder->durdays ?? null;
        $durationMultiplier = ($durDays !== null) ? $durDays : $totldays;

        if ($scheduledEndodometer > 0 && $allowed_miles > 0) {
            $billableMileage = (int) (($return['end_odometer'] - $startOdometer) - ($allowed_miles * $durationMultiplier));
            $return['billable_mileage'] = $billableMileage;

            if ($billableMileage > 0) {
                $depositTemplate = DepositTemplate::where('user_id', $userId)->first(['max_extramile_fee']);
                $maxExtraMileFee = (!empty($depositTemplate->max_extramile_fee)) ? $depositTemplate->max_extramile_fee : 500;
                $emfRate = $orderDepositRule->emf_rate ?? 0;
                $extra_mileage_feeTotal = sprintf('%0.2f', ($billableMileage * $emfRate));
                $extra_mileage_feeTotal += $return['extra_mileage_fee'];
                $return['extra_mileage_fee'] = $extra_mileage_feeTotal > $maxExtraMileFee ? sprintf('%0.2f', $maxExtraMileFee) : sprintf('%0.2f', $extra_mileage_feeTotal);
                $emfInsuRate = $orderDepositRule->emf_insu_rate ?? 0;
                $dia_insu = sprintf('%0.2f', ($billableMileage * $emfInsuRate));
                $dia_insu = $dia_insu > $maxExtraMileFee ? sprintf('%0.2f', $maxExtraMileFee) : $dia_insu;
            }
        }

        $dia_fee = $depositRuleModel->calculateDIAFee($return['rent'], $userId);
        $tax = (($return['rent'] + $dia_fee) * $taxRate) / 100;
        $return['tax'] = number_format($tax, 2, '.', '');
        $return['emf_tax'] = number_format((($return['extra_mileage_fee'] * $taxRate) / 100), 2, '.', '');
        $return['dia_fee'] = $dia_fee;
        $return['dia_insu'] = $dia_insu;

        return $return;
    }
    private function getActiveBookingTotalPending(CsOrder $leaseCsOrder, CsOrder $csOrder): CsOrder
    {
        $orderId = !empty($leaseCsOrder->parent_id) ? $leaseCsOrder->parent_id : $leaseCsOrder->id;
        $dbRule = OrderDepositRule::where('cs_order_id', $orderId)->first([
            'rental',
            "emf",
            "miles",
            "emf_rate",
            "emf_insu_rate",
            'insurance',
            'tax',
            'rental_opt',
            'duration_opt',
            'insurance_payer',
            'minimum_payment',
            'minimum_payment_exp_date'
        ]);

        $extramilefee = $dbRule->emf_rate ?? 0;
        $diainsufee = $dbRule->emf_insu_rate ?? 0;

        if (($dbRule->insurance_payer ?? 0) == 7) {
            $dbRule->insurance = 0;
        }

        $endtime = $leaseCsOrder->end_datetime;
        $starttime = $leaseCsOrder->start_datetime;
        $daysGap = strtotime($endtime) - strtotime($starttime);
        $daysGap = $daysGap / 3600;
        $durationdays = $daysGap < 24 ? 1 : floor($daysGap / 24);
        $hours = $daysGap < 24 ? 0 : $daysGap % 24;
        $hours = $hours > 6 ? $hours : 0;

        if ($hours) {
            $durationdays++;
        }

        $scheduledEndodometer = 0;
        $vehicleId = $leaseCsOrder->vehicle_id;

        if (strtotime($endtime) < time()) {
            $scheduledEndodometer = CsTrackVehicle::getLastMileFromHistory($vehicleId, $endtime);
        }

        $paidInsurancePayments = $paidRentalPayments = 0;

        $paidPayments = CsOrderPayment::where('cs_order_id', $leaseCsOrder->id)
            ->whereIn('type', [3, 2, 16, 19, 4, 14])
            ->where('status', 1)
            ->get(['id', 'amount', 'type']);

        foreach ($paidPayments as $paidPayment) {
            if ($paidPayment->type == 4 || $paidPayment->type == 14) {
                $paidInsurancePayments += $paidPayment->amount;
            }

            if (in_array($paidPayment->type, [2, 16, 19])) {
                $paidRentalPayments += $paidPayment->amount;
            }
        }

        $startOdometer = (float) ($leaseCsOrder->start_odometer ?? 0);
        $startMileage = $startOdometer > 0 ? $startOdometer : 0;
        $vehicleLastMile = (float) ($leaseCsOrder->vehicle->last_mile ?? 0);
        $vehicleModified = $leaseCsOrder->vehicle->modified ?? now();
        $endMileage = $scheduledEndodometer ? $scheduledEndodometer : ($vehicleLastMile > 0 ? $vehicleLastMile : 0);
        $totalMileage = ((int) $endMileage > 0 && ($endMileage > $startMileage)) ? ($endMileage - $startMileage) : 0;
        $timezone = $leaseCsOrder->timezone ?? config('app.timezone', 'UTC');
        $mileageChecked = Carbon::parse($vehicleModified)->setTimezone($timezone)->format('Y-m-d H:i:s');
        $diainsu = $diainsufee;
        $currency = $leaseCsOrder->currency ?? 'USD';
        $distanceUnit = $leaseCsOrder->owner->distance_unit ?? 'mi';
        $allowedMiles = $dbRule->miles ?? 0;
        $emfUsesFormatted = LegacyNumber::currency(sprintf('%0.2f', ($diainsu + $extramilefee)), $currency);
        $emfUses = $emfUsesFormatted . "/" . $distanceUnit . " above " . ceil($durationdays * $allowedMiles) . " " . $distanceUnit . "s";
        $billabemiles = (int) ($totalMileage - ($durationdays * $allowedMiles));
        $extraMileageFeeNum = ($billabemiles > 0) ? sprintf('%0.2f', ($extramilefee * $billabemiles)) : '0.00';
        $ruleTaxRate = $dbRule->tax ?? 0;
        $emfTaxNum = sprintf('%0.2f', ($extraMileageFeeNum * $ruleTaxRate / 100));
        $dia_insu = ($billabemiles > 0) ? sprintf('%0.2f', ($diainsufee * $billabemiles)) : 0;
        $preCalculatedDiaInsu = $leaseCsOrder->dia_insu ?? 0;
        $dia_insu = $dia_insu > $preCalculatedDiaInsu ? $dia_insu : $preCalculatedDiaInsu;
        $startDtFormatted = date('m/d', strtotime($starttime));
        $endDtFormatted = date('m/d', strtotime($endtime));
        $emfDetailText = sprintf('%s for %s-%s', LegacyNumber::currency(($extraMileageFeeNum + $dia_insu), $currency), $startDtFormatted, $endDtFormatted);
        $emfDetails = $leaseCsOrder->emf_details ?? [];
        $emfDetails[] = [$emfDetailText];
        $rentNum = (float) ($leaseCsOrder->rent ?? 0);
        $taxNum = (float) ($leaseCsOrder->tax ?? 0);
        $damageFeeNum = (float) ($leaseCsOrder->damage_fee ?? 0);
        $latenessFeeNum = (float) ($leaseCsOrder->lateness_fee ?? 0);
        $uncleannessFeeNum = (float) ($leaseCsOrder->uncleanness_fee ?? 0);
        $diaFeeNum = (float) ($leaseCsOrder->dia_fee ?? 0);
        $pendingTollNum = (float) ($leaseCsOrder->pending_toll ?? 0);
        $insuranceAmtNum = (float) ($leaseCsOrder->insurance_amt ?? 0);
        $initialFeeNum = (float) ($leaseCsOrder->initial_fee ?? 0);
        $initialFeeTaxNum = (float) ($leaseCsOrder->initial_fee_tax ?? 0);
        $totalCalculatedAmount = sprintf('%0.2f', ($rentNum + $taxNum + $extraMileageFeeNum + $emfTaxNum + $damageFeeNum + $latenessFeeNum + $uncleannessFeeNum + $diaFeeNum + $pendingTollNum));
        $taxCombined = sprintf('%0.2f', ($taxNum + $emfTaxNum));
        $totalRentalRemaining = ($totalCalculatedAmount > $paidRentalPayments) ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments)) : 0;
        $carsharingFeePending = $paidRentalPayments ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments)) : $totalCalculatedAmount;
        $totalCalculatedInsurance = sprintf('%0.2f', ($insuranceAmtNum + $dia_insu));
        $totalInsuranceRemaining = ($totalCalculatedInsurance > $paidInsurancePayments) ? sprintf('%0.2f', ($totalCalculatedInsurance - $paidInsurancePayments)) : 0;
        $totalInitialFeePaidArr = CsOrderPayment::getTotalInitialFee($leaseCsOrder->id);
        $totalInitialFeePaid = ($totalInitialFeePaidArr['initial_fee'] ?? 0) + ($totalInitialFeePaidArr['initial_fee_tax'] ?? 0);
        $totalInitialFeeCalculated = $initialFeeNum + $initialFeeTaxNum;
        $totalInitialFeeRemaining = ($totalInitialFeeCalculated > $totalInitialFeePaid) ? sprintf('%0.2f', ($totalInitialFeeCalculated - $totalInitialFeePaid)) : 0;
        $pendingForCurrentCycle = ($totalInitialFeeRemaining + $totalInsuranceRemaining + $totalRentalRemaining);
        $totalRemainingAutorenew = [
            "hint" => "*Due On " . Carbon::parse($starttime)->setTimezone($timezone)->format('m/d/Y'),
            "amount" => LegacyNumber::currency($pendingForCurrentCycle, $currency)
        ];
        $promoService = new PromoService();
        $renterId = $leaseCsOrder->renter_id ?? 0;
        $userId = $leaseCsOrder->user_id ?? 0;
        $ruleRental = $dbRule->rental ?? 0;
        $ruleInsurance = $dbRule->insurance ?? 0;
        $discountedRent = $promoService->useRentalPromoCode(["rent" => ($durationdays * $ruleRental)], $renterId);
        $rentDiscount = $discountedRent['rent_discount'] ?? 0;
        $futurerent = sprintf('%0.2f', (($durationdays * $ruleRental) - $rentDiscount));
        $futurerent_withTax = $futurerent + ($futurerent * $ruleTaxRate / 100);
        $depositRuleModel = new DepositRule();
        $futurerent_dia_fee = $depositRuleModel->calculateDIAFee($futurerent, $userId);
        $nextScheduleTime = strtotime($starttime . " +$durationdays days");
        $totalRemainingNextScheduleAmount = (
            $pendingForCurrentCycle +
            $futurerent_withTax +
            $futurerent_dia_fee +
            ($durationdays * $ruleInsurance)
        );
        $totalRemainingNextSchedule = [
            "hint" => "*Due On " . Carbon::parse($nextScheduleTime)->setTimezone($timezone)->format('m/d/Y'),
            "amount" => LegacyNumber::currency($totalRemainingNextScheduleAmount, $currency)
        ];
        $totalRemainingClose = [
            "hint" => "*Due On " . Carbon::parse(time())->setTimezone($timezone)->format('m/d/Y'),
            "amount" => $totalRemainingAutorenew['amount']
        ];
        $totalCalForClose = 0;
        $totalCalForCycle = ($futurerent_withTax + $futurerent_dia_fee + ($durationdays * $ruleInsurance));
        $daysGap1 = (time() - strtotime($endtime)) / 3600;
        $durdays = $daysGap1 < 6 ? 0 : floor($daysGap1 / 24);
        $hours1 = $daysGap1 % 24;
        $hours1 = $hours1 > 6 ? $hours1 : 0;

        if ($hours1) {
            $durdays++;
        }

        $durationOpt = $dbRule->duration_opt ?? 0;
        $nextdurtaion = OrderDepositRule::getFromTierData($durationOpt, $starttime, $endtime);
        $nextdurtaion = $nextdurtaion ? $nextdurtaion : $durationdays;

        if ($daysGap1 > 0) {
            $cycles = $durdays < $durationdays ? 1 : floor($durdays / $durationdays);
            $cycles += ($durdays > $durationdays) && ($durdays % $durationdays) > 0 ? 1 : 0;

            $csOrder->start_datetime = $endtime;
            $csOrder->end_datetime = date('Y-m-d H:i:s', strtotime($endtime . " +$nextdurtaion days"));
            $csOrder->start_odometer = $endMileage;

            $resp1 = $this->getNextScheduleFee($dbRule, $csOrder, true);

            $totalCalForCycle = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt']));
            $totalCalForCycle = sprintf('%0.2f', (($totalCalForCycle * $cycles) + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));

            $totalRemainingNextSchedule = [
                "hint" => "*Due On " . Carbon::parse(strtotime($starttime . " +$nextdurtaion days"))->setTimezone($timezone)->format('m/d/Y'),
                "amount" => LegacyNumber::currency(($pendingForCurrentCycle + $totalCalForCycle), $currency)
            ];

            $csOrder->start_odometer = $startMileage;
            $csOrder->last_mile = $resp1['end_odometer'];
            $csOrder->start_datetime = $endtime;
            $csOrder->end_datetime = date('Y-m-d H:i:s');
            $csOrder->durdays = ($durdays + $durationdays);

            $resp1 = $this->getNextScheduleFee($dbRule, $csOrder, true);

            $totalCalForClose = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt'] + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));

            if ($durdays == 0) {
                $totalCalForClose = sprintf('%0.2f', ($totalCalForClose - $extraMileageFeeNum));
            }

            $totalCalculatedAmount = ($totalCalculatedAmount - ($extraMileageFeeNum + $emfTaxNum) + $resp1['extra_mileage_fee'] + $resp1['emf_tax']);

            if ($resp1['extra_mileage_fee'] > 0) {
                $currentEmfVal = $resp1['extra_mileage_fee'];
            } else {
                $currentEmfVal = ($extraMileageFeeNum + $resp1['extra_mileage_fee']);
            }

            $dia_insu = $dia_insu + $resp1['dia_insu'];

            $subStartDtFormatted = date('m/d', strtotime($csOrder->start_datetime));
            $subEndDtFormatted = date('m/d', strtotime($csOrder->end_datetime));
            $subEmfDetailText = sprintf('%s for %s-%s', LegacyNumber::currency(($resp1['extra_mileage_fee'] + $resp1['emf_tax'] + $resp1['dia_insu']), $currency), $subStartDtFormatted, $subEndDtFormatted);

            $emfDetails[] = [$subEmfDetailText];

            $extraMileageFeeFormatted = LegacyNumber::currency($currentEmfVal, $currency);

            $taxCombined = sprintf('%0.2f', ($taxCombined + $resp1['emf_tax']));
            $emfTaxNum = ($emfTaxNum + $resp1['emf_tax']);
            $discountVal = $resp1['discount'];

            $totalCalculatedInsurance = sprintf('%0.2f', ($totalCalculatedInsurance + ($nextdurtaion * $ruleInsurance) + $resp1['dia_insu']));
            $totalInsuranceRemaining = ($totalCalculatedInsurance > $paidInsurancePayments) ? sprintf('%0.2f', ($totalCalculatedInsurance - $paidInsurancePayments)) : 0;
            $totalRentalRemaining = ($totalCalculatedAmount > $paidRentalPayments) ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments)) : 0;

            $totalRemainingClose = [
                "hint" => "*Due On " . Carbon::parse(time())->setTimezone($timezone)->format('m/d/Y'),
                "amount" => LegacyNumber::currency(($pendingForCurrentCycle + $totalCalForClose), $currency)
            ];

            $carsharingFeePending = $paidRentalPayments ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments)) : $totalCalculatedAmount;
        } else {
            $discountVal = $leaseCsOrder->discount ?? 0;
            $currentEmfVal = $extraMileageFeeNum;
            $extraMileageFeeFormatted = LegacyNumber::currency($extraMileageFeeNum, $currency);
        }

        $currentEmfWithDia = $currentEmfVal + $dia_insu;

        $dueDetail = [
            $totalRemainingAutorenew,
            $totalRemainingNextSchedule,
            [
                "hint" => "*Due On " . Carbon::parse(strtotime($starttime . " +" . ($nextdurtaion * 2) . " days"))->setTimezone($timezone)->format('m/d/Y'),
                "amount" => LegacyNumber::currency(
                    ($pendingForCurrentCycle + ($totalCalForCycle * 2)),
                    $currency
                )
            ]
        ];

        $fieldsToSet = [
            'start_mileage' => $startMileage,
            'end_mileage' => $endMileage,
            'total_mileage' => $totalMileage,
            'mileage_checked' => $mileageChecked,
            'emf_uses' => $emfUses,
            'extra_mileage_fee' => $extraMileageFeeFormatted,
            'current_emf' => $currentEmfWithDia,
            'emf_tax' => LegacyNumber::currency($emfTaxNum, $currency),
            'dia_insu' => LegacyNumber::currency($dia_insu, $currency),
            'emf_details' => $emfDetails,
            'total_rental_paid' => LegacyNumber::currency($paidRentalPayments, $currency),
            'carsharing_fee_total' => LegacyNumber::currency($totalCalculatedAmount, $currency),
            'tax' => LegacyNumber::currency($taxCombined, $currency),
            'carsharing_fee_paid' => LegacyNumber::currency($paidRentalPayments, $currency),
            'paid_amount' => LegacyNumber::currency($paidRentalPayments, $currency),
            'distance_unit' => $distanceUnit,
            'total_rental_calculated' => LegacyNumber::currency($totalCalculatedAmount, $currency),
            'total_rental_remaining' => LegacyNumber::currency($totalRentalRemaining, $currency),
            'carsharing_fee_pending' => LegacyNumber::currency($carsharingFeePending, $currency),
            'total_insurance_paid' => LegacyNumber::currency($paidInsurancePayments, $currency),
            'total_insurance_calculated' => LegacyNumber::currency($totalCalculatedInsurance, $currency),
            'total_insurance_remaining' => LegacyNumber::currency($totalInsuranceRemaining, $currency),
            'total_initial_fee_paid' => LegacyNumber::currency($totalInitialFeePaid, $currency),
            'total_initial_fee_calculated' => LegacyNumber::currency($totalInitialFeeCalculated, $currency),
            'total_initial_fee_remaining' => LegacyNumber::currency($totalInitialFeeRemaining, $currency),
            'total_remaining_autorenew' => $totalRemainingAutorenew,
            'total_remaining_nextschedule' => $totalRemainingNextSchedule,
            'total_remaining_close' => $totalRemainingClose,
            'lateness_fee' => LegacyNumber::currency($latenessFeeNum, $currency),
            'initial_fee' => LegacyNumber::currency($initialFeeNum, $currency),
            'initial_fee_tax' => LegacyNumber::currency($initialFeeTaxNum, $currency),
            'dia_fee' => LegacyNumber::currency($diaFeeNum, $currency),
            'deposit' => LegacyNumber::currency(($leaseCsOrder->deposit ?? 0), $currency),
            'insurance_amt' => LegacyNumber::currency($insuranceAmtNum, $currency),
            'toll' => LegacyNumber::currency(($leaseCsOrder->toll ?? 0), $currency),
            'pending_toll' => LegacyNumber::currency($pendingTollNum, $currency),
            'rent' => LegacyNumber::currency(($rentNum + $discountVal), $currency),
            'discount' => LegacyNumber::currency($discountVal, $currency),
            'current_extra_uses_fee' => LegacyNumber::currency($currentEmfWithDia, $currency),
            'due_detail' => $dueDetail,
        ];

        foreach ($fieldsToSet as $k => $v) {
            $leaseCsOrder->{$k} = $v;
            $leaseCsOrder->setAttribute($k, $v);
        }

        $minPayment = $dbRule->minimum_payment ?? 0;
        $minPaymentExpDate = $dbRule->minimum_payment_exp_date ?? null;

        if ($minPayment != 0 && empty($minPaymentExpDate)) {
            $leastAdv = LegacyNumber::currency(sprintf('%0.2f', $minPayment), $currency);
            $leaseCsOrder->least_advance_payment = $leastAdv;
            $leaseCsOrder->setAttribute('least_advance_payment', $leastAdv);
            return $leaseCsOrder;
        }

        if ($minPayment != 0 && !empty($minPaymentExpDate) && date('Y-m-d', strtotime($minPaymentExpDate)) >= date('Y-m-d')) {
            $leastAdv = LegacyNumber::currency(sprintf('%0.2f', $minPayment), $currency);
            $leaseCsOrder->least_advance_payment = $leastAdv;
            $leaseCsOrder->setAttribute('least_advance_payment', $leastAdv);
            return $leaseCsOrder;
        }

        $leastAdvancePayment = sprintf('%0.2f', ($ruleRental + $ruleInsurance) * 2);
        $daysG = 0;

        if (time() > strtotime($endtime) && ($diffSec = time() - strtotime($endtime))) {
            $daysG = abs(round($diffSec / 86400));
        }

        $totalDaysFromBegin = abs(round((time() - strtotime($starttime)) / 86400));
        $totalDaysFromBegin = $totalDaysFromBegin > 0 ? $totalDaysFromBegin : 1;
        $comparable = sprintf('%0.2f', (((1 + $daysG) / $totalDaysFromBegin) * ($pendingForCurrentCycle + $totalCalForClose)));
        $finalLeastAdvance = $comparable > $leastAdvancePayment ? $comparable : $leastAdvancePayment;
        $finalLeastAdvFormatted = LegacyNumber::currency(sprintf('%0.2f', $finalLeastAdvance), $currency);
        $leaseCsOrder->least_advance_payment = $finalLeastAdvFormatted;
        $leaseCsOrder->setAttribute('least_advance_payment', $finalLeastAdvFormatted);

        return $leaseCsOrder;
    }
}
