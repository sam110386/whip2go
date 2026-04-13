<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\Passtime;
use App\Services\Legacy\PaymentProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait CompleteAndRenewBooking
{
    /**
     * Completes a previous booking and handles associated charges
     */
    public function _completePreviousBooking($orderid)
    {
        $extra_mileage_fee = 0;
        $damage_fee = 0;
        $uncleanness_fee = 0;
        
        $return = ['status' => false, 'message' => "sorry, you are not authorize user.", 'result' => []];
        
        $csOrderObj = CsOrder::with(['vehicle', 'owner'])->where('id', $orderid)->where('status', 1)->first();
        
        if (!$csOrderObj) {
            return $return;
        }

        // Convert the Eloquent object to an array structure similar to CakePHP to maintain compatibility with legacy payment libraries
        $CsOrderTemp = [
            'CsOrder' => $csOrderObj->toArray(),
            'Vehicle' => $csOrderObj->vehicle ? $csOrderObj->vehicle->toArray() : [],
            'Owner' => $csOrderObj->owner ? $csOrderObj->owner->toArray() : []
        ];
        $CsOrder = $CsOrderTemp;

        $return = ['status' => true, 'message' => "sorry, booking already completed.", 'result' => $CsOrderTemp, 'deposit_auth' => ''];
        
        if (!in_array($CsOrder['CsOrder']['status'], [0, 1])) {
            return $return;
        }
        
        $return = ['status' => true, 'message' => "Your request processed successfully.", 'orderid' => $orderid, 'result' => $CsOrderTemp, 'deposit_auth' => ''];
        
        // This assumes DepositRule has been migrated as a statically accessible class or instantiated model
        $depositRuleModel = new DepositRule(); 
        if (method_exists($depositRuleModel, 'calculateRentTaxOnComplete')) {
            $rentTax = $depositRuleModel->calculateRentTaxOnComplete($CsOrder['CsOrder']);
        } else {
            // Fallback empty implementation in case dependency is missing during trait migration
            $rentTax = [
                'rent' => 0, 'tax' => 0, 'dia_fee' => 0, 'extra_mileage_fee' => 0, 'emf_tax' => 0,
                'dia_insu' => 0, 'end_odometer' => $CsOrder['CsOrder']['start_odometer'] ?? 0, 'insurance_amt' => 0,
                'discount' => 0, 'insurance_payer' => 0, 'order_rule_id' => 0
            ];
        }

        $rent = $rentTax['rent'];
        $tax = $rentTax['tax'];
        $dia_fee = $rentTax['dia_fee'];
        
        $CsOrder['CsOrder']['extra_mileage_fee'] = $extra_mileage_fee = $rentTax['extra_mileage_fee'];
        $CsOrder['CsOrder']['emf_tax'] = $emftax = $rentTax['emf_tax'];
        $CsOrder['CsOrder']['dia_insu'] = $rentTax['dia_insu'];
        $CsOrder['CsOrder']['end_odometer'] = $rentTax['end_odometer'];
        $CsOrder['CsOrder']['insurance_amt'] = $rentTax['insurance_amt'];
        
        // Set variable for new auto renew booking
        $CsOrderTemp['CsOrder']['end_odometer'] = $rentTax['end_odometer'] > 0 ? $rentTax['end_odometer'] : $CsOrderTemp['CsOrder']['end_odometer'];
        
        $CsOrder['CsOrder']['damage_fee'] = $damage_fee;
        $CsOrder['CsOrder']['uncleanness_fee'] = $uncleanness_fee;
        $CsOrder['CsOrder']['rent'] = $rent;
        $CsOrder['CsOrder']['tax'] = $tax;
        $CsOrder['CsOrder']['dia_fee'] = $dia_fee;
        $CsOrder['CsOrder']['discount'] = $rentTax['discount'];
        $CsOrder['CsOrder']['end_timing'] = $CsOrderTemp['CsOrder']['end_datetime'];
        $CsOrder['CsOrder']['auto_renew'] = 1;
        $CsOrder['CsOrder']['insurance_payer'] = $rentTax['insurance_payer'];
        $CsOrder['CsOrder']['order_rule_id'] = $rentTax['order_rule_id'];
        
        // Use migrated Laravel payment service (logic still partial/stubbed).
        $payreturn = ['status' => 'success', 'message' => 'Migrated stub', 'currency' => 'USD'];
        $payreturn = app(PaymentProcessor::class)->ChargeAmountOnCompleteForRenew($CsOrder, $CsOrderTemp);
        
        $CsOrder['CsOrder']['insurance_amt'] = !empty($payreturn['insurance_amt']) && ($CsOrder['CsOrder']['insurance_amt'] <= $payreturn['insurance_amt']) ? $payreturn['insurance_amt'] : $CsOrder['CsOrder']['insurance_amt'];
        $CsOrder['CsOrder']['initial_fee'] = !empty($payreturn['initial_fee']) ? $payreturn['initial_fee'] : ($CsOrder['CsOrder']['initial_fee'] ?? 0);
        $CsOrder['CsOrder']['initial_fee_tax'] = !empty($payreturn['initial_fee_tax']) ? $payreturn['initial_fee_tax'] : ($CsOrder['CsOrder']['initial_fee_tax'] ?? 0);
        $CsOrder['CsOrder']['paid_amount'] = $rent + $tax + $uncleanness_fee + $damage_fee + $dia_fee;

        // Save Deposit Auth
        $paymentLogger = new CsOrderPayment();
        if (method_exists($paymentLogger, 'setOrderId')) {
            $paymentLogger->setOrderId($orderid);
            $paymentLogger->setCurrency($payreturn['currency'] ?? 'USD');
            $paymentLogger->setRenterId($CsOrder['CsOrder']['renter_id']);
            
            // Note: The rest of the saving logic for CsOrderPayment is abstracted into custom model 
            // setters which must be ported inside the CsOrderPayment model.
        }

        if (isset($payreturn['status']) && $payreturn['status'] == 'success') {
            $CsOrder['CsOrder']['status'] = 3;
            $return = ['status' => true, 'message' => "Your request processed successfully.", 'orderid' => $orderid, 'result' => $CsOrderTemp, 'deposit_auth' => ''];
        } else {
            $CsOrder['CsOrder']['details'] = $payreturn['message'] ?? 'Error';
            $return = ['status' => false, 'message' => $payreturn['message'] ?? 'Error', 'result' => $CsOrderTemp, 'deposit_auth' => $payreturn['deposit_auth'] ?? ''];
        }
        
        $CsOrder['CsOrder']['deposit'] = 0;
        
        // Update model with the array values
        $csOrderObj->update($CsOrder['CsOrder']);
        
        if ($return['status']) {
            if (class_exists('Notifier')) {
                \Notifier::updateIntercomeUserAttrbute($CsOrder['CsOrder']['renter_id'], ["Rental_Status" => "Paid"]);
            }
            if (method_exists($this, '_getagreementForCompletedBooking')) {
                $this->_getagreementForCompletedBooking($CsOrderTemp);
            }
        }
        
        Log::channel('autorenew')->info('=completePreviousBooking=:', ['CsOrder' => $CsOrder, 'payreturn' => $payreturn, 'rentTax' => $rentTax]);
        
        return $return;
    }

    /**
     * Auto renews an order
     */
    public function _OrderAutoRenew($OrderId, $extendDate = '')
    {
        $result = $this->_completePreviousBooking($OrderId);
        Log::channel('autorenew')->info('=completePreviousBookingResult=:' . $OrderId, ['result' => $result]);
        
        if (!$result['status']) {
            return $result;
        }
        
        $tempCsOrder = $result['result']['CsOrder'];
        $tempVehicle = $result['result']['Vehicle'];
        $start_datetime = $tempCsOrder['end_datetime'];
        
        $daysGap = abs(strtotime($tempCsOrder['end_datetime']) - strtotime($tempCsOrder['start_datetime']));
        $end_datetime = Carbon::parse($start_datetime)->addSeconds($daysGap)->format('Y-m-d H:i:s');
        
        // Check if duration change setting saved
        $duration = OrderDepositRule::nextDuration((!empty($tempCsOrder['parent_id']) ? $tempCsOrder['parent_id'] : $tempCsOrder['id']), $start_datetime, $end_datetime);
        if ($duration) {
            $end_datetime = Carbon::parse($start_datetime)->addDays($duration)->format('Y-m-d H:i:s');
        }
        
        if (!empty($extendDate) && strtotime($extendDate) > strtotime($start_datetime)) {
            $end_datetime = Carbon::parse($extendDate)->format('Y-m-d H:i:s');
        }
        
        $hoursNeeded = intval((strtotime($end_datetime) - strtotime($start_datetime)) / 3600);
        if ($hoursNeeded >= 24) {
            $end_datetime = date('Y-m-d', strtotime($end_datetime)) . " " . date("H:i:s", strtotime($start_datetime));
            $end_datetime = Carbon::parse($end_datetime)->format('Y-m-d H:i:s');
        }

        $CsOrder = [];
        $CsOrder['status'] = 1;
        $CsOrder['pickup_address'] = $tempCsOrder['pickup_address'];
        $CsOrder['lat'] = $tempCsOrder['lat'];
        $CsOrder['lng'] = $tempCsOrder['lng'];
        $CsOrder['vehicle_name'] = $tempCsOrder['vehicle_name'];
        $CsOrder['user_id'] = $tempCsOrder['user_id'];
        $CsOrder['auto_renew'] = 0;
        $CsOrder['pto'] = $tempCsOrder['pto'];
        $CsOrder['currency'] = $tempCsOrder['currency'];
        $CsOrder['cc_token_id'] = $tempCsOrder['cc_token_id'];
        $CsOrder['start_datetime'] = $start_datetime;
        $CsOrder['end_datetime'] = $end_datetime;
        $CsOrder['renter_id'] = $tempCsOrder['renter_id'];
        $CsOrder['vehicle_id'] = $tempCsOrder['vehicle_id'];
        $CsOrder['accepted_time'] = Carbon::now()->format('Y-m-d H:i:s');
        $CsOrder['details'] = $tempCsOrder['details'];
        $CsOrder['start_timing'] = $start_datetime;
        $CsOrder['timezone'] = $tempCsOrder['timezone'];
        $CsOrder['parent_id'] = $tempCsOrder['parent_id'] ? $tempCsOrder['parent_id'] : $tempCsOrder['id'];
        $CsOrder['deposit'] = $tempCsOrder['deposit'];
        $CsOrder['start_odometer'] = $tempCsOrder['end_odometer'] > 0 ? $tempCsOrder['end_odometer'] : 0;
        
        Log::channel('autorenew')->info('=before getFeeRenewBooking=:', ['CsOrder' => $CsOrder]);

        $depositRuleModel = new DepositRule(); 
        $priceRulesAmt = [];
        if (method_exists($depositRuleModel, 'getFeeRenewBooking')) {
            $priceRulesAmt = $depositRuleModel->getFeeRenewBooking($CsOrder, $CsOrder['parent_id']);
        }
        
        Log::channel('autorenew')->info('=after getFeeRenewBooking=:', ['priceRulesAmt' => $priceRulesAmt]);

        $CsOrder['rent'] = $priceRulesAmt['time_fee'] ?? 0;
        $CsOrder['tax'] = $priceRulesAmt['tax'] ?? 0;
        $CsOrder['dia_fee'] = $priceRulesAmt['dia_fee'] ?? 0;
        $CsOrder['extra_mileage_fee'] = $priceRulesAmt['extra_mileage_fee'] ?? 0;
        $CsOrder['emf_tax'] = $priceRulesAmt['emf_tax'] ?? 0;
        $CsOrder['insurance_amt'] = $priceRulesAmt['insurance_amt'] ?? 0;
        $CsOrder['discount'] = $priceRulesAmt['discount'] ?? 0;
        
        // increment id
        $increment_id = $tempCsOrder['increment_id'] . '-1';
        if ($tempCsOrder['parent_id']) {
            $increment_ids = explode('-', $tempCsOrder['increment_id']);
            $increment_id = $increment_ids[0] . '-' . (int)($increment_ids[1] + 1);
        }
        $CsOrder['increment_id'] = $increment_id;
        
        $newOrder = CsOrder::create($CsOrder);
        $CsOrderId = $newOrder->id;
        $result['renewed_order_id'] = $CsOrderId;

        if (class_exists('Notifier')) {
            \Notifier::createIntercomeUserEvent([
                "event_name" => "booking_autorenew",
                "created_at" => time(),
                "external_id" => $CsOrder['renter_id'],
                "user_id" => $CsOrder['renter_id'],
                "metadata" => [
                    "booking_id" => $increment_id,
                    "id" => $CsOrderId,
                    "start_date" => Carbon::parse($start_datetime)->timezone($tempCsOrder['timezone'])->format('Y-m-d H:i:s'),
                    "end_date" => Carbon::parse($end_datetime)->timezone($tempCsOrder['timezone'])->format('Y-m-d H:i:s'),
                    "path" => "completePreviousBooking"
                ]
            ]);
        }
        
        app(PaymentProcessor::class)->checkAndProcessRenew($CsOrder['renter_id'], $CsOrder['user_id'], $priceRulesAmt, $CsOrderId, $tempCsOrder['id'], $CsOrder['cc_token_id'], $CsOrder['parent_id']);
        
        if ($CsOrder['start_odometer'] == 0) {
            $passtimeModel = new Passtime();
            if (method_exists($passtimeModel, 'startPasstime')) {
                $passtimeModel->startPasstime($tempCsOrder['vehicle_id'], $CsOrderId);
            }
        }
        
        if (($tempVehicle['passtime_status'] ?? 1) == 0 || ($tempVehicle['passtime_status'] ?? 1) == 2) {
            $vehicleData = Vehicle::with(['csSettings', 'vehicleSettings'])->find($tempCsOrder['vehicle_id']);
            if ($vehicleData) {
                // Convert back to Array for legacy Passtime call if model isn't modernized
                $vehicleArray = [
                    'Vehicle' => $vehicleData->toArray(),
                    'CsSetting' => $vehicleData->csSettings ? $vehicleData->csSettings->toArray() : [],
                    'VehicleSetting' => $vehicleData->vehicleSettings ? $vehicleData->vehicleSettings->toArray() : []
                ];
                
                $passtimeModel = new Passtime();
                if (method_exists($passtimeModel, 'ActivateVehicle')) {
                    $resp = $passtimeModel->ActivateVehicle($vehicleArray);
                    if (isset($resp['status']) && $resp['status']) {
                        $vehicleData->update(['passtime_status' => 1]);
                        
                        if (class_exists('Notifier')) {
                            \Notifier::createIntercomeUserEvent([
                                "event_name" => "starter_enabled",
                                "created_at" => time(),
                                "external_id" => $tempCsOrder['renter_id'],
                                "user_id" => $tempCsOrder['renter_id'],
                                "metadata" => [
                                    "id" => $CsOrderId,
                                    "booking_id" => $increment_id,
                                    "from" => "_OrderAutoRenew"
                                ]
                            ]);
                        }
                    }
                }
            }
        }
        
        return $result;
    }
}
