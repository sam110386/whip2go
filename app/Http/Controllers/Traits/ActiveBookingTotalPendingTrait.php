<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Ported from CakePHP app/Controller/Traits/ActiveBookingTotalPending.php
 *
 * Calculates next schedule fees and total pending amounts for active bookings.
 */
trait ActiveBookingTotalPendingTrait
{
    /**
     * Format a numeric value as currency with the given symbol.
     * Replaces CakePHP $this->Number->currency().
     */
    private function formatCurrency($amount, string $currencySymbol = '$'): string
    {
        return $currencySymbol . number_format((float) $amount, 2, '.', ',');
    }

    /**
     * Calculate next schedule fees including insurance, rent, EMF, tax.
     */
    private function getNextScheduleFee(array $OrderDepositRuleObj, array $CsOrder, bool $autorenew = false): array
    {
        $return = [
            'damage_fee'   => $CsOrder['damage_fee'],
            'uncleanness_fee' => $CsOrder['uncleanness_fee'],
            'initial_fee'  => $CsOrder['initial_fee'],
        ];

        if ($autorenew) {
            $timeDiff = (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_datetime']));
        } else {
            $timeDiff = (time() - strtotime($CsOrder['start_datetime']));
        }

        $totalHours = abs($timeDiff / 3600);
        if ($totalHours > 6) {
            $insurance_days  = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $insurance_hours = $insurance_hours > 6 ? $insurance_hours : 0;
            $totldays = $insurance_days + ($insurance_hours > 0 ? 1 : 0);
        } else {
            $totldays = $insurance_hours = 0;
        }

        if (!empty($OrderDepositRuleObj['OrderDepositRule'])) {
            $return['insurance_amt'] = number_format(($totldays * $OrderDepositRuleObj['OrderDepositRule']['insurance']), 2, '.', '');
        }

        // TODO: Replace with DepositRule service – getDayRentFromTierData()
        $day_rent = !empty($OrderDepositRuleObj)
            ? $this->getDayRentFromTierDataStub(
                $OrderDepositRuleObj['OrderDepositRule']['rental_opt'],
                $insurance_days ?? 0,
                $OrderDepositRuleObj['OrderDepositRule']['rental'],
                $CsOrder['start_datetime']
            )
            : 0;

        $time_feeDays  = (($insurance_days ?? 0) * $day_rent);
        $time_feehours = $dia_insu = 0;
        if (!empty($insurance_hours)) {
            $time_feehours = $day_rent;
        }
        $time_fee = number_format(($time_feeDays + $time_feehours), 2, '.', '');

        $taxRate = !empty($OrderDepositRuleObj) ? $OrderDepositRuleObj['OrderDepositRule']['tax'] : '';
        $return['rent'] = number_format($time_fee, 2, '.', '');

        // TODO: Replace with Promo service – useRentalPromoCode()
        $discounts = $this->useRentalPromoCodeStub(['rent' => $time_fee], $CsOrder['renter_id']);
        $return['rent']     = ($time_fee - $discounts['rent_discount']);
        $return['discount'] = $discounts['rent_discount'];

        $return['extra_mileage_fee'] = 0;
        $allowed_miles = $OrderDepositRuleObj['OrderDepositRule']['miles'];

        $scheduledEndodometer = 0;
        if ($autorenew && strtotime($CsOrder['end_datetime']) < time()) {
            // TODO: Replace with CsTrackVehicle service – getLastMileFromHistory()
            $scheduledEndodometer = $this->getLastMileFromHistoryStub($CsOrder['vehicle_id'], $CsOrder['end_datetime']);
        }
        if (isset($CsOrder['last_mile'])) {
            $scheduledEndodometer = $CsOrder['last_mile'];
        }
        if ($scheduledEndodometer == 0) {
            // TODO: Replace with Passtime service – getPasstimeMiles()
            $milesData = $this->getPasstimeMilesStub($CsOrder['vehicle_id']);
            $scheduledEndodometer = $milesData['miles'];
        }

        $return['end_odometer'] = $scheduledEndodometer;

        if ($scheduledEndodometer > 0 && $allowed_miles > 0) {
            $billableMileage = (int)(($return['end_odometer'] - $CsOrder['start_odometer']) - ($allowed_miles * (isset($CsOrder['durdays']) ? $CsOrder['durdays'] : $totldays)));
            $return['billable_mileage'] = $billableMileage;
            if ($billableMileage > 0) {
                $DepositTemplate = DB::table('deposit_templates')
                    ->where('user_id', $CsOrder['user_id'])
                    ->select('max_extramile_fee')
                    ->first();
                $maxExtraMileFee = (!empty($DepositTemplate->max_extramile_fee))
                    ? $DepositTemplate->max_extramile_fee
                    : 500;

                $extra_mileage_feeTotal = isset($OrderDepositRuleObj['OrderDepositRule']['emf_rate'])
                    ? sprintf('%0.2f', ($billableMileage * $OrderDepositRuleObj['OrderDepositRule']['emf_rate']))
                    : 0;
                $extra_mileage_feeTotal += $return['extra_mileage_fee'];
                $return['extra_mileage_fee'] = $extra_mileage_feeTotal > $maxExtraMileFee
                    ? sprintf('%0.2f', $maxExtraMileFee)
                    : sprintf('%0.2f', $extra_mileage_feeTotal);

                $dia_insu = sprintf('%0.2f', ($billableMileage * ($OrderDepositRuleObj['OrderDepositRule']['emf_insu_rate'] ?: 0)));
                $dia_insu = $dia_insu > $maxExtraMileFee ? sprintf('%0.2f', $maxExtraMileFee) : $dia_insu;
            }
        }

        // TODO: Replace with DepositRule service – calculateDIAFee()
        $dia_fee = $this->calculateDIAFeeStub($return['rent'], $CsOrder['user_id']);
        $tax = (($return['rent'] + $dia_fee) * $taxRate) / 100;
        $return['tax']      = number_format($tax, 2, '.', '');
        $return['emf_tax']  = number_format((($return['extra_mileage_fee'] * $taxRate) / 100), 2, '.', '');
        $return['dia_fee']  = $dia_fee;
        $return['dia_insu'] = $dia_insu;

        return $return;
    }

    /**
     * Calculate all pending amounts for an active booking.
     */
    private function getActiveBookingTotalPending(array &$Lease, array $CsOrder): array
    {
        $orderId = !empty($Lease['CsOrder']['parent_id'])
            ? $Lease['CsOrder']['parent_id']
            : $Lease['CsOrder']['id'];

        $OrderDepositRuleObj = DB::table('order_deposit_rules')
            ->where('cs_order_id', $orderId)
            ->select('rental', 'emf', 'miles', 'emf_rate', 'emf_insu_rate', 'insurance', 'tax', 'rental_opt', 'duration_opt', 'insurance_payer', 'minimum_payment', 'minimum_payment_exp_date')
            ->first();

        $odr = $OrderDepositRuleObj ? (array) $OrderDepositRuleObj : [];
        $OrderDepositRuleArr = ['OrderDepositRule' => $odr];

        $extramilefee = $odr['emf_rate'] ?? 0;
        $diainsufee   = $odr['emf_insu_rate'] ?? 0;

        if (($odr['insurance_payer'] ?? null) == 7) {
            $odr['insurance'] = 0;
            $OrderDepositRuleArr['OrderDepositRule']['insurance'] = 0;
        }

        $endtime   = $Lease['CsOrder']['end_datetime'];
        $starttime = $Lease['CsOrder']['start_datetime'];
        $daysGap   = strtotime($endtime) - strtotime($starttime);
        $daysGap   = $daysGap / 3600;
        $durationdays = $daysGap < 24 ? 1 : floor($daysGap / 24);
        $hours = $daysGap < 24 ? 0 : $daysGap % 24;
        $hours = $hours > 6 ? $hours : 0;
        if ($hours) {
            $durationdays++;
        }

        $scheduledEndodometer = 0;
        if (strtotime($Lease['CsOrder']['end_datetime']) < time()) {
            // TODO: Replace with CsTrackVehicle service – getLastMileFromHistory()
            $scheduledEndodometer = $this->getLastMileFromHistoryStub($Lease['CsOrder']['vehicle_id'], $Lease['CsOrder']['end_datetime']);
        }

        $paidInsurancePayments = $paidRentalPayments = 0;
        $paidPayments = DB::table('cs_order_payments')
            ->where('cs_order_id', $Lease['CsOrder']['id'])
            ->whereIn('type', [3, 2, 16, 19, 4, 14])
            ->where('status', 1)
            ->select('id', 'amount', 'type')
            ->get();

        foreach ($paidPayments as $paidPayment) {
            if (in_array($paidPayment->type, [4, 14])) {
                $paidInsurancePayments += $paidPayment->amount;
            }
            if (in_array($paidPayment->type, [2, 16, 19])) {
                $paidRentalPayments += $paidPayment->amount;
            }
        }

        $Lease['CsOrder']['start_mileage'] = $Lease['CsOrder']['start_odometer'] > 0 ? $Lease['CsOrder']['start_odometer'] : 0;
        unset($Lease['CsOrder']['start_odometer']);
        $Lease['CsOrder']['end_mileage'] = $scheduledEndodometer ?: (($Lease['Vehicle']['last_mile'] > 0) ? $Lease['Vehicle']['last_mile'] : 0);
        unset($Lease['Vehicle']['last_mile']);

        $Lease['CsOrder']['total_mileage'] = (int) $Lease['CsOrder']['end_mileage'] > 0 && ($Lease['CsOrder']['end_mileage'] > $Lease['CsOrder']['start_mileage'])
            ? ($Lease['CsOrder']['end_mileage'] - $Lease['CsOrder']['start_mileage'])
            : 0;

        $tz = $Lease['CsOrder']['timezone'] ?? 'UTC';
        $Lease['CsOrder']['mileage_checked'] = Carbon::parse($Lease['Vehicle']['modified'])->timezone($tz)->format('Y-m-d H:i:s');

        $diainsu = $diainsufee;

        $Lease['CsOrder']['emf_uses'] = $this->formatCurrency(sprintf('%0.2f', ($diainsu + $extramilefee)), $Lease['CsOrder']['currency'])
            . '/' . $Lease['Owner']['distance_unit']
            . ' above ' . ceil($durationdays * ($odr['miles'] ?? 0))
            . ' ' . $Lease['Owner']['distance_unit'] . 's';

        $billabemiles = (int)($Lease['CsOrder']['total_mileage'] - ($durationdays * ($odr['miles'] ?? 0)));

        $Lease['CsOrder']['extra_mileage_fee'] = $Lease['CsOrder']['current_emf'] = ($billabemiles > 0) ? sprintf('%0.2f', ($extramilefee * $billabemiles)) : '0.00';

        $Lease['CsOrder']['emf_tax'] = sprintf('%0.2f', ($Lease['CsOrder']['extra_mileage_fee'] * ($odr['tax'] ?? 0) / 100));

        $dia_insu = ($billabemiles > 0) ? sprintf('%0.2f', ($diainsufee * $billabemiles)) : 0;
        $dia_insu = $dia_insu > $Lease['CsOrder']['dia_insu'] ? $dia_insu : $Lease['CsOrder']['dia_insu'];

        $Lease['CsOrder']['emf_details'][] = [
            sprintf(
                '%s for %s-%s',
                $this->formatCurrency(($Lease['CsOrder']['current_emf'] + $dia_insu), $Lease['CsOrder']['currency']),
                date('m/d', strtotime($Lease['CsOrder']['start_datetime'])),
                date('m/d', strtotime($Lease['CsOrder']['end_datetime']))
            )
        ];

        $totalCalculatedAmount = sprintf('%0.2f', (
            $Lease['CsOrder']['rent'] + $Lease['CsOrder']['tax'] + $Lease['CsOrder']['current_emf'] + $Lease['CsOrder']['emf_tax']
            + $Lease['CsOrder']['damage_fee'] + $Lease['CsOrder']['lateness_fee'] + $Lease['CsOrder']['uncleanness_fee']
            + $Lease['CsOrder']['dia_fee'] + $Lease['CsOrder']['pending_toll']
        ));
        $Lease['CsOrder']['total_rental_paid'] = $paidRentalPayments;

        $Lease['CsOrder']['carsharing_fee_total'] = $totalCalculatedAmount;
        $Lease['CsOrder']['tax'] = sprintf('%0.2f', ($Lease['CsOrder']['tax'] + $Lease['CsOrder']['emf_tax']));
        $Lease['CsOrder']['carsharing_fee_paid'] = $Lease['CsOrder']['paid_amount'] = $paidRentalPayments;
        $Lease['CsOrder']['distance_unit'] = $Lease['Owner']['distance_unit'];

        $Lease['CsOrder']['total_rental_calculated'] = $totalCalculatedAmount;
        $Lease['CsOrder']['total_rental_remaining']  = ($totalCalculatedAmount > $paidRentalPayments)
            ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments))
            : 0;
        $Lease['CsOrder']['carsharing_fee_pending'] = $Lease['CsOrder']['carsharing_fee_paid']
            ? sprintf('%0.2f', ($Lease['CsOrder']['carsharing_fee_total'] - $Lease['CsOrder']['carsharing_fee_paid']))
            : $Lease['CsOrder']['carsharing_fee_total'];

        $totalCalculatedInsurance = sprintf('%0.2f', ($Lease['CsOrder']['insurance_amt'] + $dia_insu));
        $Lease['CsOrder']['total_insurance_paid']       = $paidInsurancePayments;
        $Lease['CsOrder']['total_insurance_calculated']  = $totalCalculatedInsurance;
        $Lease['CsOrder']['total_insurance_remaining']   = ($totalCalculatedInsurance > $paidInsurancePayments)
            ? sprintf('%0.2f', ($totalCalculatedInsurance - $paidInsurancePayments))
            : 0;

        // TODO: Replace with CsOrderPayment service – getTotalInitialFee()
        $total_initial_fee_paid = $this->getTotalInitialFeeFromDb($Lease['CsOrder']['id']);
        $Lease['CsOrder']['total_initial_fee_paid']       = $total_initial_fee_paid['initial_fee'] + $total_initial_fee_paid['initial_fee_tax'];
        $Lease['CsOrder']['total_initial_fee_calculated'] = $Lease['CsOrder']['initial_fee'] + $Lease['CsOrder']['initial_fee_tax'];
        $Lease['CsOrder']['total_initial_fee_remaining']  = ($Lease['CsOrder']['total_initial_fee_calculated'] > $Lease['CsOrder']['total_initial_fee_paid'])
            ? sprintf('%0.2f', ($Lease['CsOrder']['total_initial_fee_calculated'] - $Lease['CsOrder']['total_initial_fee_paid']))
            : 0;

        $pendingForCurrentCycle = ($Lease['CsOrder']['total_initial_fee_remaining'] + $Lease['CsOrder']['total_insurance_remaining'] + $Lease['CsOrder']['total_rental_remaining']);

        $Lease['CsOrder']['total_remaining_autorenew'] = [
            'hint'   => '*Due On ' . Carbon::parse($starttime)->timezone($tz)->format('m/d/Y'),
            'amount' => $this->formatCurrency($pendingForCurrentCycle, $Lease['CsOrder']['currency']),
        ];

        // TODO: Replace with Promo service – useRentalPromoCode()
        $discountedRent = $this->useRentalPromoCodeStub(
            ['rent' => ($durationdays * ($odr['rental'] ?? 0))],
            $Lease['CsOrder']['renter_id']
        );

        $futurerent          = sprintf('%0.2f', (($durationdays * ($odr['rental'] ?? 0)) - $discountedRent['rent_discount']));
        $futurerent_withTax  = $futurerent + ($futurerent * ($odr['tax'] ?? 0) / 100);
        // TODO: Replace with DepositRule service – calculateDIAFee()
        $futurerent_dia_fee  = $this->calculateDIAFeeStub($futurerent, $Lease['CsOrder']['user_id']);

        $Lease['CsOrder']['total_remaining_nextschedule'] = [
            'hint'   => '*Due On ' . Carbon::parse($starttime)->addDays($durationdays)->timezone($tz)->format('m/d/Y'),
            'amount' => $this->formatCurrency(
                ($pendingForCurrentCycle + $futurerent_withTax + $futurerent_dia_fee + ($durationdays * ($odr['insurance'] ?? 0))),
                $Lease['CsOrder']['currency']
            ),
        ];

        $Lease['CsOrder']['total_remaining_close'] = [
            'hint'   => '*Due On ' . Carbon::now()->timezone($tz)->format('m/d/Y'),
            'amount' => $Lease['CsOrder']['total_remaining_autorenew']['amount'],
        ];

        $totalCalForClose = 0;
        $totalCalForCycle = ($futurerent_withTax + $futurerent_dia_fee + ($durationdays * ($odr['insurance'] ?? 0)));

        $Lease['CsOrder']['extra_mileage_fee'] = $this->formatCurrency($Lease['CsOrder']['extra_mileage_fee'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['lateness_fee']      = $this->formatCurrency($Lease['CsOrder']['lateness_fee'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['paid_amount']       = $this->formatCurrency($Lease['CsOrder']['paid_amount'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['initial_fee']       = $this->formatCurrency($Lease['CsOrder']['initial_fee'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['initial_fee_tax']   = $this->formatCurrency($Lease['CsOrder']['initial_fee_tax'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['dia_fee']           = $this->formatCurrency($Lease['CsOrder']['dia_fee'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['deposit']           = $this->formatCurrency($Lease['CsOrder']['deposit'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['insurance_amt']     = $this->formatCurrency($Lease['CsOrder']['insurance_amt'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['toll']              = $this->formatCurrency($Lease['CsOrder']['toll'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['pending_toll']      = $this->formatCurrency($Lease['CsOrder']['pending_toll'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_rental_paid'] = $this->formatCurrency($Lease['CsOrder']['total_rental_paid'], $Lease['CsOrder']['currency']);

        $Lease['CsOrder']['total_insurance_paid']          = $this->formatCurrency($Lease['CsOrder']['total_insurance_paid'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_initial_fee_paid']        = $this->formatCurrency($Lease['CsOrder']['total_initial_fee_paid'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_initial_fee_calculated']  = $this->formatCurrency($Lease['CsOrder']['total_initial_fee_calculated'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['least_advance_payment']         = $this->formatCurrency(
            sprintf('%0.2f', (($odr['rental'] ?? 0) + ($odr['insurance'] ?? 0)) * 2),
            $Lease['CsOrder']['currency']
        );

        $daysGap1 = (time() - strtotime($Lease['CsOrder']['end_datetime'])) / 3600;
        $daysGap1 = $daysGap1 / 3600;
        $durdays  = $daysGap1 < 6 ? 0 : floor($daysGap1 / 24);
        $hours1   = $daysGap1 % 24;
        $hours1   = $hours1 > 6 ? $hours1 : 0;
        if ($hours) {
            $durdays++;
        }

        // TODO: Replace with OrderDepositRule service – getFromTierData()
        $nextdurtaion = $this->getFromTierDataStub($odr['duration_opt'] ?? null, $Lease['CsOrder']['start_datetime'], $Lease['CsOrder']['end_datetime']);
        $nextdurtaion = $nextdurtaion ?: $durationdays;

        if ($daysGap1 > 0) {
            $cycles = $durdays < $durationdays ? 1 : floor($durdays / $durationdays);
            $cycles += ($durdays > $durationdays) && ($durdays % $durationdays) > 0 ? 1 : 0;

            $CsOrder['CsOrder']['start_datetime'] = $Lease['CsOrder']['end_datetime'];
            $CsOrder['CsOrder']['end_datetime']   = date('Y-m-d H:i:s', strtotime($Lease['CsOrder']['end_datetime'] . " +$nextdurtaion days"));
            $CsOrder['CsOrder']['start_odometer'] = $Lease['CsOrder']['end_mileage'];

            $resp1 = $this->getNextScheduleFee($OrderDepositRuleArr, $CsOrder['CsOrder'], true);
            $totalCalForCycle = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt']));
            $totalCalForCycle = sprintf('%0.2f', (($totalCalForCycle * $cycles) + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));

            $Lease['CsOrder']['total_remaining_nextschedule'] = [
                'hint'   => '*Due On ' . Carbon::parse($starttime)->addDays($nextdurtaion)->timezone($tz)->format('m/d/Y'),
                'amount' => $this->formatCurrency(($pendingForCurrentCycle + $totalCalForCycle), $Lease['CsOrder']['currency']),
            ];

            $CsOrder['CsOrder']['start_odometer']  = $Lease['CsOrder']['start_mileage'];
            $CsOrder['CsOrder']['last_mile']        = $resp1['end_odometer'];
            $CsOrder['CsOrder']['start_datetime']   = $Lease['CsOrder']['end_datetime'];
            $CsOrder['CsOrder']['end_datetime']     = date('Y-m-d H:i:s');
            $CsOrder['CsOrder']['durdays']          = ($durdays + $durationdays);

            $resp1 = $this->getNextScheduleFee($OrderDepositRuleArr, $CsOrder['CsOrder'], true);

            $totalCalForClose = sprintf('%0.2f', ($resp1['rent'] + $resp1['tax'] + $resp1['insurance_amt'] + $resp1['extra_mileage_fee'] + $resp1['dia_insu'] + $resp1['emf_tax'] + $resp1['dia_fee']));
            if ($durdays == 0) {
                $totalCalForClose = sprintf('%0.2f', ($totalCalForClose - $Lease['CsOrder']['current_emf']));
            }
            $totalCalculatedAmount = ($totalCalculatedAmount - ($Lease['CsOrder']['current_emf'] + $Lease['CsOrder']['emf_tax']) + $resp1['extra_mileage_fee'] + $resp1['emf_tax']);

            if ($resp1['extra_mileage_fee'] > 0) {
                $Lease['CsOrder']['current_emf'] = $resp1['extra_mileage_fee'];
            } else {
                $Lease['CsOrder']['current_emf'] = ($Lease['CsOrder']['current_emf'] + $resp1['extra_mileage_fee']);
            }
            $dia_insu = $dia_insu + $resp1['dia_insu'];

            $Lease['CsOrder']['emf_details'][] = [
                sprintf(
                    '%s for %s-%s',
                    $this->formatCurrency(($resp1['extra_mileage_fee'] + $resp1['emf_tax'] + $resp1['dia_insu']), $Lease['CsOrder']['currency']),
                    date('m/d', strtotime($CsOrder['CsOrder']['start_datetime'])),
                    date('m/d', strtotime($CsOrder['CsOrder']['end_datetime']))
                )
            ];

            $Lease['CsOrder']['extra_mileage_fee'] = $this->formatCurrency($Lease['CsOrder']['current_emf'], $Lease['CsOrder']['currency']);
            $Lease['CsOrder']['tax'] = sprintf('%0.2f', ($Lease['CsOrder']['tax'] + $resp1['emf_tax']));
            $Lease['CsOrder']['emf_tax']  = ($Lease['CsOrder']['emf_tax'] + $resp1['emf_tax']);
            $Lease['CsOrder']['discount'] = $resp1['discount'];

            $Lease['CsOrder']['total_insurance_calculated'] = sprintf('%0.2f', ($totalCalculatedInsurance + ($nextdurtaion * ($odr['insurance'] ?? 0)) + $resp1['dia_insu']));
            $Lease['CsOrder']['total_insurance_remaining']  = ($Lease['CsOrder']['total_insurance_calculated'] > $paidInsurancePayments)
                ? sprintf('%0.2f', ($Lease['CsOrder']['total_insurance_calculated'] - $paidInsurancePayments))
                : 0;

            $Lease['CsOrder']['carsharing_fee_total']      = $totalCalculatedAmount;
            $Lease['CsOrder']['total_rental_calculated']   = $totalCalculatedAmount;
            $Lease['CsOrder']['total_rental_remaining']    = ($totalCalculatedAmount > $paidRentalPayments)
                ? sprintf('%0.2f', ($totalCalculatedAmount - $paidRentalPayments))
                : 0;

            $Lease['CsOrder']['total_remaining_close'] = [
                'hint'   => '*Due On ' . Carbon::now()->timezone($tz)->format('m/d/Y'),
                'amount' => $this->formatCurrency(($pendingForCurrentCycle + $totalCalForClose), $Lease['CsOrder']['currency']),
            ];

            $Lease['CsOrder']['carsharing_fee_pending'] = $Lease['CsOrder']['carsharing_fee_paid']
                ? sprintf('%0.2f', ($Lease['CsOrder']['carsharing_fee_total'] - $Lease['CsOrder']['carsharing_fee_paid']))
                : $Lease['CsOrder']['carsharing_fee_total'];
        }

        $Lease['CsOrder']['emf_tax']  = $this->formatCurrency($Lease['CsOrder']['emf_tax'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['rent']     = $this->formatCurrency(($Lease['CsOrder']['rent'] + ($Lease['CsOrder']['discount'] ?? 0)), $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['discount'] = $this->formatCurrency(($Lease['CsOrder']['discount'] ?? 0), $Lease['CsOrder']['currency']);

        $Lease['CsOrder']['total_initial_fee_remaining']  = $this->formatCurrency($Lease['CsOrder']['total_initial_fee_remaining'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_insurance_calculated']   = $this->formatCurrency($Lease['CsOrder']['total_insurance_calculated'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_insurance_remaining']    = $this->formatCurrency($Lease['CsOrder']['total_insurance_remaining'], $Lease['CsOrder']['currency']);

        $Lease['CsOrder']['current_emf']              = $Lease['CsOrder']['current_emf'] + $dia_insu;
        $Lease['CsOrder']['current_extra_uses_fee']   = $this->formatCurrency(($Lease['CsOrder']['current_emf'] + $dia_insu), $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['carsharing_fee_total']     = $this->formatCurrency($Lease['CsOrder']['carsharing_fee_total'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_rental_calculated']  = $this->formatCurrency($Lease['CsOrder']['total_rental_calculated'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['total_rental_remaining']   = $this->formatCurrency($Lease['CsOrder']['total_rental_remaining'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['carsharing_fee_paid']      = $this->formatCurrency($Lease['CsOrder']['carsharing_fee_paid'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['carsharing_fee_pending']   = $this->formatCurrency($Lease['CsOrder']['carsharing_fee_pending'], $Lease['CsOrder']['currency']);
        $Lease['CsOrder']['tax']                      = $this->formatCurrency($Lease['CsOrder']['tax'], $Lease['CsOrder']['currency']);

        $Lease['CsOrder']['dia_insu'] = $this->formatCurrency($dia_insu, $Lease['CsOrder']['currency']);

        $Lease['CsOrder']['due_detail'][] = $Lease['CsOrder']['total_remaining_autorenew'];
        $Lease['CsOrder']['due_detail'][] = $Lease['CsOrder']['total_remaining_nextschedule'];
        $Lease['CsOrder']['due_detail'][] = [
            'hint'   => '*Due On ' . Carbon::parse($starttime)->addDays($nextdurtaion * 2)->timezone($tz)->format('m/d/Y'),
            'amount' => $this->formatCurrency(($pendingForCurrentCycle + ($totalCalForCycle * 2)), $Lease['CsOrder']['currency']),
        ];

        if (($odr['minimum_payment'] ?? 0) != 0 && empty($odr['minimum_payment_exp_date'])) {
            $Lease['CsOrder']['least_advance_payment'] = $this->formatCurrency(sprintf('%0.2f', $odr['minimum_payment']), $Lease['CsOrder']['currency']);
            return $Lease;
        }

        if (($odr['minimum_payment'] ?? 0) != 0
            && !empty($odr['minimum_payment_exp_date'])
            && date('Y-m-d', strtotime($odr['minimum_payment_exp_date'])) >= date('Y-m-d')
        ) {
            $Lease['CsOrder']['least_advance_payment'] = $this->formatCurrency(sprintf('%0.2f', $odr['minimum_payment']), $Lease['CsOrder']['currency']);
            return $Lease;
        }

        $least_advance_payment = sprintf('%0.2f', (($odr['rental'] ?? 0) + ($odr['insurance'] ?? 0)) * 2);
        if (time() > strtotime($Lease['CsOrder']['end_datetime']) && ($daysG = time() - strtotime($Lease['CsOrder']['end_datetime']))) {
            $daysG = abs(round($daysG / 86400));
        } else {
            $daysG = 0;
        }
        $totalDaysFromBegin = abs(round((time() - strtotime($Lease['CsOrder']['start_datetime'])) / 86400));
        $comparable = sprintf('%0.2f', (((1 + $daysG) / $totalDaysFromBegin) * ($pendingForCurrentCycle + $totalCalForClose)));
        $Lease['CsOrder']['least_advance_payment'] = $this->formatCurrency(
            sprintf('%0.2f', ($comparable > $least_advance_payment ? $comparable : $least_advance_payment)),
            $Lease['CsOrder']['currency']
        );

        return $Lease;
    }

    // ------------------------------------------------------------------
    // Stub methods for dependencies that need dedicated service classes.
    // Each stub returns a sensible default; replace with real logic.
    // ------------------------------------------------------------------

    /** TODO: Migrate DepositRule->getDayRentFromTierData() */
    private function getDayRentFromTierDataStub($rentalOpt, $days, $rental, $startDatetime)
    {
        return $rental;
    }

    /** TODO: Migrate Promo->useRentalPromoCode() */
    private function useRentalPromoCodeStub(array $data, $renterId): array
    {
        return ['rent_discount' => 0];
    }

    /** TODO: Migrate CsTrackVehicle->getLastMileFromHistory() */
    private function getLastMileFromHistoryStub($vehicleId, $endDatetime): int
    {
        return 0;
    }

    /** TODO: Migrate Passtime->getPasstimeMiles() */
    private function getPasstimeMilesStub($vehicleId): array
    {
        return ['miles' => 0];
    }

    /** TODO: Migrate DepositRule->calculateDIAFee() */
    private function calculateDIAFeeStub($rent, $userId): float
    {
        return 0.00;
    }

    /** TODO: Migrate OrderDepositRule->getFromTierData() */
    private function getFromTierDataStub($durationOpt, $startDatetime, $endDatetime)
    {
        return null;
    }

    /**
     * Fetch total initial fee from DB (replaces CsOrderPayment->getTotalInitialFee).
     */
    private function getTotalInitialFeeFromDb($csOrderId): array
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 3)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(amount - tax), 0) as initial_fee, COALESCE(SUM(tax), 0) as initial_fee_tax')
            ->first();

        return [
            'initial_fee'     => $row->initial_fee ?? 0,
            'initial_fee_tax' => $row->initial_fee_tax ?? 0,
        ];
    }
}
