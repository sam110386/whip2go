<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Ported from CakePHP app/Controller/Traits/AgreementTrait.php
 *
 * Generates rental agreement PDFs for completed, pending, active bookings,
 * offers, quotes, docusign flows, and CMM service cards.
 */
trait AgreementTrait
{
    /**
     * Format datetime for a given timezone (replaces CakeTime::formatForUser).
     */
    private function formatForUser($datetime, string $format, ?string $timezone = null): string
    {
        $carbon = $datetime instanceof \Carbon\Carbon
            ? $datetime
            : Carbon::parse($datetime);

        if ($timezone) {
            $carbon = $carbon->timezone($timezone);
        }
        return $carbon->format($format);
    }

    /**
     * Calculate days between two dates (replaces $this->Common->days_between_dates).
     */
    private function daysBetweenDates(string $start, string $end): int
    {
        $diff = Carbon::parse($start)->diffInDays(Carbon::parse($end));
        return max((int) $diff, 1);
    }

    /**
     * Generate agreement for a completed booking.
     */
    public function _getagreementForCompletedBooking(array $CsLeaselists, bool $force = false): array
    {
        $odometer = $CsLeaselists['CsOrder']['start_odometer'];

        $filename     = $CsLeaselists['CsOrder']['increment_id'] . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        if (!$force && file_exists($filefullname)) {
            return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
        }

        if ($CsLeaselists['CsOrder']['parent_id']) {
            $parentOrder = DB::table('cs_orders')
                ->where('id', $CsLeaselists['CsOrder']['parent_id'])
                ->select('start_datetime', 'start_odometer', 'increment_id')
                ->first();

            $parent_datetime = $this->formatForUser($parentOrder->start_datetime, 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
            $odometer = $parentOrder->start_odometer;

            $parentfilename     = $parentOrder->increment_id . '.pdf';
            $parentfilefullname = public_path('files/agreements/' . $parentfilename);
            if (!$force && file_exists($parentfilefullname)) {
                return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $parentfilename]];
            }
        } else {
            $parent_datetime = $this->formatForUser($CsLeaselists['CsOrder']['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
        }

        $start_datetime = $this->formatForUser($CsLeaselists['CsOrder']['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
        $end_datetime   = !empty($CsLeaselists['CsOrder']['end_timing'])
            ? $this->formatForUser($CsLeaselists['CsOrder']['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone'])
            : $this->formatForUser($CsLeaselists['CsOrder']['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);

        $orderIdForRule = !empty($CsLeaselists['CsOrder']['parent_id'])
            ? $CsLeaselists['CsOrder']['parent_id']
            : $CsLeaselists['CsOrder']['id'];

        $OrderDepositRule = DB::table('order_deposit_rules')->where('cs_order_id', $orderIdForRule)->first();
        $odr = $OrderDepositRule ? (array) $OrderDepositRule : [];

        $days = $this->daysBetweenDates($CsLeaselists['CsOrder']['start_datetime'], $CsLeaselists['CsOrder']['end_datetime']);

        $DepositRuleObj = DB::table('deposit_rules')->where('vehicle_id', $CsLeaselists['CsOrder']['vehicle_id'])
            ->select('lateness_fee', 'return_fee', 'financing', 'financing_type')->first();

        $late_fee = $DepositRuleObj->lateness_fee ?? 0;

        $depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $CsLeaselists['CsOrder']['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $days = $days ?: 1;
        $vehicle = [];
        $vehicle['Vehicle'] = $CsLeaselists['Vehicle'];
        $vehicle['Owner']   = $CsLeaselists['Owner'];
        $vehicle['time_fee']   = $CsLeaselists['CsOrder']['rent'];
        $vehicle['tax']        = $CsLeaselists['CsOrder']['tax'];
        $vehicle['dia_fee']    = $CsLeaselists['CsOrder']['dia_fee'];
        $vehicle['msrp']       = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['deposit_amt']  = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee']  = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu']     = $odr['emf_insu_rate'] ?? 0;
        $vehicle['daily_insurance']   = sprintf('%0.2f', ($CsLeaselists['CsOrder']['insurance_amt'] / $days));
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (30 * $vehicle['daily_insurance']));
        $vehicle['weekly_insurance']  = sprintf('%0.2f', (7 * $vehicle['daily_insurance']));

        $userObj = DB::table('users')
            ->where('id', $CsLeaselists['CsOrder']['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'dob', 'currency')
            ->first();
        $userArr = (array) $userObj;
        // TODO: Replace Security::decrypt with Crypt::decryptString or dedicated decryption
        $userArr['licence_number'] = $this->decryptLicenceNumber($userArr['licence_number'] ?? '');

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent']      = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent                  = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime']  = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today']           = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime']    = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter']          = $userArr;
        $vehicle['Vehicle']['plate_number'] = empty($vehicle['Vehicle']['plate_number']) ? '--' : $vehicle['Vehicle']['plate_number'];
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee']      = sprintf('%0.2f', $late_fee);
        // TODO: Replace with Common service – makeDateInOption()
        $vehicle['schedulePayment']   = $this->makeDateInOptionStub(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles']       = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles']      = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles']     = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days']              = $days;
        $vehicle['day_rent']          = sprintf('%0.2f', ($totalRent / $days));
        $vehicle['weekly_rent']       = sprintf('%0.2f', (($totalRent / $days) * 7));
        $vehicle['monthly_rent']      = sprintf('%0.2f', (($totalRent / $days) * 365 / 12));
        $vehicle['booking_rental']    = sprintf('%0.2f', ($vehicle['total_rent'] + ($CsLeaselists['CsOrder']['insurance_amt']) + $CsLeaselists['CsOrder']['initial_fee']));

        $vehicle = $this->resolveOwnerSignature($vehicle);
        $vehicle = $this->resolveOwnerSignatureName($vehicle);

        // TODO: Replace with Signature service – createSignature()
        $vehicle['RenterSign']  = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['support_phone'] = config('app.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'];
        $vehicle['currency']      = $CsLeaselists['CsOrder']['currency'];
        $vehicle['financing']     = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;
        $vehicle['DepositRule']   = $DepositRuleObj ? (array) $DepositRuleObj : [];
        $vehicle['odometer']      = $odometer;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee']    = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);

        // TODO: Replace with OrderDepositRule service – getFromTierData()
        $nextBookingDuration = !empty($odr['duration_opt'])
            ? $this->getFromTierDataStubAgreement($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease']  = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
    }

    /**
     * Get pending booking agreement.
     */
    public function _getPendingBookingAgreement($booking_id): array
    {
        $CsLeaselists = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'VehicleReservation.user_id')
            ->where('VehicleReservation.id', $booking_id)
            ->select(
                'VehicleReservation.*', 'Vehicle.*',
                'Owner.id as owner_id', 'Owner.first_name as owner_first_name', 'Owner.last_name as owner_last_name',
                'Owner.company_address as address', 'Owner.company_city as city', 'Owner.company_state as state',
                'Owner.company_zip as zip', 'Owner.timezone', 'Owner.distance_unit', 'Owner.company_name',
                'Owner.representative_name', 'Owner.representative_role', 'Owner.representative_sign', 'Owner.contact_number'
            )
            ->first();

        if (empty($CsLeaselists)) {
            return ['status' => false, 'message' => 'Sorry, you are not authorized for this booking.', 'result' => []];
        }

        $reservation = (array) $CsLeaselists;

        $start_datetime = $parent_datetime = $this->formatForUser($reservation['start_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);
        $end_datetime = $this->formatForUser($reservation['end_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);

        $OrderDepositRule = DB::table('order_deposit_rules')->where('vehicle_reservation_id', $reservation['id'])->first();
        $odr = $OrderDepositRule ? (array) $OrderDepositRule : [];

        $days = $this->daysBetweenDates($reservation['start_datetime'], $reservation['end_datetime']);

        $depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $reservation['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $tbd = false;
        if (!empty($odr) && in_array($odr['insurance_payer'] ?? 0, [3, 4, 5, 6, 7])) {
            $tbd = true;
        }

        // TODO: Replace with DepositRule service – getInsuranceFee()
        $Temp = [
            'rent'       => sprintf('%0.2f', (($odr['rental'] ?? 0) * $days)),
            'user_id'    => $reservation['user_id'],
            'vehicle_id' => $reservation['vehicle_id'],
            'insurance'  => $odr['insurance'] ?? 0,
            'renter_id'  => $reservation['renter_id'],
            'pto'        => $reservation['pto'] ?? 0,
        ];

        $vehicle = [];
        $vehicle['Vehicle'] = $reservation;
        $vehicle['Owner']   = $reservation;
        $vehicle['time_fee']   = $Temp['rent'];
        $vehicle['tax']        = 0;
        $vehicle['dia_fee']    = 0;
        $vehicle['deposit_amt']  = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee']  = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu']     = $odr['emf_insu_rate'] ?? 0;
        $vehicle['msrp']         = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * ($odr['insurance'] ?? 0))) : 'TBD';
        $vehicle['daily_insurance']   = !$tbd ? sprintf('%0.2f', ($odr['insurance'] ?? 0)) : 'TBD';
        $vehicle['weekly_insurance']  = !$tbd ? sprintf('%0.2f', (7 * ($odr['insurance'] ?? 0))) : 'TBD';

        $userObj = DB::table('users')
            ->where('id', $reservation['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'dob')
            ->first();
        $userArr = (array) $userObj;
        $userArr['licence_number'] = $this->decryptLicenceNumber($userArr['licence_number'] ?? '');

        $filename     = $reservation['id'] . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent']     = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent                 = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today']          = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime']   = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter']         = $userArr;
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee']      = '0.00';
        $vehicle['schedulePayment']   = $this->makeDateInOptionStub(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles']       = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles']      = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles']     = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days']              = $days;
        $vehicle['day_rent']          = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent']       = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent']      = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental']    = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * ($odr['insurance'] ?? 0)) + ($odr['initial_fee'] ?? 0)));

        $vehicle = $this->resolveOwnerSignature($vehicle);
        $vehicle = $this->resolveOwnerSignatureName($vehicle);

        $vehicle['RenterSign']    = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['support_phone'] = config('app.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency']      = $userArr['currency'] ?? '$';
        $vehicle['financing']     = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;

        $DepositRuleObj = DB::table('deposit_rules')
            ->where('vehicle_id', $reservation['vehicle_id'])
            ->select('financing', 'financing_type', 'return_fee')
            ->first();
        $vehicle['DepositRule']       = $DepositRuleObj ? (array) $DepositRuleObj : [];
        $vehicle['odometer']          = $reservation['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee']    = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);
        $vehicle['tbd'] = $tbd;

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? $this->getFromTierDataStubAgreement($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease']  = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename, 'filepath' => $filefullname]];
    }

    /**
     * Generate agreement for an active booking.
     */
    public function _generateAgreementForBooking(array $CsLeaselists, bool $force = false): array
    {
        $filename     = $CsLeaselists['CsOrder']['increment_id'] . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        if (!$force && file_exists($filefullname)) {
            return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
        }

        $odometer = $CsLeaselists['CsOrder']['start_odometer'];
        $orderIdForRule = !empty($CsLeaselists['CsOrder']['parent_id'])
            ? $CsLeaselists['CsOrder']['parent_id']
            : $CsLeaselists['CsOrder']['id'];

        $OrderDepositRule = DB::table('order_deposit_rules')->where('cs_order_id', $orderIdForRule)->first();
        $odr = $OrderDepositRule ? (array) $OrderDepositRule : [];

        $start_datetime = $this->formatForUser($CsLeaselists['CsOrder']['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
        $end_datetime   = !empty($CsLeaselists['CsOrder']['end_timing'])
            ? $this->formatForUser($CsLeaselists['CsOrder']['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone'])
            : $this->formatForUser($CsLeaselists['CsOrder']['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);

        $days = $this->daysBetweenDates($CsLeaselists['CsOrder']['start_datetime'], $CsLeaselists['CsOrder']['end_datetime']);
        $parent_datetime = $this->formatForUser($CsLeaselists['CsOrder']['start_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
        $increment_id    = $CsLeaselists['CsOrder']['increment_id'];

        if ($CsLeaselists['CsOrder']['parent_id']) {
            $parentOrder = DB::table('cs_orders')
                ->where('id', $CsLeaselists['CsOrder']['parent_id'])
                ->select('increment_id', 'start_datetime', 'end_timing', 'end_datetime', 'start_odometer')
                ->first();

            $parent_datetime = $this->formatForUser($parentOrder->start_datetime, 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
            $odometer = $parentOrder->start_odometer;

            $parentfilename     = $parentOrder->increment_id . '.pdf';
            $parentfilefullname = public_path('files/agreements/' . $parentfilename);
            if (!$force && file_exists($parentfilefullname)) {
                return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $parentfilename]];
            }
            if (($odr['financing'] ?? 0) == 1) {
                $start_datetime = $parent_datetime;
                $end_datetime   = !empty($CsLeaselists['CsOrder']['end_timing'])
                    ? $this->formatForUser($CsLeaselists['CsOrder']['end_timing'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone'])
                    : $this->formatForUser($CsLeaselists['CsOrder']['end_datetime'], 'Y-m-d H:i:s', $CsLeaselists['CsOrder']['timezone']);
                $increment_id = $parentOrder->increment_id;
            }
        }

        $depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $CsLeaselists['VehicleReservation']['user_id'] ?? $CsLeaselists['CsOrder']['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        // TODO: Replace with DepositRule service – getInsuranceFee()
        $Temp = [
            'rent'       => sprintf('%0.2f', (($odr['rental'] ?? 0) * $days)),
            'user_id'    => $CsLeaselists['CsOrder']['user_id'],
            'vehicle_id' => $CsLeaselists['CsOrder']['vehicle_id'],
            'insurance'  => $odr['insurance'] ?? 0,
            'renter_id'  => $CsLeaselists['CsOrder']['renter_id'],
            'pto'        => $CsLeaselists['CsOrder']['pto'] ?? 0,
        ];

        $vehicle = [];
        $vehicle['Vehicle'] = $CsLeaselists['Vehicle'];
        $vehicle['Owner']   = $CsLeaselists['Owner'];
        $vehicle['time_fee']   = $Temp['rent'];
        $vehicle['tax']        = 0;
        $vehicle['dia_fee']    = 0;
        $vehicle['deposit_amt']  = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee']  = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['msrp']         = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['dia_insu']     = $odr['emf_insu_rate'] ?? 0;
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (($odr['insurance'] ?? 0) * 365 / 12));
        $vehicle['daily_insurance']   = sprintf('%0.2f', ($odr['insurance'] ?? 0));
        $vehicle['weekly_insurance']  = sprintf('%0.2f', (7 * ($odr['insurance'] ?? 0)));

        $userObj = DB::table('users')
            ->where('id', $CsLeaselists['CsOrder']['renter_id'])
            ->select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'dob')
            ->first();
        $userArr = (array) $userObj;
        $userArr['licence_number'] = $this->decryptLicenceNumber($userArr['licence_number'] ?? '');

        $filename     = $increment_id . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent']     = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent                 = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today']          = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime']   = date('Y-m-d', strtotime($parent_datetime . ' +28 days'));
        $vehicle['Renter']         = $userArr;
        $vehicle['Vehicle']['plate_number'] = empty($vehicle['Vehicle']['plate_number']) ? '--' : $vehicle['Vehicle']['plate_number'];
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee']      = '0.00';
        $vehicle['schedulePayment']   = $this->makeDateInOptionStub(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles']       = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles']      = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles']     = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days']              = $days;
        $vehicle['day_rent']          = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent']       = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent']      = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental']    = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * ($odr['insurance'] ?? 0)) + $CsLeaselists['CsOrder']['initial_fee']));

        $vehicle = $this->resolveOwnerSignature($vehicle);
        $vehicle = $this->resolveOwnerSignatureName($vehicle);

        $vehicle['RenterSign']    = config('app.url') . '/files/signatures/' . $userArr['id'] . '.png';
        $vehicle['support_phone'] = config('app.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency']      = $CsLeaselists['CsOrder']['currency'];
        $vehicle['financing']     = $odr['financing'] ?? 0;

        $orderDepositRuleData = !empty($odr['calculation'])
            ? array_merge($odr, json_decode($odr['calculation'], true))
            : $odr;
        if (!isset($orderDepositRuleData['maintenance_total'])) {
            $orderDepositRuleData['maintenance_total'] = sprintf('%0.2f', (($orderDepositRuleData['maintenance_per_month'] ?? 0) * 12 * ($orderDepositRuleData['days'] ?? 0) / 365));
        }
        $vehicle['OrderDepositRule'] = $orderDepositRuleData;

        $DepositRuleObj = DB::table('deposit_rules')
            ->where('vehicle_id', $CsLeaselists['CsOrder']['vehicle_id'])
            ->select('financing', 'financing_type', 'return_fee')
            ->first();
        $vehicle['DepositRule']       = $DepositRuleObj ? (array) $DepositRuleObj : [];
        $vehicle['odometer']          = $odometer;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee']    = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? $this->getFromTierDataStubAgreement($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease']  = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename]];
    }

    /**
     * Generate agreement for a vehicle offer.
     */
    public function _generateAgreementForOffer(array $VehicleData, array $userObj): array
    {
        $start_date = $VehicleData['VehicleOffer']['start_datetime'];
        $end_date   = date('Y-m-d H:i:s', strtotime($start_date . ' +' . $VehicleData['VehicleOffer']['duration'] . ' days'));

        $depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $VehicleData['Vehicle']['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $DepositRule = DB::table('deposit_rules')->where('vehicle_id', $VehicleData['VehicleOffer']['vehicle_id'])->first();
        $dr = $DepositRule ? (array) $DepositRule : [];

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
        $vehicle['time_fee']   = $time_fee;
        $vehicle['tax']        = $tax;
        $vehicle['dia_fee']    = $dia_fee;
        $vehicle['deposit_amt']  = $VehicleData['VehicleOffer']['total_deposit_amt'];
        $vehicle['initial_fee']  = $VehicleData['VehicleOffer']['total_initial_fee'];
        $vehicle['dia_insu']     = 0;
        $vehicle['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * $insurance)) : 'TBD';
        $vehicle['daily_insurance']   = !$tbd ? sprintf('%0.2f', $insurance) : 'TBD';
        $vehicle['weekly_insurance']  = !$tbd ? sprintf('%0.2f', (7 * $insurance)) : 'TBD';

        $userObj['licence_number'] = $this->decryptLicenceNumber($userObj['licence_number'] ?? '');
        $filename     = time() . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = json_decode($VehicleData['VehicleOffer']['initial_fee_opt'] ?? '[]', true);
        $initialfeeOpt = !empty($initialfeeOpt)
            ? array_merge([['after_day_date' => date('m/d/Y', strtotime($start_date)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt)
            : [['after_day_date' => date('m/d/Y', strtotime($start_date)), 'amount' => $vehicle['initial_fee']]];

        $vehicle['total_rent']     = sprintf('%0.2f', ($time_fee + $tax + $dia_fee));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_date));
        $vehicle['today']          = date('m/d/Y');
        $vehicle['end_datetime']   = date('Y-m-d', strtotime($start_date . ' +28 days'));
        $vehicle['Renter']         = $userObj;
        $vehicle['extra_mileage_fee'] = $dr['emf'] ?? 0;
        $vehicle['lateness_fee']      = sprintf('%0.2f', $dr['lateness_fee'] ?? 0);
        $vehicle['schedulePayment']   = $this->makeDateInOptionStub($start_date, $initialfeeOpt);
        $vehicle['daily_miles']       = ceil(($VehicleData['VehicleOffer']['miles'] ?? 0) * 12 / 365);
        $vehicle['weekly_miles']      = ceil(($VehicleData['VehicleOffer']['miles'] ?? 0) * 12 / 365 * 7);
        $vehicle['monthly_miles']     = $VehicleData['VehicleOffer']['miles'] ?? 0;
        $vehicle['days']              = $VehicleData['VehicleOffer']['days'] ?? 0;
        $vehicle['Owner']             = $VehicleData['Owner'];
        $vehicle['Vehicle']           = $VehicleData['Vehicle'];
        $vehicle['Vehicle']['plate_number'] = empty($VehicleData['Vehicle']['plate_number']) ? '' : $VehicleData['Vehicle']['plate_number'];
        $vehicle['financing']         = $VehicleData['VehicleOffer']['financing'] ?? 0;
        $vehicle['OrderDepositRule']  = !empty($VehicleData['VehicleOffer']['calculation'])
            ? json_decode($VehicleData['VehicleOffer']['calculation'], true)
            : $dr;
        $vehicle['disposition_fee']   = sprintf('%0.2f', $dr['return_fee'] ?? 0);
        $vehicle['day_rent']          = sprintf('%0.2f', ($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)));
        $vehicle['weekly_rent']       = sprintf('%0.2f', (($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)) * 7));
        $vehicle['monthly_rent']      = sprintf('%0.2f', (($vehicle['total_rent'] / max($VehicleData['VehicleOffer']['duration'], 1)) * 365 / 12));
        $vehicle['booking_rental']    = sprintf('%0.2f', ($vehicle['total_rent'] + ($VehicleData['VehicleOffer']['duration'] * $insurance) + $vehicle['initial_fee']));

        $vehicle = $this->resolveOwnerSignature($vehicle);
        $vehicle = $this->resolveOwnerSignatureName($vehicle);

        $vehicle['RenterSign']    = config('app.url') . '/files/signatures/' . ($userObj['id'] ?? '') . '.png';
        $vehicle['support_phone'] = config('app.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency']      = $this->userObj['currency'] ?? '$';
        $vehicle['odometer']      = $VehicleData['Vehicle']['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['tbd'] = $tbd;

        $nextBookingDuration = !empty($vehicle['OrderDepositRule']['duration_opt'])
            ? $this->getFromTierDataStubAgreement($vehicle['OrderDepositRule']['duration_opt'], $start_date, $end_date)
            : $vehicle['days'];
        $vehicle['next_duration'] = $nextBookingDuration ?: $vehicle['days'];
        $vehicle['end_of_lease']  = date('m/d/Y', strtotime($start_date . ' +' . ($VehicleData['VehicleOffer']['days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'File generated', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename]];
    }

    /**
     * Generate agreement for a quote.
     */
    public function _generateAgreementForQuote($dataValues, $userObj): array
    {
        $lease_id = $dataValues->list_id;
        $pto = (isset($dataValues->financing) && strtolower($dataValues->financing) == 'pto') ? 1 : 0;
        $start_datetime = date('Y-m-d H:i:s', strtotime($dataValues->startdatetime));
        $end_datetime   = date('Y-m-d H:i:s', strtotime($start_datetime . " +$dataValues->days days"));

        $rental_options      = isset($dataValues->rental_options) ? sprintf('%0.2f', preg_replace('/[^0-9,.]/', '', $dataValues->rental_options)) : 0;
        $initial_fee_options = isset($dataValues->initial_fee_options) ? preg_replace('/[^0-9,.]/', '', $dataValues->initial_fee_options) : 0;
        $emf_options         = isset($dataValues->emf_options) ? ($dataValues->emf_options * 12 / 365) : 0;
        $miles_options       = isset($dataValues->miles_options) ? sprintf('%0.2f', ($dataValues->miles_options * 12 / 365)) : 0;

        $vehicle = DB::table('vehicles as Vehicle')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'Vehicle.user_id')
            ->where('Vehicle.id', $lease_id)
            ->select(
                'Vehicle.*',
                'Owner.id as owner_id', 'Owner.first_name as owner_first_name', 'Owner.last_name as owner_last_name',
                'Owner.company_address as address', 'Owner.company_city as city', 'Owner.company_state as state',
                'Owner.company_zip as zip', 'Owner.timezone', 'Owner.distance_unit', 'Owner.currency as owner_currency',
                'Owner.company_name', 'Owner.representative_name', 'Owner.representative_role', 'Owner.representative_sign',
                'Owner.contact_number'
            )
            ->first();

        if (empty($vehicle)) {
            return ['status' => false, 'message' => 'Sorry, this vehicle is not available for selected date range.', 'result' => []];
        }
        $vehicleArr = (array) $vehicle;

        $depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $vehicleArr['user_id'])
            ->select('fixed_program_cost', 'deposit_title', 'is_deposit_refundable')
            ->first();
        $fixedProgramCost = !empty($depositTemplateObj) ? $depositTemplateObj->fixed_program_cost : 0;

        $DepositRule = DB::table('deposit_rules')->where('vehicle_id', $vehicleArr['id'])->first();
        $dr = $DepositRule ? (array) $DepositRule : [];

        $tbd = false;
        $insurance = 0;
        if (!empty($dr) && in_array($dr['insurance_payer'] ?? 0, [3, 4, 5, 6, 7])) {
            $tbd = true;
        }

        $userObj['licence_number'] = $this->decryptLicenceNumber($userObj['licence_number'] ?? '');
        $filename     = time() . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $vehData = [];
        $vehData['Vehicle']       = $vehicleArr;
        $vehData['Owner']         = $vehicleArr;
        $vehData['time_fee']      = 0;
        $vehData['tax']           = 0;
        $vehData['dia_fee']       = 0;
        $vehData['deposit_amt']   = '0.00';
        $vehData['initial_fee']   = '0.00';
        $vehData['dia_insu']      = 0;
        $vehData['monthly_insurance'] = !$tbd ? sprintf('%0.2f', (30 * $insurance)) : 'TBD';
        $vehData['daily_insurance']   = !$tbd ? sprintf('%0.2f', $insurance) : 'TBD';
        $vehData['weekly_insurance']  = !$tbd ? sprintf('%0.2f', (7 * $insurance)) : 'TBD';
        $vehData['total_rent']     = '0.00';
        $vehData['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehData['today']          = date('m/d/Y');
        $vehData['end_datetime']   = date('Y-m-d H:i:s', strtotime($start_datetime . ' +28 days'));
        $vehData['Renter']         = $userObj;
        $vehData['DepositRule']    = $dr;
        $vehData['financing']      = $vehicleArr['financing'] ?? 0;
        $vehData['disposition_fee']    = sprintf('%0.2f', $dr['return_fee'] ?? 0);
        $vehData['extra_mileage_fee']  = $dr['emf'] ?? 0;
        $vehData['lateness_fee']       = '0.00';
        $vehData['schedulePayment']    = [];
        $vehData['daily_miles']        = ceil($miles_options);
        $vehData['weekly_miles']       = ceil($miles_options * 7);
        $vehData['monthly_miles']      = $dataValues->miles_options ?? 0;
        $vehData['days']               = 1;

        $vehData = $this->resolveOwnerSignature($vehData);
        $vehData = $this->resolveOwnerSignatureName($vehData);

        $vehData['RenterSign']    = config('app.url') . '/files/signatures/' . ($userObj['id'] ?? '') . '.png';
        $vehData['support_phone'] = config('app.support_phone', '');
        $vehData['distance_unit'] = $vehicleArr['distance_unit'] ?? '';
        $vehData['currency']      = $this->userObj['currency'] ?? '$';
        $vehData['odometer']      = $vehicleArr['odometer'] ?? 0;
        $vehData['fixed_program_cost'] = $fixedProgramCost;
        $vehData['tbd'] = $tbd;

        $vehData['deposit_description'] = $this->buildDepositDescription($depositTemplateObj, $vehData['currency'], $vehData['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehData, $filefullname);

        return ['status' => true, 'message' => 'File generated', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename]];
    }

    /**
     * Generate Docusign agreement for pending booking.
     */
    public function _generateDocusignAgreement($booking_id, $dailyInsurance = 0): array
    {
        $CsLeaselists = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'VehicleReservation.user_id')
            ->where('VehicleReservation.id', $booking_id)
            ->select(
                'VehicleReservation.*', 'Vehicle.*',
                'Owner.id as owner_id', 'Owner.first_name as owner_first_name', 'Owner.last_name as owner_last_name',
                'Owner.company_address as address', 'Owner.company_city as city', 'Owner.company_state as state',
                'Owner.company_zip as zip', 'Owner.timezone', 'Owner.distance_unit', 'Owner.company_name',
                'Owner.representative_name', 'Owner.representative_role', 'Owner.representative_sign', 'Owner.contact_number'
            )
            ->first();

        if (empty($CsLeaselists)) {
            return ['status' => false, 'message' => 'Sorry, you are not authorized for this booking.', 'result' => []];
        }

        $reservation = (array) $CsLeaselists;
        $start_datetime = $parent_datetime = $this->formatForUser($reservation['start_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);
        $end_datetime = $this->formatForUser($reservation['end_datetime'], 'Y-m-d H:i:s', $reservation['timezone']);

        $OrderDepositRule = DB::table('order_deposit_rules')->where('vehicle_reservation_id', $reservation['id'])->first();
        $odr = $OrderDepositRule ? (array) $OrderDepositRule : [];

        $days = $this->daysBetweenDates($reservation['start_datetime'], $reservation['end_datetime']);

        $vehicle = [];
        $vehicle['Vehicle'] = $reservation;
        $vehicle['Owner']   = $reservation;
        $vehicle['time_fee']   = sprintf('%0.2f', (($odr['rental'] ?? 0) * $days));
        $vehicle['tax']        = 0;
        $vehicle['dia_fee']    = 0;
        $vehicle['deposit_amt']  = sprintf('%0.2f', $odr['deposit_amt'] ?? 0);
        $vehicle['initial_fee']  = sprintf('%0.2f', $odr['initial_fee'] ?? 0);
        $vehicle['dia_insu']     = $odr['emf_insu_rate'] ?? 0;
        $vehicle['msrp']         = $odr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $odr['premium_msrp'] ?? 0;
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (30 * $dailyInsurance));
        $vehicle['daily_insurance']   = sprintf('%0.2f', $dailyInsurance);
        $vehicle['weekly_insurance']  = sprintf('%0.2f', (7 * $dailyInsurance));

        $filename     = $reservation['id'] . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $initialfeeOpt = !empty($odr['initial_fee_opt']) ? json_decode($odr['initial_fee_opt'], true) : [];
        $initialfeeOpt = array_merge([['after_day_date' => date('m/d/Y', strtotime($parent_datetime)), 'amount' => $vehicle['initial_fee']]], $initialfeeOpt);

        $vehicle['total_rent']     = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent                 = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today']          = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime']   = date('Y-m-d', strtotime($start_datetime . ' +28 days'));
        $vehicle['Renter']         = $this->userObj['User'] ?? [];
        unset($vehicle['Renter']['licence_number']);
        $vehicle['extra_mileage_fee'] = $odr['emf_rate'] ?? 0;
        $vehicle['lateness_fee']      = '0.00';
        $vehicle['schedulePayment']   = $this->makeDateInOptionStub(date('m/d/Y', strtotime($start_datetime)), $initialfeeOpt);
        $vehicle['daily_miles']       = ceil($odr['miles'] ?? 0);
        $vehicle['weekly_miles']      = ceil(($odr['miles'] ?? 0) * 7);
        $vehicle['monthly_miles']     = ceil(($odr['miles'] ?? 0) * 365 / 12);
        $vehicle['days']              = $days;
        $vehicle['day_rent']          = sprintf('%0.2f', ($totalRent / max($days, 1)));
        $vehicle['weekly_rent']       = sprintf('%0.2f', (($totalRent / max($days, 1)) * 7));
        $vehicle['monthly_rent']      = sprintf('%0.2f', (($totalRent / max($days, 1)) * 365 / 12));
        $vehicle['booking_rental']    = sprintf('%0.2f', ($vehicle['total_rent'] + ($vehicle['days'] * $dailyInsurance) + ($odr['initial_fee'] ?? 0)));

        $vehicle = $this->resolveOwnerSignature($vehicle);
        $vehicle = $this->resolveOwnerSignatureName($vehicle);

        $vehicle['RenterSign']    = '';
        $vehicle['support_phone'] = config('app.support_phone', '');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? '';
        $vehicle['currency']      = $this->userObj['User']['currency'] ?? '$';
        $vehicle['financing']     = $odr['financing'] ?? 0;
        $vehicle['OrderDepositRule'] = !empty($odr['calculation']) ? json_decode($odr['calculation'], true) : $odr;

        $DepositRuleObj = DB::table('deposit_rules')
            ->where('vehicle_id', $reservation['vehicle_id'])
            ->select('financing', 'financing_type', 'emf', 'return_fee', 'tax')
            ->first();
        $vehicle['DepositRule']       = $DepositRuleObj ? (array) $DepositRuleObj : [];
        $vehicle['DepositRule']['emf_rate'] = $odr['emf'] ?? 0;
        $vehicle['odometer']          = $reservation['last_mile'] ?? $reservation['odometer'] ?? 0;
        $vehicle['fixed_program_cost'] = 0;
        $vehicle['disposition_fee']    = sprintf('%0.2f', $DepositRuleObj->return_fee ?? 0);

        $nextBookingDuration = !empty($odr['duration_opt'])
            ? $this->getFromTierDataStubAgreement($odr['duration_opt'], $start_datetime, $end_datetime)
            : $days;
        $vehicle['next_duration'] = $nextBookingDuration ?: $days;
        $vehicle['end_of_lease']  = date('m/d/Y', strtotime(($odr['start_datetime'] ?? 'now') . '+' . ($odr['num_of_days'] ?? 0) . ' days'));

        $vehicle['deposit_description'] = $this->buildDepositDescription(null, $vehicle['currency'], $vehicle['deposit_amt']);

        // TODO: Replace with Agreement service – generateQuoteAgreementPdf()
        // $this->Agreement->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/temp/' . $filename, 'filepath' => $filefullname]];
    }

    /**
     * Generate CMM service card.
     */
    public function _generateCMMCard($renterid, $orderid = null): array
    {
        if ($orderid !== null) {
            $conditions = ['cs_orders.id' => $orderid];
        } else {
            $conditions = ['cs_orders.renter_id' => $renterid, 'cs_orders.status' => 1];
        }

        $query = DB::table('cs_orders')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'cs_orders.vehicle_id')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'cs_orders.renter_id')
            ->select(
                'cs_orders.id', 'cs_orders.increment_id', 'cs_orders.parent_id',
                'Vehicle.year', 'Vehicle.make', 'Vehicle.model', 'Vehicle.vin_no', 'Vehicle.ccm_auth_no',
                'Driver.first_name', 'Driver.last_name'
            );

        foreach ($conditions as $col => $val) {
            $query->where($col, $val);
        }
        $CsOrderObj = $query->first();

        if (empty($CsOrderObj)) {
            return ['status' => false, 'message' => 'Sorry, you are not authorized for this booking.', 'result' => []];
        }

        $filename = 'cmm-service-card-' . ($CsOrderObj->parent_id ?: $CsOrderObj->id) . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        $dataToPass = [
            'year'              => $CsOrderObj->year,
            'make'              => $CsOrderObj->make,
            'model'             => $CsOrderObj->model,
            'vin'               => $CsOrderObj->vin_no,
            'ccm_auth_no'       => !empty($CsOrderObj->ccm_auth_no) ? $CsOrderObj->ccm_auth_no : 'XXXXXXXXXXXXXX',
            'vehicle_unique_id' => substr($CsOrderObj->vin_no, -6),
            'driver_name'       => $CsOrderObj->first_name . ' ' . $CsOrderObj->last_name,
            'logo'              => '<img src="' . config('app.url') . '/img/cmm-card-logo.png" alt="logo"/>',
        ];

        // TODO: Replace with Agreement service – generateCMMCard()
        // $this->Agreement->generateCMMCard($dataToPass, $filefullname);

        return ['status' => true, 'message' => 'Success', 'result' => ['file' => config('app.url') . '/files/agreements/' . $filename, 'filepath' => $filefullname]];
    }

    // ------------------------------------------------------------------
    // Private helper methods
    // ------------------------------------------------------------------

    /** TODO: Migrate Security::decrypt – replace with Crypt::decryptString or legacy decrypt */
    private function decryptLicenceNumber(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        // TODO: Implement actual decryption matching legacy Security::decrypt
        return $encrypted;
    }

    /** Resolve owner signature URL. */
    private function resolveOwnerSignature(array $vehicle): array
    {
        if (!empty($vehicle['Owner']['representative_sign'])
            && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))
        ) {
            $vehicle['OwnerSign'] = config('app.url') . '/files/userdocs/' . $vehicle['Owner']['representative_sign'];
        } else {
            // TODO: Replace with Signature service – createSignature()
            $vehicle['OwnerSign'] = config('app.url') . '/files/signatures/' . ($vehicle['Owner']['id'] ?? $vehicle['Owner']['owner_id'] ?? '') . '.png';
        }
        return $vehicle;
    }

    /** Resolve owner signature name and role. */
    private function resolveOwnerSignatureName(array $vehicle): array
    {
        $ownerFirstName = $vehicle['Owner']['first_name'] ?? $vehicle['Owner']['owner_first_name'] ?? '';
        $ownerLastName  = $vehicle['Owner']['last_name'] ?? $vehicle['Owner']['owner_last_name'] ?? '';

        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name'])
            ? $vehicle['Owner']['representative_name']
            : $ownerFirstName . ' ' . $ownerLastName;

        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role'])
            ? $vehicle['Owner']['representative_role']
            : 'COO';

        return $vehicle;
    }

    /** Build deposit description text. */
    private function buildDepositDescription($depositTemplateObj, string $currency, string $depositAmt): string
    {
        $description = sprintf(
            'Deposit. In addition to the fees listed in Section 2, Renter shall pay a deposit of %s%s at the time this Agreement is signed. Owner may use the deposit funds to cover any amounts due under this Agreement',
            $currency, $depositAmt
        );

        if (!empty($depositTemplateObj)) {
            $title = !empty($depositTemplateObj->deposit_title) ? $depositTemplateObj->deposit_title : 'Deposit';
            if ($depositTemplateObj->is_deposit_refundable == 1 || $depositTemplateObj->is_deposit_refundable == 0) {
                $description = sprintf(
                    '%s. In addition to the fees listed in Section 2, Renter shall pay a deposit of %s%s at the time this Agreement is signed. Owner may use the this funds to cover any amounts due under this Agreement',
                    $title, $currency, $depositAmt
                );
            }
        }

        return $description;
    }

    /** TODO: Migrate Common->makeDateInOption() */
    private function makeDateInOptionStub(string $startDate, array $options): array
    {
        return $options;
    }

    /** TODO: Migrate OrderDepositRule->getFromTierData() */
    private function getFromTierDataStubAgreement($durationOpt, $startDatetime, $endDatetime)
    {
        return null;
    }
}
