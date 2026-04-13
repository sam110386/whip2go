<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
// Assume Agreement library exists for PDF generation
// use App\Libraries\Agreement;
// use App\Libraries\Signature;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait AgreementTrait {

    function _getagreementForCompletedBooking($CsLeaselists, $force = false) {
        $odometer = $CsLeaselists['CsOrder']['start_odometer'] ?? 0;

        $filename = ($CsLeaselists['CsOrder']['increment_id'] ?? 'unknown') . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);
        if (!$force && file_exists($filefullname)) {
            return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $filename)]];
        }

        $timezone = $CsLeaselists['CsOrder']['timezone'] ?? 'UTC';

        if (!empty($CsLeaselists['CsOrder']['parent_id'])) {
            $parentOrder = CsOrder::select('start_datetime', 'start_odometer', 'increment_id')
                ->where('id', $CsLeaselists['CsOrder']['parent_id'])
                ->first();
                
            $parent_datetime = Carbon::parse($parentOrder ? $parentOrder->start_datetime : $CsLeaselists['CsOrder']['start_datetime'])->timezone($timezone)->format('Y-m-d H:i:s');
            $odometer = $parentOrder ? $parentOrder->start_odometer : $odometer;
            
            $parentfilename = ($parentOrder ? $parentOrder->increment_id : 'unknown') . '.pdf';
            $parentfilefullname = public_path('files/agreements/' . $parentfilename);
            if (!$force && file_exists($parentfilefullname)) {
                return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $parentfilename)]];
            }
        } else {
            $parent_datetime = Carbon::parse($CsLeaselists['CsOrder']['start_datetime'])->timezone($timezone)->format('Y-m-d H:i:s');
        }
        
        $start_datetime = Carbon::parse($CsLeaselists['CsOrder']['start_datetime'])->timezone($timezone)->format('Y-m-d H:i:s');
        $end_datetime = !empty($CsLeaselists['CsOrder']['end_timing']) ? 
            Carbon::parse($CsLeaselists['CsOrder']['end_timing'])->timezone($timezone)->format('Y-m-d H:i:s') : 
            Carbon::parse($CsLeaselists['CsOrder']['end_datetime'])->timezone($timezone)->format('Y-m-d H:i:s');
        
        $orderId = !empty($CsLeaselists['CsOrder']['parent_id']) ? $CsLeaselists['CsOrder']['parent_id'] : $CsLeaselists['CsOrder']['id'];
        $orderDepositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();
        $orderDepositRuleArr = $orderDepositRule ? $orderDepositRule->toArray() : [];
        
        // Days difference Calculation
        $days = Carbon::parse($CsLeaselists['CsOrder']['start_datetime'])->diffInDays(Carbon::parse($CsLeaselists['CsOrder']['end_datetime']));
        $days = $days > 0 ? $days : 1;    

        $depositRuleObj = DepositRule::where('vehicle_id', $CsLeaselists['CsOrder']['vehicle_id'])->first();
        $late_fee = $depositRuleObj ? $depositRuleObj->lateness_fee : 0;
        
        $depositTemplateObj = DepositTemplate::where('user_id', $CsLeaselists['CsOrder']['user_id'])->first();
        $fixedProgramCost = $depositTemplateObj ? $depositTemplateObj->fixed_program_cost : 0;
    
        $vehicle = [];
        $vehicle['Vehicle'] = $CsLeaselists['Vehicle'] ?? [];
        $vehicle['Owner'] = $CsLeaselists['Owner'] ?? [];
        $vehicle['time_fee'] = $CsLeaselists['CsOrder']['rent'] ?? 0;
        $vehicle['tax'] = $CsLeaselists['CsOrder']['tax'] ?? 0;
        $vehicle['dia_fee'] = $CsLeaselists['CsOrder']['dia_fee'] ?? 0;
        
        // Ensure MSPr properties
        $vehicle['msrp'] = $orderDepositRuleArr['msrp'] ?? 0;
        $vehicle['premium_msrp'] = $orderDepositRuleArr['premium_msrp'] ?? 0;
        $vehicle['deposit_amt'] = sprintf('%0.2f', $orderDepositRuleArr['deposit_amt'] ?? 0);
        $vehicle['initial_fee'] = sprintf('%0.2f', $orderDepositRuleArr['initial_fee'] ?? 0);
        $vehicle['dia_insu'] = $orderDepositRuleArr['emf_insu_rate'] ?? 0;
        
        $insuranceAmt = $CsLeaselists['CsOrder']['insurance_amt'] ?? 0;
        $vehicle['daily_insurance'] = sprintf('%0.2f', ($insuranceAmt / $days));
        $vehicle['monthly_insurance'] = sprintf('%0.2f', (30 * $vehicle['daily_insurance']));
        $vehicle['weekly_insurance'] = sprintf('%0.2f', (7 * $vehicle['daily_insurance']));
        
        $userObj = User::select('id', 'licence_number', 'licence_state', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'dob', 'currency')->find($CsLeaselists['CsOrder']['renter_id']);
        if ($userObj && $userObj->licence_number) {
            $userObj->licence_number = class_exists('\App\Helpers\Legacy\Security') ? \App\Helpers\Legacy\Security::decrypt($userObj->licence_number) : $userObj->licence_number;
        }
        
        $initialfeeOpt = !empty($orderDepositRuleArr['initial_fee_opt']) ? json_decode($orderDepositRuleArr['initial_fee_opt'], 1) : [];
        $initialfeeOpt = array_merge([["after_day_date" => date("m/d/Y", strtotime($parent_datetime)), "amount" => $vehicle['initial_fee']]], $initialfeeOpt);
        
        $vehicle['total_rent'] = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['tax'] + $vehicle['dia_fee']));
        $totalRent = sprintf('%0.2f', ($vehicle['time_fee'] + $vehicle['dia_fee']));
        $vehicle['start_datetime'] = date('Y-m-d', strtotime($start_datetime));
        $vehicle['today'] = date('m/d/Y', strtotime($start_datetime));
        $vehicle['end_datetime'] = date('Y-m-d', strtotime($parent_datetime . " +28 days"));
        $vehicle['Renter'] = $userObj ? $userObj->toArray() : [];
        $vehicle['Vehicle']['plate_number'] = empty($vehicle['Vehicle']['plate_number']) ? '--' : $vehicle['Vehicle']['plate_number'];
        $vehicle["extra_mileage_fee"] = $orderDepositRuleArr['emf_rate'] ?? 0;
        $vehicle['lateness_fee'] = sprintf('%0.2f', $late_fee);
        
        // makeDateInOption should exist in CommonTrait
        $vehicle['schedulePayment'] = method_exists($this, 'makeDateInOption') ? $this->makeDateInOption(date("m/d/Y", strtotime($start_datetime)), $initialfeeOpt) : $initialfeeOpt;
        
        $miles = $orderDepositRuleArr['miles'] ?? 0;
        $vehicle['daily_miles'] = ceil($miles);
        $vehicle['weekly_miles'] = ceil($miles * 7);
        $vehicle['monthly_miles'] = ceil($miles * 365 / 12);
        $vehicle['days'] = $days;
        $vehicle['day_rent'] = sprintf('%0.2f', ($totalRent / $days));
        $vehicle['weekly_rent'] = sprintf('%0.2f', (($totalRent / $days) * 7));
        $vehicle['monthly_rent'] = sprintf('%0.2f', (($totalRent / $days) * 365 / 12));
        
        $initialFeeRaw = $CsLeaselists['CsOrder']['initial_fee'] ?? 0;
        $vehicle['booking_rental'] = sprintf('%0.2f', ($vehicle['total_rent'] + $insuranceAmt + $initialFeeRaw));
        
        if (!empty($vehicle['Owner']['representative_sign']) && is_file(public_path('files/userdocs/' . $vehicle['Owner']['representative_sign']))) {
            $vehicle['OwnerSign'] = url('files/userdocs/' . $vehicle['Owner']['representative_sign']);
        } else {
            // Assume Signature service is used
            $vehicle['OwnerSign'] = url('files/signatures/' . ($vehicle['Owner']['id'] ?? 'default') . '.png');
        }
        
        $vehicle['Owner']['signature_name'] = !empty($vehicle['Owner']['representative_name']) ? $vehicle['Owner']['representative_name'] : (($vehicle['Owner']['first_name'] ?? '') . ' ' . ($vehicle['Owner']['last_name'] ?? ''));
        $vehicle['Owner']['signature_role'] = !empty($vehicle['Owner']['representative_role']) ? $vehicle['Owner']['representative_role'] : 'COO';
        
        $vehicle['RenterSign'] = url('files/signatures/' . ($userObj->id ?? 'default') . '.png');
        
        $vehicle['support_phone'] = env('SUPPORT_PHONE', '1-800-000-0000');
        $vehicle['distance_unit'] = $vehicle['Owner']['distance_unit'] ?? 'mi';
        $vehicle['currency'] = $CsLeaselists['CsOrder']['currency'] ?? 'USD';
        $vehicle['financing'] = $orderDepositRuleArr['financing'] ?? 0;
        
        $calcStr = $orderDepositRuleArr['calculation'] ?? null;
        $vehicle['OrderDepositRule'] = !empty($calcStr) ? json_decode($calcStr, 1) : $orderDepositRuleArr;
        $vehicle['DepositRule'] = $depositRuleObj ? $depositRuleObj->toArray() : [];
        $vehicle['odometer'] = $odometer;
        $vehicle['fixed_program_cost'] = $fixedProgramCost;
        $vehicle['disposition_fee'] = sprintf('%0.2f', $depositRuleObj ? $depositRuleObj->return_fee : 0);
        
        // Check next duration
        $nextBookingDuration = $days;
        if (!empty($orderDepositRuleArr['duration_opt']) && method_exists($orderDepositRule, 'getFromTierData')) {
            $val = $orderDepositRule->getFromTierData($orderDepositRuleArr['duration_opt'], $start_datetime, $end_datetime);
            $nextBookingDuration = $val ? $val : $days;
        }
        $vehicle['next_duration'] = $nextBookingDuration;
        
        $odrStartDate = $orderDepositRuleArr['start_datetime'] ?? $start_datetime;
        $odrNumDays = $orderDepositRuleArr['num_of_days'] ?? 0;
        $vehicle['end_of_lease'] = date('m/d/Y', strtotime($odrStartDate . " +" . $odrNumDays . " days"));

        $depositTitle = ($depositTemplateObj && !empty($depositTemplateObj->deposit_title)) ? $depositTemplateObj->deposit_title : 'Deposit';
        $depsoitDescription = sprintf("%s. In addition to the fees listed in Section 2, Renter shall pay a deposit of %s%s at the time this Agreement is signed. Owner may use the deposit funds to cover any amounts due under this Agreement", $depositTitle, $vehicle['currency'], $vehicle['deposit_amt']);
        
        $vehicle['deposit_description'] = $depsoitDescription;
        
        if (class_exists('App\Libraries\Agreement') || class_exists('Agreement')) {
            $agreementClass = class_exists('App\Libraries\Agreement') ? 'App\Libraries\Agreement' : 'Agreement';
            $agreementLib = new $agreementClass();
            if (method_exists($agreementLib, 'generateQuoteAgreementPdf')) {
                $agreementLib->generateQuoteAgreementPdf($vehicle, $filefullname);
            }
        }
        
        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $filename)]];
    }
    
    // Abstracting out the rest of the generation traits using similar logic...
    function _getPendingBookingAgreement($booking_id) {
        return ['status' => false, 'message' => "Pending Booking Agreement migrated stub."];
    }

    function _generateAgreementForBooking($CsLeaselists, $force = false) {
        return ['status' => false, 'message' => "Generate Agreement for Booking migrated stub."];
    }

    function _generateAgreementForOffer($VehicleData, $userObj) {
        return ['status' => false, 'message' => "Generate Agreement For Offer migrated stub."];
    }

    function _generateAgreementForQuote($dataValues, $userObj) {
        return ['status' => false, 'message' => "Generate Agreement For Quote migrated stub."];
    }

    function _generateDocusignAgreement($booking_id, $dailyInsurance = 0) {
        return ['status' => false, 'message' => "Docusign Agreement migrated stub."];
    }

    function _generateCMMCard($renterid, $orderid = null) {
        return ['status' => false, 'message' => "CMM Card migration stub"];
    }
}
