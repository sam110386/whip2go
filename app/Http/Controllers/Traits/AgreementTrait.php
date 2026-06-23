<?php

namespace App\Http\Controllers\Traits;

use App\Helpers\Legacy\Security;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\User;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Services\Legacy\Agreement;
use App\Services\Legacy\Common as CommonService;
use App\Services\Legacy\SignatureService;
use Carbon\Carbon;

/**
 * Ported from CakePHP app/Controller/Traits/AgreementTrait.php
 */
trait AgreementTrait
{
    public function _getagreementForCompletedBooking(array $CsLeaselists, bool $force = false): array
    {
        $odometer = $CsLeaselists['start_odometer'];
        $filename = $CsLeaselists['increment_id'] . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        if (!$force && file_exists($filefullname)) {
            return [
                'status' => true,
                'message' => 'Success',
                'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]
            ];
        }

        if ($CsLeaselists['parent_id']) {
            $parentOrder = CsOrder::where('id', $CsLeaselists['parent_id'])
                ->select('start_datetime', 'start_odometer', 'increment_id')
                ->first();

            $parent_datetime = $this->formatForUser($parentOrder->start_datetime, 'Y-m-d H:i:s', $CsLeaselists['timezone']);
            $odometer = $parentOrder->start_odometer;
            $parentfilename = $parentOrder->increment_id . '.pdf';
            $parentfilefullname = public_path('files/agreements/' . $parentfilename);

            if (!$force && file_exists($parentfilefullname)) {
                return [
                    'status' => true,
                    'message' => 'Success',
                    'result' => ['file' => config('app.url') . '/files/agreements/' . $parentfilename]
                ];
            }
        } else {
            $parent_datetime = $this->formatForUser($CsLeaselists['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);
        }

        $start_datetime = $this->formatForUser($CsLeaselists['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);
        $end_datetime = !empty($CsLeaselists['end_timing'])
            ? $this->formatForUser($CsLeaselists['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['timezone'])
            : $this->formatForUser($CsLeaselists['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);

        $orderIdForRule = !empty($CsLeaselists['parent_id'])
            ? $CsLeaselists['parent_id']
            : $CsLeaselists['id'];

        $OrderDepositRule = OrderDepositRule::where('cs_order_id', $orderIdForRule)->first();
        $odr = $OrderDepositRule ? $OrderDepositRule->toArray() : [];

        $days = (new CommonService())->days_between_dates($CsLeaselists['start_datetime'], $CsLeaselists['end_datetime']);

        $DepositRuleObj = DepositRule::where('vehicle_id', $CsLeaselists['vehicle_id'])
            ->select('lateness_fee', 'return_fee', 'financing', 'financing_type')
            ->first();

        $late_fee = $DepositRuleObj->lateness_fee ?? 0;

        $depositTemplateObj = DepositTemplate::where('user_id', $CsLeaselists['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();

        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $days = $days ?: 1;
        $vehicle = [];
        $vehicle['Vehicle'] = $CsLeaselists['vehicle'];
        $vehicle['Owner'] = $CsLeaselists['owner'];
        $vehicle['time_fee'] = $CsLeaselists['rent'];
        $vehicle['tax'] = $CsLeaselists['tax'];
        $vehicle['dia_fee'] = $CsLeaselists['dia_fee'];
        $vehicle['msrp'] = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['deposit_amt'] = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee'] = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu'] = $odr['emf_insu_rate'] ?? 0;
        $vehicle['daily_insurance'] = sprintf('%0.2f', ($CsLeaselists['insurance_amt'] / $days));
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (30 * $vehicle['daily_insurance']));
        $vehicle['weekly_insurance'] = sprintf('%0.2f', (7 * $vehicle['daily_insurance']));

        $userObj = User::where('id', $CsLeaselists['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'dob', 'currency')
            ->first();
        $userArr = $userObj ? $userObj->toArray() : [];

        $userArr['licence_number'] = Security::decrypt($userArr['licence_number']);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent'] = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today'] = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter'] = $userArr;
        $vehicle['Vehicle']['plate_number'] = empty($vehicle['Vehicle']['plate_number']) ? '--' : $vehicle['Vehicle']['plate_number'];
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee'] = sprintf('%0.2f', $late_fee);
        $vehicle['schedulePayment'] = (new CommonService())->makeDateInOption(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles'] = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles'] = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles'] = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days'] = $days;
        $vehicle['day_rent'] = sprintf('%0.2f', ($totalRent / $days));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($totalRent / $days) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($totalRent / $days) * 365 / 12));
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + ($CsLeaselists['insurance_amt']) + $CsLeaselists['initial_fee']));

        if (
            !empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            $vehicle['OwnerSign'] = (new SignatureService())->createSignature($vehicle['Owner']['id'], $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name']);
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['id']) . '.png';
        }

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name'];

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        $vehicle['RenterSign'] = (new SignatureService())->createSignature($userArr['id'], $userArr['first_name'] . ' ' . $userArr['last_name']);
        $RenterSign = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['RenterSign'] = $RenterSign;
        $vehicle['support_phone'] = config('legacy.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'];
        $vehicle['currency'] = $CsLeaselists['currency'];
        $vehicle['financing'] = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;
        $vehicle['DepositRule'] = $DepositRuleObj ? (array) $DepositRuleObj : [];
        $vehicle['odometer'] = $odometer;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);
        $nextBookingDuration = !empty($odr['duration_opt'])
            ? OrderDepositRule::getFromTierData($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));
        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        (new Agreement())->generateQuoteAgreementPdf($vehicle, $filefullname);

        return [
            'status' => true,
            'message' => 'Success',
            'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]
        ];
    }
    public function _getPendingBookingAgreement($booking_id): array
    {
        $booking = VehicleReservation::with([
            'vehicle',
            'owner:id,first_name,last_name,company_address,company_city,company_state,company_zip,timezone,distance_unit,company_name,representative_name,representative_role,representative_sign,contact_number'
        ])->find($booking_id);

        if (empty($booking)) {
            return [
                'status' => false,
                'message' => 'Sorry, you are not authorized for this booking.',
                'result' => []
            ];
        }

        $reservation = $booking->toArray();
        $start_datetime = $parent_datetime = $this->formatForUser($reservation['start_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);
        $end_datetime = $this->formatForUser($reservation['end_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);
        $OrderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $reservation['id'])->first();
        $odr = $OrderDepositRule ? $OrderDepositRule->toArray() : [];
        $days = (new CommonService())->days_between_dates($reservation['start_datetime'], $reservation['end_datetime']);
        $depositTemplateObj = DepositTemplate::where('user_id', $reservation['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;
        $tbd = false;

        if (!empty($odr) && in_array($odr['insurance_payer'] ?? 0, [3, 4, 5, 6, 7])) {
            $tbd = true;
        }

        $Temp = [
            'rent' => sprintf('%0.2f', (($odr['rental'] ?? 0) * $days)),
            'user_id' => $reservation['user_id'],
            'vehicle_id' => $reservation['vehicle_id'],
            'insurance' => $odr['insurance'] ?? 0,
            'renter_id' => $reservation['renter_id'],
            'pto' => $reservation['pto'] ?? 0,
        ];

        $priceRulesAmt = (new DepositRule())->getInsuranceFee($Temp, $days, $odr['insurance']);

        $vehicle = [];
        $vehicle['Vehicle'] = $reservation['vehicle'];
        $vehicle['Owner'] = $reservation['owner'];
        $vehicle['time_fee'] = $Temp['rent'];
        $vehicle['tax'] = 0;
        $vehicle['dia_fee'] = 0;
        $vehicle['deposit_amt'] = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee'] = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu'] = $odr['emf_insu_rate'] ?? 0;
        $vehicle['msrp'] = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * ($odr['insurance'] ?? 0))) : 'TBD';
        $vehicle['daily_insurance'] = !$tbd ? sprintf('%0.2f', ($odr['insurance'] ?? 0)) : 'TBD';
        $vehicle['weekly_insurance'] = !$tbd ? sprintf('%0.2f', (7 * ($odr['insurance'] ?? 0))) : 'TBD';

        $userObj = User::where('id', $reservation['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'dob')
            ->first();
        $userArr = $userObj ? $userObj->toArray() : [];
        $userArr['licence_number'] = Security::decrypt($userArr['licence_number'] ?? '');

        $filename = $reservation['id'] . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent'] = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today'] = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter'] = $userArr;
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee'] = '0.00';
        $vehicle['schedulePayment'] = (new CommonService())->makeDateInOption(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles'] = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles'] = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles'] = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days'] = $days;
        $vehicle['day_rent'] = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * ($odr['insurance'] ?? 0)) + ($odr['initial_fee'] ?? 0)));

        if (
            !empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            $vehicle['OwnerSign'] = (new SignatureService())->createSignature($vehicle['Owner']['owner_id'] ?? $vehicle['Owner']['id'], ($vehicle['Owner']['owner_first_name'] ?? ($vehicle['Owner']['first_name'] ?? '')) . ' ' . ($vehicle['Owner']['owner_last_name'] ?? ($vehicle['Owner']['last_name'] ?? '')));
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['owner_id'] ?? $vehicle['Owner']['id']) . '.png';
        }

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : ($vehicle['Owner']['owner_first_name'] ?? ($vehicle['Owner']['first_name'] ?? '')) . ' ' . ($vehicle['Owner']['owner_last_name'] ?? ($vehicle['Owner']['last_name'] ?? ''));

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        $vehicle['RenterSign'] = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['support_phone'] = config('legacy.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency'] = $userArr['currency'] ?? '$';
        $vehicle['financing'] = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;

        $DepositRuleObj = DepositRule::where('vehicle_id', $reservation['vehicle_id'])
            ->select('financing', 'financing_type', 'return_fee')
            ->first();
        $vehicle['DepositRule'] = $DepositRuleObj ? $DepositRuleObj->toArray() : [];
        $vehicle['odometer'] = $reservation['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);
        $vehicle['tbd'] = $tbd;

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? OrderDepositRule::getFromTierData($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename, 'filepath' => $filefullname]];
    }


    public function _generateAgreementForBooking(array $CsLeaselists, bool $force = false): array
    {
        $filename = $CsLeaselists['increment_id'] . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        if (!$force && file_exists($filefullname)) {
            return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
        }

        $odometer = $CsLeaselists['start_odometer'];
        $orderIdForRule = !empty($CsLeaselists['parent_id'])
            ? $CsLeaselists['parent_id']
            : $CsLeaselists['id'];

        $OrderDepositRule = OrderDepositRule::where('cs_order_id', $orderIdForRule)->first();
        $odr = $OrderDepositRule ? $OrderDepositRule->toArray() : [];

        $start_datetime = $this->formatForUser($CsLeaselists['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);
        $end_datetime = !empty($CsLeaselists['end_timing'])
            ? $this->formatForUser($CsLeaselists['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['timezone'])
            : $this->formatForUser($CsLeaselists['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);

        $days = (new CommonService())->days_between_dates($CsLeaselists['start_datetime'], $CsLeaselists['end_datetime']);
        $parent_datetime = $this->formatForUser($CsLeaselists['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);
        $increment_id = $CsLeaselists['increment_id'];

        if ($CsLeaselists['parent_id']) {
            $parentOrder = CsOrder::where('id', $CsLeaselists['parent_id'])
                ->select('increment_id', 'start_datetime', 'end_timing', 'end_datetime', 'start_odometer')
                ->first();

            $parent_datetime = $this->formatForUser($parentOrder->start_datetime, 'Y-m-d H:i:s', $CsLeaselists['timezone']);
            $odometer = $parentOrder->start_odometer;

            $parentfilename = $parentOrder->increment_id . '.pdf';
            $parentfilefullname = public_path('files/agreements/' . $parentfilename);
            if (!$force && file_exists($parentfilefullname)) {
                return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $parentfilename]];
            }
            if (($odr['financing'] ?? 0) == 1) {
                $start_datetime = $parent_datetime;
                $end_datetime = !empty($CsLeaselists['end_timing'])
                    ? $this->formatForUser($CsLeaselists['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['timezone'])
                    : $this->formatForUser($CsLeaselists['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['timezone']);
                $increment_id = $parentOrder->increment_id;
            }
        }

        $depositTemplateObj = DepositTemplate::where('user_id', $CsLeaselists['VehicleReservation']['user_id'] ?? $CsLeaselists['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        // TODO: Replace with DepositRule service – getInsuranceFee()
        $Temp = [
            'rent' => sprintf('%0.2f', (($odr['rental'] ?? 0) * $days)),
            'user_id' => $CsLeaselists['user_id'],
            'vehicle_id' => $CsLeaselists['vehicle_id'],
            'insurance' => $odr['insurance'] ?? 0,
            'renter_id' => $CsLeaselists['renter_id'],
            'pto' => $CsLeaselists['pto'] ?? 0,
        ];

        $vehicle = [];
        $vehicle['Vehicle'] = $CsLeaselists['vehicle'];
        $vehicle['Owner'] = $CsLeaselists['owner'];
        $vehicle['time_fee'] = $Temp['rent'];
        $vehicle['tax'] = 0;
        $vehicle['dia_fee'] = 0;
        $vehicle['deposit_amt'] = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee'] = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['msrp'] = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['dia_insu'] = $odr['emf_insu_rate'] ?? 0;
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (($odr['insurance'] ?? 0) * 365 / 12));
        $vehicle['daily_insurance'] = sprintf('%0.2f', ($odr['insurance'] ?? 0));
        $vehicle['weekly_insurance'] = sprintf('%0.2f', (7 * ($odr['insurance'] ?? 0)));

        $userObj = User::where('id', $CsLeaselists['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'dob')
            ->first();
        $userArr = $userObj ? $userObj->toArray() : [];
        $userArr['licence_number'] = Security::decrypt($userArr['licence_number'] ?? '');

        $filename = $increment_id . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent'] = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today'] = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter'] = $userArr;
        $vehicle['Vehicle']['plate_number'] = empty($vehicle['Vehicle']['plate_number']) ? '--' : $vehicle['Vehicle']['plate_number'];
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee'] = '0.00';
        $vehicle['schedulePayment'] = (new CommonService())->makeDateInOption(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles'] = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles'] = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles'] = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days'] = $days;
        $vehicle['day_rent'] = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * ($odr['insurance'] ?? 0)) + $CsLeaselists['initial_fee']));

        if (
            !empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            $vehicle['OwnerSign'] = (new SignatureService())->createSignature($vehicle['Owner']['id'], $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name']);
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['id']) . '.png';
        }

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name'];

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        $vehicle['RenterSign'] = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['support_phone'] = config('legacy.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency'] = $CsLeaselists['currency'];
        $vehicle['financing'] = $odr['financing'] ?? 0;

        $orderDepositRuleData = !empty($odr['calculation'])
            ? array_merge($odr, json_decode($odr['calculation'], true))
            : $odr;
        if (!isset($orderDepositRuleData['maintenance_total'])) {
            $orderDepositRuleData['maintenance_total'] = sprintf('%0.2f', (($orderDepositRuleData['maintenance_per_month'] ?? 0) * 12 * ($orderDepositRuleData['days'] ?? 0) / 365));
        }
        $vehicle['OrderDepositRule'] = $orderDepositRuleData;

        $DepositRuleObj = DepositRule::where('vehicle_id', $CsLeaselists['vehicle_id'])
            ->select('financing', 'financing_type', 'return_fee')
            ->first();
        $vehicle['DepositRule'] = $DepositRuleObj ? $DepositRuleObj->toArray() : [];
        $vehicle['odometer'] = $odometer;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? OrderDepositRule::getFromTierData($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
    }


    public function _generateAgreementForOffer(array $VehicleData, array $userObj): array
    {
        $start_date = $VehicleData['VehicleOffer']['start_datetime'];
        $end_date = date('Y-m-d H:i:s', strtotime($start_date . ' +' . $VehicleData['VehicleOffer']['duration'] . ' days'));

        $depositTemplateObj = DepositTemplate::where('user_id', $VehicleData['Vehicle']['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $DepositRule = DepositRule::where('vehicle_id', $VehicleData['VehicleOffer']['vehicle_id'])->first();
        $dr = $DepositRule ? $DepositRule->toArray() : [];

        $tbd = false;
        $insurance = 0;
        if (!empty($dr) && in_array($dr['insurance_payer'] ?? 0, [3, 4, 5, 6, 7])) {
            $tbd = true;
        } else {
            // TODO: Replace with PathToOwnership service – getDynamicFareMatrixInsurance()
            $insurance = 0;
        }

        $time_fee = sprintf('%0.2f', ($VehicleData['VehicleOffer']['duration'] * ($VehicleData['VehicleOffer']['day_rent'] + $VehicleData['VehicleOffer']['emf'])));

        // TODO: Replace with DepositRule service – calculateDIAFee()
        $dia_fee = 0;
        $tax = sprintf('%0.2f', ((($time_fee + $dia_fee) * ($dr['tax'] ?? 0)) / 100));

        $vehicle = [];
        $vehicle['time_fee'] = $time_fee;
        $vehicle['tax'] = $tax;
        $vehicle['dia_fee'] = $dia_fee;
        $vehicle['deposit_amt'] = $VehicleData['VehicleOffer']['total_deposit_amt'];
        $vehicle['initial_fee'] = $VehicleData['VehicleOffer']['total_initial_fee'];
        $vehicle['dia_insu'] = 0;
        $vehicle['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * $insurance)) : 'TBD';
        $vehicle['daily_insurance'] = !$tbd ? sprintf('%0.2f', $insurance) : 'TBD';
        $vehicle['weekly_insurance'] = !$tbd ? sprintf('%0.2f', (7 * $insurance)) : 'TBD';

        $userObj['licence_number'] = Security::decrypt($userObj['licence_number'] ?? '');
        $filename = time() . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = json_decode($VehicleData['VehicleOffer']['initial_fee_opt'] ?? '[]', true);
        $initialfeeOpt = !empty($initialfeeOpt)
            ? array_merge([['after_day_date' => date('m/d/Y', strtotime($start_date)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt)
            : [['after_day_date' => date('m/d/Y', strtotime($start_date)), 'amount' => $vehicle['initial_fee']]];

        $vehicle['total_rent'] = sprintf('%0.2f', ($time_fee + $tax + $dia_fee));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_date));
        $vehicle['today'] = date('m/d/Y');
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($start_date . ' +28 days'));
        $vehicle['Renter'] = $userObj;
        $vehicle['extra_mileage_fee'] = $dr['emf'] ?? 0;
        $vehicle['lateness_fee'] = sprintf('%0.2f', $dr['lateness_fee'] ?? 0);
        $vehicle['schedulePayment'] = (new CommonService())->makeDateInOption($start_date, $initialfeeOpt);
        $vehicle['daily_miles'] = ceil(($VehicleData['VehicleOffer']['miles'] ?? 0) * 12 / 365);
        $vehicle['weekly_miles'] = ceil(($VehicleData['VehicleOffer']['miles'] ?? 0) * 12 / 365 * 7);
        $vehicle['monthly_miles'] = $VehicleData['VehicleOffer']['miles'] ?? 0;
        $vehicle['days'] = $VehicleData['VehicleOffer']['days'] ?? 0;
        $vehicle['Owner'] = $VehicleData['Owner'];
        $vehicle['Vehicle'] = $VehicleData['Vehicle'];
        $vehicle['Vehicle']['plate_number'] = empty($VehicleData['Vehicle']['plate_number']) ? '' : $VehicleData['Vehicle']['plate_number'];
        $vehicle['financing'] = $VehicleData['VehicleOffer']['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($VehicleData['VehicleOffer']['calculation'])
            ? json_decode($VehicleData['VehicleOffer']['calculation'], true)
            : $dr;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $dr['return_fee'] ?? 0);
        $vehicle['day_rent'] = sprintf('%0.2f', ($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)) * 365 / 12));
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + ($VehicleData['VehicleOffer']['duration'] * $insurance) + $vehicle['initial_fee']));

        if (
            !empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            $vehicle['OwnerSign'] = (new SignatureService())->createSignature($vehicle['Owner']['id'], $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name']);
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['id']) . '.png';
        }

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name'];

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        $vehicle['RenterSign'] = config('app.url') . '/files/signatures/' . ($userObj['id'] ?? '') . '.png';
        $vehicle['support_phone'] = config('legacy.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency'] = $this->userObj['currency'] ?? '$';
        $vehicle['odometer'] = $VehicleData['Vehicle']['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['tbd'] = $tbd;

        $nextBookingDuration = !empty($vehicle['OrderDepositRule']['duration_opt'])
            ? OrderDepositRule::getFromTierData($vehicle['OrderDepositRule']['duration_opt'], $start_date, $end_date)
            : $vehicle['days'];
        $vehicle['next_duration'] = $nextBookingDuration ?: $vehicle['days'];
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime($start_date . ' +' . ($VehicleData['VehicleOffer']['days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'File generated', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename]];
    }

    public function _generateAgreementForQuote($dataValues, $userObj): array
    {
        $lease_id = $dataValues->list_id;
        $pto = (isset($dataValues->financing) && strtolower($dataValues->financing) == 'pto') ? 1 : 0;
        $start_datetime = date('Y-m-d H:i:s', strtotime($dataValues->startdatetime));
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " +$dataValues->days days"));

        $rental_options = isset($dataValues->rental_options) ? sprintf('%0.2f', preg_replace('/[^0-9,.]/', '', $dataValues->rental_options)) : 0;
        $initial_fee_options = isset($dataValues->initial_fee_options) ? preg_replace('/[^0-9,.]/', '', $dataValues->initial_fee_options) : 0;
        $emf_options = isset($dataValues->emf_options) ? ($dataValues->emf_options * 12 / 365) : 0;
        $miles_options = isset($dataValues->miles_options) ? sprintf('%0.2f', ($dataValues->miles_options * 12 / 365)) : 0;

        $vehicleModel = Vehicle::with([
            'owner:id,first_name,last_name,company_address,company_city,company_state,company_zip,timezone,distance_unit,currency,company_name,representative_name,representative_role,representative_sign,contact_number'
        ])->find($lease_id);

        if (empty($vehicleModel)) {
            return ['status' => false, 'message' => 'Sorry, this vehicle is not available for selected date range.', 'result' => []];
        }
        $vehicleArr = $vehicleModel->toArray();
        if ($vehicleModel->owner) {
            $ownerArr = $vehicleModel->owner->toArray();
            $vehicleArr['owner_id'] = $ownerArr['id'] ?? null;
            $vehicleArr['owner_first_name'] = $ownerArr['first_name'] ?? null;
            $vehicleArr['owner_last_name'] = $ownerArr['last_name'] ?? null;
            $vehicleArr['address'] = $ownerArr['company_address'] ?? null;
            $vehicleArr['city'] = $ownerArr['company_city'] ?? null;
            $vehicleArr['state'] = $ownerArr['company_state'] ?? null;
            $vehicleArr['zip'] = $ownerArr['company_zip'] ?? null;
            $vehicleArr['timezone'] = $ownerArr['timezone'] ?? null;
            $vehicleArr['distance_unit'] = $ownerArr['distance_unit'] ?? null;
            $vehicleArr['owner_currency'] = $ownerArr['currency'] ?? null;
            $vehicleArr['company_name'] = $ownerArr['company_name'] ?? null;
            $vehicleArr['representative_name'] = $ownerArr['representative_name'] ?? null;
            $vehicleArr['representative_role'] = $ownerArr['representative_role'] ?? null;
            $vehicleArr['representative_sign'] = $ownerArr['representative_sign'] ?? null;
            $vehicleArr['contact_number'] = $ownerArr['contact_number'] ?? null;
        }

        $depositTemplateObj = DepositTemplate::where('user_id', $vehicleArr['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $DepositRule = DepositRule::where('vehicle_id', $vehicleArr['id'])->first();
        $dr = $DepositRule ? $DepositRule->toArray() : [];

        $tbd = false;
        $insurance = 0;
        if (!empty($dr) && in_array($dr['insurance_payer'] ?? 0, [3, 4, 5, 6, 7])) {
            $tbd = true;
        }

        $userObj['licence_number'] = Security::decrypt($userObj['licence_number'] ?? '');
        $filename = time() . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $vehData = [];
        $vehData['Vehicle'] = $vehicleArr;
        $vehData['Owner'] = $vehicleArr;
        $vehData['time_fee'] = 0;
        $vehData['tax'] = 0;
        $vehData['dia_fee'] = 0;
        $vehData['deposit_amt'] = '0.00';
        $vehData['initial_fee'] = '0.00';
        $vehData['dia_insu'] = 0;
        $vehData['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * $insurance)) : 'TBD';
        $vehData['daily_insurance'] = !$tbd ? sprintf('%0.2f', $insurance) : 'TBD';
        $vehData['weekly_insurance'] = !$tbd ? sprintf('%0.2f', (7 * $insurance)) : 'TBD';
        $vehData['total_rent'] = '0.00';
        $vehData['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehData['today'] = date('m/d/Y');
        $vehData['end_datetime'] = date('Y-m-d H:i:s', strtotime($start_datetime . ' +28 days'));
        $vehData['Renter'] = $userObj;
        $vehData['DepositRule'] = $dr;
        $vehData['financing'] = $vehicleArr['financing'] ?? 0;
        $vehData['disposition_fee'] = sprintf('%0.2f', $dr['return_fee'] ?? 0);
        $vehData['extra_mileage_fee'] = $dr['emf'] ?? 0;
        $vehData['lateness_fee'] = '0.00';
        $vehData['schedulePayment'] = [];
        $vehData['daily_miles'] = ceil($miles_options);
        $vehData['weekly_miles'] = ceil($miles_options * 7);
        $vehData['monthly_miles'] = $dataValues->miles_options ?? 0;
        $vehData['days'] = 1;

        if (
            !empty($vehData['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehData['Owner']['representative_sign']))
        ) {
            $vehData['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehData['Owner']['representative_sign'];
        } else {
            $vehData['OwnerSign'] = (new SignatureService())->createSignature($vehData['Owner']['id'], $vehData['Owner']['first_name'] . ' ' . $vehData['Owner']['last_name']);
            $vehData['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehData['Owner']['id']) . '.png';
        }

        $vehData['Owner']['signature_name'] = !empty($vehData['Owner']['representative_name'])
            ? $vehData['Owner']['representative_name']
            : $vehData['Owner']['first_name'] . ' ' . $vehData['Owner']['last_name'];

        $vehData['Owner']['signature_role'] = !empty($vehData['Owner']['representative_role'])
            ? $vehData['Owner']['representative_role']
            : 'COO';

        $vehData['RenterSign'] = config('app.url') . '/files/signatures/' . ($userObj['id'] ?? '') . '.png';
        $vehData['support_phone'] = config('legacy.support_phone', '');
        $vehData['distance_unit'] = $vehicleArr['distance_unit'] ?? '';
        $vehData['currency'] = $this->userObj['currency'] ?? '$';
        $vehData['odometer'] = $vehicleArr['odometer'] ?? 0;
        $vehData['fixed_program_cost'] = $fixedProgramCost;
        $vehData['tbd'] = $tbd;

        $vehData['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehData['currency'], $vehData['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehData, $filefullname);

        return ['status' => true, 'message' => 'File generated', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename]];
    }


    public function _generateDocusignAgreement($booking_id, $dailyInsurance = 0): array
    {
        $booking = VehicleReservation::with([
            'vehicle',
            'owner:id,first_name,last_name,company_address,company_city,company_state,company_zip,timezone,distance_unit,company_name,representative_name,representative_role,representative_sign,contact_number'
        ])->find($booking_id);

        if (empty($booking)) {
            return ['status' => false, 'message' => 'Sorry, you are not authorized for this booking.', 'result' => []];
        }

        $reservation = $booking->toArray();
        if ($booking->vehicle) {
            $reservation = array_merge($booking->vehicle->toArray(), $reservation);
        }
        if ($booking->owner) {
            $ownerArr = $booking->owner->toArray();
            $reservation['owner_id'] = $ownerArr['id'] ?? null;
            $reservation['owner_first_name'] = $ownerArr['first_name'] ?? null;
            $reservation['owner_last_name'] = $ownerArr['last_name'] ?? null;
            $reservation['address'] = $ownerArr['company_address'] ?? null;
            $reservation['city'] = $ownerArr['company_city'] ?? null;
            $reservation['state'] = $ownerArr['company_state'] ?? null;
            $reservation['zip'] = $ownerArr['company_zip'] ?? null;
            $reservation['timezone'] = $ownerArr['timezone'] ?? null;
            $reservation['distance_unit'] = $ownerArr['distance_unit'] ?? null;
            $reservation['company_name'] = $ownerArr['company_name'] ?? null;
            $reservation['representative_name'] = $ownerArr['representative_name'] ?? null;
            $reservation['representative_role'] = $ownerArr['representative_role'] ?? null;
            $reservation['representative_sign'] = $ownerArr['representative_sign'] ?? null;
            $reservation['contact_number'] = $ownerArr['contact_number'] ?? null;
        }

        $start_datetime = $parent_datetime = $this->formatForUser($reservation['start_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);
        $end_datetime = $this->formatForUser($reservation['end_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);

        $OrderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $reservation['id'])->first();
        $odr = $OrderDepositRule ? $OrderDepositRule->toArray() : [];

        $days = (new CommonService())->days_between_dates($reservation['start_datetime'], $reservation['end_datetime']);

        $vehicle = [];
        $vehicle['Vehicle'] = $reservation;
        $vehicle['Owner'] = $reservation;
        $vehicle['time_fee'] = sprintf('%0.2f', (($odr['rental'] ?? 0) * $days));
        $vehicle['tax'] = 0;
        $vehicle['dia_fee'] = 0;
        $vehicle['deposit_amt'] = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee'] = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu'] = $odr['emf_insu_rate'] ?? 0;
        $vehicle['msrp'] = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (30 * $dailyInsurance));
        $vehicle['daily_insurance'] = sprintf('%0.2f', $dailyInsurance);
        $vehicle['weekly_insurance'] = sprintf('%0.2f', (7 * $dailyInsurance));

        $filename = $reservation['id'] . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent'] = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today'] = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($start_datetime . ' +28 days'));
        $vehicle['Renter'] = $this->userObj['User'] ?? [];
        unset($vehicle['Renter']['licence_number']);
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee'] = '0.00';
        $vehicle['schedulePayment'] = (new CommonService())->makeDateInOption(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles'] = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles'] = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles'] = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days'] = $days;
        $vehicle['day_rent'] = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * $dailyInsurance) + ($odr['initial_fee'] ?? 0)));

        if (
            !empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            $vehicle['OwnerSign'] = (new SignatureService())->createSignature($vehicle['Owner']['id'], $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name']);
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['id']) . '.png';
        }

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : $vehicle['Owner']['first_name'] . ' ' . $vehicle['Owner']['last_name'];

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        $vehicle['RenterSign'] = '';
        $vehicle['support_phone'] = config('legacy.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency'] = $this->userObj['User']['currency'] ?? '$';
        $vehicle['financing'] = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;

        $DepositRuleObj = DepositRule::where('vehicle_id', $reservation['vehicle_id'])
            ->select('financing', 'financing_type', 'emf', 'return_fee', 'tax')
            ->first();
        $vehicle['DepositRule'] = $DepositRuleObj ? $DepositRuleObj->toArray() : [];
        $vehicle['DepositRule']['emf_rate'] = $odr['emf'] ?? 0;
        $vehicle['odometer'] = $reservation['last_mile'] ?? $reservation['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = 0;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? OrderDepositRule::getFromTierData($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription(null, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename, 'filepath' => $filefullname]];
    }

    public function _generateCMMCard($renterid, $orderid = null): array
    {
        $query = CsOrder::with([
            'vehicle:id,year,make,model,vin_no,ccm_auth_no',
            'driver:id,first_name,last_name'
        ])->select('id', 'increment_id', 'parent_id', 'vehicle_id', 'renter_id');

        if ($orderid !== null) {
            $query->where('id', $orderid);
        } else {
            $query->where('renter_id', $renterid)->where('status', 1);
        }
        $CsOrderObj = $query->first();

        if (empty($CsOrderObj)) {
            return ['status' => false, 'message' => 'Sorry, you are not authorized for this booking.', 'result' => []];
        }

        $filename = 'cmm-service-card-' . ($CsOrderObj->parent_id ?: $CsOrderObj->id) . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        $dataToPass = [
            'year' => $CsOrderObj->vehicle->year ?? '',
            'make' => $CsOrderObj->vehicle->make ?? '',
            'model' => $CsOrderObj->vehicle->model ?? '',
            'vin' => $CsOrderObj->vehicle->vin_no ?? '',
            'ccm_auth_no' => !empty($CsOrderObj->vehicle->ccm_auth_no) ? $CsOrderObj->vehicle->ccm_auth_no : 'XXXXXXXXXXXXXX',
            'vehicle_unique_id' => substr($CsOrderObj->vehicle->vin_no ?? '', -6),
            'driver_name' => ($CsOrderObj->driver->first_name ?? '') . ' ' . ($CsOrderObj->driver->last_name ?? ''),
            'logo' => '<img src="' . config('app.url') . '/img/cmm-card-logo.png" alt="logo"/>',
        ];

        // TODO: Replace with Agreement service – generateCMMCard()
        // $this->Agreement->generateCMMCard($dataToPass, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename, 'filepath' => $filefullname]];
    }


    private function buildDepositDescription($depositTemplateObj, string $currency, string $depositAmt): string
    {
        $description = sprintf(
            'Deposit. In addition to the fees listed in Section 2, Renter shall pay a deposit of %s%s at the time this Agreement is signed. Owner may use the deposit funds to cover any amounts due under this Agreement',
            $currency,
            $depositAmt
        );

        if (
            !empty($depositTemplateObj)
            && (
                $depositTemplateObj->is_deposit_refundable == 1
                || $depositTemplateObj->is_deposit_refundable == 0
            )
        ) {
            $description = sprintf(
                '%s. In addition to the fees listed in Section 2, Renter shall pay a deposit of %s%s at the time this Agreement is signed. Owner may use the this funds to cover any amounts due under this Agreement',
                $depositTemplateObj->deposit_title ?? 'Deposit',
                $currency,
                $depositAmt
            );

        }

        return $description;
    }
    private function formatForUser($datetime, string $format, ?string $timezone = null): string
    {
        $carbon = $datetime instanceof Carbon
            ? $datetime
            : Carbon::parse($datetime);

        if ($timezone) {
            $carbon = $carbon->timezone($timezone);
        }

        return $carbon->format($format);
    }
}
