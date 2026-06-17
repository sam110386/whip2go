<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsVehicleIssue;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\PrepaidPlan;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\CsOrderStatuslog;
use App\Models\Legacy\CsReservationPayment;
use App\Services\Legacy\Emailnotify;
use App\Services\Legacy\Insurance;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

trait VehicleReservationsTrait
{
    use CommonTrait, MobileApi, AgreementTrait, VehicleDynamicFareMatrix, CopyVehicleImageTrait;

    protected $checklist = [
        "income_provan" => "Initial Income proven for usage",
        "insurance_affordable" => "Insurance quote affordable",
        "insurance_quote_number" => "Insurance quote number",
        "income_more_than_required" => "Income proven greater than income required",
        "market" => "Market",
        "updated_address" => "Updated Address",
        "mvr" => "MVR clear",
        "clue" => "CLUE clear",
        "vehicle_agreed_with_customer" => "Vehicle agreed with customer and VIN secured",
        "insurance_quoted_with_real_vin" => "Insurance requoted with real VIN",
        "proof_of_residency" => "Proof of residency",
        "streetview_address" => "Streetview of address",
        "identity_verified" => "Identity verified",
        "payments_made" => "Payments made",
        "vehicle_ordered" => "Vehicle ordered",
        'vehicle_image_downloaded' => "Vehicle Images Pulled",
        "registration_in_process" => "Registration In Process",
        "gps_ordered" => "GPS ordered",
        "gps_installation_scheduled" => "GPS installation scheduled",
        "gps_installed_tested" => "GPS installed and tested",
        "lease_agreement_signed" => "Lease Agreement Signed",
        "insurance_bound" => "Insurance bound",
        "company_garage_insurance_place" => "Company garage insurance in place",
        "vehicle_registered" => "Temp Tag",
        'permanent_license_plate_attached' => 'Permanent License Plate Attached',
        'spare_key_collected' => 'Spare Key Collected',
        "pickup_scheduled" => "Pick up scheduled",
        'dia_additional_insured' => 'DIA additional insured',
        'axle_in_place' => 'Axle in place',
        'ccm_maintenance_card' => 'CCM Maintenance Card'
    ];
    protected $readyForDealerStatus = [
        0 => ["In Review", "bg-primary"],
        1 => ["Sale Request", "bg-orange bg-orange-300"],
        2 => ["Vehicle Sold", "bg-green bg-green-700"],
        3 => ["Not Interested", "bg-danger"],
        4 => ["Find a Replacement", "bg-info"]
    ];
    private function _markBookingCancel(VehicleReservation $reservation, string $cancelNote = "")
    {
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        if (!in_array($reservation->status, $allowedStatus)) {
            return response()->json([
                'status' => false,
                'message' => "Sorry, you don't have permission to cancel it.",
                'result' => []
            ]);
        }

        $leaseId = $reservation->id;
        $reservation->update([
            'status' => 2,
            'cancel_note' => $cancelNote
        ]);

        Vehicle::where('id', $reservation->vehicle_id)->update(['booked' => 0]);

        $transactionTypes = [
            'getDepositTransaction' => 'deposit is refunded from pending booking',
            'getInitialFeeTransaction' => 'initial fee is refunded from pending booking',
            'getRentalTransaction' => 'rental fee is refunded from pending booking',
            'getInsuranceTransaction' => 'insurance fee is refunded from pending booking',
        ];

        $paymentProcessor = new PaymentProcessor();
        $csWallet = new CsWallet();

        foreach ($transactionTypes as $method => $logMessage) {
            $transactions = CsReservationPayment::$method($leaseId);
            foreach ($transactions as $transaction) {
                if ($transaction->txntype === 'C') {
                    $csWallet->addBalance(
                        $transaction->amount,
                        $reservation->renter_id,
                        $transaction->transaction_id,
                        $logMessage,
                        '',
                        $transaction->created
                    );
                } else {
                    $paymentProcessor->ReservationReleaseAuthorizePayment($transaction, $reservation->user_id);
                }

                CsReservationPayment::where('id', $transaction->id)->update(['status' => 2]);
            }
        }

        PrepaidPlan::where('reservation_id', $leaseId)->delete();

        Notifier::updateIntercomeUserAttrbute($reservation->renter_id, [
            'Booking_Cancel_Note' => $cancelNote,
            'Pending booking' => false,
            'Booking_Status' => ""
        ]);

        return [
            'status' => true,
            'message' => "Request processed successfully",
            'result' => ['lease_id' => $leaseId]
        ];
    }
    private function _saveVehicleBooking(array $requestData): array
    {
        $startTimeInput = str_replace('AM', '', $requestData['start_time'] ?? '');
        $endTimeInput = str_replace('AM', '', $requestData['end_time'] ?? '');
        $startTime = date('h:i A', strtotime($startTimeInput));
        $endTime = date('h:i A', strtotime($endTimeInput));
        $leaseId = base64_decode(trim($requestData['lease_id'] ?? ''));
        $return = ['status' => false, 'message' => 'Invalid inputs', 'result' => []];
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        $vehicleReservation = VehicleReservation::with('owner:id,currency,address,address_lat,address_lng')
            ->where('id', $leaseId)
            ->where('buy', 0)
            ->whereIn('status', $allowedStatus)
            ->first();

        if (empty($vehicleReservation)) {
            $return['message'] = 'Sorry, Reservation data not found';
            return $return;
        }

        $vehicleId = $vehicleReservation->vehicle_id;
        $customerId = $vehicleReservation->renter_id;
        $address = $vehicleReservation->owner->address ?? '';
        $addressLat = $vehicleReservation->owner->address_lat ?? '';
        $addressLng = $vehicleReservation->owner->address_lng ?? '';

        $vehicleData = Vehicle::where('id', $vehicleId)
            ->where('user_id', $vehicleReservation->user_id)
            ->first();

        if (empty($vehicleData)) {
            $return['message'] = 'Sorry, you are not authorize owner of selected Vehicle';
            return $return;
        }

        $startDate = $requestData['daterangefrom'] ?? '';
        $endDate = $requestData['daterangeto'] ?? '';
        $timezone = $vehicleReservation->timezone;
        $startDatetime = date('Y-m-d H:i:s', strtotime($startDate . ' ' . $startTime));
        $endDatetime = date('Y-m-d H:i:s', strtotime($endDate . ' ' . $endTime));

        if (empty($startDate) || empty($endDate) || strtotime($startDatetime) > strtotime($endDatetime)) {
            $return['message'] = 'Sorry, please select correct date range';
            return $return;
        }

        $bookingRentalChoice = OrderDepositRule::where('vehicle_reservation_id', $leaseId)->first();

        if (empty($bookingRentalChoice)) {
            $return['message'] = 'Sorry, Renter booking rent preference data not found';
            return $return;
        }

        $nextDate = OrderDepositRule::getFromTierData($bookingRentalChoice->duration_opt, $startDate, $endDate);

        if ($nextDate) {
            $endDatetime = date('Y-m-d H:i:s', strtotime($startDatetime . " +$nextDate days"));
        }

        $csOrder = [];
        $csOrder['status'] = 0;
        $csOrder['pickup_address'] = $address;
        $csOrder['lat'] = $addressLat;
        $csOrder['lng'] = $addressLng;
        $csOrder['vehicle_id'] = $vehicleId;
        $csOrder['vehicle_name'] = $vehicleData->vehicle_name;
        $csOrder['user_id'] = $vehicleData->user_id;
        $csOrder['start_datetime'] = Carbon::parse($startDatetime, $timezone)->setTimezone(config('app.timezone'))->toDateTimeString();
        $csOrder['end_datetime'] = Carbon::parse($endDatetime, $timezone)->setTimezone(config('app.timezone'))->toDateTimeString();
        $csOrder['pto'] = $vehicleReservation->pto;
        $csOrder['delivery'] = $vehicleReservation->delivery;
        $csOrder['renter_id'] = $customerId;

        $priceRulesAmt = (new DepositRule())->getPendingBookingFee($csOrder, $bookingRentalChoice->toArray());

        $csOrder['start_odometer'] = $vehicleData->last_mile;
        $csOrder['rent'] = $priceRulesAmt['time_fee'];
        $csOrder['tax'] = $priceRulesAmt['tax'];
        $csOrder['dia_fee'] = $priceRulesAmt['dia_fee'];
        $csOrder['insurance_amt'] = $priceRulesAmt['insurance_amt'] ?? 0;
        $csOrder['extra_mileage_fee'] = $priceRulesAmt['extra_mileage_fee'] ?? 0;
        $csOrder['emf_tax'] = $priceRulesAmt['emf_tax'];

        $priceRulesAmt['initial_fee'] = 0;
        $priceRulesAmt['initial_fee_tax'] = 0;
        $priceRulesAmt['deposit_amt'] = 0;
        $priceRulesAmt['currency'] = $vehicleReservation->owner->currency;
        $priceRulesAmt['insurance_payer'] = $bookingRentalChoice->insurance_payer;

        $csOrder['discount'] = $priceRulesAmt['discount'];
        $csOrder['initial_discount'] = $vehicleReservation->initial_discount;

        $rentalPayments = CsReservationPayment::getRentalTransaction($vehicleReservation->id);

        if (!empty($rentalPayments)) {
            $rentalCollection = collect($rentalPayments);
            $paidRent = sprintf('%0.2f', $rentalCollection->sum('rent'));
            $paidTax = sprintf('%0.2f', $rentalCollection->sum('tax'));
            $paidDiaFee = sprintf('%0.2f', $rentalCollection->sum('dia_fee'));

            $priceRulesAmt['time_fee'] = ($priceRulesAmt['time_fee'] - $paidRent) >= 0
                ? ($priceRulesAmt['time_fee'] - $paidRent)
                : $priceRulesAmt['time_fee'];
            $priceRulesAmt['tax'] = ($priceRulesAmt['tax'] - $paidTax);
            $priceRulesAmt['dia_fee'] = ($priceRulesAmt['dia_fee'] - $paidDiaFee);
        }

        $paidInsurances = CsReservationPayment::getInsuranceTransaction($vehicleReservation->id);

        if (!empty($paidInsurances)) {
            $paidInsurance = sprintf('%0.2f', collect($paidInsurances)->sum('amount'));
            $priceRulesAmt['insurance_amt'] = ($paidInsurance > $priceRulesAmt['insurance_amt'])
                ? 0
                : ($priceRulesAmt['insurance_amt'] - $paidInsurance);
            $csOrder['insurance_amt'] = ($paidInsurance > $priceRulesAmt['insurance_amt'])
                ? $paidInsurance
                : $csOrder['insurance_amt'];
        }

        $paymentProcessorObj = new PaymentProcessor();
        $paymentProcessResult = $paymentProcessorObj->checkAndProcessForMobile($customerId, $csOrder['user_id'], $priceRulesAmt);

        if (($paymentProcessResult['status'] ?? '') === 'success') {
            $csOrder['initial_fee'] = $priceRulesAmt['initial_fee'] = $bookingRentalChoice->initial_fee ?? $priceRulesAmt['initial_fee'];
            $csOrder['initial_fee_tax'] = isset($bookingRentalChoice->initial_fee)
                ? sprintf('%0.2f', ($bookingRentalChoice->initial_fee * $bookingRentalChoice->tax / 100))
                : 0;
            $csOrder['deposit'] = $bookingRentalChoice->deposit_amt ?? $priceRulesAmt['deposit_amt'];
            $csOrder['deposit_type'] = 'C';
            $csOrder['increment_id'] = $this->commonService->getOrderIncrementId();
            $csOrder['dpa_status'] = $paymentProcessResult['dpa_status'];
            $csOrder['insu_status'] = $priceRulesAmt['insurance_amt'] == 0 ? 1 : $paymentProcessResult['insu_status'];
            $csOrder['emf_status'] = $priceRulesAmt['extra_mileage_fee'] == 0 ? 1 : $paymentProcessResult['emf_status'];
            $csOrder["payment_status"] = ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']) == 0 ? 1 : $paymentProcessResult['payment_status'];
            $csOrder['infee_status'] = $paymentProcessResult['infee_status'];
            $csOrder['timezone'] = $timezone;
            $csOrder['currency'] = $vehicleReservation->owner->currency;

            $createdOrder = CsOrder::create($csOrder);
            $csOrderId = $createdOrder->id;

            VehicleReservation::withoutEvents(function () use ($vehicleReservation) {
                VehicleReservation::where('id', $vehicleReservation->id)->update(['status' => 1]);
            });

            $initialFeeOpt = $initialFeeOptTemp = !empty($bookingRentalChoice->initial_fee_opt)
                ? json_decode($bookingRentalChoice->initial_fee_opt, true)
                : [];
            $depositOpt = $depositOptTemp = !empty($bookingRentalChoice->deposit_opt)
                ? json_decode($bookingRentalChoice->deposit_opt, true)
                : [];

            if (strtotime($vehicleReservation->start_datetime) !== strtotime($csOrder['start_datetime'])) {
                $initialFeeOptTemp = $this->commonService->updateMissedScheduleOpt($csOrder['start_datetime'], $initialFeeOpt);
                $depositOptTemp = $this->commonService->updateMissedScheduleOpt($csOrder['start_datetime'], $depositOpt);
            }

            OrderDepositRule::where('id', $bookingRentalChoice->id)->update([
                'cs_order_id' => $csOrderId,
                'start_datetime' => $csOrder['start_datetime'],
                'initial_fee_opt' => json_encode($initialFeeOptTemp),
                'deposit_opt' => json_encode($depositOptTemp),
                'msrp' => $vehicleData->msrp,
                'premium_msrp' => $vehicleData->premium_msrp
            ]);

            $depositAuth = CsReservationPayment::getDepositTransaction($leaseId);

            if (!empty($depositAuth)) {
                $responseCapture = $paymentProcessorObj->ReservationPaymentCaptureOnly($csOrderId, $depositAuth, $csOrder['user_id']);
                CsOrder::where('id', $csOrderId)->update([
                    'dpa_status' => ($responseCapture['status'] === 'success') ? 1 : 2
                ]);
            }

            $csOrderPayment = new CsOrderPayment();
            $csOrderPayment->setOrderId($csOrderId);
            $csOrderPayment->setCurrency($paymentProcessResult['currency']);
            $csOrderPayment->setRenterId($csOrder['renter_id']);

            if (!empty($paymentProcessResult['insurance_transaction_id'])) {
                $csOrderPayment->setAmount($priceRulesAmt['insurance_amt']);
                $csOrderPayment->setTransactionidId($paymentProcessResult['insurance_transaction_id']);
                $csOrderPayment->setType('C');
                $csOrderPayment->setPayerId($paymentProcessResult['insu_payerid']);
                $csOrderPayment->saveInsuranceTransaction();
            }

            if (!empty($paymentProcessResult['transaction_id'])) {
                $csOrderPayment->setAmount(($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee']));
                $csOrderPayment->setTransactionidId($paymentProcessResult['transaction_id']);
                $csOrderPayment->setType('C');
                $csOrderPayment->setTax($priceRulesAmt['tax']);
                $csOrderPayment->setDiaFee($priceRulesAmt['dia_fee']);
                $csOrderPayment->saveRentalTransaction();
            }

            if (!empty($rentalPayments)) {
                foreach ($rentalPayments as $rentalPayment) {
                    $csOrderPayment->setAmount($rentalPayment['amount']);
                    $csOrderPayment->setTransactionidId($rentalPayment['transaction_id']);
                    $csOrderPayment->setType('C');
                    $csOrderPayment->setTax($rentalPayment['tax']);
                    $csOrderPayment->setChargedAt($rentalPayment['created']);
                    $csOrderPayment->setDiaFee($rentalPayment['dia_fee']);
                    $csOrderPayment->saveRentalTransaction();
                }
            }

            if (!empty($paidInsurances)) {
                foreach ($paidInsurances as $pInsurance) {
                    $csOrderPayment->setAmount($pInsurance['amount']);
                    $csOrderPayment->setTransactionidId($pInsurance['transaction_id']);
                    $csOrderPayment->setType('C');
                    $csOrderPayment->setChargedAt($pInsurance['created']);
                    $csOrderPayment->setPayerId($pInsurance['payer_id']);
                    $csOrderPayment->saveInsuranceTransaction();
                }
            }

            if (!empty($paymentProcessResult['emf_transaction_id'])) {
                $csOrderPayment->setAmount($csOrder['extra_mileage_fee']);
                $csOrderPayment->setTransactionidId($paymentProcessResult['emf_transaction_id']);
                $csOrderPayment->setType('C');
                $csOrderPayment->setTax($csOrder['emf_tax']);
                $csOrderPayment->saveEmfTransaction();
            }

            $initialFeeAuth = CsReservationPayment::getInitialFeeTransaction($leaseId);

            if (!empty($initialFeeAuth)) {
                $responseCap = $paymentProcessorObj->ReservationPaymentCaptureOnly($csOrderId, $initialFeeAuth, $csOrder['user_id']);
                CsOrder::where('id', $csOrderId)->update([
                    'infee_status' => ($responseCap['status'] === 'success') ? 1 : 2
                ]);
            }

            $pendingInsu = false;
            if ($bookingRentalChoice->insurance_payer == 3 && ($priceRulesAmt['insurance_event'] ?? '') === 'P') {
                $insuranceObj = new Insurance();
                $insuPassData = [
                    'pending_insu' => 0,
                    "order_rule_id" => $bookingRentalChoice->id,
                    "start_datetime" => $csOrder['start_datetime']
                ];

                $insuObjResult = $insuranceObj->getCalculatedAndChargeInsurance($insuPassData);

                if (!$insuObjResult['status']) {
                    CsOrder::where('id', $csOrderId)->update(['pending_insu' => $insuObjResult['pending_insu']]);
                    $pendingInsu = true;
                }
            }

            if ($bookingRentalChoice->insurance_payer == 7) {
                DepositRule::where('vehicle_id', $vehicleId)->update(['insurance_fee' => 25]);
            }

            CsOrderStatuslog::saveBookingPendingToActiveEvent($csOrderId, $vehicleData->user_id);

            $owner = User::find($vehicleData->user_id, ['email', 'notify_email']);
            $renterInfo = $this->commonService->getRenterDetails($customerId);
            (new Emailnotify())->sendNotificationToOwner($vehicleData->toArray(), $owner->toArray(), $csOrder['start_datetime'], $renterInfo);

            $notifier = new Notifier();
            $tag = ["Booking_Status" => "Active", 'Rental_Status' => "Paid"];

            if (
                $pendingInsu
                || ($csOrder['infee_status'] ?? 0) == 2
                || ($csOrder['dpa_status'] ?? 0) == 2
                || ($csOrder['insu_status'] ?? 0) == 2
                || ($csOrder['payment_status'] ?? 0) == 2
            ) {
                $tag['Rental_Status'] = "Unpaid";
            }

            $notifier->notifyForActivateBooking($csOrderId, $renterInfo, $tag);

            Notifier::createIntercomeUserEvent([
                "event_name" => "booking_activated",
                "created_at" => time(),
                "external_id" => $customerId,
                "user_id" => $customerId,
                "metadata" => [
                    "booking_id" => $csOrderId,
                    "pending_booking_id" => $leaseId,
                    'vehicle_id' => $vehicleId,
                    'vehicle_name' => $vehicleData->vehicle_name,
                    "goal" => $bookingRentalChoice->goal,
                    "total_program_cost" => $bookingRentalChoice->total_program_cost
                ]
            ]);

            $this->_createOutstanidngIssues($csOrderId, $vehicleReservation->toArray());
            $this->_CopyVehicleImageFromRemote($vehicleData->id);

            $newType = ($vehicleData->from_feed == 1) ? 'real' : $vehicleData->type;
            Vehicle::where('id', $vehicleId)->update(['type' => $newType]);

            return [
                'status' => true,
                'message' => "Your acceptance booked successfully",
                'result' => [],
                "lease_id" => $leaseId
            ];
        } else {
            return [
                'status' => false,
                'message' => "Your acceptance failed due to payment failed with error: " . ($paymentProcessResult['message'] ?? 'Unknown Error'),
                'result' => []
            ];
        }

    }
    private function _createOutstanidngIssues($orderId, $reservationObj)
    {
        $bookingChecklists = !empty($reservationObj['checklists'])
            ? json_decode($reservationObj['checklists'], true)
            : [];

        if (!empty($bookingChecklists)) {
            $bookingChecklists = $this->commonService->getMissingChecklist($reservationObj['checklists'], $this->checklist, true);
        }

        $baseData = [
            'user_id' => $reservationObj['user_id'],
            'vehicle_id' => $reservationObj['vehicle_id'],
            'renter_id' => $reservationObj['renter_id'],
            'cs_order_id' => $orderId,
            'type' => 8
        ];

        if (!empty($bookingChecklists) && is_array($bookingChecklists)) {
            foreach ($bookingChecklists as $key) {
                if (isset($this->checklist[$key])) {
                    $dataToSave = array_merge($baseData, [
                        'extra' => json_encode([$key => $this->checklist[$key]])
                    ]);

                    CsVehicleIssue::create($dataToSave);
                }
            }
        }

        $licenseIssueData = $baseData;
        $licenseIssueData['type'] = 9;

        CsVehicleIssue::create($licenseIssueData);

        return;
    }
    public function _renderlog($filename)
    {
        $filepath = app_path('CreditLogs/' . $filename);

        if (!File::isFile($filepath)) {
            return response("<pre>Sorry, file does not exist.</pre>", 404)
                ->header('Content-Type', 'text/html');
        }

        $jsonString = File::get($filepath);
        $content = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $content = $jsonString;
        }

        $output = '<pre>' . print_r($content, true) . '</pre>';

        return response($output)
            ->header('Content-Type', 'text/html');
    }




    public function _getfarecalculations($reservationId)
    {
        $reservation = VehicleReservation::with('OrderDepositRule')->find($reservationId);
        if (!$reservation)
            return ['status' => false, 'message' => "Reservation not found"];

        // Dynamic calculation logic from VehicleDynamicFareMatrix trait
        $results = $this->calculateDynamicFare($reservation);
        return $results;
    }

    public function _changeStatus($reservationId, $status)
    {
        $reservation = VehicleReservation::find($reservationId);
        if ($reservation) {
            $reservation->update(['status' => $status]);
            CsOrderStatuslog::create([
                'reservation_id' => $reservationId,
                'status' => $status,
                'comment' => "Status changed by system/admin"
            ]);
            return true;
        }
        return false;
    }

    public function _changeSaveStatus($reservationId, $saveStatus)
    {
        $reservation = VehicleReservation::find($reservationId);
        if ($reservation) {
            $reservation->update(['save_status' => $saveStatus]);
            return true;
        }
        return false;
    }

    protected function _changeInsuranceTypePopup(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _changeinsurancepopup(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _changeinsurancesave(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }

    protected function _insudoc(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _loadcancelblock(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _loadinsurancepopup(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _saveVehicleSellingOption(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _saveinsurancepayer(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _updateDatetime(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _updateReservationVehicle(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
    protected function _vehicleReservationLog(...$args)
    {
        return ['status' => false, 'message' => __FUNCTION__ . ' pending migration'];
    }
}
