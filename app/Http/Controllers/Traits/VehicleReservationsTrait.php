<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\CsVehicleIssue;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\InsurancePayer;
use App\Models\Legacy\InsuranceQuote;
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
use App\Models\Legacy\VehicleReservationLog;
use App\Services\Legacy\Agreement;
use App\Services\Legacy\Emailnotify;
use App\Services\Legacy\Insurance;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\PathToOwnership;
use App\Services\Legacy\PaymentProcessor;
use App\Helpers\Legacy\Number as LegacyNumber;
use Carbon\Carbon;

trait VehicleReservationsTrait
{
    use MobileApi, AgreementTrait, VehicleDynamicFareMatrix, CopyVehicleImageTrait;

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
    protected $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];
    private function _markBookingCancel(VehicleReservation $reservation, string $cancelNote = "")
    {
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        if (!in_array($reservation->status, $allowedStatus)) {
            return [
                'status' => false,
                'message' => "Sorry, you don't have permission to cancel it.",
                'result' => []
            ];
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
    private function _renderlog($filename)
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
    private function _updateReservationVehicle(array $data, $userid = null)
    {
        $reservationId = $data['VehicleReservation']['id'] ?? null;
        $vehicleId = $data['VehicleReservation']['vehicle_id'] ?? null;
        $newRentalRaw = $data['VehicleReservation']['newrental'] ?? null;

        if (empty($reservationId) || empty($vehicleId) || empty($newRentalRaw)) {
            return ['status' => false, 'message' => "Sorry, invalid inputs, please try again", 'view' => ""];
        }

        $newRentalData = explode('X', $newRentalRaw);
        $query = VehicleReservation::where('id', $reservationId)->where('status', 0);

        if (!empty($userid)) {
            $query->where('user_id', $userid);
        }

        $vehicleReservationObj = $query->first();

        if (!$vehicleReservationObj) {
            return ['status' => false, 'message' => "Sorry, invalid inputs, please try again", 'view' => ""];
        }

        $initialFeeOptions = isset($newRentalData[0]) ? preg_replace("/[^0-9,.]/", "", $newRentalData[0]) : 0;
        $miles = $newRentalData[1] ?? 0;
        $rentalOptions = isset($newRentalData[2]) ? preg_replace("/[^0-9,.]/", "", $newRentalData[2]) : 0;
        $emf = isset($newRentalData[3]) ? preg_replace("/[^0-9,.]/", "", $newRentalData[3]) : 0;

        if (!$initialFeeOptions || !$miles) {
            return ['status' => false, 'message' => "Sorry, invalid inputs, please try again", 'view' => ""];
        }

        $vehicle = Vehicle::with('owner:id,currency')
            ->where('id', $vehicleId)
            ->where('status', 1)
            ->first();

        if (!$vehicle) {
            return ['status' => false, 'message' => "Sorry, this vehicle is not available now", 'result' => []];
        }

        $ownerId = $vehicle->user_id;
        $pto = $vehicleReservationObj->pto;

        $orderDepositRuleObj = OrderDepositRule::where('vehicle_reservation_id', $vehicleReservationObj->id)->first();
        $pathToOwnership = new PathToOwnership();

        if ($orderDepositRuleObj && in_array((int) $orderDepositRuleObj->insurance_payer, [3, 5, 6, 7])) {
            $insurance = 0;
        } else {
            $depositRule = DepositRule::where('vehicle_id', $vehicle->id)->first();
            $insurance = $pathToOwnership->getDynamicFareMatrixInsurance($miles, $vehicle->toArray(), $depositRule->toArray());
        }

        $allCalculations = $pathToOwnership->getQuoteForBooking($vehicle->toArray(), [
            "rental_options" => $rentalOptions,
            "initial_fee" => $initialFeeOptions,
            "pto" => $pto,
            "renter_id" => $vehicleReservationObj->renter_id
        ]);

        $orderDepositRuleData = [
            "initial_fee" => $initialFeeOptions,
            "base_rent" => $allCalculations['base_dayrent'],
            "rental" => $rentalOptions,
            "tax" => $allCalculations['tax_rate'],
            "totalcost" => $allCalculations['totalcost'],
            "num_of_days" => $allCalculations['num_of_days'],
            "equityshare" => $allCalculations['equityshare'],
            "downpayment" => $allCalculations['downpayment'],
            "rental_opt" => json_encode($allCalculations['rental_opt']),
            "deposit_opt" => $allCalculations['deposit_opt'],
            'deposit_amt' => $allCalculations['deposit_amt'],
            "total_initial_fee" => $initialFeeOptions,
            "total_program_cost" => $allCalculations['total_program_cost'],
            "goal" => $allCalculations['goal'],
            "emf" => sprintf('%0.2f', ($emf / 30)),
            "miles" => sprintf('%0.2f', ($miles / 30)),
            'insurance' => $insurance,
            "emf_rate" => $allCalculations['emf_rate'],
            "emf_insu_rate" => ($orderDepositRuleObj && in_array((int) $orderDepositRuleObj->insurance_payer, [3, 5, 6, 7])) ? 0 : $allCalculations['emf_insu_rate'],
            'write_down_allocation' => $allCalculations['write_down_allocation'],
            'finance_allocation' => $allCalculations['finance_allocation'],
            'maintenance_allocation' => $allCalculations['maintenance_allocation'],
            'disposition_fee' => $allCalculations['disposition_fee'],
            "calculation" => json_encode($allCalculations),
        ];

        $priceRulesAmt = ['deposit_amt' => 0, "initial_fee" => 0, 'initial_fee_tax' => 0];

        if ($orderDepositRuleObj) {
            if ($orderDepositRuleObj->initial_fee < $orderDepositRuleData['initial_fee']) {
                $priceRulesAmt['initial_fee'] = (float) ($orderDepositRuleData['initial_fee'] - $orderDepositRuleObj->initial_fee);
                $priceRulesAmt['initial_fee_tax'] = (float) ($priceRulesAmt['initial_fee'] * $orderDepositRuleObj->tax / 100);
            }
            if ($orderDepositRuleObj->deposit_amt < $orderDepositRuleData['deposit_amt']) {
                $priceRulesAmt['deposit_amt'] = (float) ($orderDepositRuleData['deposit_amt'] - $orderDepositRuleObj->deposit_amt);
            }
        }

        $paymentProcessorObj = new PaymentProcessor();
        $paymentProcessResult = $paymentProcessorObj->PaymentAutherizeOnly(
            $vehicleReservationObj->renter_id,
            $ownerId,
            $priceRulesAmt,
            $vehicle->owner->currency ?? 'USD'
        );

        if (($paymentProcessResult['status'] ?? '') !== 'success') {
            return [
                'status' => false,
                'message' => "Sorry, your request failed due to the balance payment authorization with error: " . ($paymentProcessResult['message'] ?? 'Unknown Error'),
                'result' => []
            ];
        }

        $vehicleReservationObj->update([
            'user_id' => $ownerId,
            'vehicle_id' => $vehicle->id
        ]);

        OrderDepositRule::updateOrCreate(
            ['id' => $orderDepositRuleObj->id ?? null],
            $orderDepositRuleData
        );

        $targetVehicleType = ($vehicle->from_feed == 1) ? 'real' : $vehicle->type;
        Vehicle::where('id', $vehicleId)->update(['booked' => 1, 'type' => $targetVehicleType]);
        Vehicle::where('id', $vehicleReservationObj->vehicle_id)->update(['booked' => 0]);

        $csPayment = new CsReservationPayment();
        $csPayment->setOrderId($vehicleReservationObj->id);
        $csPayment->setCurrency($paymentProcessResult['currency'] ?? 'USD');

        if (!empty($paymentProcessResult['deposit_auth'])) {
            $csPayment->setAmount($priceRulesAmt['deposit_amt']);
            $csPayment->setTransactionidId($paymentProcessResult['deposit_auth']);
            $csPayment->setType($paymentProcessResult['deposit_type']);
            $csPayment->saveDepositTransaction();
        }

        if (!empty($paymentProcessResult['initial_fee_id'])) {
            $csPayment->setAmount(($priceRulesAmt['initial_fee'] + $priceRulesAmt['initial_fee_tax']));
            $csPayment->setTax($priceRulesAmt['initial_fee_tax']);
            $csPayment->setTransactionidId($paymentProcessResult['initial_fee_id']);
            $csPayment->setType($paymentProcessResult['infee_type']);
            $csPayment->saveInitialFeeTransaction();
        }

        if ($orderDepositRuleObj && $orderDepositRuleObj->insurance_payer == 7) {
            DepositRule::where('vehicle_id', $vehicleId)->update(['insurance_fee' => 25]);
        }

        return ['status' => true, 'message' => "Your request saved successfully", 'result' => ""];

    }
    private function _updateDatetime(array $data, $userid = null)
    {
        $input = $data['VehicleReservation'] ?? $data;
        $startTime = date('h:i A', strtotime(str_replace('AM', '', $input['start_time'] ?? '')));
        $endTime = date('h:i A', strtotime(str_replace('AM', '', $input['end_time'] ?? '')));

        $query = VehicleReservation::where('id', $input['id'] ?? null);

        if ($userid) {
            $query->where('user_id', $userid);
        }

        $vehicleReservation = $query->first(['id', 'timezone']);

        if (!$vehicleReservation) {
            return [
                'status' => false,
                'message' => "Sorry, Reservation data not found",
                'result' => []
            ];
        }

        $startDate = $input['daterangefrom'] ?? '';
        $endDate = $input['daterangeto'] ?? '';
        $timezone = $vehicleReservation->timezone;

        $startDatetimeStr = date('Y-m-d H:i:s', strtotime($startDate . ' ' . $startTime));
        $endDatetimeStr = date('Y-m-d H:i:s', strtotime($endDate . ' ' . $endTime));

        if (
            empty($startDate) ||
            empty($endDate) ||
            strtotime($startDatetimeStr) > strtotime($endDatetimeStr)
        ) {
            return [
                'status' => false,
                'message' => "Sorry, please select correct date range",
                'result' => []
            ];
        }

        $serverTimezone = config('app.timezone', 'UTC');
        $startDatetimeServer = Carbon::createFromFormat('Y-m-d H:i:s', $startDatetimeStr, $timezone)
            ->setTimezone($serverTimezone)
            ->toDateTimeString();
        $endDatetimeServer = Carbon::createFromFormat('Y-m-d H:i:s', $endDatetimeStr, $timezone)
            ->setTimezone($serverTimezone)
            ->toDateTimeString();

        $vehicleReservation->update([
            'start_datetime' => $startDatetimeServer,
            'end_datetime' => $endDatetimeServer,
        ]);

        return [
            "status" => true,
            "message" => "Your request saved successfully"
        ];
    }
    private function _getfarecalculations(array $data)
    {
        if (empty($data['bookingid']) || empty($data['vehicleid'])) {
            return [
                'status' => false,
                'message' => "Sorry, invalid inputs, please try again",
                'view' => ""
            ];
        }

        $bookingid = $data['bookingid'];
        $vehicleid = $data['vehicleid'];

        $vehicleReservationObj = VehicleReservation::with('owner:id,currency')->find($bookingid);

        if (!$vehicleReservationObj) {
            return [
                'status' => false,
                'message' => "Sorry, booking not found",
                'view' => ""
            ];
        }

        $renter = User::find($vehicleReservationObj->renter_id);
        $vehicle = Vehicle::find($vehicleid);
        $orderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $vehicleReservationObj->id)->first();

        if (!$vehicle || !$renter) {
            return [
                'status' => false,
                'message' => "Sorry, vehicle or renter data missing",
                'view' => ""
            ];
        }

        $vehicle->currency = $vehicleReservationObj->owner->currency ?? 'USD';

        $pathToOwnership = new PathToOwnership();
        $dayRent = $pathToOwnership->getVehiclePerDayPriceForQuote(
            $vehicle->toArray(),
            $renter->toArray(),
            $orderDepositRule->total_initial_fee ?? 0
        );

        $result = [
            'rent' => $vehicle->day_rent,
            'fare_des' => '$' . $vehicle->day_rent . '/day',
            'rental_options' => $dayRent['tier_rental'] ?? [],
            'pre_miles' => !empty($orderDepositRule) ? ceil($orderDepositRule->miles * 30) : 1000
        ];

        $view = view('vehicle_reservations._getfarecalculations', compact('result'))->render();

        return [
            'status' => true,
            'message' => "",
            'view' => $view
        ];
    }
    private function _changeSaveStatus(array $data, $userid = null)
    {
        $input = $data['VehicleReservation'] ?? $data;
        $reservationId = $input['id'] ?? null;
        $status = (int) ($input['status'] ?? 0);

        $query = VehicleReservation::where('id', $reservationId);

        if (!empty($userid)) {
            $query->where('user_id', $userid);
        }

        $vehicleReservation = $query->first([
            'id',
            'user_id',
            'vehicle_id',
            'renter_id',
            'status'
        ]);

        if (!$vehicleReservation) {
            return [
                'status' => false,
                'message' => "Sorry, Reservation data not found",
                'result' => []
            ];
        }

        $loggedUserId = session('SESSION_ADMIN.id')
            ?? session('userParentId')
            ?? session('userid')
            ?? auth()->id()
            ?? 0;
        $note = $input['note'] ?? '';
        $logData = [
            'user_id' => $loggedUserId,
            'reservation_id' => $vehicleReservation->id,
            'status' => $status,
        ];

        $previousStatusTitle = $this->commonService->getReservationStatus(false, $vehicleReservation->status);

        if ($status !== 10) {
            $vehicleReservation->update(['status' => $status]);
        }

        $msg = '';

        if ($status === 4) {
            $dealer = User::find($vehicleReservation->user_id, ['first_name', 'last_name']);
            $msg = ($dealer->first_name ?? '') . ' ' . ($dealer->last_name ?? '') . ' has approved order and is now planning to prep the vehicle. We will be in touch soon with a pick up time.';
            $note = !empty($note) ? $note : "The dealer is aware of your booking and is now planning to prep the vehicle. We will be in touch soon with a pick up time.";
        }

        if ($status === 5) {
            $driver = User::find($vehicleReservation->renter_id, ['first_name', 'last_name', 'contact_number']);
            $msg = ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '') . ', your vehicle is now being prepped. We will know shortly the pick up time.';
            $note = !empty($note) ? $note : "Your vehicle is now being prepped. We will know shortly the pick up time.";
        }

        if ($status === 6) {
            $driver = User::find($vehicleReservation->renter_id, ['first_name', 'last_name', 'contact_number']);
            $msg = ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '') . ', your vehicle will be ready at ' . ($input['note'] ?? '') . '. Please bring your driver’s license with you when picking up the vehicle.';
            $note = 'Your vehicle will be ready at ' . ($input['note'] ?? '') . '. Please bring your driver’s license with you when picking up the vehicle.';
        }

        if ($status === 7) {
            $driver = User::find($vehicleReservation->renter_id, ['first_name', 'last_name', 'contact_number']);
            $msg = ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '') . ', your vehicle is ready for pick up! Congrats again on your order. We look forward to seeing you soon. If you cannot pick up the vehicle up at this time, please let us know.';
            $note = 'Your vehicle is ready for pick up! Congrats again on your order. We look forward to seeing you soon. If you cannot pick up the vehicle up at this time, please let us know.';
        }

        if ($status !== 10) {
            $logData['note'] = $note;
        } else {
            $logData['note'] = $input['note'] ?? '';
        }

        VehicleReservationLog::create($logData);

        if ($status !== 10) {
            $newStatusTitle = $this->commonService->getReservationStatus(false, $status);

            Notifier::createIntercomeUserEvent([
                "event_name" => "pending_booking_update",
                "created_at" => time(),
                "external_id" => $vehicleReservation->renter_id,
                "user_id" => $vehicleReservation->renter_id,
                "metadata" => [
                    "id" => $vehicleReservation->id,
                    "pending_booking_id" => $vehicleReservation->id,
                    "previous_status" => $previousStatusTitle,
                    "new_status" => $newStatusTitle
                ]
            ]);
        }

        return [
            "status" => true,
            "message" => "Your request saved successfully",
            "orderid" => $vehicleReservation->id
        ];
    }
    private function _vehicleReservationLog(array $data)
    {
        $orderid = base64_decode($data['orderid']);

        if (empty($orderid)) {
            return [
                'status' => false,
                'message' => "Sorry, invalid inputs, please try again",
                'view' => ""
            ];
        }

        $statuslogs = VehicleReservationLog::with('user:id,first_name,last_name')
            ->where('reservation_id', $orderid)
            ->orderBy('id', 'desc')
            ->get();

        $allowedstatus = $this->commonService->getReservationStatus(true, true);
        $view = view('vehicle_reservations.reservation_status_log', compact('statuslogs', 'allowedstatus'))->render();

        return [
            'status' => true,
            'message' => "",
            'view' => $view
        ];
    }
    private function _insudoc($id)
    {
        $lease = VehicleReservation::select(['id', 'user_id', 'vehicle_id', 'renter_id'])
            ->with([
                'orderDepositRule:id,vehicle_reservation_id,insurance_payer',
                'vehicle:id,make,year,model,vin_no,insurance_policy_date,insurance_policy_no,insurance_policy_exp_date,insurance_company',
                'renter:id,first_name,last_name',
                'owner:id,first_name,last_name,address,city,state,zip'
            ])
            ->where('id', $id)
            ->first();

        if (!$lease) {
            return [
                'status' => false,
                'message' => "Sorry, you can't perform this action now.",
                'result' => []
            ];
        }

        $insurancePayerId = data_get($lease, 'orderDepositRule.insurance_payer');
        $orderDepositRuleId = data_get($lease, 'orderDepositRule.id');

        if ($insurancePayerId == 3) {
            $insurancePayerObj = InsurancePayer::where('order_deposit_rule_id', $orderDepositRuleId)->first();

            if (!$insurancePayerObj || empty(data_get($insurancePayerObj, 'insurance_card'))) {
                return [
                    'status' => false,
                    'message' => "Sorry, Driver didn't upload insurance token yet. He agreed to manage it himself.",
                    'result' => []
                ];
            }

            return [
                'status' => true,
                'message' => "Success",
                'result' => [
                    'file' => url('files/reservation/' . data_get($insurancePayerObj, 'insurance_card'))
                ]
            ];
        }

        $filename = 'reservationinsudoc_' . $lease->id . '.pdf';
        $pdfPath = public_path('files/insurancedoc/' . $filename);

        if (!file_exists($pdfPath)) {
            $ownerState = data_get($lease, 'owner.state', '');

            if (!empty($ownerState) && strlen($ownerState) === 2) {
                $ownerState = $this->commonService->getStateName(strtoupper($ownerState));
            } else {
                $ownerState = strtoupper($ownerState);
            }

            if (empty($ownerState)) {
                $ownerState = 'NEW JERSEY';
            }

            if ($lease->owner) {
                $lease->owner->state = $ownerState;
            }

            $template = CsInsuranceTemplate::where('user_id', $lease->user_id)->first(['insu_token_name']);

            if ($template && !empty(data_get($template, 'insu_token_name'))) {
                $lease->owner->first_name = data_get($template, 'insu_token_name');
                $lease->owner->last_name = '';
            }

            $lease->insuranceCompany = data_get($lease, 'vehicle.insurance_company', "Voyager Indemnity Insurance Company");
            $lease->policy_no = data_get($lease, 'vehicle.insurance_policy_no', "");
            $lease->policy_date = data_get($lease, 'vehicle.insurance_policy_date', Carbon::now()->format('m/d/Y'));
            $lease->policy_exp_date = data_get($lease, 'vehicle.insurance_policy_exp_date', Carbon::now()->format('m/d/Y'));
            $lease->SUPPORT_PHONE = config('legacy.SUPPORT_PHONE');

            $agreementService = new Agreement();
            $agreementService->generateInsuranceToken($lease->toArray(), $filename);
        }

        return [
            'status' => true,
            'message' => "Success",
            'result' => [
                'file' => url('files/insurancedoc/' . $filename)
            ]
        ];

    }
    private function _changeinsurancepopup(array $data)
    {
        if (empty($data['order'])) {
            return [
                'status' => false,
                'message' => "Sorry, invalid inputs, please try again",
                'view' => ""
            ];
        }

        $orderRule = OrderDepositRule::find($data['order']);
        $view = view('vehicle_reservations._changeinsurancepopup', compact('orderRule'))->render();

        return [
            'status' => true,
            'message' => "",
            'view' => $view
        ];
    }
    private function _changeinsurancesave(array $data, $userid = null)
    {
        $ruleId = $data['OrderDepositRule']['id'] ?? null;

        $query = OrderDepositRule::with([
            'reservation:id,user_id,renter_id,status',
            'reservation.owner:id,currency'
        ])->where('id', $ruleId);

        if ($userid) {
            $query->whereHas('reservation', function ($q) use ($userid) {
                $q->where('user_id', $userid);
            });
        }

        $orderRule = $query->first();

        if (empty($orderRule) || empty($orderRule->reservation)) {
            return [
                'status' => false,
                'message' => "Sorry, Reservation data not found",
                'result' => []
            ];
        }

        $reservation = $orderRule->reservation;
        $owner = $reservation->owner;

        $insurance = $data['OrderDepositRule']['insurance'];
        $notifyToDriver = $data['OrderDepositRule']['notify'];

        if ($notifyToDriver == 3) {
            $orderRule->update(['insurance' => $insurance]);
            return [
                'status' => true,
                'message' => "Booking insurance saved successfully",
                'result' => []
            ];
        }

        $status = $reservation->status;
        $loggedUserId = Session::get('SESSION_ADMIN.id') ?: (Session::get('userParentId') ?: Session::get('userid', 0));

        $note = "";
        $currency = $owner ? $owner->currency : 'USD';

        if ($notifyToDriver == 1) {
            $dIC = LegacyNumber::currency($insurance, $currency);
            $wIC = LegacyNumber::currency(number_format(($insurance * 7), 2, '.', ''), $currency);
            $note = "Because of your driving history, the insurance rate will be " . $dIC . "/day, " . $wIC . "/week. Do you agree?";
        }

        if ($notifyToDriver == 2) {
            $providerQuote = InsuranceQuote::with(['provider:id,name'])
                ->where('order_id', $reservation->id)
                ->where('selected', 1)
                ->first();

            if (empty($providerQuote) || empty($providerQuote->provider)) {
                return [
                    'status' => false,
                    'message' => "Sorry, driver didn't choose any quote yet",
                    'result' => []
                ];
            }

            $insurancePayer = InsurancePayer::where('order_deposit_rule_id', $ruleId)->first();

            if (empty($insurancePayer)) {
                return [
                    'status' => false,
                    'message' => "Sorry, admin didn't complete yet insurance quote process",
                    'result' => []
                ];
            }

            $dIC = LegacyNumber::currency($insurancePayer->premium_total, $currency);
            $wIC = LegacyNumber::currency(number_format(($insurance * 7), 2, '.', ''), $currency);
            $note = "Progressive \nFull policy amount " . $dIC . "\nWeekly installment amount through " . $providerQuote->provider->name . " " . $wIC . "/week";
        }

        $orderRule->update([
            'insu_agreed' => 0,
            'insurance' => $insurance
        ]);

        VehicleReservationLog::create([
            'user_id' => $loggedUserId,
            'reservation_id' => $reservation->id,
            'status' => $status,
            'note' => $note,
            'created' => now()
        ]);

        Notifier::notifyByIntercomWithTag($reservation->renter_id, $note, 'booked', '', ["Booking_Status" => "Pending"]);
        Notifier::createIntercomeUserEvent([
            "event_name" => "pending_booking_update",
            "created_at" => time(),
            "external_id" => $reservation->renter_id,
            "user_id" => $reservation->renter_id,
            "metadata" => [
                "id" => $reservation->id,
                "pending_booking_id" => $reservation->id,
                "note" => $note
            ]
        ]);

        return [
            "status" => true,
            "message" => "Your request saved successfully",
            "orderid" => $reservation->id
        ];
    }
    private function _loadcancelblock(array $data)
    {
        $lease_id = $data['lease_id'] ?? null;
        return view('vehicle_reservations._loadcancelblock', compact('lease_id'))->render();
    }
    private function _loadinsurancepopup(array $data)
    {
        $orderid = $data['order'];
        $booking = VehicleReservation::with([
            'vehicle:id,msrp,vin_no,vehicle_name',
            'orderDepositRule'
        ])->find($orderid);

        $insuranceQuote = InsuranceQuote::with('provider:id,name,logo')
            ->where('order_id', $orderid)
            ->where('selected', 1)
            ->first();

        return view('admin.vehicle_reservations._insurancepopup', [
            'trip' => $booking,
            'insuranceQuote' => $insuranceQuote
        ]);
    }
    private function _changeInsuranceTypePopup(array $data)
    {
        $orderRuleId = $data['orderruleid'];
        $booking = OrderDepositRule::find($orderRuleId);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, booking details not found',
                'view' => ''
            ]);
        }

        $types = $this->commonService->getInsurancePayer(null);
        $view = view('vehicle_reservations._openchangeinsurancepayerpopup', [
            'trip' => $booking,
            'types' => $types
        ])->render();

        return response()->json([
            'status' => true,
            'message' => '',
            'view' => $view
        ]);
    }
    private function _saveinsurancepayer(array $data)
    {
        $id = $data['OrderDepositRule']['id'];
        $insurancePayer = $data['OrderDepositRule']['insurance_payer'];
        $booking = OrderDepositRule::find($id);
        $allowedTypes = array_keys($this->commonService->getInsurancePayer(null));

        if (!$booking || !in_array($insurancePayer, $allowedTypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, booking details not found',
                'view' => ''
            ]);
        }

        $booking->insurance_payer = $insurancePayer;
        $booking->save();

        return response()->json([
            'status' => true,
            'message' => 'Your request saved successfully'
        ]);
    }
    private function _saveVehicleSellingOption(array $data)
    {
        $return = ["status" => false, "message" => "Sorry, something went wrong."];

        if (!empty($data)) {
            $orderId = $data['orderid'] ?? null;
            $status = $data['status'] ?? null;
            $vehicleReplacement = (!empty($data['vehicle_replacement']) && !empty($data['vehicle_id']));

            $booking = OrderDepositRule::select('id', 'selling_option', 'vehicle_reservation_id')
                ->where('id', $orderId)
                ->first();

            if ($booking) {
                $sellingOption = !empty($booking->selling_option)
                    ? json_encode($booking->selling_option, true)
                    : [];

                if ($vehicleReplacement) {
                    $sellingOption['vehicle_replacement'] = $data['vehicle_id'];
                    $booking->selling_option = json_encode($sellingOption, true);
                    $booking->save();
                }

                VehicleReservation::where('id', $booking->vehicle_reservation_id)
                    ->update(['ready_for_dealer' => $status]);

                $return['status'] = true;
                $return['message'] = "Your request saved successfully";
            }
        }

        return response()->json($return);
    }
}
