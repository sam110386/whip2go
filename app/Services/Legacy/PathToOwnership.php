<?php

namespace App\Services\Legacy;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Component/PathToOwnershipComponent.php
 * Complex pricing/fare calculation for Path-to-Ownership vehicle programs.
 *
 * DB tables used: deposit_rules, pto_settings, rev_settings,
 *                 cs_equity_settings, deposit_templates.
 */
class PathToOwnership
{
    private int $_usercreditscore = 650;
    private int $_msrp = 1;

    /** Default PTO downpayment rate when no PtoSetting row is found. */
    private float $_defaultDownpaymentRate = 10.0;

    // ─── helpers replacing CakeNumber / CakeTime ───────────────────

    protected function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($amount, 2, '.', ',');
    }

    protected function getCurrencySymbol(string $currency = 'USD'): string
    {
        $map = [
            'USD' => '$',
            'CAD' => 'CA$',
            'GBP' => '£',
            'EUR' => '€',
        ];
        return $map[strtoupper($currency)] ?? '$';
    }

    protected function formatForUser(int $timestamp, string $format = 'm/d/Y', string $timezone = ''): string
    {
        $dt = Carbon::createFromTimestamp($timestamp);
        if ($timezone) {
            $dt->setTimezone($timezone);
        }
        return $dt->format($format);
    }

    protected function toServerDate(int $timestamp, string $format = 'Y-m-d', string $timezone = ''): string
    {
        $dt = Carbon::createFromTimestamp($timestamp);
        if ($timezone) {
            $dt->setTimezone($timezone);
        }
        return $dt->format($format);
    }

    protected function toServerDateFromString(string $dateStr, string $format = 'Y-m-d', string $timezone = ''): string
    {
        $dt = Carbon::parse($dateStr);
        if ($timezone) {
            $dt->setTimezone($timezone);
        }
        return $dt->format($format);
    }

    // ─── DB helper wrappers replacing CakePHP model calls ──────────

    protected function findDepositRuleByVehicleId(int $vehicleId): ?object
    {
        return DB::table('deposit_rules')->where('vehicle_id', $vehicleId)->first();
    }

    protected function findPtoSetting(int $ownerId, float $price): ?object
    {
        return DB::table('pto_settings')
            ->where('user_id', $ownerId)
            ->where('msrp_from', '<=', $price)
            ->where('msrp_to', '>=', $price)
            ->where('credit_score_from', '<=', $this->_usercreditscore)
            ->where('credit_score_to', '>=', $this->_usercreditscore)
            ->first();
    }

    protected function findRevSetting(int $ownerId): ?object
    {
        return DB::table('rev_settings')->where('user_id', $ownerId)->first();
    }

    protected function findCsEquitySetting(int $ownerId): ?object
    {
        return DB::table('cs_equity_settings')->where('user_id', $ownerId)->first();
    }

    protected function getDownpaymentRate(?object $depositRule, int $ownerId, float $price): float
    {
        if ($depositRule && $depositRule->write_down_allocation > 0) {
            return (float)$depositRule->write_down_allocation;
        }
        $ptoSetting = $this->findPtoSetting($ownerId, $price);
        return $ptoSetting ? (float)$ptoSetting->downpayment : $this->_defaultDownpaymentRate;
    }

    protected function getRevShare(int $ownerId): float
    {
        $revSetting = $this->findRevSetting($ownerId);
        $share = ($revSetting && !empty($revSetting->rental_rev)) ? $revSetting->rental_rev : config('app.owner_part', 100);
        return (float)$share;
    }

    // ─── public business methods ───────────────────────────────────

    public function getperDayPrice(float $price = 0): int
    {
        if ($price == '' || $price == 0) {
            return 40;
        }
        if ($price > 0 && $price <= 10000) {
            return 35;
        }
        return 40;
    }

    public function getVehiclePerDayPriceForQuote(array $vehicleData, $renter, float $initialfee = 0): array
    {
        $vehicleid = $vehicleData['id'];
        $dr = $this->findDepositRuleByVehicleId($vehicleid);

        $price = $vehicleData['msrp'] ?: 10000;
        $ownerid = $vehicleData['user_id'];
        $homenet_msrp = $vehicleData['homenet_msrp'] ?: 10000;

        $downpaymentRate = $this->getDownpaymentRate($dr, $ownerid, $price);

        $goalLength = $dr->program_length ?? 180;
        $emf = $dia_insu = $allowedMiles = $deposit_amt = 0;
        $miles_options = $initial_fee_options = [];
        $allowedMiles = $vehicleData['allowed_miles'] ? ceil($vehicleData['allowed_miles'] * 30) : 1000;
        $capitalize_starting_fee = (bool)($dr->capitalize_starting_fee ?? 0);

        if ($dr) {
            $initial_fee = $dr->total_initial_fee ? (int)$dr->total_initial_fee : 100;
            $emf = $dr->emf ?: 0;
            $dia_insu = $dr->emf_insu ?: 0;
            $insurance = $dr->insurance_fee;
            $deposit_amt = $dr->total_deposit_amt ? (int)$dr->total_deposit_amt : 0;
        }
        if (in_array($dr->insurance_payer ?? 0, [3, 5, 7])) {
            $insurance = 0;
            $dia_insu = 0;
        }

        $initial_fee_options[$initialfee] = $this->getCurrencySymbol($vehicleData['currency']) . $initialfee;

        $k = $allowedMiles;
        while ($k <= 10000) {
            $insu = $insurance ? ($insurance + (($k - $allowedMiles) * 12 / 365) * $dia_insu) : 0;
            $miles_options[$k] = [
                'emf'           => sprintf('%0.2f', ($k - $allowedMiles) * $emf),
                'dayInsurance'  => $this->formatCurrency($insu, $vehicleData['currency']),
                'weekInsurance' => $this->formatCurrency($insu * 7, $vehicleData['currency']),
            ];
            $k += 500;
        }

        $maintenance = $dr->monthly_maintenance ?? 0;
        $financing = $dr->financing ?? 0;
        $financing_type = $dr->financing_type ?? 'P';
        $dispositionfee = $dr->disposition_fee ?? 0;

        $free2MoveData = json_decode($dr->free_two_move ?? '{}', true) ?: [];
        if ($vehicleData['fare_type'] == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * ($free2MoveData['residual_value'] ?? 0))));
        } else {
            $totalWriteDownPayment = sprintf('%0.4f', (($price * $downpaymentRate / 100) * ($goalLength / 365)));
        }

        $revshare = $this->getRevShare($ownerid);
        $diAFee = $revshare * 1;

        $TierPricing = [];
        $rentalEquityData = $this->findCsEquitySetting($ownerid);
        $otherVehicleequityShare = $rentalEquityData ? ($rentalEquityData->other_vhshare ?? 0) : 0;

        foreach ($initial_fee_options as $initial_fee_val => $val) {
            if ($vehicleData['fare_type'] == 'L') {
                $leasePaymentsToLessor = sprintf('%0.4f', (($free2MoveData['monthly_price'] ?? 0) * $goalLength * 12 / 365));
                $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
                $vehicleCostInclRecon = $vehicleData['vehicleCostInclRecon'] ?? 0;
                $incentive = $dr->incentive ?? 0;
                $doc_fee = $dr->doc_fee ?? 0;
                $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleCostInclRecon - $incentive + $doc_fee)));
            } else {
                if ($capitalize_starting_fee && $financing_type == 'P') {
                    $totalFinancing = ((($price - $initial_fee_val) * $financing / 100) / 365) * $goalLength;
                } elseif (!$capitalize_starting_fee && $financing_type == 'P') {
                    $totalFinancing = (($price * $financing / 100) / 365) * $goalLength;
                } else {
                    $totalFinancing = ($financing / 365) * $goalLength;
                }
            }

            $totalProgramFee = $totalWriteDownPayment + ($maintenance * 12 * ($goalLength / 365)) + $dispositionfee + $totalFinancing;
            $totalProgramFeeWithDia = $diAFee ? sprintf('%0.4f', ($totalProgramFee * 100 / $diAFee)) : $totalProgramFee;

            foreach ($miles_options as $miles => $emfval) {
                if ($vehicleData['fare_type'] == 'D' || $vehicleData['fare_type'] == 'L') {
                    $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee_val) / ($goalLength));
                    $equityShare = sprintf('%0.2f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
                } else {
                    $dailyFee = $vehicleData['day_rent'];
                    $equityShare = 0;
                }
                $newDailyFee = sprintf('%0.2f', ($dailyFee + ($emfval['emf'] * 12 / 365)));
                $newGoallenghth = $emfval['emf']
                    ? floor((($totalProgramFeeWithDia - $initial_fee_val) / $newDailyFee) / 30)
                    : floor($goalLength / 30);
                $cur = $vehicleData['currency'];
                $TierPricing[$initial_fee_val . 'X' . $miles] = [
                    'label'              => "~{$newGoallenghth} Month(s)",
                    'dayRent'            => $this->formatCurrency($newDailyFee, $cur),
                    'dayEmfRent'         => $this->formatCurrency($newDailyFee, $cur),
                    'weekRent'           => $this->formatCurrency($dailyFee * 7, $cur),
                    'weekkEmfRent'       => $this->formatCurrency($newDailyFee * 7, $cur),
                    'samevehiclecoupon'  => $this->formatCurrency((($newDailyFee * $equityShare) / 100) * 7, $cur),
                    'othervehiclecoupon' => $this->formatCurrency((($newDailyFee * $otherVehicleequityShare) / 100) * 7, $cur),
                    'emf'                => $emfval['emf'],
                ];
            }
        }

        krsort($initial_fee_options);
        return [
            'initial_fee_options' => $initial_fee_options,
            'miles_options'       => $miles_options,
            'tier_rental'         => $TierPricing,
            'extra_mile_fee'      => $emf,
            'dia_insu'            => $dia_insu,
            'deposit_amt'         => $this->formatCurrency($deposit_amt, $vehicleData['currency']),
        ];
    }

    public function getVehicleDynamicFareMatrix(array $vehicleData, $renter): array
    {
        $vehicleid = $vehicleData['id'];
        $dr = $this->findDepositRuleByVehicleId($vehicleid);

        $price = $vehicleData['msrp'] ?: 10000;
        $ownerid = $vehicleData['user_id'];
        $vehicleListeddPrice = $vehicleData['premium_msrp'] ?: $vehicleData['msrp'];
        $homenet_msrp = $vehicleData['homenet_msrp'] ?: 10000;
        $cur = $vehicleData['currency'];

        $downpaymentRate = $this->getDownpaymentRate($dr, $ownerid, $price);

        $goalLength = $dr->program_length ?? 180;
        $initial_fee = 100;
        $emf = $dia_insu = $allowedMiles = $deposit_amt = $prepaid_initial_fee = 0;
        $miles_options = $initial_fee_options = [];
        $allowedMiles = $vehicleData['allowed_miles'] ? ceil($vehicleData['allowed_miles'] * 30) : 1000;
        $capitalize_starting_fee = (bool)($dr->capitalize_starting_fee ?? 0);

        if ($dr) {
            $prepaid_initial_fee = $initial_fee = $dr->total_initial_fee ? (int)$dr->total_initial_fee : 100;
            $emf = $dr->emf ?: 0;
            $dia_insu = $dr->emf_insu ?: 0;
            $insurance = $dr->insurance_fee ?? 0;
            $deposit_amt = $dr->total_deposit_amt ? (int)$dr->total_deposit_amt : 0;
        }
        if ($dr && ($dr->insurance_payer ?? 0) == 7) {
            $dia_insu = 0;
            $insurance = 0;
        }

        // TODO: Promo integration — port Plugin/Promo/Lib/Promo class
        $quote = ['initial_fee_discount' => 0];
        // $quote = (new Promo())->applyPromoCode(['initial_fee'=>$initial_fee,'rent'=>0], $renter['id']);
        if (($quote['initial_fee_discount'] ?? 0) > 0) {
            $prepaid_initial_fee = $initial_fee = $initial_fee - $quote['initial_fee_discount'];
        }

        $j = $initial_fee;
        $juper = ($price > 20000) ? 3000 : 1500;
        while ($j <= $juper) {
            $initial_fee_options[$j] = $this->getCurrencySymbol($cur) . $j;
            $j += 100;
        }

        $showInsurance = 1;
        if (in_array($dr->insurance_payer ?? 0, [3, 7])) {
            $showInsurance = 0;
        }

        $k = $allowedMiles;
        while ($k <= 5000) {
            if ($showInsurance) {
                $insu = ($insurance + (($k - $allowedMiles) * 12 / 365) * $dia_insu);
            } else {
                $insu = $insurance;
            }
            if ($dr && ($dr->insurance_payer ?? 0) == 7) {
                $miles_options[$k] = [
                    'emf'              => sprintf('%0.2f', ($k - $allowedMiles) * $emf),
                    'dayInsurance'     => 'BYO',
                    'weekInsurance'    => 'BYO',
                    'biWeekInsurance'  => 'BYO',
                    'monthlyInsurance' => 'BYO',
                ];
            } else {
                $miles_options[$k] = [
                    'emf'              => sprintf('%0.2f', ($k - $allowedMiles) * $emf),
                    'dayInsurance'     => $this->formatCurrency($insu, $cur),
                    'weekInsurance'    => $this->formatCurrency($insu * 7, $cur),
                    'biWeekInsurance'  => $this->formatCurrency($insu * 15, $cur),
                    'monthlyInsurance' => $this->formatCurrency($insu * 365 / 12, $cur),
                ];
            }
            $k += 500;
        }

        $maintenance = $dr->monthly_maintenance ?? 0;
        $financing = $dr->financing ?? 0;
        $financing_type = $dr->financing_type ?? 'P';
        $dispositionfee = $dr->disposition_fee ?? 0;

        $free2MoveData = json_decode($dr->free_two_move ?? '{}', true) ?: [];
        if ($vehicleData['fare_type'] == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * ($free2MoveData['residual_value'] ?? 0))));
        } else {
            $totalWriteDownPayment = sprintf('%0.4f', (($price * $downpaymentRate / 100) * ($goalLength / 365)));
        }

        $revshare = $this->getRevShare($ownerid);
        $diAFee = $revshare * 1;

        $TierPricing = [];
        $rentalEquityData = $this->findCsEquitySetting($ownerid);
        $equityShare = $rentalEquityData ? ($rentalEquityData->share ?? 60) : 60;

        foreach ($initial_fee_options as $initial_fee_val => $val) {
            if ($vehicleData['fare_type'] == 'L') {
                $leasePaymentsToLessor = sprintf('%0.4f', (($free2MoveData['monthly_price'] ?? 0) * $goalLength * 12 / 365));
                $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
                $vehicleCostInclRecon = $vehicleData['vehicleCostInclRecon'] ?? 0;
                $incentive = $dr->incentive ?? 0;
                $doc_fee = $dr->doc_fee ?? 0;
                $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleCostInclRecon - $incentive + $doc_fee)));
            } else {
                if ($capitalize_starting_fee && $financing_type == 'P') {
                    $totalFinancing = ((($price - $initial_fee_val) * $financing / 100) / 365) * $goalLength;
                } elseif (!$capitalize_starting_fee && $financing_type == 'P') {
                    $totalFinancing = (($price * $financing / 100) / 365) * $goalLength;
                } else {
                    $totalFinancing = ($financing / 365) * $goalLength;
                }
            }

            $totalProgramFee = $totalWriteDownPayment + ($maintenance * 12 * ($goalLength / 365)) + $dispositionfee + $totalFinancing;
            $totalProgramFeeWithDia = $diAFee ? sprintf('%0.4f', ($totalProgramFee * 100 / $diAFee)) : $totalProgramFee;
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
            if (in_array($vehicleData['financing'] ?? 0, [3, 5])) {
                $equityShare = 0;
            }

            foreach ($miles_options as $miles => $emfval) {
                if ($vehicleData['fare_type'] == 'D' || $vehicleData['fare_type'] == 'L') {
                    $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee_val) / ($goalLength));
                } else {
                    $dailyFee = $vehicleData['day_rent'];
                }
                $newDailyFee = sprintf('%0.2f', ($dailyFee + ($emfval['emf'] * 12 / 365)));
                $specialDayRent = $specialWeekEmfRent = $specialBiWeeklyEmfRent = $specialMonthlyEmfRent = 0;
                if (isset($quote['rent_promo']) && ($quote['rent_promo']['discountval'] ?? 0)) {
                    if ($quote['rent_promo']['type'] == 'flat') {
                        $specialDayRent = ($newDailyFee > $quote['rent_promo']['discountval']
                            ? ($newDailyFee - $quote['rent_promo']['discountval'])
                            : $quote['rent_promo']['discountval']);
                        $specialWeekEmfRent = ($newDailyFee * 7) - (7 * $quote['rent_promo']['discountval']);
                        $specialBiWeeklyEmfRent = ($newDailyFee * 14) - (14 * $quote['rent_promo']['discountval']);
                        $specialMonthlyEmfRent = ($newDailyFee * 365 / 12) - ((365 / 12) * $quote['rent_promo']['discountval']);
                    }
                    if ($quote['rent_promo']['type'] == 'percent') {
                        $specialDayRent = ($newDailyFee - ($newDailyFee * $quote['rent_promo']['discountval']) / 100);
                        $specialWeekEmfRent = ($newDailyFee * 7) - ($newDailyFee * 7 * $quote['rent_promo']['discountval']) / 100;
                        $specialBiWeeklyEmfRent = ($newDailyFee * 14) - ($newDailyFee * 14 * $quote['rent_promo']['discountval']) / 100;
                        $specialMonthlyEmfRent = ($newDailyFee * 365 / 12) - (($newDailyFee * 365 / 12) * $quote['rent_promo']['discountval']) / 100;
                    }
                }
                if ($specialDayRent) {
                    $newGoallenghth = floor((($totalProgramFeeWithDia - $initial_fee_val) / $specialDayRent) * 12 / 365);
                } else {
                    $newGoallenghth = $emfval['emf']
                        ? floor((($totalProgramFeeWithDia - $initial_fee_val) / $newDailyFee) * 12 / 365)
                        : floor($goalLength * 12 / 365);
                }

                $effectiveDailyFee = $specialDayRent ?: $newDailyFee;
                $TierPricing[$initial_fee_val . 'X' . $miles] = [
                    'label'                  => "~{$newGoallenghth} Month(s)",
                    'dayRent'                => $this->formatCurrency($newDailyFee, $cur),
                    'dayEmfRent'             => $this->formatCurrency($newDailyFee, $cur),
                    'weekRent'               => $this->formatCurrency($dailyFee * 7, $cur),
                    'weekkEmfRent'           => $this->formatCurrency($newDailyFee * 7, $cur),
                    'samevehiclecoupon'      => $this->formatCurrency((($effectiveDailyFee * $equityShare) / 100) * 7, $cur),
                    'vehicleCoupon'          => [
                        'weekly'   => $this->formatCurrency((($effectiveDailyFee * $equityShare) / 100) * 7, $cur),
                        'biweekly' => $this->formatCurrency((($effectiveDailyFee * $equityShare) / 100) * 14, $cur),
                        'monthly'  => $this->formatCurrency((($effectiveDailyFee * $equityShare) / 100) * 365 / 12, $cur),
                    ],
                    'specialWeekEmfRent'     => $specialDayRent ? $this->formatCurrency($specialWeekEmfRent, $cur) : '',
                    'biWeeklyEmfRent'        => $this->formatCurrency($newDailyFee * 14, $cur),
                    'specialBiWeeklyEmfRent' => $specialBiWeeklyEmfRent ? $this->formatCurrency($specialBiWeeklyEmfRent, $cur) : '',
                    'monthlyEmfRent'         => $this->formatCurrency($newDailyFee * 365 / 12, $cur),
                    'specialMonthlyEmfRent'  => $specialMonthlyEmfRent ? $this->formatCurrency($specialMonthlyEmfRent, $cur) : '',
                ];
            }
        }

        krsort($initial_fee_options);

        $return = [];
        $return['rent_opt_title'] = 'After ' . floor($goalLength / 30) . ' months';
        $return['initial_fee_options'] = $initial_fee_options;
        $return['miles_options'] = $miles_options;
        $return['tier_rental'] = $TierPricing;

        if (in_array($vehicleData['financing'] ?? 0, [2, 4])) {
            $return['rent_opt_des'][] = sprintf('%s Option to Buy Price', $this->formatCurrency($vehicleListeddPrice - $totalWriteDownPayment, $cur));
            $return['rent_opt_des'][] = 'Drive More and Reduce Buyout Price Further';
        } else {
            $return['rent_opt_des'][] = 'Pay For Use';
            $return['rent_opt_des'][] = 'No purchase option';
        }
        $return['rent_opt_des'][] = 'Return at Anytime';
        $return['extra_mile_fee'] = $emf;
        $return['dia_insu'] = $dia_insu;
        $return['deposit_amt'] = $this->formatCurrency($deposit_amt, $cur);
        $return['show_insurance'] = $showInsurance;

        $initialfeeplans = $this->getVehicleInitialFeePrepaidPlans(
            $prepaid_initial_fee,
            $dr ? (array)$dr : [],
            $cur,
            $vehicleData['timezone'] ?? ''
        );
        $return['prepaid_initial_fee'] = $initialfeeplans['prepaid_initial_fee'];
        $return['prepaid_initial_fee_plans'] = $initialfeeplans['prepaid_initial_fee_plans'];
        $return['prepaid_plan_text'] = $initialfeeplans['prepaid_plan_text'];

        $insurancePayer = $dr->insurance_payer ?? 0;
        $return['insurance_help_text'] = in_array($insurancePayer, [4, 7])
            ? 'Bring Your Own Insurance: Before or after booking, you are welcome to check insurance rates. Allowed providers include Progressive, Geico, National General, and The General. You can go to any of these providers to check rates.'
            : 'Driver will bring his own insurance. Before or after booking you can get insurance quotes that will be based on you and the vehicle.';

        return $return;
    }

    public function getDynamicFareMatrixInsurance($miles_options, array $vehicleData, $DepositRuleObj): string
    {
        $dia_insu = $allowedMiles = $insurance = 0;
        $allowedMiles = ($vehicleData['allowed_miles'] ?? 0) > 0 ? ceil($vehicleData['allowed_miles'] * 30) : 1000;

        $drData = is_object($DepositRuleObj) ? $DepositRuleObj : (object)($DepositRuleObj['DepositRule'] ?? $DepositRuleObj);
        if ($drData) {
            $dia_insu = $drData->emf_insu ?? 0;
            $insurance = $drData->insurance_fee ?? 0;
        }

        return sprintf('%0.2f', ($insurance + (($miles_options - $allowedMiles) * 12 / 365) * $dia_insu));
    }

    public function getQuoteForBooking(array $vehicleData, array $options = []): array
    {
        $rentaloption = $options['rental_options'] ?? 0;
        $initial_fee = $options['initial_fee'] ?? 0;
        $renterid = $options['renter_id'] ?? 0;
        $price = $vehicleData['msrp'] ?: 10000;
        $homenet_msrp = $vehicleData['homenet_msrp'] ?: 10000;
        $ownerid = $vehicleData['user_id'];
        $vehicleCostInclRecon = ($vehicleData['msrp'] - $initial_fee);

        $return = [
            'dayrent'     => $rentaloption,
            'initial_fee' => $initial_fee,
            'deposit_opt' => '',
            'msrp'        => $price,
            'premium_msrp' => $vehicleData['premium_msrp'] ?? 0,
        ];

        $dr = $this->findDepositRuleByVehicleId($vehicleData['id']);
        if ($dr) {
            $return['deposit_amt'] = $dr->deposit_amt ?? 200;
            $return['deposit_opt'] = $dr->deposit_amt_opt ?? '';
        } else {
            $return['deposit_amt'] = 200;
        }

        $return['emf_rate'] = $dr->emf ?? 0;
        $return['emf_insu_rate'] = $dr->emf_insu ?? 0;

        $downpaymentRate = $this->getDownpaymentRate($dr, $ownerid, $price);

        $goalLength = $dr->program_length ?? 180;
        $capitalize_starting_fee = (bool)($dr->capitalize_starting_fee ?? 0);
        $maintenance = $dr->monthly_maintenance ?? 0;
        $financing = $dr->financing ?? 0;
        $financing_type = $dr->financing_type ?? 'P';
        $dispositionfee = $dr->disposition_fee ?? 0;

        $free2MoveData = json_decode($dr->free_two_move ?? '{}', true) ?: [];
        if ($vehicleData['fare_type'] == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * ($free2MoveData['residual_value'] ?? 0))));
        } else {
            $totalWriteDownPayment = sprintf('%0.4f', (($price * $downpaymentRate / 100) * ($goalLength / 365)));
        }

        $revshare = $this->getRevShare($ownerid);
        $diAFee = $revshare * 1;

        $equityShare = 60;
        $toalMaintenance = sprintf('%0.4f', ($maintenance * 12 * ($goalLength / 365)));

        if ($vehicleData['fare_type'] == 'L') {
            $leasePaymentsToLessor = sprintf('%0.4f', (($free2MoveData['monthly_price'] ?? 0) * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $vehicleCostInclReconCalc = $vehicleData['vehicleCostInclRecon'] ?? 0;
            $incentive = $dr->incentive ?? 0;
            $doc_fee = $dr->doc_fee ?? 0;
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleCostInclReconCalc - $incentive + $doc_fee)));
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

        if (in_array($vehicleData['fare_type'], ['D', 'L'])) {
            $equityShare = sprintf('%0.4f', ($totalWriteDownPayment / $totalProgramFeeWithDia) * 100);
        }
        if (in_array($vehicleData['fare_type'], ['D', 'L'])) {
            $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / ($goalLength));
        } else {
            $dailyFee = $vehicleData['day_rent'];
        }

        // TODO: Promo integration — port Plugin/Promo/Lib/Promo class
        // $quote = (new Promo())->applyPromoCode(['rent'=>$dailyFee], $renterid);
        $quote = [];
        $specialDayRent = 0;
        if (isset($quote['rent_promo']) && ($quote['rent_promo']['discountval'] ?? 0)) {
            if ($quote['rent_promo']['type'] == 'flat') {
                $specialDayRent = ($dailyFee > $quote['rent_promo']['discountval']
                    ? ($dailyFee - $quote['rent_promo']['discountval'])
                    : $quote['rent_promo']['discountval']);
            }
            if ($quote['rent_promo']['type'] == 'percent') {
                $specialDayRent = sprintf('%0.2f', ($dailyFee - ($dailyFee * $quote['rent_promo']['discountval']) / 100));
            }
            if ($specialDayRent) {
                $goalLength = floor((($totalProgramFeeWithDia - $initial_fee) / $specialDayRent) * 12 / 365);
            }
        }

        $return['num_of_days'] = $goalLength;
        $return['totalcost'] = $price;
        $return['equityshare'] = $equityShare;
        $return['downpayment'] = $totalWriteDownPayment;
        $return['initial_fee'] = $initial_fee;
        $return['rental'] = $rentaloption;
        $return['tax_rate'] = $dr->tax ?? 0;
        $return['rental_opt'] = [];
        $return['goal'] = $downpaymentRate;
        $return['total_program_cost'] = sprintf('%0.4f', $totalProgramFeeWithDia);
        $return['base_dayrent'] = $dailyFee;
        $return['write_down_allocation'] = sprintf('%0.4f', (100 * $totalWriteDownPayment / $totalProgramFeeWithDia));
        $return['finance_allocation'] = $finance_allocation;
        $return['maintenance_allocation'] = $maintenance_allocation;
        $return['maintenance_total'] = $toalMaintenance;
        $return['financing_total'] = $totalFinancing;
        $return['disposition_fee'] = $dispositionfee;
        $return['total_program_fee_without_dia'] = $totalProgramFee;
        $return['total_program_fee_with_dia'] = $totalProgramFeeWithDia;
        $return['maintenance_per_month'] = $maintenance;
        $return['fixed_program_cost'] = 0;
        $return['finance_per_year'] = ($financing_type == 'P')
            ? ((($vehicleCostInclRecon * $financing / 100) / 365))
            : $financing;

        return $return;
    }

    public function getVehicleRentForListing(array $vehicle, ?string $currency = null): array
    {
        $dayRent = $vehicle['day_rent'];
        $goalLength = 180;
        $vehicleid = $vehicle['id'];
        $dr = $this->findDepositRuleByVehicleId($vehicleid);
        $DepositRuleData = $dr ? (array)$dr : [];
        $unit_text = '';

        if ($vehicle['day_rent'] > 0 || $vehicle['fare_type'] == 'D') {
            $dailyRental = $this->calculatelowestDayRent($vehicle, ['DepositRule' => $DepositRuleData]);
            $dayRent = $this->formatCurrency($dailyRental['dailyFee'] * 7, $vehicle['currency']);
            $goalLength = $dailyRental['goalLength'];
            $unit_text = 'per week';
        } else {
            $dayRent = $this->formatCurrency($vehicle['rate'], $vehicle['currency']) . '/hr';
            $unit_text = 'per hr';
        }

        $initialFee = $DepositRuleData['initial_fee'] ?? 0;
        return [
            'dayRent'    => $dayRent,
            'initialFee' => $this->formatCurrency($initialFee, $vehicle['currency']),
            'goalLength' => $goalLength,
            'unit_text'  => $unit_text,
        ];
    }

    public function calculatelowestDayRent(array $vehicleData, array $DepositRuleData): array
    {
        $drRow = $DepositRuleData['DepositRule'] ?? $DepositRuleData;

        $price = $vehicleData['msrp'] ?: 10000;
        $initial_fee = 0;
        $ownerid = $vehicleData['user_id'];
        $homenet_msrp = $vehicleData['homenet_msrp'] ?: 10000;

        $downpaymentRate = ($drRow['write_down_allocation'] ?? 0) > 0
            ? (float)$drRow['write_down_allocation']
            : $this->getDownpaymentRate(null, $ownerid, $price);

        $goalLength = $drRow['program_length'] ?? 180;
        $maintenance = $drRow['monthly_maintenance'] ?? 0;
        $financing = $drRow['financing'] ?? 0;
        $financing_type = $drRow['financing_type'] ?? 'P';
        $dispositionfee = $drRow['disposition_fee'] ?? 0;
        $capitalize_starting_fee = (bool)($drRow['capitalize_starting_fee'] ?? 0);

        $free2MoveData = json_decode($drRow['free_two_move'] ?? '{}', true) ?: [];
        if ($vehicleData['fare_type'] == 'L') {
            $totalWriteDownPayment = sprintf('%0.4f', ($homenet_msrp - ($homenet_msrp * ($free2MoveData['residual_value'] ?? 0))));
        } else {
            $totalWriteDownPayment = sprintf('%0.4f', (($price * $downpaymentRate / 100) * ($goalLength / 365)));
        }

        $revshare = $this->getRevShare($ownerid);
        $diAFee = $revshare * 1;

        if ($vehicleData['fare_type'] == 'L') {
            $leasePaymentsToLessor = sprintf('%0.4f', (($free2MoveData['monthly_price'] ?? 0) * $goalLength * 12 / 365));
            $totalFinancing = sprintf('%0.4f', ($leasePaymentsToLessor - $totalWriteDownPayment));
            $vehicleCostInclRecon = $vehicleData['vehicleCostInclRecon'] ?? 0;
            $incentive = $drRow['incentive'] ?? 0;
            $doc_fee = $drRow['doc_fee'] ?? 0;
            $financing = sprintf('%0.2f', (100 * ($totalFinancing / $goalLength) / ($vehicleCostInclRecon - $incentive + $doc_fee)));
        } else {
            if ($capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = ((($price - $initial_fee) * $financing / 100) / 365) * $goalLength;
            } elseif (!$capitalize_starting_fee && $financing_type == 'P') {
                $totalFinancing = (($price * $financing / 100) / 365) * $goalLength;
            } else {
                $totalFinancing = ($financing / 365) * $goalLength;
            }
        }

        $totalProgramFee = $totalWriteDownPayment + ($maintenance * 12 * ($goalLength / 365)) + $dispositionfee + $totalFinancing;
        $totalProgramFeeWithDia = $diAFee ? sprintf('%0.4f', ($totalProgramFee * 100 / $diAFee)) : $totalProgramFee;

        if (in_array($vehicleData['fare_type'], ['D', 'L'])) {
            $dailyFee = sprintf('%0.2f', ($totalProgramFeeWithDia - $initial_fee) / ($goalLength));
        } else {
            $dailyFee = $vehicleData['day_rent'];
        }

        return ['dailyFee' => $dailyFee, 'goalLength' => (int)$goalLength];
    }

    public function getVehicleInitialFeePrepaidPlans($initialfee, $DepositRuleObj, string $currency = 'USD', string $timezone = ''): array
    {
        $drRow = is_array($DepositRuleObj) ? $DepositRuleObj : (array)$DepositRuleObj;

        $prepaid_initial_fee = $scheduleNextDays = 0;
        $prepaid_initial_fee_data = $plans = [];
        $supperinitialfee = $initialfee;

        if (!empty($drRow)) {
            $prepaid_initial_fee = $drRow['prepaid_initial_fee'] ?? 0;
            $prepaid_initial_fee_data = ($drRow['prepaid_initial_fee'] ?? 0)
                ? json_decode($drRow['prepaid_initial_fee_data'] ?? '{}', true)
                : [];
        }

        if (!empty($prepaid_initial_fee_data)) {
            $interval = $prepaid_initial_fee_data['day'] ?? 0;
            $amount = $prepaid_initial_fee_data['amount'] ?? 0;
            $supperinitialfee = $initialfee;
            if ($amount < $initialfee) {
                while (($initialfee - $amount) > 0) {
                    $scheduleNextDays = 0;
                    $initialfee = $initialfee - $amount;
                    $pending = $supperinitialfee - $initialfee;
                    while ($pending > 0) {
                        $amount = ($amount < $pending ? $amount : $pending);
                        $taxRate = $drRow['tax'] ?? 0;
                        $amountWithTax = sprintf('%0.2f', ($amount + $amount * $taxRate / 100));
                        $plans[$initialfee][] = [
                            'amount'        => $this->formatCurrency($amount, $currency),
                            'amountwithtax' => $this->formatCurrency((float)$amountWithTax, $currency),
                            'date'          => $this->formatForUser(time(), 'm/d/Y', $timezone),
                            'max_date'      => $this->formatForUser(strtotime('+' . ($scheduleNextDays + 7) . ' days'), 'm/d/Y', $timezone),
                        ];
                        $pending -= $amount;
                        $scheduleNextDays += $interval;
                    }
                }
                if (empty($plans)) {
                    $prepaid_initial_fee = $scheduleNextDays = 0;
                }
            }
        }

        $prepaid_plan_text = sprintf(
            'I want to pay my initial fee in installments. I understand the full %s must be paid prior to receiving the vehicle, but I want to reserve the vehicle now. All fees are refundable if the vehicle is not received.',
            $this->formatCurrency($supperinitialfee, $currency)
        );

        return [
            'prepaid_initial_fee'       => $prepaid_initial_fee,
            'prepaid_initial_fee_plans' => $plans,
            'prepaid_plan_text'         => $prepaid_plan_text,
        ];
    }

    public function validateInitialFeePrepaidPlan($initialfee, $DepositRuleObj, $dataValues, string $timezone = ''): array
    {
        $drRow = is_array($DepositRuleObj) ? $DepositRuleObj : (array)$DepositRuleObj;

        $plans = $dataValues->initial_fee_plan ?? [];
        $planamount = preg_replace('/[^0-9.]/', '', $dataValues->prepaid_plan_selected ?? '');
        $prepaid_initial_fee_data = $saveplans = [];
        $pending = 0;
        $maxDate = '';

        if (empty($drRow)) {
            return ['status' => 0, 'message' => 'Vehicle fare setting is not found. Please choose another vehicle.', 'result' => []];
        }

        $prepaid_initial_fee_data = ($drRow['prepaid_initial_fee'] ?? 0)
            ? json_decode($drRow['prepaid_initial_fee_data'] ?? '{}', true)
            : [];

        if (empty($prepaid_initial_fee_data)) {
            return ['status' => 0, 'message' => 'Sorry prepaid initial payment plan is not allowed for this vehicle. Please choose another vehicle.', 'result' => []];
        }
        if (empty($plans)) {
            return ['status' => 0, 'message' => 'Sorry prepaid payment plans not submitted. Please try again.', 'result' => []];
        }

        $error = false;
        $now = $this->toServerDate(time(), 'Y-m-d', $timezone);

        foreach ($plans as $plan) {
            $planamount += preg_replace('/[^0-9.]/', '', $plan->amount ?? '');
            $pending += preg_replace('/[^0-9.]/', '', $plan->amount ?? '');
            $date = $this->toServerDateFromString($plan->max_date ?? '', 'Y-m-d', $timezone);
            if ($date < $now) {
                $error = true;
                break;
            }
            if (!empty($maxDate)) {
                $maxDate = $date > $maxDate ? $date : $maxDate;
            } else {
                $maxDate = $this->toServerDate(time(), 'Y-m-d', $timezone);
            }
            $saveplans[] = [
                'date'          => $date,
                'amount'        => preg_replace('/[^0-9.]/', '', $plan->amount ?? ''),
                'amountwithtax' => preg_replace('/[^0-9.]/', '', $plan->amountwithtax ?? ''),
            ];
        }

        if ($error) {
            return ['status' => 0, 'message' => 'Sorry, selected prepaid plan date seems incorrect. Please choose date correctly.', 'result' => []];
        }
        if ($planamount != $initialfee) {
            return ['status' => 0, 'message' => 'Sorry, selected prepaid plan total amount doesnt match with required initial payment fee. Please try again.', 'result' => []];
        }

        return ['status' => 1, 'message' => 'success', 'saveplans' => $saveplans, 'maxDate' => $maxDate, 'pending' => $pending];
    }
}
