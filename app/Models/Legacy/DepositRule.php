<?php

namespace App\Models\Legacy;

use App\Services\Legacy\Passtime;
use App\Services\Legacy\PromoService;

class DepositRule extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_deposit_rules';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'initial_fee',
        'initial_event',
        'deposit_amt',
        'deposit_event',
        'deposit_type',
        'charge_rent',
        'emf',
        'emf_insu',
        'tax',
        'min_rent',
        'lateness_fee',
        'cancellation_fee',
        'insurance_fee',
        'insurance_event',
        'deposit_amt_opt',
        'total_deposit_amt',
        'initial_fee_opt',
        'total_initial_fee',
        'depreciation_rate',
        'financing',
        'financing_type',
        'monthly_maintenance',
        'disposition_fee',
        'write_down_allocation',
        'lender_fee',
        'lender_type',
        'insurance_payer',
        'insurance_lender',
        'lender_anticipated_date',
        'prepaid_initial_fee',
        'prepaid_initial_fee_data',
        'program_length',
        'capitalize_starting_fee',
        'incentive',
        'doc_fee',
        'free_two_move',
        'return_fee',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];
    protected $casts = [];

    private $_depositObj = null;

    public function calculateHourRent($hours)
    {
        if (!empty($this->_depositObj)) {
            $time_fee = $this->_depositObj->time_fee;
            $charge_rentTotal = number_format(($time_fee * $hours), 2, '.', '');
            return [
                'time_fee' => $charge_rentTotal,
                'charge_rent_event' => $this->_depositObj->charge_rent
            ];
        }

        return [
            'time_fee' => 0,
            'charge_rent_event' => 'N'
        ];
    }
    public function calculateLatenessFee($hours, $vehicleid)
    {
        if (!empty($vehicleid)) {
            $depositObj = self::where('vehicle_id', $vehicleid)->first();

            if (!empty($depositObj)) {
                $lateness_fee = $depositObj->lateness_fee;
                $lateness_feeTotal = number_format(($lateness_fee * $hours), 2, '.', '');
                return ['lateness_fee' => $lateness_feeTotal];
            }
        }

        return ['lateness_fee' => 0];
    }
    public function calculateTax($amount, $vehicleid)
    {
        if (!empty($vehicleid)) {
            $depositObj = self::where('vehicle_id', $vehicleid)->first();

            if (!empty($depositObj)) {
                return number_format((($amount * $depositObj->tax) / 100), 2, '.', '');
            }

            return 0;
        }

        return 0;
    }
    public function calculateDIAFee($amount, $userid)
    {
        $diaRate = config('legacy.DIA_FEE', 5);
        $RevSetting = RevSetting::where('user_id', $userid)->first(['dia_fee']);

        if (!empty($RevSetting)) {
            $diaRate = $RevSetting->dia_fee;
        }

        return sprintf("%0.2f", ($amount * $diaRate) / 100);
    }
    public function getVehicleInfo($vehicleid)
    {
        return Vehicle::where('id', $vehicleid)->select('rate', 'day_rent')->first();
    }
    public function getAgreementPdfCalculation($lease, $vehicleid)
    {
        $this->_depositObj = self::where('vehicle_id', $vehicleid)->first();

        if (empty($lease) || empty($this->_depositObj)) {
            return [
                'time_fee' => 0,
                'deposit_amt' => 0,
                'tax' => 0,
                'insurance_amt' => 0,
                'initial_fee' => 0,
                'days' => 1,
                'discount' => 0
            ];
        }

        $timeDiff = strtotime($lease['end_datetime']) - strtotime($lease['start_datetime']);
        $totalHours = abs($timeDiff / 3600);
        $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
        $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
        $insurance_hours = $insurance_hours > 6 ? $insurance_hours : 0;

        $totldays = $insurance_days + ($insurance_hours > 0 ? 1 : 0);
        $insurance_fee = sprintf('%0.2f', ($totldays * $lease['insurance']));
        $time_fee = sprintf('%0.2f', ($totldays * $lease['day_rent']));
        $emf_fee = sprintf('%0.2f', ($totldays * $lease['emf']));

        $totalinitialFee = $this->_depositObj->total_initial_fee;
        $initialfeeOpt = !empty($this->_depositObj->initial_fee_opt) ? json_decode($this->_depositObj->initial_fee_opt, true) : [];
        $depositFee = $this->_depositObj->deposit_amt;
        $totaldepositFee = $this->_depositObj->total_deposit_amt;
        $depositfeeOpt = !empty($this->_depositObj->deposit_amt_opt) ? json_decode($this->_depositObj->deposit_amt_opt, true) : [];

        // Apply Discount
        $promo = new PromoService();
        $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $lease['renter_id']);
        $time_fee = ($time_fee - ($discounts['rent_discount'] * $totldays));
        $discount = ($discounts['rent_discount'] * $totldays);

        $dia_fee = $this->calculateDIAFee($time_fee, $lease['user_id']);
        $tax = sprintf('%0.2f', ((($time_fee + $dia_fee) * $this->_depositObj->tax) / 100));
        $emftax = sprintf('%0.2f', (($emf_fee * $this->_depositObj->tax) / 100));
        $emf_fee = sprintf('%0.2f', ($emftax + $emf_fee));
        $return = [
            'time_fee' => ($time_fee),
            'tax' => $tax,
            'dia_fee' => $dia_fee,
            'extra_mileage_fee' => $emf_fee,
            'insurance_amt' => $insurance_fee,
            'days' => $totldays,
            'discount' => $discount
        ];
        $return['deposit_amt'] = $depositFee;
        $return['total_deposit_amt'] = $totaldepositFee;
        $return['deposit_amt_opt'] = $depositfeeOpt;
        $return['initial_fee'] = $lease['initial_fee'];
        $return['total_initial_fee'] = $totalinitialFee;
        $return['initial_fee_opt'] = $initialfeeOpt;
        $return['day_rent'] = $lease['day_rent'];
        $return['tax_rate'] = $this->_depositObj->tax;
        $return['lateness_fee'] = $this->_depositObj->lateness_fee;
        $dia_insu = $this->_depositObj->emf_insu;
        $return['dia_insu'] = $dia_insu;
        return $return;
    }
    public function getBookingChargeEvent($vehicleid)
    {
        $return = [
            'charge_rent_event' => 'N',
            'deposit_amt' => 0,
            'deposit_event' => 'N',
            'insurance_event' => 'N',
            'initial_fee' => 0,
            'initial_event' => 'P'
        ];

        $this->_depositObj = self::where('vehicle_id', $vehicleid)->first();

        if (!empty($this->_depositObj)) {
            $return = [
                'charge_rent_event' => $this->_depositObj->charge_rent,
                'deposit_amt' => $this->_depositObj->deposit_amt,
                'deposit_event' => $this->_depositObj->deposit_event,
                'deposit_type' => $this->_depositObj->deposit_type,
                'insurance_event' => $this->_depositObj->insurance_event,
                'initial_fee' => $this->_depositObj->initial_fee,
                'initial_event' => $this->_depositObj->initial_event,
            ];
        }

        return $return;
    }
    public function getCancellationFee($vehicleid)
    {
        $return = 0;
        $this->_depositObj = self::where('vehicle_id', $vehicleid)->first();

        if (!empty($this->_depositObj)) {
            $return = $this->_depositObj->cancellation_fee;
        }

        return $return;
    }
    public function getAllFee(array $CsOrder, bool $autorenew = false)
    {
        $return = [
            'estimated_rent' => $CsOrder['rent'],
            'rent' => $CsOrder['rent'],
            'tax' => $CsOrder['tax'],
            'damage_fee' => $CsOrder['damage_fee'],
            'lateness_fee' => $CsOrder['lateness_fee'],
            'uncleanness_fee' => $CsOrder['uncleanness_fee'],
            'extra_mileage_fee' => $CsOrder['extra_mileage_fee'],
            'insurance_amt' => $CsOrder['insurance_amt'],
            'end_odometer' => $CsOrder['end_odometer'],
            'initial_fee' => $CsOrder['initial_fee'],
            'initial_fee_tax' => $CsOrder['initial_fee_tax'],
            'discount' => $CsOrder['discount'],
            'dia_insu' => 0
        ];

        $CsOrder['parent_id'] = !empty($CsOrder['parent_id']) ? $CsOrder['parent_id'] : $CsOrder['id'];
        $OrderDepositRuleObj = OrderDepositRule::where('cs_order_id', $CsOrder['parent_id'])->first();
        $vhcileInfo = $this->getVehicleInfo($CsOrder['vehicle_id']);

        if (empty($vhcileInfo)) {
            return $return;
        }

        if ($autorenew) {
            $timeDiff = (!empty($CsOrder['start_timing']) && $CsOrder['start_timing'] != '0000-00-00 00:00:00')
                ? (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_timing']))
                : (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_datetime']));
        } else {
            $timeDiff = (!empty($CsOrder['start_timing']) && $CsOrder['start_timing'] != '0000-00-00 00:00:00')
                ? (time() - strtotime($CsOrder['start_timing']))
                : (time() - strtotime($CsOrder['start_datetime']));
        }

        $totalHours = abs($timeDiff / 3600);
        $rate = $vhcileInfo->rate > 0 ? $vhcileInfo->rate : 0;
        $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
        $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
        $insurance_hours = $insurance_hours > 6 ? $insurance_hours : 0;
        $totldays = $insurance_days + ($insurance_hours > 0 ? 1 : 0);

        if (!empty($OrderDepositRuleObj)) {
            $return['insurance_amt'] = number_format(($totldays * $OrderDepositRuleObj->insurance), 2, '.', '');
        }

        if (!empty($OrderDepositRuleObj) && $OrderDepositRuleObj->insurance_payer == 7) {
            $return['insurance_amt'] = 0;
        }

        $time_fee = 0;
        $day_rent = !empty($OrderDepositRuleObj)
            ? $this->getDayRentFromTierData($OrderDepositRuleObj->rental_opt, $insurance_days, $OrderDepositRuleObj->rental, $CsOrder['start_datetime'])
            : $vhcileInfo->day_rent;

        if ($day_rent > 0) {
            $time_fee = sprintf('%0.2f', ($totldays * $day_rent));
        }

        $taxRate = !empty($OrderDepositRuleObj) ? $OrderDepositRuleObj->tax : '';
        $return['rent'] = number_format($time_fee, 2, '.', '');

        $promo = new PromoService();
        $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $CsOrder['renter_id']);

        $return['rent'] = ($time_fee - ($discounts['rent_discount'] * $totldays));
        $return['discount'] = ($discounts['rent_discount'] * $totldays);
        $return['extra_mileage_fee'] = 0;
        $scheduledEndodometer = 0;

        if ($autorenew && strtotime($CsOrder['end_datetime']) < time()) {
            $scheduledEndodometer = CsTrackVehicle::getLastMileFromHistory($CsOrder['vehicle_id'], $CsOrder['end_datetime']);
        }

        $Passtime = new Passtime();
        $milesData = $Passtime->getPasstimeMiles($CsOrder['vehicle_id']);

        if ($milesData['miles'] && $milesData['allowed_miles']) {
            $return['end_odometer'] = $scheduledEndodometer ? $scheduledEndodometer : $milesData['miles'];

            if (isset($OrderDepositRuleObj->miles) && $OrderDepositRuleObj->miles > 0) {
                $billableMileage = (int) (($return['end_odometer'] - $CsOrder['start_odometer']) - ($OrderDepositRuleObj->miles * $totldays));
            } else {
                $billableMileage = ($CsOrder['start_odometer'] > 0) ? (($return['end_odometer'] - $CsOrder['start_odometer']) - ($milesData['allowed_miles'] * $totldays)) : 0;
            }

            if ($billableMileage > 0) {
                $DepositTemplate = CsDepositTemplate::where('user_id', $CsOrder['user_id'])->first(['max_extramile_fee']);
                $maxExtraMileFee = isset($DepositTemplate->max_extramile_fee) && !empty($DepositTemplate->max_extramile_fee)
                    ? $DepositTemplate->max_extramile_fee
                    : 500;
                $extra_mileage_feeTotal = isset($OrderDepositRuleObj->emf_rate)
                    ? sprintf('%0.2f', ($billableMileage * $OrderDepositRuleObj->emf_rate))
                    : 0;
                $extra_mileage_feeTotal += $return['extra_mileage_fee'];
                $return['extra_mileage_fee'] = $extra_mileage_feeTotal > $maxExtraMileFee
                    ? sprintf('%0.2f', $maxExtraMileFee)
                    : sprintf('%0.2f', $extra_mileage_feeTotal);

                $dia_insu = sprintf('%0.2f', ($billableMileage * ($OrderDepositRuleObj->emf_insu_rate ? $OrderDepositRuleObj->emf_insu_rate : 0)));
                $return['dia_insu'] = $dia_insu > $maxExtraMileFee ? sprintf('%0.2f', $maxExtraMileFee) : $dia_insu;
            }
        }

        $dia_fee = $this->calculateDIAFee($return['rent'], $CsOrder['user_id']);
        $tax = (($return['rent'] + $dia_fee) * $taxRate) / 100;
        $return['tax'] = number_format($tax, 2, '.', '');
        $return['initial_fee_tax'] = number_format(($return['initial_fee'] * $taxRate / 100), 2, '.', '');
        $return['emf_tax'] = number_format((($return['extra_mileage_fee'] * $taxRate) / 100), 2, '.', '');
        $return['dia_fee'] = $dia_fee;
        $return['days'] = $totldays;
        return $return;
    }
    public function calculateRentTaxOnComplete(array $CsOrder)
    {
        $return = [
            'rent' => $CsOrder['rent'],
            'tax' => $CsOrder['tax'],
            'end_odometer' => $CsOrder['end_odometer'],
            'extra_mileage_fee' => $CsOrder['extra_mileage_fee'],
            'dia_insu' => 0,
            'discount' => $CsOrder['discount'],
            'insurance_amt' => $CsOrder['insurance_amt']
        ];

        $vhcileInfo = $this->getVehicleInfo($CsOrder['vehicle_id']);
        $CsOrder['parent_id'] = !empty($CsOrder['parent_id']) ? $CsOrder['parent_id'] : $CsOrder['id'];
        $OrderDepositRuleObj = OrderDepositRule::where('cs_order_id', $CsOrder['parent_id'])->first();

        if (empty($vhcileInfo)) {
            return $return;
        }

        $timeDiff = (!empty($CsOrder['start_timing']) && $CsOrder['start_timing'] != '0000-00-00 00:00:00')
            ? (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_timing']))
            : (strtotime($CsOrder['end_datetime']) - strtotime($CsOrder['start_datetime']));
        $totalHours = abs($timeDiff / 3600);
        $rate = $vhcileInfo->rate > 0 ? $vhcileInfo->rate : 0;
        $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
        $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
        $totldays = $insurance_days + ($insurance_hours > 2 ? 1 : 0);

        if (!empty($OrderDepositRuleObj)) {
            $return['insurance_amt'] = number_format(($totldays * $OrderDepositRuleObj->insurance), 2, '.', '');
        }

        if (!empty($OrderDepositRuleObj) && $OrderDepositRuleObj->insurance_payer == 7) {
            $return['insurance_amt'] = 0;
        }

        $totalDays = $insurance_days;

        if (!empty($OrderDepositRuleObj)) {
            $tDiff = strtotime($CsOrder['end_datetime']) - (!empty($OrderDepositRuleObj->start_datetime)
                ? strtotime($OrderDepositRuleObj->start_datetime)
                : strtotime($OrderDepositRuleObj->created));
            $tHours = abs($tDiff / 3600);
            $totalDays = $tHours < 24 ? 1 : floor($tHours / 24);
        }

        $day_rent = !empty($OrderDepositRuleObj)
            ? $this->getDayRentFromTierData($OrderDepositRuleObj->rental_opt, $totalDays, $OrderDepositRuleObj->rental, $CsOrder['start_datetime'])
            : $vhcileInfo->day_rent;

        if ($day_rent > 0) {
            $time_feeDays = ($insurance_days * $day_rent);
            $time_feehours = 0;

            if ($insurance_hours > 6) {
                $time_feehours = $day_rent;
            }

            $time_fee = number_format(($time_feeDays + $time_feehours), 2, '.', '');
        } else {
            $time_fee = number_format(($totalHours * $rate), 2, '.', '');
        }

        $taxRate = !empty($OrderDepositRuleObj) ? $OrderDepositRuleObj->tax : 0;

        $totaldays = $insurance_days + ($insurance_hours > 6 ? 1 : 0);
        $return['extra_mileage_fee'] = 0;
        $scheduledEndodometer = 0;

        if (strtotime($CsOrder['end_datetime']) < time()) {
            $scheduledEndodometer = CsTrackVehicle::getLastMileFromHistory($CsOrder['vehicle_id'], $CsOrder['end_datetime']);
        }

        $Passtime = new Passtime();
        $milesData = $Passtime->getPasstimeMiles($CsOrder['vehicle_id']);

        $return['end_odometer'] = $scheduledEndodometer ? $scheduledEndodometer : $milesData['miles'];

        if (isset($OrderDepositRuleObj->miles) && $OrderDepositRuleObj->miles > 0) {
            $billableMileage = (int) (($return['end_odometer'] - $CsOrder['start_odometer']) - ($OrderDepositRuleObj->miles * $totaldays));
        } else {
            $billableMileage = ($CsOrder['start_odometer'] > 0)
                ? (($return['end_odometer'] - $CsOrder['start_odometer']) - ($milesData['allowed_miles'] * $totaldays))
                : 0;
        }

        if ($billableMileage > 0) {
            $DepositTemplate = CsDepositTemplate::where('user_id', $CsOrder['user_id'])->first(['max_extramile_fee']);
            $maxExtraMileFee = isset($DepositTemplate->max_extramile_fee) && !empty($DepositTemplate->max_extramile_fee) ? $DepositTemplate->max_extramile_fee : 500;

            $extra_mileage_feeTotal = sprintf('%0.2f', ($billableMileage * $OrderDepositRuleObj->emf_rate));
            $extra_mileage_feeTotal += $return['extra_mileage_fee'];
            $return['extra_mileage_fee'] = $extra_mileage_feeTotal > $maxExtraMileFee
                ? sprintf('%0.2f', $maxExtraMileFee)
                : sprintf('%0.2f', $extra_mileage_feeTotal);

            $dia_insu = sprintf('%0.2f', ($billableMileage * $OrderDepositRuleObj->emf_insu_rate));
            $return['dia_insu'] = $dia_insu > $maxExtraMileFee ? sprintf('%0.2f', $maxExtraMileFee) : $dia_insu;
        }

        $promo = new PromoService();
        $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $CsOrder['renter_id']);

        $return['rent'] = ($time_fee - ($discounts['rent_discount'] * $totaldays));
        $return['discount'] = ($discounts['rent_discount'] * $totaldays);

        $dia_fee = $this->calculateDIAFee($return['rent'], $CsOrder['user_id']);

        $tax = sprintf('%0.2f', ((($return['rent'] + $dia_fee) * $taxRate) / 100));

        $return['tax'] = $tax;
        $return['emf_tax'] = sprintf('%0.2f', (($return['extra_mileage_fee'] * $taxRate) / 100));
        $return['dia_fee'] = $dia_fee;
        $return['insurance_payer'] = $OrderDepositRuleObj->insurance_payer;
        $return['order_rule_id'] = $OrderDepositRuleObj->id;

        return $return;
    }
    public function getQuoteFee($lease, $vehicleid)
    {
        $return = [
            'rent' => 0,
            'rent_days' => 0,
            'rent_hours' => 0,
            'rent_des' => '',
            'deposit_amt' => 0,
            'deposit_amt_des' => '*One time and refundable ',
            'tax' => 0,
            'tax_des' => '',
            'insurance_amt' => 0,
            'insurance_amt_des' => 'One time for a booking',
            'initial_fee' => 0,
            'initial_fee_des' => '*once per booking',
            'dia_fee' => 0
        ];

        $this->_depositObj = self::where('vehicle_id', $vehicleid)->first();
        $vhcileInfo = $this->getVehicleInfo($vehicleid);

        if (!empty($this->_depositObj) || !empty($vhcileInfo)) {

            $timeDiff = strtotime($lease['end_datetime']) - strtotime($lease['start_datetime']);
            $totalHours = abs($timeDiff / 3600);
            $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $return['rent_days'] = $insurance_days;
            $insuranceofdays = ($insurance_days * $this->_depositObj->insurance_fee);
            $insurance_hours_fee = 0;

            if ($insurance_hours) {
                $insurance_hours_fee = $this->_depositObj->insurance_fee;
            }

            $insurance_fee = sprintf('%0.2f', ($insuranceofdays + $insurance_hours_fee));

            if ($vhcileInfo->day_rent > 0) {
                $time_feeDays = ($insurance_days * $vhcileInfo->day_rent);
                $time_feehours = 0;

                if ($insurance_hours) {
                    $time_feehours = $vhcileInfo->day_rent;
                    $return['rent_days'] = $return['rent_days'] + 1;
                }

                $return['rent_des'] = $return['rent_days'] . ' days X $' . $vhcileInfo->day_rent;
                $time_fee = sprintf('%0.2f', ($time_feeDays + $time_feehours));
            } else {
                $time_fee = sprintf('%0.2f', ($totalHours * $vhcileInfo->rate));
                $return['rent_hours'] = $insurance_hours;
                $return['rent_des'] = $totalHours . ' Hrs X $' . $vhcileInfo->rate;
            }

            $initialFee = $this->_depositObj->initial_fee;
            $totalinitialFee = $this->_depositObj->total_initial_fee;
            $initialfeeOpt = !empty($this->_depositObj->initial_fee_opt) ? json_decode($this->_depositObj->initial_fee_opt, true) : [];
            $depositFee = $this->_depositObj->deposit_amt;
            $totaldepositFee = $this->_depositObj->total_deposit_amt;
            $depositfeeOpt = !empty($this->_depositObj->deposit_amt_opt) ? json_decode($this->_depositObj->deposit_amt_opt, true) : [];

            $return['insurance_amt'] = $insurance_fee;
            $return['insurance_amt_des'] = $return['rent_days'] . ' days X $' . number_format($insurance_fee / $return['rent_days'], 2);

            if ($this->_depositObj->insurance_payer == 1 || $this->_depositObj->insurance_payer == 3) {
                $return['insurance_amt'] = 0;
                $return['insurance_amt_des'] = '';
            }
            $return['initial_fee'] = $initialFee;
            $return['total_initial_fee'] = $totalinitialFee;
            $return['initial_fee_opt'] = $initialfeeOpt;
            // Apply Discount
            $promo = new PromoService();
            $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $lease['renter_id']);
            $return['rent'] = ($time_fee - ($discounts['rent_discount'] * $insurance_days));
            $return['discount'] = ($discounts['rent_discount'] * $insurance_days);

            $return['surcharge'] = 0;
            $dia_fee = $this->calculateDIAFee($return['rent'], $lease['user_id']);
            $tax = sprintf('%0.2f', ((($return['rent'] + $dia_fee) * $this->_depositObj->tax) / 100));

            $return['rent_opt'] = [];
            $return['deposit_amt'] = $depositFee;
            $return['total_deposit_amt'] = $totaldepositFee;
            $return['deposit_amt_opt'] = $depositfeeOpt;
            $return['tax'] = $tax;
            $return['dia_fee'] = $dia_fee;

            $return['total_rent'] = (float) sprintf('%0.2f', ($return['rent'] + $tax + $dia_fee));
            $return['total_payable_today'] = (float) sprintf('%0.2f', ($return['rent'] + $tax + $dia_fee + $insurance_fee + $return['deposit_amt'] + $return['initial_fee']));
            $return['final_payable_today'] = (float) sprintf('%0.2f', ($return['deposit_amt'] + $return['initial_fee']));
            $return['rental_options'] = [];
            $return['initial_fee_options'] = [];
            $return['convert_to_ownership'] = '0 days';
            $return['estimate_rate_after'] = 'Estimated rate/day after 0days: 0/day';
        }

        return $return;
    }
    public function getInsuranceFee(array $CsOrder, $insurance_days, $insurance_fee = '')
    {
        $return = [
            'time_fee' => $CsOrder['rent'],
            'charge_rent_event' => 'N',
            'deposit_amt' => $CsOrder['deposit'],
            'deposit_event' => 'N',
            'deposit_type' => 'P',
            'tax' => 0,
            'insurance_amt' => 0,
            'insurance_event' => 'N',
            'initial_fee' => $CsOrder['initial_fee'],
            'initial_event' => 'P',
            'discount' => 0,
            'dia_fee' => isset($CsOrder['dia_fee']) ? $CsOrder['dia_fee'] : 0
        ];

        $this->_depositObj = self::where('vehicle_id', $CsOrder['vehicle_id'])->first();

        if (!empty($this->_depositObj)) {

            if (empty($insurance_fee)) {
                $insurance_fee = number_format(($insurance_days * $this->_depositObj->insurance_fee), 2, '.', '');
            }

            $CsOrder['dia_fee'] = $this->calculateDIAFee($CsOrder['rent'], $CsOrder['user_id']);
            $tax = sprintf('%0.2f', ((($CsOrder['rent'] + $CsOrder['dia_fee']) * $this->_depositObj->tax) / 100));

            $emf_insu_rate = $this->_depositObj->emf_insu;
            $dia_insu = $emf_insu_rate;
            $emf_rate = $this->_depositObj->emf;

            $return = [
                'time_fee' => $CsOrder['rent'],
                'charge_rent_event' => $this->_depositObj->charge_rent,
                'deposit_amt' => $CsOrder['deposit'],
                'deposit_event' => $this->_depositObj->deposit_event,
                'deposit_type' => $this->_depositObj->deposit_type,
                'tax' => $tax,
                'insurance_amt' => sprintf('%0.2f', $insurance_fee),
                'insurance_event' => $this->_depositObj->insurance_event,
                'initial_fee' => $CsOrder['initial_fee'],
                'initial_event' => $this->_depositObj->initial_event,
                'days' => $insurance_days,
                'discount' => 0,
                'dia_fee' => $CsOrder['dia_fee'],
                'lateness_fee' => $this->_depositObj->lateness_fee,
                'tax_rate' => $this->_depositObj->tax,
                'dia_insu' => $dia_insu,
                'extra_mileage_fee' => $emf_rate,
                'emf_tax' => $CsOrder['emf_tax'],
                'emf_insu_rate' => $emf_insu_rate,
                'insurance_payer' => $this->_depositObj->insurance_payer,
                'insurance_lender' => $this->_depositObj->insurance_lender,
                'initial_fee_tax' => sprintf('%0.2f', ($CsOrder['initial_fee'] * $this->_depositObj->tax / 100))
            ];
        }

        return $return;
    }
    public function getFeeRenewBooking(array $lease, int $parentId)
    {
        $return = [
            'time_fee' => 0,
            'charge_rent_event' => 'N',
            'deposit_amt' => $lease['deposit'],
            'deposit_event' => 'N',
            'deposit_type' => 'P',
            'tax' => 0,
            'insurance_amt' => 0,
            'insurance_event' => 'N',
            'initial_fee' => 0,
            'initial_event' => 'P',
            'days' => 1,
            'discount' => 0
        ];

        $OrderDepositRuleObj = OrderDepositRule::where('cs_order_id', $parentId)->first();
        $this->_depositObj = self::where('vehicle_id', $lease['vehicle_id'])->first();
        $vhcileInfo = $this->getVehicleInfo($lease['vehicle_id']);

        if (empty($this->_depositObj) || empty($vhcileInfo)) {
            return $return;
        }

        $timeDiff = strtotime($lease['end_datetime']) - strtotime($lease['start_datetime']);
        $totalHours = abs($timeDiff / 3600);
        $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
        $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
        $totldays = $insurance_days + ($insurance_hours > 6 ? 1 : 0);

        if (!empty($OrderDepositRuleObj)) {
            $insurance_fee = number_format(($totldays * $OrderDepositRuleObj->insurance), 2, '.', '');
        } else {
            $insuranceofdays = ($insurance_days * $this->_depositObj->insurance_fee);
            $insurance_hours_fee = 0;

            if ($insurance_hours) {
                $insurance_hours_fee = $this->_depositObj->insurance_fee;
            }

            $insurance_fee = number_format(($insuranceofdays + $insurance_hours_fee), 2, '.', '');
        }

        if (!empty($OrderDepositRuleObj) && $OrderDepositRuleObj->insurance_payer == 7) {
            $insurance_fee = 0;
        }

        $totalDays = $insurance_days;

        if (!empty($OrderDepositRuleObj)) {
            $tDiff = strtotime($lease['end_datetime']) - (!empty($OrderDepositRuleObj->start_datetime)
                ? strtotime($OrderDepositRuleObj->start_datetime)
                : strtotime($OrderDepositRuleObj->created));
            $tHours = abs($tDiff / 3600);
            $totalDays = $tHours < 24 ? 1 : floor($tHours / 24);
        }

        $day_rent = !empty($OrderDepositRuleObj)
            ? $this->getDayRentFromTierData($OrderDepositRuleObj->rental_opt, $totalDays, $OrderDepositRuleObj->rental, $lease['start_datetime'])
            : $vhcileInfo->day_rent;

        if ($day_rent > 0) {
            $time_feeDays = ($totldays * $day_rent);
            $time_fee = sprintf('%0.2f', $time_feeDays);
        } else {
            $time_fee = number_format(($totalHours * $vhcileInfo->rate), 2, '.', '');
        }

        // Apply Discount
        $promo = new PromoService();
        $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $lease['renter_id']);
        $time_fee = ($time_fee - ($discounts['rent_discount'] * $insurance_days));
        $discount = ($discounts['rent_discount'] * $insurance_days);

        $taxRate = !empty($OrderDepositRuleObj) ? $OrderDepositRuleObj->tax : $this->_depositObj->tax;
        $emf = 0;
        $dia_fee = $this->calculateDIAFee($time_fee, $lease['user_id']);
        $tax = sprintf('%0.2f', ((($time_fee + $dia_fee) * $taxRate) / 100));
        $emftax = 0;
        return [
            'time_fee' => $time_fee,
            'charge_rent_event' => $this->_depositObj->charge_rent,
            'deposit_event' => $this->_depositObj->deposit_event,
            'deposit_type' => $this->_depositObj->deposit_type,
            'tax' => $tax,
            'dia_fee' => $dia_fee,
            'extra_mileage_fee' => 0,
            'emf_tax' => $emftax,
            'insurance_amt' => $insurance_fee,
            'insurance_event' => $this->_depositObj->insurance_event,
            'initial_event' => $this->_depositObj->initial_event,
            'days' => $insurance_days,
            'discount' => $discount,
            'deposit_amt' => $lease['deposit'],
            'insurance_payer' => $OrderDepositRuleObj->insurance_payer,
            'order_rule_id' => $OrderDepositRuleObj->id
        ];
    }
    public function getDayRentFromTierData($tierData, $days, $retrun, $startdatetime)
    {
        $ruleObj = json_decode($tierData, 1);

        if (empty($ruleObj)) {
            return $retrun;
        }

        foreach ($ruleObj as $rlObj) {
            if (
                (isset($rlObj['after_day']) && !empty($rlObj['after_day']) && $days > $rlObj['after_day']) ||
                (isset($rlObj['after_day_date']) && !empty($rlObj['after_day_date']) && strtotime($startdatetime) > strtotime($rlObj['after_day_date']))
            ) {
                $retrun = $rlObj['amount'];
                break;
            }
        }

        return $retrun;
    }
    public function getPendingBookingFee($lease, $OrderDepositRuleObj)
    {
        $return = [
            'time_fee' => 0,
            'charge_rent_event' => 'N',
            'deposit_amt' => 0,
            'deposit_event' => 'N',
            'deposit_type' => 'P',
            'tax' => 0,
            'insurance_amt' => 0,
            'insurance_event' => 'N',
            'initial_fee' => 0,
            'initial_event' => 'P',
            'days' => 1,
            'discount' => 0
        ];

        $vehicleId = is_array($lease) ? $lease['vehicle_id'] : $lease->vehicle_id;
        $depositObj = self::where('vehicle_id', $vehicleId)->first();

        if (!empty($depositObj)) {
            $startDatetime = is_array($lease) ? $lease['start_datetime'] : $lease->start_datetime;
            $endDatetime = is_array($lease) ? $lease['end_datetime'] : $lease->end_datetime;
            $renterId = is_array($lease) ? $lease['renter_id'] : $lease->renter_id;
            $userId = is_array($lease) ? $lease['user_id'] : $lease->user_id;

            $timeDiff = strtotime($endDatetime) - strtotime($startDatetime);
            $totalHours = abs($timeDiff / 3600);
            $insurance_days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $insurance_hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $totldays = $insurance_days + ($insurance_hours > 6 ? 1 : 0);

            $odrInsurance = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['insurance'] ?? 0) : ($OrderDepositRuleObj->insurance ?? 0);
            $odrRental = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['rental'] ?? 0) : ($OrderDepositRuleObj->rental ?? 0);
            $odrTax = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['tax'] ?? 0) : ($OrderDepositRuleObj->tax ?? 0);
            $odrEmfRate = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['emf_rate'] ?? 0) : ($OrderDepositRuleObj->emf_rate ?? 0);
            $odrEmfInsuRate = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['emf_insu_rate'] ?? 0) : ($OrderDepositRuleObj->emf_insu_rate ?? 0);
            $odrInitialFee = is_array($OrderDepositRuleObj) ? ($OrderDepositRuleObj['initial_fee'] ?? 0) : ($OrderDepositRuleObj->initial_fee ?? 0);

            if (!empty($OrderDepositRuleObj)) {
                $insurance_fee = number_format(($totldays * $odrInsurance), 2, '.', '');
            } else {
                $insuranceofdays = ($insurance_days * $depositObj->insurance_fee);
                $insurance_hours_fee = 0;

                if ($insurance_hours) {
                    $insurance_hours_fee = $depositObj->insurance_fee;
                }

                $insurance_fee = number_format(($insuranceofdays + $insurance_hours_fee), 2, '.', '');
            }

            $time_fee = number_format(($totldays * $odrRental), 2, '.', '');
            $emf = $discount = 0;

            // Apply Discount on rental fee
            if ($time_fee > 0) {
                $promo = new PromoService();
                $discounts = $promo->useRentalPromoCode(["rent" => $time_fee], $renterId);
                $time_fee = ($time_fee - ($discounts['rent_discount'] * $totldays));
                $discount = ($discounts['rent_discount'] * $totldays);
            }

            $dia_fee = $this->calculateDIAFee($time_fee, $userId);
            $taxRate = $odrTax;
            $tax = sprintf('%0.2f', ((($time_fee + $dia_fee) * $taxRate) / 100));
            $emftax = sprintf('%0.2f', (($emf * $taxRate) / 100));

            $return = [
                'time_fee' => $time_fee,
                'charge_rent_event' => $depositObj->charge_rent,
                'deposit_event' => $depositObj->deposit_event,
                'deposit_type' => $depositObj->deposit_type,
                'tax' => $tax,
                'dia_fee' => $dia_fee,
                'extra_mileage_fee' => $emf,
                'emf_tax' => $emftax,
                'insurance_amt' => $insurance_fee,
                'insurance_event' => $depositObj->insurance_event,
                'initial_event' => $depositObj->initial_event,
                'days' => $totldays,
                'discount' => $discount,
                'emf' => (!empty($OrderDepositRuleObj) ? $odrEmfRate : 0),
                'dia_insu' => (!empty($OrderDepositRuleObj) ? $odrEmfInsuRate : 0),
                'initial_fee_tax' => (!empty($OrderDepositRuleObj) ? sprintf('%0.2f', ($odrInitialFee * $odrTax / 100)) : 0),
            ];
        }

        return $return;
    }
    public function getInsuranceWithFactor($driverid, $dealerid, $amt)
    {
        return $amt;
    }
    public function calculateInsuranceFactor($age, $amt, $factorjson)
    {
        return $amt;
    }
}
