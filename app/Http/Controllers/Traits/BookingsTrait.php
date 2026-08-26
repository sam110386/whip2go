<?php
namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Legacy\CsPaymentLog;
use App\Models\Legacy\CsPaymentRetry;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Services\Legacy\AutoPiFleetClient;
use App\Services\Legacy\EmailQueueService;
use App\Services\Legacy\GeotabClient;
use App\Services\Legacy\GeotabkeylessClient;
use App\Services\Legacy\OnestepGpsClient;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PaymentProcessor;
use Carbon\Carbon;

trait BookingsTrait
{
    use MobileApi, AgreementTrait, VehicleDynamicFareMatrix, InsuranceToken, ActiveBookingTotalPending, PasstimeActivateVehicle, CompleteAndRenewBookingTrait;

    public function _startBooking(CsOrder $csOrder)
    {
        $orderId = $csOrder->id;

        $csOrder->status = 1;
        $csOrder->insurance_payer = $csOrder->depositRule->insurance_payer;
        $csOrder->order_rule_id = $csOrder->depositRule->id;
        $csOrder->start_timing = now();
        unset($csOrder->start_odometer);

        (new Passtime())->startPasstime($csOrder->vehicle_id, $orderId);
        $depositRule = (new DepositRule())->getBookingChargeEvent($csOrder->vehicle_id);

        $shouldCharge = in_array('S', [
            $depositRule['charge_rent_event'] ?? '',
            $depositRule['deposit_event'] ?? '',
            $depositRule['insurance_event'] ?? '',
            $depositRule['initial_event'] ?? ''
        ]);

        if ($shouldCharge) {
            return DB::transaction(function () use ($csOrder, $depositRule, $orderId) {
                $paymentProcessor = new PaymentProcessor();
                $payReturn = $paymentProcessor->ChargeAmount($csOrder->toArray(), $depositRule);

                $csOrder->deposit_type = $payReturn['deposit_type'] ?? $csOrder->deposit_type;
                $csOrder->insurance_amt = $payReturn['insurance_amt'] ?? $csOrder->insurance_amt;
                $csOrder->dpa_status = $payReturn['dpa_status'] ?? $csOrder->dpa_status;
                $csOrder->insu_status = $payReturn['insu_status'] ?? $csOrder->insu_status;
                $csOrder->emf_status = $payReturn['emf_status'] ?? $csOrder->emf_status;
                $csOrder->infee_status = $payReturn['infee_status'] ?? $csOrder->infee_status;
                $csOrder->payment_status = $payReturn['payment_status'] ?? $csOrder->payment_status;

                $csOrderPayment = new CsOrderPayment();
                $csOrderPayment->setOrderId($orderId);
                $csOrderPayment->setCurrency($payReturn['currency']);
                $csOrderPayment->setRenterId($csOrder->renter_id);

                if (!empty($payReturn['deposit_auth'])) {
                    $csOrderPayment->setAmount($csOrder->deposit);
                    $csOrderPayment->setTransactionidId($payReturn['deposit_auth']);
                    $csOrderPayment->setType($payReturn['deposit_type']);
                    $csOrderPayment->saveDepositTransaction();
                }

                if (!empty($payReturn['insurance_transaction_id'])) {
                    $csOrderPayment->setAmount($csOrder->insurance_amt);
                    $csOrderPayment->setTransactionidId($payReturn['insurance_transaction_id']);
                    $csOrderPayment->setPayerId($payReturn['insu_payerid']);
                    $csOrderPayment->saveInsuranceTransaction();
                }

                if (!empty($payReturn['transaction_id'])) {
                    $rentalAmount = $csOrder->rent + $csOrder->tax + $csOrder->dia_fee;
                    $csOrderPayment->setAmount($rentalAmount);
                    $csOrderPayment->setTransactionidId($payReturn['transaction_id']);
                    $csOrderPayment->setTax($csOrder->tax);
                    $csOrderPayment->setDiaFee($csOrder->dia_fee);
                    $csOrderPayment->saveRentalTransaction();
                }

                if (!empty($payReturn['emf_transaction_id'])) {
                    $emfAmount = $csOrder->extra_mileage_fee + $csOrder->emf_tax;
                    $csOrderPayment->setAmount($emfAmount);
                    $csOrderPayment->setTransactionidId($payReturn['emf_transaction_id']);
                    $csOrderPayment->setTax($csOrder->emf_tax);
                    $csOrderPayment->saveEmfTransaction();
                }

                if (!empty($payReturn['initial_fee_id'])) {
                    $initialAmount = $csOrder->initial_fee + $csOrder->initial_fee_tax;
                    $csOrderPayment->setAmount($initialAmount);
                    $csOrderPayment->setTax($csOrder->initial_fee_tax);
                    $csOrderPayment->setTransactionidId($payReturn['initial_fee_id']);
                    $csOrderPayment->saveInitialFeeTransaction();
                }

                $csOrder->save();

                if (($payReturn['status'] ?? '') === 'error') {
                    return [
                        'status' => false,
                        'message' => $payReturn['message'] ?? 'Payment failed',
                        'result' => []
                    ];
                }

            });
        } else {
            $csOrder->save();
        }


        return [
            'status' => true,
            'message' => "Your request processed successfully.",
            'orderid' => $orderId,
            'result' => []
        ];
    }
    private function withautorenew(array $result, string $autoRenewEndDateTime, bool $dontCharge = false)
    {
        if (empty($result['status']) || empty($result['result'] || $result['status'] != 1)) {
            return;
        }

        $tempCsOrder = $result['result'];
        $startDateTime = data_get($tempCsOrder, 'end_datetime');
        $timezone = data_get($tempCsOrder, 'timezone', 'UTC');
        $newOrderData = [
            'status' => 1,
            'pickup_address' => data_get($tempCsOrder, 'pickup_address'),
            'lat' => data_get($tempCsOrder, 'lat'),
            'lng' => data_get($tempCsOrder, 'lng'),
            'vehicle_name' => data_get($tempCsOrder, 'vehicle_name'),
            'user_id' => data_get($tempCsOrder, 'user_id'),
            'cc_token_id' => data_get($tempCsOrder, 'cc_token_id'),
            'timezone' => $timezone,
            'currency' => data_get($tempCsOrder, 'currency'),
            'start_datetime' => $startDateTime,
            'end_datetime' => Carbon::parse($autoRenewEndDateTime, $timezone)->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s'),
            'renter_id' => data_get($tempCsOrder, 'renter_id'),
            'vehicle_id' => data_get($tempCsOrder, 'vehicle_id'),
            'accepted_time' => now()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s'),
            'details' => data_get($tempCsOrder, 'details'),
            'start_timing' => $startDateTime,
            'parent_id' => data_get($tempCsOrder, 'parent_id') ?: data_get($tempCsOrder, 'id'),
            'deposit' => data_get($tempCsOrder, 'deposit'),
            'start_odometer' => data_get($tempCsOrder, 'end_odometer'),
        ];

        $priceRulesAmt = (new DepositRule())->getFeeRenewBooking($newOrderData, $newOrderData['parent_id']);

        $newOrderData['rent'] = $priceRulesAmt['time_fee'] ?? 0;
        $newOrderData['tax'] = $priceRulesAmt['tax'] ?? 0;
        $newOrderData['dia_fee'] = $priceRulesAmt['dia_fee'] ?? 0;
        $newOrderData['insurance_amt'] = $priceRulesAmt['insurance_amt'] ?? 0;
        $newOrderData['discount'] = $priceRulesAmt['discount'] ?? 0;
        $newOrderData['extra_mileage_fee'] = $priceRulesAmt['extra_mileage_fee'] ?? 0;
        $currentIncrementId = data_get($tempCsOrder, 'increment_id', '');

        if (data_get($tempCsOrder, 'parent_id') && Str::contains($currentIncrementId, '-')) {
            $incrementParts = explode('-', $currentIncrementId);
            $nextSequenceNumber = ((int) end($incrementParts)) + 1;
            $newOrderData['increment_id'] = $incrementParts[0] . '-' . $nextSequenceNumber;
        } else {
            $newOrderData['increment_id'] = $currentIncrementId . '-1';
        }

        $newOrder = CsOrder::create($newOrderData);

        Notifier::createIntercomeUserEvent([
            "event_name" => "booking_autorenew",
            "created_at" => time(),
            "external_id" => $newOrder->renter_id,
            "user_id" => $newOrder->renter_id,
            "metadata" => [
                "booking_id" => $newOrder->increment_id,
                "id" => $newOrder->id,
                "start_date" => Carbon::parse($startDateTime)->timezone($timezone)->format('Y-m-d H:i:s'),
                "end_date" => $autoRenewEndDateTime,
                "path" => "withautorenew"
            ]
        ]);

        if ($dontCharge) {
            $updatePayload = [
                'rent' => $priceRulesAmt['time_fee'] ?? 0,
                'tax' => $priceRulesAmt['tax'] ?? 0,
                'dia_fee' => $priceRulesAmt['dia_fee'] ?? 0,
                'extra_mileage_fee' => $priceRulesAmt['extra_mileage_fee'] ?? 0,
                'insurance_amt' => $priceRulesAmt['insurance_amt'] ?? 0,
                'insu_status' => 0,
                'payment_status' => 0,
            ];

            $paymentManager = new CsOrderPayment();
            $preDeposits = CsOrderPayment::getTotalDeposit(data_get($tempCsOrder, 'id'));

            if ($preDeposits > 0) {
                if (DepositTemplate::checkDepositRefundable($newOrder->user_id)) {
                    $updatePayload['deposit'] = $preDeposits;
                    $updatePayload['dpa_status'] = 1;
                    CsOrderPayment::copyDeposits(data_get($tempCsOrder, 'id'), $newOrder->id);
                }
            }

            $newOrder->update($updatePayload);
            return;
        }

        $paymentProcessor = new PaymentProcessor();
        $paymentProcessor->checkAndProcessRenew(
            $newOrder->renter_id,
            $newOrder->user_id,
            $priceRulesAmt,
            $newOrder->id,
            data_get($tempCsOrder, 'id'),
            $newOrder->cc_token_id,
            $newOrder->parent_id
        );
    }
    protected function _getAgreement(array $conditions): array
    {
        $csLeaseLists = CsOrder::with([
            'vehicle:id,make,model,year,vin_no,user_id,allowed_miles,msrp,premium_msrp,vehicleCostInclRecon,plate_number,disclosure',
            'owner:id,first_name,last_name,company_address,company_city,company_state,company_zip,timezone,distance_unit,company_name,representative_name,representative_role,representative_sign,contact_number'
        ])->where($conditions)->first();

        if (!$csLeaseLists) {
            return [
                'status' => false,
                'message' => "Sorry, you are not authorized for this booking",
                'result' => []
            ];
        }

        $leaseDataArray = $csLeaseLists->toArray();

        if ($csLeaseLists->status == 3 && $csLeaseLists->auto_renew == 0) {
            return $this->_getAgreementForCompletedBooking($leaseDataArray);
        }

        return $this->_generateAgreementForBooking($leaseDataArray);
    }
    private function _chargeLateFee(CsOrder $order): void
    {
        $depositRule = DepositRule::where('vehicle_id', $order->vehicle_id)
            ->select('lateness_fee')
            ->first();

        $extFee = ($depositRule && $depositRule->lateness_fee) ? $depositRule->lateness_fee : 0;

        if ($extFee <= 0) {
            return;
        }

        $paymentProcessor = new PaymentProcessor();
        $payreturn = $paymentProcessor->chargeAmtToUser(
            $extFee,
            $order->renter_id,
            'DIA Late Fee',
            $order->currency
        );

        if (isset($payreturn['status']) && $payreturn['status'] === 'success') {
            CsPaymentLog::savePartialPaymentLog([
                'orderid' => $order->id,
                'amount' => $payreturn['amt'],
                'transaction_id' => $payreturn['transaction_id'],
                'note' => 'Late fee is charged by admin booking extension'
            ], 30);

            $csOrderPayment = new CsOrderPayment();
            $csOrderPayment->setOrderId($order->id);
            $csOrderPayment->setCurrency($payreturn['currency']);
            $csOrderPayment->setRenterId($order->renter_id);
            $csOrderPayment->setAmount($payreturn['amt']);
            $csOrderPayment->setTransactionidId($payreturn['transaction_id']);
            $csOrderPayment->setTax(0);
            $csOrderPayment->setDiaFee(0);
            $csOrderPayment->saveLateFeeTransaction();
        }

        $latenessFeeStatus = $order->lateness_fee_status;

        if (!isset($payreturn['status']) || $payreturn['status'] !== 'success') {
            $latenessFeeStatus = 2;
        }

        $order->timestamps = false;
        $order->update([
            'lateness_fee' => ($order->lateness_fee + $extFee),
            'lateness_fee_status' => $latenessFeeStatus
        ]);
    }
    private function _syncvehiclegps(array $orderData = [])
    {
        $defaultErrorResponse = [
            'status' => false,
            'message' => 'Sorry, you are not authorized user for this action.',
            'result' => []
        ];

        $vehicleId = $orderData['vehicle_id'];

        if (!$orderData || !$vehicleId) {
            return response()->json($defaultErrorResponse);
        }

        $vehicleData = Vehicle::with(['csSetting', 'vehicleSetting'])
            ->select('id', 'user_id', 'vin_no')
            ->where('id', $vehicleId)
            ->first();

        if (!$vehicleData) {
            return response()->json($defaultErrorResponse);
        }

        $passtimeService = new Passtime();
        $passtimeService->parseVehicleSetting($vehicleData->toArray());

        $csSetting = $vehicleData->csSetting;
        $passtimeSetting = $csSetting->passtime ?? null;
        $gpsProvider = $csSetting->gps_provider ?? null;
        $vinNo = $vehicleData->vin_no;

        if ($passtimeSetting !== 'geotab' || $gpsProvider !== 'geotab') {
            $geotab = new GeotabClient();
            $geotabResponse = $geotab->getDealerDevices([
                'geotab_server' => $csSetting->geotab_server ?? null,
                'geotab_user' => $csSetting->geotab_user ?? null,
                'geotab_pwd' => $csSetting->geotab_pwd ?? null,
                'geotab_db' => $csSetting->geotab_db ?? null,
            ], [
                'vehicleIdentificationNumber' => $vinNo
            ]);

            if (empty($geotabResponse['status']) || empty($geotabResponse['result'])) {
                $geotabResponse['message'] = "Sorry no device found";
                return response()->json($geotabResponse);
            }

            return response()->json([
                'status' => true,
                'message' => "Your request is processed successfully",
                'result' => $geotabResponse['result'][0]['id'] ?? ''
            ]);
        }

        if ($passtimeSetting === 'onestepgps' || $gpsProvider === 'onestepgps') {
            $params = [
                'api-key' => $csSetting->onestepgps ?? null,
                'device_id' => 1,
                'vin' => 1
            ];

            $onestepgps = new OnestepGpsClient();
            $gpsResponse = $onestepgps->ExecuteCustomCall('device-info', $params);

            if (empty($gpsResponse['status'])) {
                return response()->json($gpsResponse);
            }

            $deviceMap = collect($gpsResponse['result'] ?? [])
                ->pluck('device_id', 'vin')
                ->toArray();

            return response()->json([
                'status' => true,
                'message' => "Your request is processed successfully",
                'result' => $deviceMap[$vinNo] ?? ""
            ]);
        }

        if ($passtimeSetting === 'autopi' || $gpsProvider === 'autopi') {
            $params = [
                'autopi_token' => $csSetting->autopi_token ?? null
            ];

            $autoPi = new AutoPiFleetClient();
            $autoPiResponse = $autoPi->getDealerDevices($csSetting->autopi_token);

            if (empty($autoPiResponse['status'])) {
                return response()->json($autoPiResponse);
            }

            $connectionsMap = collect($autoPiResponse['result'] ?? [])
                ->pluck('connections', 'vin')
                ->toArray();

            $vinConnections = $connectionsMap[$vinNo] ?? [];
            $firstConnection = is_array($vinConnections) ? reset($vinConnections) : null;

            return response()->json([
                'status' => true,
                'message' => "Your request is processed successfully",
                'result' => $firstConnection['id'] ?? ""
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => "Sorry, we support only geotab vehicle sync. Please check your passtime setting to use this feature",
            'result' => []
        ]);

    }
    private function processfullpayment(CsOrder $order): array
    {
        $hasRetryInQueue = CsPaymentRetry::where('cs_order_id', $order->id)
            ->where('status', 0)
            ->exists();

        if ($hasRetryInQueue) {
            return [
                'status' => false,
                'message' => 'Sorry, we cant perform this request now because same request is already in queue. Please try again later',
                'result' => []
            ];
        }

        $error = $this->processRetryPendingPayment($order);

        if ($error) {
            return [
                'status' => false,
                'message' => 'Payment has failed due to a lack of funds on the card or your bank denying the charge for another reason. Please add funds to your card or change the default credit card by going to My Account and the Make Payments section. Then, reattempt making a payment',
                'result' => []
            ];
        }

        $failedPayments = '';

        if ($order->payment_status == 2) {
            $failedPayments .= 'Rental, ';
        }
        if ($order->insu_status == 2) {
            $failedPayments .= ' Insurance,';
        }
        if ($order->dpa_status == 2) {
            $failedPayments .= ' Deposit,';
        }
        if ($order->infee_status == 2) {
            $failedPayments .= ' Initial Fee,';
        }
        if ($order->dia_insu_status == 2) {
            $failedPayments .= ' EMF Insurance,';
        }
        if ($order->emf_status == 2 || $order->emf_status) {
            $failedPayments .= ' EMF';
        }

        $beginDate = Carbon::parse($order->start_datetime)->setTimezone($order->timezone)->format('m/d/Y');
        $endDate = Carbon::parse($order->end_datetime)->setTimezone($order->timezone)->format('m/d/Y');

        Notifier::createIntercomeUserEvent([
            'event_name' => 'full_payment',
            'created_at' => time(),
            'external_id' => $order->renter_id,
            'user_id' => $order->renter_id,
            'metadata' => [
                'id' => $order->id,
                'booking_id' => $order->increment_id,
                'begin_date' => $beginDate,
                'end_date' => $endDate,
                'failed_payments' => $failedPayments,
                'type' => 'retryPendingPayment'
            ]
        ]);

        $unlocked = $this->ActivatePasstimeVehicle($order->vehicle_id);

        if ($unlocked) {
            Notifier::createIntercomeUserEvent([
                'event_name' => 'starter_enabled',
                'created_at' => time(),
                'external_id' => $order->renter_id,
                'user_id' => $order->renter_id,
                'metadata' => [
                    'id' => $order->id,
                    'booking_id' => $order->increment_id,
                    'begin_date' => $beginDate,
                    'end_date' => $endDate,
                    'failed_payments' => $failedPayments,
                    'type' => 'retryPendingPayment'
                ]
            ]);
        }

        return [
            'status' => true,
            'message' => 'Your payment was successful and the vehicle is now activated.',
            'result' => []
        ];
    }
    private function processAdvancePayment(array $post): array
    {
        $booking = $post['Booking'] ?? [];
        $advAmt = (float) preg_replace("/[^0-9.]/", "", $booking['advamt'] ?? 0);
        $bookingId = $booking['id'] ?? null;

        if (empty($post) || $advAmt === 0.0 || empty($bookingId)) {
            return [
                'status' => false,
                'message' => 'Invalid request body',
                'result' => []
            ];
        }

        $order = CsOrder::where('id', $bookingId)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$order) {
            return [
                'status' => false,
                'message' => 'You dont have permission for this request',
                'result' => []
            ];
        }

        $paymentProcessor = new PaymentProcessor();
        $res = $paymentProcessor->chargeAmtToUser(
            $advAmt,
            $order->renter_id,
            'Advance Payment',
            $order->currency
        );

        if (($res['status'] ?? '') !== 'success') {
            return [
                'status' => false,
                'message' => $res['message'] ?? 'Payment charging failed',
                'result' => []
            ];
        }

        $formattedAmt = sprintf('%0.2f', $advAmt);
        $note = "I advance payment {$formattedAmt}";

        CsPaymentLog::savePartialPaymentLog([
            'orderid' => $order->id,
            'amount' => $res['amt'],
            'transaction_id' => $res['transaction_id'],
            'note' => $note,
        ], 29);

        CsWallet::addBalance(
            $res['amt'],
            $order->renter_id,
            $res['transaction_id'],
            'Advance Payment',
            $order->id,
            now()
        );

        OrderExtlog::create([
            'admin_count' => 1,
            'cs_order_id' => $order->id,
            'ext_date' => now(),
            'note' => $note,
            'owner' => $order->renter_id,
            'amt' => $formattedAmt,
            'created' => now(),
        ]);

        $msg = "Advance Payment $" . $res['amt'] . " was made successfully, of your DriveItAway order ";
        (new EmailQueueService())->saveEmailToQueue(null, $res['amt'], $msg, $order->id, 'card');

        return [
            'status' => true,
            'message' => 'Payment is processed successfully',
            'result' => []
        ];
    }
    private function _geotabkeylesslock($vehicleId): array
    {
        if (!$vehicleId) {
            return [
                'status' => false,
                'message' => 'Sorry, respective dealer setting is not geotab keyless provider',
                'result' => []
            ];
        }

        $vehicleData = Vehicle::select(['id', 'user_id', 'passtime_serialno'])
            ->with([
                'vehicleSetting',
                'csSetting:user_id,passtime,geotab_user,geotab_server,geotab_pwd,geotab_db'
            ])
            ->where('id', $vehicleId)
            ->whereHas('csSetting', function ($query) {
                $query->where('passtime', 'geotabkeyless');
            })
            ->first();

        if (!$vehicleData) {
            return [
                'status' => false,
                'message' => 'Sorry, respective dealer setting is not geotab keyless provider',
                'result' => []
            ];
        }

        $parsedData = (new Passtime())->parseVehicleSetting($vehicleData->toArray());

        if (empty($parsedData['passtime_serialno'])) {
            return [
                'status' => false,
                'message' => 'Sorry, vehicle serial number not saved.',
                'result' => []
            ];
        }

        return (new GeotabkeylessClient())->lock($parsedData);
    }
    private function _geotabkeylessunlock($vehicleId): array
    {
        if (!$vehicleId) {
            return [
                'status' => false,
                'message' => 'Sorry, respective dealer setting is not geotab keyless provider',
                'result' => []
            ];
        }

        $vehicleData = Vehicle::select(['id', 'user_id', 'passtime_serialno'])
            ->with([
                'vehicleSetting',
                'csSetting:user_id,passtime,geotab_user,geotab_server,geotab_pwd,geotab_db'
            ])
            ->where('id', $vehicleId)
            ->whereHas('csSetting', function ($query) {
                $query->where('passtime', 'geotabkeyless');
            })
            ->first();

        if (!$vehicleData) {
            return [
                'status' => false,
                'message' => 'Sorry, respective dealer setting is not geotab keyless provider',
                'result' => []
            ];
        }

        $parsedData = (new Passtime())->parseVehicleSetting($vehicleData->toArray());

        if (empty($parsedData['passtime_serialno'])) {
            return [
                'status' => false,
                'message' => 'Sorry, vehicle serial number not saved.',
                'result' => []
            ];
        }

        return (new GeotabkeylessClient())->unlock($parsedData);
    }
    private function _overdue_booking_details(Request $request)
    {
        $orderId = $request->input('order');

        if (!$orderId) {
            return view('_overdue_booking_details', ['bookings' => collect()]);
        }

        $orderObj = CsOrder::select(['id', 'parent_id'])->find($orderId);

        $suborders = [$orderId];

        if ($orderObj && $orderObj->parent_id) {
            $suborders = CsOrder::where('id', $orderObj->parent_id)
                ->orWhere('parent_id', $orderObj->parent_id)
                ->pluck('id')
                ->toArray();
        }

        $bookings = CsOrder::select([
            'id',
            'increment_id',
            'start_datetime',
            'end_datetime',
            'timezone'
        ])
            ->with([
                'orderExtlogs' => function ($query) {
                    $query->orderBy('id', 'desc');
                }
            ])
            ->whereIn('id', $suborders)
            ->get();

        return view('bookings._overdue_booking_details', compact('bookings'));
    }
    public function _saveBookingOdometer(Request $request)
    {
        $bookingId = $this->decodeId($request->input('Text.booking', $request->input('booking')));
        $currentOdometer = $request->input('Text.current_odomter', $request->input('current_odomter'));
        $currentOdometerTo = $request->input('Text.current_odomter_to', $request->input('current_odomter_to'));

        $order = CsOrder::select(['id', 'vehicle_id'])
            ->where('id', $bookingId)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you are not authorized user for this action.',
                'result' => []
            ]);
        }

        if (!in_array($currentOdometerTo, ['start_odometer', 'end_odometer'])) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, wrong selection, to update.',
                'result' => []
            ]);
        }

        $order->update([
            $currentOdometerTo => $currentOdometer
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Your request is processed successfully',
            'result' => []
        ]);
    }
    public function _pullVehicleOdometer(Request $request)
    {
        $bookingId = $this->decodeId($request->input('Text.booking', $request->input('booking')));
        $order = CsOrder::select(['id', 'vehicle_id'])
            ->where('id', $bookingId)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you are not authorized user for this action.',
                'result' => []
            ]);
        }

        $vehicle = Vehicle::select([
            'id',
            'user_id',
            'passtime_serialno',
            'gps_serialno',
            'passtime_status',
            'last_mile',
        ])
            ->with([
                'csSetting',
                'vehicleSetting',
                'owner:id,distance_unit'
            ])
            ->find($order->vehicle_id);

        if (!$vehicle) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you are not authorized user for this action.',
                'result' => []
            ]);
        }

        $passtimeService = new Passtime();
        $response = $passtimeService->getVehicleLastMile($vehicle->toArray());

        if (empty($response['status'])) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, GPS didnt responded, please check GPS provider setting again',
                'result' => []
            ]);
        }

        return response()->json($response);
    }
    public function _autocomplete(Request $request)
    {
        $searchTerm = trim($request->input('term', ''));
        $bookingId = trim($request->input('id', ''));
        $query = CsOrder::select(['id', 'increment_id', 'vehicle_id']);

        if ($bookingId !== '') {
            $query->where('id', $bookingId);
        } else {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "{$searchTerm}%")
                    ->orWhere('increment_id', 'LIKE', "%{$searchTerm}%");
            });
        }

        $bookings = $query->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'tag' => $order->increment_id,
                    'vehicle' => $order->vehicle_id,
                ];
            });

        return response()->json($bookings);
    }

    public function _editsave($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $orderId = $data['Text']['id'] ?? null;
                $vehicleId = $data['Text']['vehicle_id'] ?? null;

                if (!$orderId || !$vehicleId) {
                    return ['status' => false, 'message' => "Invalid inputs"];
                }

                $csOrder = CsOrder::findOrFail($orderId);
                $vehicle = Vehicle::findOrFail($vehicleId);

                $endTimeStr = str_replace('AM', ' AM', str_replace('PM', ' PM', $data['Text']['end_time']));
                $endDate = $data['daterangeto'];
                $endDateTime = Carbon::parse($endDate . ' ' . $endTimeStr);

                // Logic to match start time if > 24h
                $startDateTime = Carbon::parse($csOrder->start_datetime);
                if ($endDateTime->diffInHours($startDateTime) >= 24) {
                    $endDateTime = Carbon::parse($endDate . ' ' . $startDateTime->format('H:i:s'));
                }

                $csOrder->update([
                    'pickup_address' => trim($data['Text']['location'] ?? $csOrder->pickup_address),
                    'end_datetime' => $endDateTime->toDateTimeString(),
                    'vehicle_id' => $vehicleId,
                    'vehicle_name' => $vehicle->vehicle_name,
                    'user_id' => $vehicle->user_id
                ]);

                if ($vehicleId != $csOrder->getOriginal('vehicle_id')) {
                    Vehicle::where('id', $vehicleId)->update(['booked' => 1]);
                    Vehicle::where('id', $csOrder->getOriginal('vehicle_id'))->update(['booked' => 0]);

                    // Reset Passtime
                    $this->ActivatePasstimeVehicle($vehicleId);

                    // Update rental fee if requested
                    if (isset($data['Text']['updatebooking'])) {
                        $parentId = $csOrder->parent_id ?: $csOrder->id;
                        OrderDepositRule::where('cs_order_id', $parentId)->update(['rental' => $vehicle->day_rent]);
                    }
                }

                return ['status' => true, 'message' => "Your changes saved successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _editsave: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function _cancelBooking($csOrder, $cancelNote, $cancellationFee)
    {
        try {
            return DB::transaction(function () use ($csOrder, $cancelNote, $cancellationFee) {
                $csOrder->update([
                    'status' => 2,
                    'cancel_note' => $cancelNote,
                    'cancellation_fee' => $cancellationFee,
                    'rent' => 0,
                    'tax' => 0
                ]);

                Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0]);

                // Placeholder for PaymentProcessor::ChargeCancelAmount
                Log::info("Charging cancellation fee for booking " . $csOrder->id);

                return ['status' => true, 'message' => "Booking cancelled successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _cancelBooking: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
