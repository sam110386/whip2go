<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use App\Models\Legacy\AxleStatus;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsPaymentLog;
use App\Models\Legacy\CsUserBalance;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\DriverFinancedInsuranceQuote;
use App\Models\Legacy\DynamicDeposit;
use App\Models\Legacy\InsuranceQuote;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsTwilioOrder;
use App\Services\Legacy\AxleService;
use App\Services\Legacy\EmailQueueService;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\UnlockVehicle;
use App\Http\Controllers\Traits\BookingsTrait;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class BookingsController extends LegacyAppController
{
    use BookingsTrait;

    public function index(Request $request)
    {
        $admin = $this->getAdminUserid();

        if (empty($admin['administrator'])) {
            session()->flash('error', 'Sorry, you are not authorized user for this action!');
            return redirect('/admin/linked_bookings/index');
        }

        $limit = (int) $request->input('Record.limit', 100);
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $tripLog = CsOrder::with([
            'owner:id,first_name,last_name',
            'driver:id,first_name,last_name',
        ])
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', function ($join) {
                $join->on('OrderDepositRule.cs_order_id', '=', 'cs_orders.id')
                    ->orOn('OrderDepositRule.cs_order_id', '=', 'cs_orders.parent_id');
            })
            ->select([
                'cs_orders.*',
                'OrderDepositRule.insurance_payer',
                'OrderDepositRule.vehicle_reservation_id',
                'OrderDepositRule.id as deposit_rule_id'
            ])
            ->whereNotIn('cs_orders.status', [2, 3])
            ->orderBy("cs_orders.$sort", $direction)
            ->paginate($limit)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.bookings.elements.booking', ['tripLog' => $tripLog, 'limit' => $limit]);
        }

        return view('admin.bookings.index', ['tripLog' => $tripLog, 'limit' => $limit]);
    }
    public function startBooking(Request $request)
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));

        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $csOrder = CsOrder::with('depositRule:id,cs_order_id,insurance_payer')
            ->where('id', $orderId)
            ->where('parent_id', 0)
            ->first();

        if (!$csOrder) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }

        if ($csOrder->status != 0) {
            return response()->json(['status' => false, 'message' => 'sorry, booking already accepted.', 'result' => []]);
        }

        $return = $this->_startBooking($csOrder);

        return response()->json($return);
    }
    public function loadcancelBooking(Request $request)
    {
        $orderId = $this->decodeId($request->input('orderid', ''));

        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $order = CsOrder::where('id', $orderId)->first(['id', 'vehicle_id']);

        if (!$order) {
            return response('Order not found', 404);
        }

        $cancellation_fee = (new DepositRule())->getCancellationFee($order->vehicle_id);

        return response()->view('admin.bookings.loadcancel_booking', [
            'orderid' => base64_encode($order->id),
            'cancellation_fee' => $cancellation_fee,
        ]);
    }
    public function load_single_row(Request $request)
    {
        $orderId = $this->decodeId($request->input('orderid', ''));

        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $trip = CsOrder::select([
            'cs_orders.*',
            'OrderDepositRule.insurance_payer as rule_insurance_payer',
            'OrderDepositRule.id as rule_id'
        ])
            ->with(['owner', 'driver'])
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', function ($join) {
                $join->on('OrderDepositRule.cs_order_id', '=', 'cs_orders.id')
                    ->orOn('OrderDepositRule.cs_order_id', '=', 'cs_orders.parent_id');
            })
            ->find($orderId);

        if (!$trip) {
            return response('Order not found', 404);
        }

        return response()->view('admin.bookings.load_single_row', ['trip' => $trip]);
    }
    public function cancelBooking(Request $request)
    {
        $orderId = $this->decodeId($request->input('Text.orderid', $request->input('orderid', '')));
        $cancelNote = trim($request->input('Text.cancel_note', $request->input('cancel_note', '')));
        $cancellationFee = $request->input('Text.cancellation_fee', $request->input('cancellation_fee', 0));

        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $csOrder = CsOrder::find($orderId);

        if (!$csOrder) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }

        if ($csOrder->status != 0 && $csOrder->status != 2) {
            return response()->json(['status' => false, 'message' => 'sorry, you cant cancel this booking. Please mark it complete if you really want to finish this booking.', 'result' => []]);
        }

        if ($csOrder->status !== 0) {
            return response()->json(['status' => false, 'message' => 'sorry, booking already canceled.', 'result' => []]);
        }


        $result = DB::transaction(function () use ($csOrder, $orderId, $cancellationFee, $cancelNote) {
            Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0]);

            $paymentProcessor = new PaymentProcessor();
            $csOrderPayment = new CsOrderPayment();
            $payReturn = $paymentProcessor->ChargeCancelAmount($csOrder->toArray(), $cancellationFee);

            if (($payReturn['status'] ?? '') === 'success') {
                $csOrder->status = 2;
                $csOrder->cancellation_fee = $cancellationFee;
                $csOrder->cancel_note = $cancelNote;
                $csOrder->rent = 0;
                $csOrder->tax = 0;

                if (!empty($payReturn['cancel_fee_transaction_id'])) {
                    $csOrderPayment->saveCancelTransaction(
                        $orderId,
                        $cancellationFee,
                        $payReturn['cancel_fee_transaction_id'],
                        $csOrder->user_id
                    );
                }

                $csOrder->save();

                Notifier::updateIntercomeUserAttrbute($csOrder->renter_id, ['Booking_Status' => '']);

                return [
                    'status' => true,
                    'message' => 'Your booking canceled successfully.',
                    'orderid' => $orderId,
                    'result' => []
                ];
            }

            return [
                'status' => false,
                'message' => $payReturn['message'] ?? 'Payment authorization failed.',
                'result' => []
            ];
        });

        return response()->json($result);
    }
    public function loadcompleteBooking(Request $request)
    {
        $orderId = $this->decodeId($request->input('orderid', ''));
        $autorenew = $request->input('autorenew');

        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $csOrder = CsOrder::findOrFail($orderId);

        if (!$csOrder) {
            return response('Order not found', 404);
        }

        $allFee = (new DepositRule())->getAllFee($csOrder->toArray(), $autorenew);

        if (($allFee['end_odometer'] ?? 0) > 0) {
            CsOrder::where('id', $orderId)->update(['end_odometer' => $allFee['end_odometer']]);
        }

        if ($csOrder->insu_status == 2 && ($allFee['insurance_amt'] ?? 0) <= $csOrder->insurance_amt) {
            $allFee['insurance_amt'] = $csOrder->insurance_amt;
        }

        $allFee['pending_toll'] = $csOrder->pending_toll;
        $allFee['estimated_rent'] = ($allFee['estimated_rent'] ?? 0) + ($allFee['discount'] ?? 0);

        $startTime = Carbon::parse($csOrder->start_datetime);
        $endTime = Carbon::parse($csOrder->end_datetime);
        $daysGapInSeconds = $endTime->diffInSeconds($startTime);

        $endDateTime = Carbon::parse($csOrder->end_datetime)
            ->addSeconds($daysGapInSeconds)
            ->timezone($csOrder->timezone ?? 'UTC')
            ->format('Y-m-d H:i:s');

        $targetRuleId = $csOrder->parent_id ?: $csOrder->id;

        $duration = OrderDepositRule::nextDuration($targetRuleId, $csOrder->end_datetime, $endDateTime);

        if ($duration) {
            $endDateTime = Carbon::parse($csOrder->end_datetime)
                ->addDays($duration)
                ->timezone($csOrder->timezone ?? 'UTC')
                ->format('Y-m-d H:i:s');
        }

        $orderDepositRule = OrderDepositRule::where('cs_order_id', $targetRuleId)
            ->select(['id', 'cs_order_id', 'insurance'])
            ->first();

        $calculatedInsurance = 0;
        if ($orderDepositRule) {
            $axleStatusObj = AxleStatus::where('order_id', $orderDepositRule->id)->first();

            if ($axleStatusObj && in_array($axleStatusObj->axle_status, [3, 4]) && $axleStatusObj->expired_on < now()->format('Y-m-d')) {
                $days = $this->commonService->days_between_dates($axleStatusObj->expired_on, now()->format('Y-m-d'));
                $totalInsurance = sprintf('%0.2f', ($days * $orderDepositRule->insurance));
                $calculatedInsurance = sprintf('%0.2f', ($totalInsurance - $axleStatusObj->calculated_insurance));
            }
        }

        return view('admin.bookings.loadcompleteBooking', [
            'calculatedInsurance' => $calculatedInsurance,
            'end_datetime' => $endDateTime,
            'autorenew' => $autorenew,
            'all_fee' => $allFee,
            'orderid' => base64_encode($orderId),
        ]);
    }
    public function completeBooking(Request $request)
    {
        $textInputs = $request->input('Text', []);
        $orderId = isset($textInputs['orderid']) ? $this->decodeId(trim($textInputs['orderid'])) : null;
        $rent = $textInputs['rent'] ?? null;
        $tax = $textInputs['tax'] ?? null;
        $extra_mileage_fee = $textInputs['extra_mileage_fee'] ?? null;

        if (empty($orderId)) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $csOrder = CsOrder::with([
            'vehicle:id,make,model,year,vin_no,user_id,allowed_miles,msrp,vehicleCostInclRecon,plate_number,passtime_status,disclosure',
            'user:id,first_name,last_name,company_address,company_city,company_state,company_zip,timezone,distance_unit,company_name,representative_name,representative_role,representative_sign,contact_number'
        ])
            ->where('id', $orderId)
            ->where('status', 1)
            ->first();

        if (!$csOrder) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }

        if (!in_array($csOrder->status, [0, 1])) {
            return response()->json(['status' => false, 'message' => 'sorry, booking already completed.', 'result' => []]);
        }

        $targetRuleId = $csOrder->parent_id ?: $csOrder->id;
        $bookingRentalChoice = OrderDepositRule::where('cs_order_id', $targetRuleId)
            ->select(['id', 'insurance_payer', 'tax', 'insurance'])
            ->first();

        if (!$bookingRentalChoice) {
            return response()->json(['status' => false, 'message' => 'Sorry, Renter booking rent preference data not found', 'result' => []]);
        }

        $csOrderTemp = clone $csOrder;
        $paymentProcessor = new PaymentProcessor();
        $csOrderPayment = new CsOrderPayment();

        $csOrder->extra_mileage_fee = trim($textInputs['extra_mileage_fee'] ?? 0);
        $csOrder->lateness_fee = trim($textInputs['lateness_fee'] ?? 0);
        $csOrder->damage_fee = trim($textInputs['damage_fee'] ?? 0);
        $csOrder->uncleanness_fee = trim($textInputs['uncleanness_fee'] ?? 0);
        $csOrder->rent = trim($textInputs['rent'] ?? 0);
        $csOrder->tax = trim($textInputs['tax'] ?? 0);
        $csOrder->dia_fee = trim($textInputs['dia_fee'] ?? 0);
        $csOrder->dia_insu = trim($textInputs['dia_insu'] ?? 0);
        $csOrder->discount = trim($textInputs['discount'] ?? 0) > $csOrder->rent ? 0 : trim($textInputs['discount'] ?? 0);
        $csOrder->insurance_amt = trim($textInputs['insurance_fee'] ?? 0);
        $csOrder->pending_toll = trim($textInputs['pending_toll'] ?? 0);
        $csOrder->initial_fee = trim($textInputs['initial_fee'] ?? 0);
        $csOrder->initial_fee_tax = trim($textInputs['initial_fee_tax'] ?? 0);
        $csOrder->end_timing = now();
        $csOrder->details = trim($textInputs['details'] ?? '') ?: $csOrder->details;
        $csOrder->insurance_payer = $bookingRentalChoice->insurance_payer;
        $csOrder->order_rule_id = $bookingRentalChoice->id;

        $totalPaid = $csOrder->rent + $csOrder->tax + $csOrder->uncleanness_fee + $csOrder->damage_fee + $csOrder->dia_fee;
        $emfTax = number_format((($csOrder->extra_mileage_fee * $bookingRentalChoice->tax) / 100), 2, '.', '');
        $totalEmf = $csOrder->extra_mileage_fee + $emfTax;
        $csOrder->emf_tax = $emfTax;

        $alreadyPaid = CsOrderPayment::getTotalPaidRental($csOrder->id);
        $paidLateFee = CsOrderPayment::getTotalPaidLateFee($csOrder->id);
        $paidInitialFee = CsOrderPayment::getTotalInitialFee($csOrder->id);

        $autoRenew = isset($textInputs['autorenew']);
        $renewButDontCharge = isset($textInputs['renew_but_dont_charge']) && $textInputs['renew_but_dont_charge'] == 1;
        $autoRenewEndDateInput = $textInputs['autorenewenddate'] ?? null;
        $autoRenewEndDateTime = !empty($autoRenewEndDateInput) ? Carbon::parse($autoRenewEndDateInput)->format('Y-m-d H:i:s') : false;

        $hasBalanceChanges = (
            $totalEmf > 0 ||
            $totalPaid > 0 ||
            $alreadyPaid > 0 ||
            $csOrder->insurance_amt > 0 ||
            $csOrder->dia_insu > 0 ||
            $csOrder->pending_toll > 0 ||
            ($csOrder->lateness_fee != $paidLateFee) ||
            (($paidInitialFee['initial_fee'] ?? 0) != $csOrder->initial_fee) ||
            (($paidInitialFee['initial_fee_tax'] ?? 0) != $csOrder->initial_fee_tax)
        );

        $payReturn = ['status' => 'success'];

        if ($autoRenew && $autoRenewEndDateTime && $hasBalanceChanges) {
            $payReturn = $paymentProcessor->ChargeAmountOnCompleteForRenew($csOrder->toArray(), $csOrderTemp->toArray());
        } elseif (!$autoRenew && $hasBalanceChanges) {
            $payReturn = $paymentProcessor->ChargeAmountOnComplete($csOrder->toArray(), $csOrderTemp->toArray());
        }

        $csOrder->dpa_status = $payReturn['dpa_status'] ?? $csOrder->dpa_status;
        $csOrder->insu_status = $payReturn['insu_status'] ?? $csOrder->insu_status;
        $csOrder->emf_status = $payReturn['emf_status'] ?? $csOrder->emf_status;
        $csOrder->infee_status = $payReturn['infee_status'] ?? $csOrder->infee_status;
        $csOrder->payment_status = $payReturn['payment_status'] ?? $csOrder->payment_status;
        $csOrder->dia_insu_status = $payReturn['dia_insu_status'] ?? $csOrder->dia_insu_status;
        $csOrder->paid_amount = $csOrder->payment_status ? $totalPaid : 0;
        $csOrder->lateness_fee_status = $payReturn['latefee_status'] ?? $csOrder->lateness_fee_status;

        return DB::transaction(function () use ($csOrder, $payReturn, $csOrderPayment, $orderId, $totalPaid, $rent, $tax, $extra_mileage_fee, $emfTax, $autoRenew, $autoRenewEndDateTime, $renewButDontCharge, $bookingRentalChoice, $csOrderTemp) {

            $csOrderPayment->setOrderId($orderId);
            $csOrderPayment->setCurrency($payReturn['currency']);
            $csOrderPayment->setRenterId($csOrder->renter_id);

            if (!empty($payReturn['insurance_transaction_id'])) {
                $csOrderPayment->setAmount($payReturn['insurance_amt']);
                $csOrderPayment->setTransactionidId($payReturn['insurance_transaction_id']);
                $csOrderPayment->setPayerId($payReturn['insu_payerid']);
                $csOrderPayment->saveInsuranceTransaction();
            }

            if (!empty($payReturn['transaction_id'])) {
                $newPayment = $payReturn['new_payment'] ?? null;

                if ($newPayment == 1) {
                    $csOrderPayment->setAmount($totalPaid);
                    $csOrderPayment->setTransactionidId($payReturn['transaction_id']);
                    $csOrderPayment->setTax($tax);
                    $csOrderPayment->setDiaFee($csOrder->dia_fee);
                    $csOrderPayment->saveRentalTransaction();
                } elseif ($newPayment == 3) {
                    $csOrderPayment->setAmount(($payReturn['balance_rent'] + $payReturn['balance_tax'] + $payReturn['balance_dia_fee']));
                    $csOrderPayment->setTransactionidId($payReturn['transaction_id']);
                    $csOrderPayment->setTax($payReturn['balance_tax']);
                    $csOrderPayment->setDiaFee($payReturn['balance_dia_fee']);
                    $csOrderPayment->saveRentalTransaction();
                } elseif ($newPayment == 4) {
                    CsOrderPayment::where([
                        'id' => $orderId,
                        'transaction_id' => $payReturn['transaction_id'],
                        'type' => 2
                    ])
                        ->update([
                            'amount' => $csOrder->paid_amount,
                            'rent' => $rent,
                            'tax' => $tax,
                            'dia_fee' => $csOrder->dia_fee
                        ]);
                }
            }

            if (!empty($payReturn['emf_transaction_id'])) {
                $newEmfPayment = $payReturn['new_emf_payment'] ?? null;

                if ($newEmfPayment == 1) {
                    $csOrderPayment->setAmount(($csOrder->extra_mileage_fee + $csOrder->emf_tax));
                    $csOrderPayment->setTransactionidId($payReturn['emf_transaction_id']);
                    $csOrderPayment->setTax($emfTax);
                    $csOrderPayment->saveEmfTransaction();
                } elseif ($newEmfPayment == 3) {
                    $csOrderPayment->setAmount(($payReturn['balance_emf'] + $payReturn['balance_emf_tax']));
                    $csOrderPayment->setTransactionidId($payReturn['emf_transaction_id']);
                    $csOrderPayment->setTax($payReturn['balance_emf_tax']);
                    $csOrderPayment->saveEmfTransaction();
                } elseif ($newEmfPayment == 4) {
                    CsOrderPayment::where([
                        'id' => $orderId,
                        'transaction_id' => $payReturn['emf_transaction_id'],
                        'type' => 16
                    ])
                        ->update([
                            'amount' => ($csOrder->extra_mileage_fee + $csOrder->emf_tax),
                            'rent' => $extra_mileage_fee,
                            'tax' => $emfTax
                        ]);
                }
            }

            if (!empty($payReturn['initial_fee_id'])) {
                $csOrderPayment->setAmount($csOrder->initial_fee + $csOrder->initial_fee_tax);
                $csOrderPayment->setTax($csOrder->initial_fee_tax);
                $csOrderPayment->setTransactionidId($payReturn['initial_fee_id']);
                $csOrderPayment->saveInitialFeeTransaction();
            }

            if (!empty($payReturn['toll_transaction_id'])) {
                $csOrderPayment->saveTollTransaction($orderId, $payReturn['pending_toll'], $payReturn['toll_transaction_id'], $csOrder->user_id);
                $csOrder->toll += $payReturn['pending_toll'];
                $csOrder->toll_status = $payReturn['toll_status'];
                $csOrder->pending_toll -= $payReturn['pending_toll'];
            }

            if (!empty($payReturn['dia_insu_transaction_id'])) {
                $csOrderPayment->setAmount($payReturn['dia_insu']);
                $csOrderPayment->setTransactionidId($payReturn['dia_insu_transaction_id']);
                $csOrderPayment->setPayerId($payReturn['insu_payerid']);
                $csOrderPayment->saveDiaInsuranceTransaction();
            }

            if (!empty($payReturn['latefee_transaction_id'])) {
                $csOrderPayment->setAmount($payReturn['latefee']);
                $csOrderPayment->setTransactionidId($payReturn['latefee_transaction_id']);
                $csOrderPayment->saveLateFeeTransaction();
            }

            if (!$autoRenew) {
                $csOrder->auto_renew = 0;
            }

            $statusResult = $payReturn['status'] ?? 'error';

            if ($statusResult === 'success') {
                $csOrder->status = 3;

                if ($autoRenew && $autoRenewEndDateTime) {
                    $csOrder->auto_renew = 1;
                }

                $csOrder->save();

                Notifier::updateIntercomeUserAttrbute($csOrder->renter_id, ["Rental_Status" => "Paid"]);

                if ($autoRenew && $autoRenewEndDateTime) {
                    $this->withautorenew(['status' => true], $autoRenewEndDateTime, $renewButDontCharge);
                } else {
                    Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0, 'status' => 10]);
                    Notifier::updateIntercomeUserAttrbute($csOrder->renter_id, ['Booking_Status' => ""]);
                    (new AxleService())->closeAxleConnection($bookingRentalChoice->id);
                }

                $unlocked = $this->ActivatePasstimeVehicle($csOrder->vehicle_id);

                if ($unlocked) {
                    Notifier::createIntercomeUserEvent([
                        "event_name" => "starter_enabled",
                        "created_at" => time(),
                        "external_id" => $csOrder->renter_id,
                        "user_id" => $csOrder->renter_id,
                        "metadata" => [
                            "id" => $csOrder->id,
                            "booking_id" => $csOrder->increment_id,
                            "begin_date" => Carbon::parse($csOrder->start_datetime)->timezone($csOrder->timezone ?? 'UTC')->format('m/d/Y'),
                            "end_date" => Carbon::parse($csOrder->end_datetime)->timezone($csOrder->timezone ?? 'UTC')->format('m/d/Y'),
                            "type" => "auto_renew"
                        ]
                    ]);
                }

                $this->_getagreementForCompletedBooking($csOrderTemp->toArray());

                return response()->json(['status' => true, 'message' => "Your request processed successfully.", 'orderid' => $orderId, 'result' => $csOrderTemp]);

            } elseif (!$autoRenew && $statusResult === 'error') {
                $csOrder->status = 3;
                $csOrder->bad_debt = $payReturn['bad_debt'] ?? 0;
                $csOrder->save();

                Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0, 'status' => 10]);

                if ($csOrder->bad_debt > 0) {
                    CsUserBalance::addBadDebtRecord([
                        'user_id' => $csOrder->renter_id,
                        'amount' => $csOrder->bad_debt,
                        'note' => "Debt amount deducted for booking #" . $csOrder->increment_id . " complete cause of failed payment"
                    ]);
                }

                Notifier::updateIntercomeUserAttrbute($csOrder->renter_id, ['Booking_Status' => ""]);
                (new AxleService())->closeAxleConnection($bookingRentalChoice->id);
                $this->_getagreementForCompletedBooking($csOrderTemp->toArray());
            }

            return response()->json(['status' => false, 'message' => $payReturn['message'] ?? 'Payment error occurred', 'result' => []]);
        });
    }
    public function getVehicle(Request $request)
    {
        $searchTerm = trim($request->query('term'));
        $id = $request->query('id');
        $query = Vehicle::query();

        if (!empty($id)) {
            $query->where('id', $id);
        } else {
            $query->where('status', 1)
                ->where('trash', 0)
                ->where(function ($q) {
                    $q->where('booked', 0)
                        ->orWhere('type', 'demo');
                });

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('vehicle_unique_id', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('vehicle_name', 'LIKE', "%{$searchTerm}%");
                });
            }
        }

        $vehicles = $query->orderBy('vehicle_unique_id', 'ASC')
            ->limit(10)
            ->get(['id', 'vehicle_unique_id', 'vehicle_name', 'address', 'rate', 'lat', 'lng'])
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'tag' => "{$vehicle->vehicle_unique_id}-{$vehicle->vehicle_name}",
                    'address' => $vehicle->address,
                    'lat' => $vehicle->lat,
                    'lng' => $vehicle->lng,
                    'rate' => $vehicle->rate,
                ];
            });

        return response()->json($vehicles);
    }
    public function customerautocomplete(Request $request)
    {
        $searchTerm = trim($request->query('term'));
        $isDealer = $request->query('is_dealer');
        $dealerId = $request->query('dealer_id');
        $id = $request->query('id');

        $query = User::where('status', 1);

        if (!empty($id)) {
            $query->where('id', $id);
        } elseif (!empty($dealerId)) {
            $query->where('dealer_id', $dealerId);
        } elseif (isset($isDealer) && empty($dealerId)) {
            $query->where('is_dealer', 1);
        }

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contact_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        $users = $query->orderBy('first_name', 'ASC')
            ->limit(10)
            ->get(['id', 'first_name', 'contact_number'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'tag' => "{$user->first_name} - {$user->contact_number}",
                ];
            });

        return response()->json($users);
    }
    public function getinsurancetoken(Request $request)
    {
        $return = [
            'status' => false,
            'message' => "Invalid Booking ID",
            'result' => []
        ];

        $bookingId = $this->decodeId($request->input('orderid'));

        if (!empty($bookingId)) {
            $conditions = ['id' => $bookingId];
            $return = $this->_getInsuranceToken($conditions);
        }

        return response()->json($return);
    }
    public function overdue(Request $request)
    {
        $sessionLimitName = "admin_overdue_limit";
        $title = "Rental Overdue Orders";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessionLimitName, $limit);
        } else {
            $limit = Session::get($sessionLimitName, $this->recordsPerPage ?? 50);
        }

        $query = CsOrder::select('cs_orders.*', 'vehicles.passtime_status')
            ->selectRaw('DATEDIFF(CURDATE(), cs_orders.start_datetime) as due_days')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_orders.vehicle_id')
            ->where('cs_orders.status', 1)
            ->where(function ($q) {
                $q->where('cs_orders.end_datetime', '<', Carbon::now())
                    ->orWhere('cs_orders.payment_status', 2)
                    ->orWhere('cs_orders.insu_status', 2)
                    ->orWhere('cs_orders.dpa_status', 2)
                    ->orWhere('cs_orders.infee_status', 2)
                    ->orWhere('cs_orders.dia_insu_status', 2)
                    ->orWhere(function ($subQ) {
                        $subQ->where('cs_orders.payment_status', 0)
                            ->where('cs_orders.rent', '>', 0);
                    })
                    ->orWhere(function ($subQ) {
                        $subQ->where('cs_orders.infee_status', 0)
                            ->where('cs_orders.initial_fee', '>', 0);
                    });
            });

        $query->with([
            'orderExtlogs' => function ($relation) {
                $relation->orderBy('id', 'DESC')->limit(1);
            }
        ]);

        $bookings = $query->orderBy('due_days', 'DESC')
            ->paginate($limit);

        $request->merge(['Record' => ['limit' => $limit]]);

        if ($request->ajax()) {
            return view('admin.bookings.elements.overdue', ['title' => $title, 'tripLog' => $bookings, 'limit' => $limit]);
        }

        return view('admin.bookings.overdue', ['title' => $title, 'tripLog' => $bookings, 'limit' => $limit]);
    }
    public function retryinsurancefee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('insu_status', 2)
            ->where('insurance_amt', '>', 0)
            ->first([
                'id',
                'user_id',
                'insurance_amt',
                'renter_id',
                'cc_token_id',
                'insu_status',
                'payment_status',
                'emf_status',
                'dia_insu_status',
                'infee_status',
                'start_datetime',
                'currency',
                'parent_id'
            ]);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $originalStatus = $order->insu_status;
        $rule = OrderDepositRule::where('cs_order_id', $order->parent_id ?: $order->id)->first(['insurance_payer']);
        $order->insurance_payer = $rule ? $rule->insurance_payer : 0;

        $paidInsurance = CsOrderPayment::getTotalInsurance($order->id);
        $pendingInsurance = sprintf('%0.2f', ($order->insurance_amt - $paidInsurance));

        $return = ['status' => 'success', 'message' => 'There were no pending insurance to charge', 'result' => []];

        if ($pendingInsurance > 0) {
            $return = (new PaymentProcessor())->retryInsurance($pendingInsurance, $order->toArray());
            if (($return['status'] ?? '') === 'success') {
                $order->insu_status = 1;
            }
        } else {
            $order->insu_status = 1;
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        if ($originalStatus === 2 && $order->insu_status === 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function retrydiainsurancefee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('dia_insu_status', 2)
            ->where('dia_insu', '>', 0)
            ->first([
                'id',
                'user_id',
                'dia_insu',
                'renter_id',
                'cc_token_id',
                'start_datetime',
                'insu_status',
                'payment_status',
                'emf_status',
                'dia_insu_status',
                'infee_status',
                'currency',
                'parent_id'
            ]);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $originalStatus = $order->dia_insu_status;

        $rule = OrderDepositRule::where('cs_order_id', $order->parent_id ?: $order->id)->first(['insurance_payer']);
        $order->insurance_payer = $rule ? $rule->insurance_payer : 0;

        $paidInsurance = CsOrderPayment::getTotalDiaInsurance($order->id);
        $pendingInsurance = sprintf('%0.2f', ($order->dia_insu - $paidInsurance));

        $return = ['status' => 'success', 'message' => 'There were no pending insurance to charge', 'result' => []];

        if ($pendingInsurance > 0) {
            $return = (new PaymentProcessor())->retryDiaInsurance($pendingInsurance, $order->toArray());
            if (($return['status'] ?? '') === 'success') {
                $order->dia_insu_status = 1;
            }
        } else {
            $order->dia_insu_status = 1;
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        if ($originalStatus === 2 && $order->dia_insu_status === 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function retryinitialfee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('infee_status', 2)
            ->where('initial_fee', '>', 0)
            ->first([
                'id',
                'initial_fee',
                'initial_fee_tax',
                'cc_token_id',
                'renter_id',
                'user_id',
                'insu_status',
                'payment_status',
                'emf_status',
                'dia_insu_status',
                'infee_status',
                'start_datetime',
                'currency'
            ]);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $originalStatus = $order->infee_status;

        $paidInitial = CsOrderPayment::getTotalInitialFee($order->id);
        $pendingFee = sprintf('%0.2f', ($order->initial_fee - ($paidInitial['initial_fee'] ?? 0)));
        $pendingTax = sprintf('%0.2f', ($order->initial_fee_tax - ($paidInitial['initial_fee_tax'] ?? 0)));
        $return = ['status' => 'success', 'message' => 'There were no pendings to charge', 'result' => []];

        if ($pendingFee > 0) {
            $return = (new PaymentProcessor())->retryInitialfee($pendingFee, $order->toArray(), $pendingTax);
            if (($return['status'] ?? '') === 'success') {
                $order->infee_status = 1;
            }
        } else {
            $order->infee_status = 1;
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        if ($originalStatus === 2 && $order->infee_status === 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function retrydepositfee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('dpa_status', 2)
            ->where('deposit', '>', 0)
            ->first(['id', 'deposit', 'deposit_type', 'renter_id', 'cc_token_id', 'start_datetime', 'currency']);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $processor = new PaymentProcessor();
        $return = ['status' => 'success', 'message' => 'There were no pendings to charge', 'result' => []];

        if ($order->deposit_type === 'D') {
            $order->dpa_status = 1;
            $failedDeposits = DynamicDeposit::where('cs_order_id', $order->id)
                ->where('status', 2)
                ->get();

            foreach ($failedDeposits as $failedDeposit) {
                $chargeReturn = $processor->retryDeposit($failedDeposit->amount, $order->toArray(), 'C');

                if (($chargeReturn['status'] ?? '') === 'success') {
                    $order->deposit += $failedDeposit->amount;
                    $failedDeposit->update(['status' => 1]);
                } else {
                    $order->dpa_status = 2;
                    $return = $chargeReturn;
                }
            }
        } else {
            $paidDeposit = CsOrderPayment::getTotalDeposit($order->id);
            $balanceDeposit = sprintf('%0.2f', ($order->deposit - $paidDeposit));

            if ($balanceDeposit > 0) {
                $return = $processor->retryDeposit($balanceDeposit, $order->toArray(), $order->deposit_type);
                if (($return['status'] ?? '') === 'success') {
                    $order->dpa_status = 1;
                }
            } else {
                $order->dpa_status = 1;
            }
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        return response()->json($return);
    }
    public function retryrentalfee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('payment_status', 2)
            ->where('rent', '>', 0)
            ->first([
                'id',
                'rent',
                'tax',
                'dia_fee',
                'renter_id',
                'cc_token_id',
                'user_id',
                'lateness_fee',
                'damage_fee',
                'uncleanness_fee',
                'insu_status',
                'payment_status',
                'emf_status',
                'dia_insu_status',
                'infee_status',
                'start_datetime',
                'currency'
            ]);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $originalStatus = $order->payment_status;
        $paidData = CsOrderPayment::getTotalRentalTax($order->id);

        $pendingRent = sprintf('%0.2f', (($order->rent + $order->lateness_fee + $order->damage_fee + $order->uncleanness_fee) - ($paidData['rent'] ?? 0)));
        $pendingTax = sprintf('%0.2f', ($order->tax - ($paidData['tax'] ?? 0)));
        $pendingDiaFee = sprintf('%0.2f', ($order->dia_fee - ($paidData['dia_fee'] ?? 0)));

        $totalPending = ($order->rent + $order->tax + $order->dia_fee);
        $totalPaid = (($paidData['rent'] ?? 0) + ($paidData['tax'] ?? 0) + ($paidData['dia_fee'] ?? 0));

        $return = ['status' => 'success', 'message' => 'There were no pendings to charge', 'result' => []];

        if ($totalPending > $totalPaid && ($pendingRent > 0 || $pendingTax > 0 || $pendingDiaFee > 0)) {
            $return = (new PaymentProcessor())->retryRental($pendingRent, $pendingTax, $pendingDiaFee, $order->toArray());
            if (($return['status'] ?? '') === 'success') {
                $order->payment_status = 1;
            }
        } else {
            $order->payment_status = 1;
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        if ($originalStatus === 2 && $order->payment_status === 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function retryemf(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('emf_status', 2)
            ->where('extra_mileage_fee', '>', 0)
            ->first([
                'id',
                'emf_tax',
                'renter_id',
                'cc_token_id',
                'user_id',
                'extra_mileage_fee',
                'insu_status',
                'payment_status',
                'emf_status',
                'dia_insu_status',
                'infee_status',
                'start_datetime',
                'currency'
            ]);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $originalEmfStatus = $order->emf_status;
        $paidData = CsOrderPayment::getTotalEmf($order->id);

        $pendingRent = sprintf('%0.2f', ($order->extra_mileage_fee - ($paidData['emf'] ?? 0)));
        $pendingTax = sprintf('%0.2f', ($order->emf_tax - ($paidData['tax'] ?? 0)));
        $return = ['status' => 'success', 'message' => 'There were no pendings to charge', 'result' => []];

        if ($pendingRent > 0 || $pendingTax > 0) {
            $return = (new PaymentProcessor())->retryEmf($pendingRent, $pendingTax, $order->toArray());
            if (($return['status'] ?? '') === 'success') {
                $order->emf_status = 1;
            }
        } else {
            $order->emf_status = 1;
        }

        $order->save();
        $return['orderid'] = base64_encode($orderId);

        if ($originalEmfStatus === 2 && $order->emf_status === 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function retrytollfee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid', '')));

        if (empty($orderId)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)
            ->where('pending_toll', '>', 0)
            ->first(['id', 'toll', 'pending_toll', 'cc_token_id', 'renter_id', 'user_id', 'start_datetime', 'currency']);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Invalid inputs', 'result' => []]);
        }

        $return = (new PaymentProcessor())->retryTollfee($order->pending_toll, $order->toArray());

        if (($return['status'] ?? '') !== 'success' && !empty($return['transaction_id']) && is_array($return['transaction_id'])) {
            $partialAmount = collect($return['transaction_id'])->sum('amt');
            $order->decrement('pending_toll', $partialAmount);
            $order->increment('toll', $partialAmount);
        }

        if (($return['status'] ?? '') === 'success') {
            $pendingToll = $order->pending_toll;
            $order->update([
                'toll' => $order->toll + $pendingToll,
                'pending_toll' => $order->pending_toll - $pendingToll,
                'toll_status' => 1
            ]);

            $return['orderid'] = base64_encode($orderId);
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function edit($id)
    {
        $title = 'Update Booking';
        $curpickuptime = '12:01 AM';
        $curendtime = '11:59 PM';

        $orderId = $this->decodeId($id);

        if (!$orderId) {
            return redirect('/admin/bookings/index');
        }

        $order = CsOrder::with('vehicle:id,vehicle_name')
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return redirect('/admin/bookings/index')->with('error', 'Booking record not found.');
        }

        return view('admin.bookings.edit', compact('title', 'curpickuptime', 'curendtime', 'order'));
    }
    public function editsave(Request $request)
    {
        $textData = $request->input('Text', []);
        $startTime = date('h:i A', strtotime(str_replace('AM', '', $textData['start_time'] ?? '')));
        $endTime = date('h:i A', strtotime(str_replace('AM', '', $textData['end_time'] ?? '')));
        $vehicleId = $textData['vehicle_id'] ?? null;
        $orderId = $textData['id'] ?? null;
        $address = trim($textData['location'] ?? '');
        $latLng = explode(',', $textData['originlatlng'] ?? '');

        if (empty($vehicleId) || empty($orderId)) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $vehicle = Vehicle::find($vehicleId);
        $order = CsOrder::find($orderId);

        if (!$vehicle || !$order) {
            return response()->json(['status' => false, 'message' => 'Sorry, you are not authorized owner of selected Vehicle', 'result' => []]);
        }

        $startDateStr = $request->input('daterangefrom');
        $endDateStr = $request->input('daterangeto');
        $startDate = Carbon::parse($startDateStr)->format('Y-m-d');
        $endDate = Carbon::parse($endDateStr)->format('Y-m-d');
        $startDateTimeString = Carbon::parse($startDate . ' ' . $startTime);
        $endDateTimeString = Carbon::parse($endDate . ' ' . $endTime);
        $timezone = $order->timezone ?: 'UTC';
        $serverTimezone = config('app.timezone', 'UTC');
        $userBoundStartDate = Carbon::parse($startDateTimeString)->timezone($timezone)->format('Y-m-d');

        if (
            empty($startDateStr) ||
            empty($endDateStr) ||
            $startDateTimeString->gt($endDateTimeString) ||
            (!empty($order->start_timing) && Carbon::parse($userBoundStartDate)->timestamp > Carbon::parse($startDate)->timestamp)
        ) {
            return response()->json(['status' => false, 'message' => 'Sorry, please select correct date range', 'result' => []]);
        }

        $lat = !empty($latLng[0]) ? trim($latLng[0]) : $vehicle->lat;
        $lng = !empty($latLng[1]) ? trim($latLng[1]) : $vehicle->lng;
        $hoursNeeded = intval($startDateTimeString->diffInHours($endDateTimeString));

        if ($hoursNeeded >= 24) {
            $endDateTimeString = Carbon::parse($endDate . ' ' . $startTime);
        }

        $oldVehicleId = $order->vehicle_id;
        $order->update([
            'pickup_address' => $address,
            'lat' => $lat,
            'lng' => $lng,
            'vehicle_name' => $vehicle->vehicle_name,
            'start_datetime' => Carbon::parse($startDateTimeString, $timezone)->setTimezone($serverTimezone)->format('Y-m-d H:i:s'),
            'end_datetime' => Carbon::parse($endDateTimeString, $timezone)->setTimezone($serverTimezone)->format('Y-m-d H:i:s'),
            'vehicle_id' => $vehicleId,
            'user_id' => $vehicle->user_id,
        ]);

        if ((int) $vehicleId !== (int) $oldVehicleId) {
            Vehicle::where('id', $vehicleId)->update(['booked' => 1]);
            Vehicle::where('id', $oldVehicleId)->update(['booked' => 0]);

            (new Passtime())->startPasstime($vehicleId, $orderId);

            if (!empty($textData['updatebooking'])) {
                $bookingParentId = $order->parent_id ?: $order->id;
                OrderDepositRule::where('cs_order_id', $bookingParentId)->update(['rental' => $vehicle->day_rent]);
            }
        }

        $oldEndDate = Carbon::parse($order->getOriginal('end_datetime'))->format('Y-m-d');
        $newEndDate = Carbon::parse($endDateTimeString)->format('Y-m-d');

        if ($oldEndDate !== $newEndDate) {
            $order->refresh();
            $allFee = (new DepositRule())->getFeeRenewBooking($order->toArray(), ($order->parent_id ?: $order->id));

            $feeUpdates = [
                'rent' => $allFee['time_fee'] ?? 0,
                'tax' => $allFee['tax'] ?? 0,
                'dia_fee' => $allFee['dia_fee'] ?? 0,
                'insurance_amt' => $allFee['insurance_amt'] ?? 0
            ];

            if (($order->getOriginal('rent') ?? 0) < $feeUpdates['rent']) {
                $feeUpdates['payment_status'] = 2;
            }
            if (($order->getOriginal('insurance_amt') ?? 0) < $feeUpdates['insurance_amt']) {
                $feeUpdates['insu_status'] = 2;
            }

            $order->update($feeUpdates);
        }

        return response()->json(['status' => true, 'message' => 'Your acceptance booked successfully', 'result' => []]);
    }
    public function getagreement(Request $request)
    {
        $return = [
            'status' => false,
            'message' => "Invalid Booking ID",
            'result' => []
        ];

        $bookingId = $this->decodeId($request->input('orderid'));

        if (!empty($bookingId)) {
            $conditions = [['id', '=', $bookingId]];
            $return = $this->_getAgreement($conditions);
        }

        return response()->json($return);
    }
    public function loadvehicleexpiretime(Request $request)
    {
        $encodedBooking = $request->input('booking');
        $booking = $this->decodeId($encodedBooking);
        $vehicle = [];
        $unpaidlatefee = '0.00';

        $orderData = CsOrder::select(['id', 'vehicle_id', 'timezone', 'lateness_fee'])
            ->where('id', $booking)
            ->whereIn('status', [0, 1])
            ->first();

        if ($orderData) {
            $vehicle = Vehicle::select(['passtime_threshold', 'id'])
                ->where('id', $orderData->vehicle_id)
                ->first();

            $paidlatefee = CsOrderPayment::getTotalPaidLateFee($booking);
            $unpaidlatefee = sprintf('%0.2f', ($orderData->lateness_fee - $paidlatefee));
        }

        $orderExtlog = OrderExtlog::where('cs_order_id', $booking)
            ->latest('id')
            ->first();

        $timezone = $orderData ? $orderData->timezone : null;

        return view('admin.bookings.loadvehicleexpiretime', compact(
            'booking',
            'vehicle',
            'orderExtlog',
            'unpaidlatefee',
            'timezone'
        ));

    }
    public function processvehicleexpiretime(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, you are not authorized user for this action.',
                'result' => []
            ]);
        }

        $booking = $request->input('booking');
        $vehicleId = $request->input('vehicle_id');
        $passtimeThreshold = $request->input('passtime_threshold');
        $balAmt = (float) $request->input('amt');
        $adminCount = (int) $request->input('admin_count', 0);
        $chargeLateFee = (int) $request->input('charge_late_fee', 0);
        $note = $request->input('note');

        $order = CsOrder::where('id', $booking)
            ->whereIn('status', [0, 1])
            ->first();

        if ($order && $vehicleId) {

            if ($chargeLateFee) {
                $this->_chargeLateFee($order);
            }

            $unlocked = $this->activatePasstimeVehicle($vehicleId);

            if ($unlocked) {
                Notifier::createIntercomeUserEvent([
                    'event_name' => 'starter_enabled',
                    'created_at' => time(),
                    'external_id' => $order->renter_id,
                    'user_id' => $order->renter_id,
                    'metadata' => [
                        'id' => $order->id,
                        'booking_id' => $order->increment_id,
                        'extension_date' => $passtimeThreshold,
                        'from' => 'processvehicleexpiretime'
                    ]
                ]);
            }

            $userTimezone = $order->timezone ?? config('app.timezone');
            $serverDateTime = Carbon::createFromFormat('m/d/Y h:i A', $passtimeThreshold, $userTimezone)
                ->setTimezone(config('app.timezone'));

            Vehicle::where('id', $vehicleId)->update([
                'passtime_threshold' => $serverDateTime->timestamp
            ]);

            OrderExtlog::create([
                'cs_order_id' => $booking,
                'ext_date' => $serverDateTime->toDateTimeString(),
                'note' => $note,
                'owner' => auth()->id(),
                'created' => now(),
                'amt' => $balAmt,
                'admin_count' => $adminCount
            ]);

            $failedPaymentsList = [];

            if ($order->payment_status == 2)
                $failedPaymentsList[] = 'Rental';
            if ($order->insu_status == 2)
                $failedPaymentsList[] = 'Insurance';
            if ($order->dpa_status == 2)
                $failedPaymentsList[] = 'Deposit';
            if ($order->infee_status == 2)
                $failedPaymentsList[] = 'Initial Fee';
            if ($order->dia_insu_status == 2)
                $failedPaymentsList[] = 'EMF Insurance';
            if ($order->emf_status == 2)
                $failedPaymentsList[] = 'EMF';
            if ($order->lateness_fee_status == 2)
                $failedPaymentsList[] = 'Late Fee';

            $failedPayments = implode(', ', $failedPaymentsList);

            Notifier::createIntercomeUserEvent([
                'event_name' => 'extension_request',
                'created_at' => time(),
                'external_id' => $order->renter_id,
                'user_id' => $order->renter_id,
                'metadata' => [
                    'booking_id' => $order->increment_id,
                    'id' => $order->id,
                    'begin_date' => Carbon::parse($order->start_datetime)->setTimezone($userTimezone)->format('m/d/Y'),
                    'end_date' => Carbon::parse($order->end_datetime)->setTimezone($userTimezone)->format('m/d/Y'),
                    'failed_payments' => $failedPayments,
                    'extension_date' => $passtimeThreshold,
                    'reason' => $note,
                    'from' => 'admin_processvehicleexpiretime'
                ]
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Your request is processed successfully',
                'result' => []
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Sorry, you are not authorized user for this action.',
            'result' => []
        ]);
    }
    public function getinsurancepopup(Request $request)
    {
        $bookingId = $this->decodeId($request->input('orderid'));
        $showUpload = $request->input('showupload', false);
        $lease = null;
        $insuranceQuoteObj = null;
        $payments = [];
        $orderRuleId = '';
        $insurancePayer = '';
        $vehicleReservationId = null;

        if (!empty($bookingId)) {

            $lease = CsOrder::select('id', 'parent_id', 'vehicle_id', 'renter_id')
                ->where('id', $bookingId)
                ->first();

            if ($lease) {
                $targetOrderId = !empty($lease->parent_id) ? $lease->parent_id : $lease->id;
                $orderRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')
                    ->where('cs_order_id', $targetOrderId)
                    ->first();

                if ($orderRuleObj) {
                    $orderRuleId = $orderRuleObj->id;
                    $insurancePayer = $orderRuleObj->insurance_payer;
                    $vehicleReservationId = $orderRuleObj->vehicle_reservation_id;

                    if (in_array($insurancePayer, [5, 7]) && !empty($vehicleReservationId)) {
                        $quote = DriverFinancedInsuranceQuote::select('id')
                            ->where('order_id', $vehicleReservationId)
                            ->first();

                        if ($quote) {
                            $insuranceQuoteObj = ['InsuranceQuote' => $quote->toArray()];
                        }
                    } elseif (!empty($vehicleReservationId)) {
                        $quote = InsuranceQuote::where('order_id', $vehicleReservationId)
                            ->where('selected', 1)
                            ->first();

                        if ($quote) {
                            $insuranceQuoteObj = ['InsuranceQuote' => $quote->toArray()];
                        }
                    }
                }
            }

            $payments = CsOrderPayment::where('cs_order_id', $bookingId)
                ->where('status', 1)
                ->get();
        }

        $paymentTypeValue = $this->commonService->getPayoutTypeValue(true);

        return view('admin.bookings.getinsurancepopup', compact(
            'lease',
            'showUpload',
            'vehicleReservationId',
            'orderRuleId',
            'insurancePayer',
            'insuranceQuoteObj',
            'payments',
            'paymentTypeValue'
        ));
    }
    public function checkrapprove(Request $request)
    {
        $orderId = $this->decodeId($request->input('orderid', ''));
        $return = [
            'status' => 'error',
            'message' => 'Invalid inputs',
            'result' => []
        ];

        $orderData = CsOrder::select('id', 'user_id')
            ->where('id', $orderId)
            ->where('checkr_status', 1)
            ->first();

        if ($orderData) {
            $paymentProcessor = new PaymentProcessor();
            $return = $paymentProcessor->PaymentCaptureOnly($orderData->id, $orderData->user_id);

            if (isset($return['status']) && $return['status'] === 'success') {
                CsOrder::where('id', $orderId)->update(['checkr_status' => 0]);

                $return['orderid'] = $orderId;
                $return['message'] = "Transaction captured successfully and booking activated.";
            }
        }

        return response()->json($return);
    }
    public function checkrdisapprove(Request $request)
    {
        $orderId = $this->decodeId($request->input('orderid', ''));
        $return = [
            'status' => 'error',
            'message' => 'Invalid inputs',
            'result' => []
        ];

        $orderData = CsOrder::with('renter:id:contact_number')
            ->where('id', $orderId)
            ->where('checkr_status', 1)
            ->first();

        if (!$orderData) {
            return response()->json($return);
        }

        $paymentProcessor = new PaymentProcessor();
        $return = $paymentProcessor->ReleaseAuthorizePayment($orderData->id, $orderData->user_id);

        if (isset($return['status']) && $return['status'] === 'success') {
            $orderData->update([
                'status' => 2,
                'note' => 'Booking canceled due to suspected info',
            ]);

            $return['orderid'] = $orderId;
            $return['message'] = "Transaction refunded successfully and booking closed.";

            Vehicle::where('id', $orderData->vehicle_id)->update(['booked' => 0]);

            $contactNumber = $orderData->renter->contact_number ?? null;
            $supportPhone = config('app.support_phone', env('SUPPORT_PHONE'));
            $msg = "Sorry, your booking has been canceled due to suspected account information. Please contact to support {$supportPhone}";

            $notifier = new Notifier();
            $notifier->notifyByIntercom($contactNumber, $msg, $orderData->toArray());
        }

        return response()->json($return);
    }
    public function loadvehiclegps(Request $request)
    {
        $booking = $this->decodeId($request->input('booking', ''));
        $vehicle = null;

        $orderData = CsOrder::select('id', 'vehicle_id')
            ->where('id', $booking)
            ->first();

        if ($orderData) {
            $vehicle = Vehicle::with('csSetting:user_id,passtime,gps_provider')
                ->select(
                    'id',
                    'user_id',
                    'passtime_serialno',
                    'passtime_status',
                    'plate_number',
                    'gps_serialno',
                    'battery'
                )
                ->where('id', $orderData->vehicle_id)
                ->first();
        }

        return view('admin.bookings.loadvehiclegps', compact('booking', 'vehicle'));
    }
    public function updatevehiclegps(Request $request)
    {
        $vehicleId = $this->decodeId(trim($request->input('Text.vehicle_id')));
        $booking = $this->decodeId(trim($request->input('Text.booking')));
        $passtimeSerial = $request->input('Text.passtime_serialno');
        $gpsSerial = $request->input('Text.gps_serialno');
        $plateNumber = $request->input('Text.plate_number');
        $sync = $request->input('sync');

        $unauthorizedResponse = [
            'status' => false,
            'message' => 'Sorry, you are not authorized user for this action.',
            'result' => []
        ];

        $orderData = CsOrder::select('id', 'vehicle_id')
            ->where('id', $booking)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$orderData) {
            return response()->json($unauthorizedResponse);
        }

        if ($sync === 'true' || $sync === true) {
            return $this->_syncvehiclegps($orderData->toArray());
        }

        Vehicle::where('id', $orderData->vehicle_id)->update([
            'passtime_serialno' => $passtimeSerial,
            'plate_number' => $plateNumber,
            'gps_serialno' => $gpsSerial,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Your request is processed successfully',
            'result' => []
        ]);
    }
    public function diabletempvehicle(Request $request)
    {
        $vehicleId = $this->decodeId(trim($request->input('vehicle_id')));
        $booking = $this->decodeId(trim($request->input('booking')));
        $status = $request->input('status') === 1 ? 2 : 1;
        $unauthorizedResponse = [
            'status' => false,
            'message' => 'Sorry, you are not authorized user for this action.',
            'result' => []
        ];

        $vehicleData = Vehicle::with(['csSetting', 'vehicleSetting'])
            ->where('id', $vehicleId)
            ->first();

        if (!$vehicleData) {
            return response()->json($unauthorizedResponse);
        }

        $bookingObj = CsOrder::select('id', 'renter_id')->find($booking);
        $passtime = new Passtime();

        if ($status === 2) {
            $resp = $passtime->deActivateVehicle($vehicleData->toArray());
            if (($resp['status'] ?? '') !== 'success') {
                return response()->json([
                    'status' => false,
                    'message' => $resp['message'] ?? 'Failed to deactivate vehicle',
                    'result' => []
                ]);
            }

            $vehicleData->update(['passtime_status' => $status]);
            $starterDisableState = 'Disabled';
        }

        if ($status === 1) {
            $resp = $passtime->ActivateVehicle($vehicleData->toArray());
            if (($resp['status'] ?? '') !== 'success') {
                return response()->json([
                    'status' => false,
                    'message' => $resp['message'] ?? 'Failed to activate vehicle',
                    'result' => []
                ]);
            }

            $vehicleData->update(['passtime_status' => $status]);
            $starterDisableState = 'Enabled';
        }

        if ($bookingObj) {
            Notifier::createIntercomeUserEvent([
                'event_name' => 'starter_' . strtolower($starterDisableState),
                'created_at' => time(),
                'external_id' => $bookingObj->renter_id,
                'user_id' => $bookingObj->renter_id,
                'metadata' => [
                    'booking_id' => $booking,
                    'vehicle_id' => $vehicleId,
                    'type' => 'admin_diabletempvehicle'
                ]
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your request is processed successfully',
            'result' => []
        ]);
    }
    public function goalrecalculate($id = null)
    {
        $orderId = $this->decodeId($id);
        $adminUser = $this->getAdminUserid();
        $timezone = $adminUser['timezone'] ?? config('app.timezone');
        $depositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();

        if ($depositRule) {
            $depositRule->rent_opt = is_string($depositRule->rent_opt) ? json_decode($depositRule->rent_opt, true) : [];
            $depositRule->initial_fee_opt = is_string($depositRule->initial_fee_opt) ? json_decode($depositRule->initial_fee_opt, true) : [];
            $depositRule->deposit_opt = is_string($depositRule->deposit_opt) ? json_decode($depositRule->deposit_opt, true) : [];
            $depositRule->duration_opt = is_string($depositRule->duration_opt) ? json_decode($depositRule->duration_opt, true) : [];
            $depositRule->calculation = is_string($depositRule->calculation) ? json_decode($depositRule->calculation, true) : [];
        }

        $csOrder = CsOrder::select('id', 'vehicle_id')->find($depositRule->cs_order_id ?? null);
        $vehicles = [];

        if ($csOrder) {
            $vehicleList = Vehicle::select('id', 'msrp', 'allowed_miles')
                ->where('id', $csOrder->vehicle_id)
                ->first();

            if ($vehicleList) {
                $vehicles['id'] = $vehicleList->id;
                $startMiles = $vehicleList->allowed_miles
                    ? (int) ceil($vehicleList->allowed_miles * 30)
                    : 1000;

                $milesOptions = [];

                while ($startMiles <= 15000) {
                    $milesOptions[$startMiles] = $startMiles;
                    $startMiles += 500;
                }

                $vehicles['miles_options'] = $milesOptions;
            }
        }

        return view('admin.bookings.goalrecalculate', compact('depositRule', 'vehicles', 'timezone'));
    }
    public function getVehicleDynamicFareMatrix(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $result = $this->_bookingVehicleDynamicFareMatrix($offer);
        return response()->json($result);
    }
    public function saveGoalRecalculation(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $jsonDecoded = isset($offer['json']) ? json_decode($offer['json'], true) : [];

        if (is_array($jsonDecoded)) {
            $offer = array_merge($offer, $jsonDecoded);
        }

        if (empty($offer['id'])) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, required input data are missing.',
                'result' => []
            ]);
        }

        $dataToSave = [
            'totalcost' => $offer['totalcost'] ?? null,
            'goal' => $offer['goal'] ?? null,
            'downpayment' => $offer['downpayment'] ?? null,
            'miles' => sprintf('%0.2f', ($offer['miles'] ?? 0) / 30),
            'insurance' => $offer['insurance'] ?? null,
            'emf' => $offer['emf'] ?? null,
            'total_program_cost' => $offer['total_program_cost'] ?? null,
            'rental' => $offer['dayEmfRent'] ?? null,
            'num_of_days' => $offer['days'] ?? null,
            'tax' => $offer['tax_rate'] ?? null,
            'total_initial_fee' => $offer['total_initial_fee'] ?? null,
            'write_down_allocation' => $offer['write_down_allocation'] ?? null,
            'finance_allocation' => $offer['finance_allocation'] ?? null,
            'maintenance_allocation' => $offer['maintenance_allocation'] ?? null,
            'financing_total' => $offer['financing_total'] ?? null,
            'disposition_fee' => $offer['disposition_fee'] ?? null,
            'calculation' => $offer['json'] ?? null,
        ];

        OrderDepositRule::where('id', $offer['id'])->update($dataToSave);

        return response()->json([
            'status' => true,
            'message' => 'Data updated successfully',
            'result' => []
        ]);
    }
    public function savemanualcalculation(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $calc = $offer['calculation'] ?? [];

        if (empty($offer['id'])) {

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sorry, required input data are missing.',
                    'result' => []
                ]);
            }

            return redirect()->back()->with('error', 'Sorry, required input data are missing.');
        }

        $dataToSave = [
            'totalcost' => $calc['totalcost'] ?? null,
            'goal' => $calc['goal'] ?? null,
            'downpayment' => $calc['downpayment'] ?? null,
            'miles' => sprintf('%0.2f', $offer['miles'] ?? 0),
            'insurance' => $offer['insurance'] ?? null,
            'emf' => $offer['emf'] ?? null,
            'total_program_cost' => $calc['total_program_cost'] ?? null,
            'rental' => $calc['rental'] ?? null,
            'base_rent' => $calc['base_dayrent'] ?? null,
            'num_of_days' => $calc['num_of_days'] ?? null,
            'tax' => $calc['tax_rate'] ?? null,
            'initial_fee' => $calc['initial_fee'] ?? null,
            'total_initial_fee' => $calc['initial_fee'] ?? null,
            'write_down_allocation' => $calc['write_down_allocation'] ?? null,
            'finance_allocation' => $calc['finance_allocation'] ?? null,
            'maintenance_allocation' => $calc['maintenance_allocation'] ?? null,
            'financing_total' => $calc['financing_total'] ?? null,
            'disposition_fee' => $calc['disposition_fee'] ?? null,
            'calculation' => is_array($calc) ? json_encode($calc) : $calc,
        ];

        OrderDepositRule::where('id', $offer['id'])->update($dataToSave);
        return redirect()->back()->with('success', 'Data updated successfully');
    }
    public function loadextendtime(Request $request)
    {
        $bookingId = $this->decodeId($request->input('orderid'));
        $order = [];
        $order = CsOrder::with('twilioOrder')
            ->select('id', 'timezone', 'renter_id', 'user_id', 'end_datetime', 'start_datetime')
            ->whereIn('status', [0, 1])
            ->where('id', $bookingId)
            ->first();

        if ($order) {
            $tz = $order->timezone ?? config('app.timezone');
            $start = Carbon::parse($order->start_datetime);
            $end = Carbon::parse($order->end_datetime);
            $daysGapInSeconds = $end->diffInSeconds($start);

            $suggestAutorenewEnd = $end->copy()
                ->addSeconds($daysGapInSeconds)
                ->setTimezone($tz)
                ->format('m/d/Y h:i A');

            $extendDate = $orderData->twilioOrder->extend ?? null;
            $order->scheduled_till = $extendDate
                ? Carbon::parse($extendDate)->setTimezone($tz)->format('m/d/Y h:i A')
                : $suggestAutorenewEnd;
        }

        return view('admin.bookings.loadextendtime', compact('order'));
    }
    public function changeExtendTime(Request $request)
    {
        $booking = $request->input('Text.booking');

        if (empty($booking)) {
            return response()->json([
                'status' => false,
                'message' => 'something went wrong',
                'result' => []
            ]);
        }

        $orderData = CsOrder::find($booking);

        if (!$orderData) {
            return response()->json([
                'status' => false,
                'message' => 'You dont have permission for this action.',
                'result' => []
            ]);
        }

        $tz = $orderData->timezone ?? config('app.timezone');
        $extendInput = $request->input('Text.extend');
        $autoRenewEndDatetime = null;

        if (!empty($extendInput) && strtotime($extendInput)) {
            $autoRenewEndDatetime = Carbon::parse($extendInput, $tz)
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');
            $diffInHours = (strtotime($autoRenewEndDatetime) - strtotime($orderData->end_datetime)) / 3600;

            if ($diffInHours >= 24) {
                $targetDate = Carbon::parse($autoRenewEndDatetime)->format('Y-m-d');
                $targetTime = Carbon::parse($orderData->end_datetime)->format('H:i:s');
                $autoRenewEndDatetime = Carbon::parse("{$targetDate} {$targetTime}")->format('Y-m-d H:i:s');
            }
        }

        if ($autoRenewEndDatetime) {
            $alreadySent = CsTwilioOrder::where('cs_order_id', $orderData->id)->first();

            if (!$alreadySent) {
                $renterData = $this->commonService->getRenterDetails($orderData->renter_id);
                $twilioLog = $orderData->toArray();
                unset($twilioLog['id'], $twilioLog['created_at'], $twilioLog['updated_at'], $twilioLog['status']);
                $twilioLog['renter_phone'] = $renterData['contact_number'] ?? null;
                $twilioLog['cs_order_id'] = $orderData->id;
                $twilioLog['approved'] = 1;
                $twilioLog['extend'] = $autoRenewEndDatetime;
                $twilioLog['status'] = 0;
                CsTwilioOrder::create($twilioLog);
            } else {
                $alreadySent->update([
                    'approved' => 1,
                    'extend' => $autoRenewEndDatetime
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Your request is processed successfully',
            'result' => []
        ]);
    }
    public function partial_payment(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid')));
        $leaseOrder = $csOrder = CsOrder::with([
            'vehicle:id,engine,last_mile,modified',
            'owner:id,distance_unit',
            'twilioOrder:cs_order_id,extend'
        ])
            ->whereIn('status', [0, 1])
            ->where('id', $orderId)
            ->latest('id')
            ->first();

        if (!$csOrder) {
            abort(404, 'Order not found');
        }

        $tz = $csOrder->timezone ?? config('app.timezone');
        $startSec = strtotime($csOrder->start_datetime);
        $endSec = strtotime($csOrder->end_datetime);
        $daysGap = $endSec - $startSec;
        $suggestedSec = $endSec + $daysGap;
        $suggestedAutorenewDatetime = Carbon::createFromTimestamp($suggestedSec)
            ->setTimezone($tz)
            ->format('Y-m-d H:i:s');

        $paymentStatuses = [
            $csOrder->payment_status,
            $csOrder->dpa_status,
            $csOrder->insu_status,
            $csOrder->infee_status,
            $csOrder->toll_status,
            $csOrder->dia_insu_status,
            $csOrder->emf_status
        ];
        $leaseOrder->payment_retry = in_array(2, $paymentStatuses) ? 1 : 0;
        $leaseOrder->suggested_autorenew_datetime = $suggestedAutorenewDatetime;
        $extendDate = $csOrder->twilioOrder->extend ?? null;
        $leaseOrder->scheduled_till = $extendDate
            ? Carbon::parse($extendDate)->setTimezone($tz)->format('Y-m-d H:i:s')
            : '';

        foreach ($csOrder->getAttributes() as $key => $value) {
            if (is_null($value)) {
                $csOrder->setAttribute($key, '');
            }
        }

        $leaseOrder = $this->getActiveBookingTotalPending($leaseOrder, $csOrder);
        $leaseOrder->start_datetime = Carbon::parse($csOrder->start_datetime)->setTimezone($tz)->format('Y-m-d H:i:s');
        $leaseOrder->end_datetime = Carbon::parse($csOrder->end_datetime)->setTimezone($tz)->format('Y-m-d H:i:s');

        $booking = $leaseOrder;
        $extCount = OrderExtlog::where('cs_order_id', $orderId)->count();

        if (($endSec + (7 * 86400)) < time()) {
            $allowed_min_date = Carbon::now($tz)->addDay()->format('m/d/Y');
            $allowed_max_date = Carbon::parse($allowed_min_date, $tz)->addDays(2)->format('m/d/Y');
        } else {
            $allowed_min_date = (($endSec + 86400) > time())
                ? Carbon::now($tz)->format('m/d/Y')
                : Carbon::createFromTimestamp($endSec + 86400, $tz)->format('m/d/Y');

            $daysToAdd = ($extCount === 0) ? 7 : 14;
            $allowed_max_date = Carbon::parse($allowed_min_date, $tz)->addDays($daysToAdd)->format('m/d/Y');
        }

        return view('admin.bookings.partial_payment', compact('allowed_min_date', 'allowed_max_date', 'booking'));
    }
    public function process_partial_payment(Request $request)
    {
        $return = ["status" => false, "message" => "Something went wrong"];

        if (!$request->is('ajax')) {
            return response()->json($return);
        }

        $data = $request->all();

        if ($request->input('payment') === 'advance') {
            return response()->json($this->processAdvancepayment($data));
        }

        $booking = $data['Booking'] ?? [];
        $famt = (float) preg_replace("/[^0-9.]/", "", $booking['famt'] ?? 0);
        $pamt = (float) preg_replace("/[^0-9.]/", "", $booking['pamt'] ?? 0);
        $bookingId = $booking['id'] ?? null;

        if (empty($data) || ($famt === 0.0 && $pamt === 0.0) || empty($bookingId)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request body',
                'result' => []
            ]);
        }

        $orderData = CsOrder::where('id', $bookingId)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$orderData) {
            return response()->json([
                'status' => false,
                'message' => 'You dont have permission for this request',
                'result' => []
            ]);
        }

        if ($orderData->payment_status == 0 && $orderData->rent > 0) {
            $orderData->payment_status = 2;
        }

        if ($orderData->insu_status == 0 && $orderData->insurance_amt > 0) {
            $orderData->insu_status = 2;
        }

        if ($orderData->infee_status == 0 && $orderData->initial_fee > 0) {
            $orderData->infee_status = 2;
        }

        if ($orderData->toll_status == 0 && $orderData->pending_toll > 0) {
            $orderData->toll_status = 2;
        }

        if ($orderData->dia_insu_status == 0 && $orderData->dia_insu > 0) {
            $orderData->dia_insu_status = 2;
        }

        if ($orderData->emf_status == 0 && $orderData->extra_mileage_fee > 0) {
            $orderData->emf_status = 2;
        }

        if ($request->input('payment') === 'fullpay') {
            return response()->json($this->processfullpayment($orderData));
        }

        $paymentProcessor = new PaymentProcessor();
        $res = $paymentProcessor->chargeAmtToUser(
            $pamt,
            $orderData->renter_id,
            'DIA Partial Pay',
            $orderData->currency
        );

        if (($res['status'] ?? '') !== 'success') {
            return response()->json([
                'status' => false,
                'message' => $res['message'] ?? 'Payment charging failed',
                'result' => []
            ]);
        }

        $balance = sprintf('%0.2f', $famt - $pamt);
        $note = !empty($pamt)
            ? "I agree to  pay balance {$balance} on " . $booking['date'] . ". Current Paid amount={$pamt}"
            : "";

        CsPaymentLog::savePartialPaymentLog([
            'orderid' => $orderData->id,
            'amount' => $res['amt'],
            'transaction_id' => $res['transaction_id'],
            'note' => $note
        ], 29);

        CsWallet::addBalance(
            $res['amt'],
            $orderData->renter_id,
            $res['transaction_id'],
            'Advance Payment',
            $orderData->id,
            now()
        );

        if (!empty($note)) {
            $extDate = Carbon::parse($booking['date'], $orderData->timezone)
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString();

            OrderExtlog::create([
                'cs_order_id' => $orderData->id,
                'ext_date' => $extDate,
                'note' => $note,
                'owner' => $orderData->renter_id,
                'amt' => $balance,
                'created' => now()
            ]);
        }

        $msg = "Partial Payment $" . $res['amt'] . " was made successfully, and you agreed to pay remaing amount $" . $balance . ", shortly of your DriveItAway order ";
        (new EmailQueueService())->saveEmailToQueue(null, $res['amt'], $msg, $orderData->id, 'card');

        $this->retryPendingPaymentFromWallet($orderData);

        $nextLockDate = Carbon::parse($booking['date'] . ' 15:00:00', $orderData->timezone)
            ->setTimezone(config('app.timezone'))
            ->timestamp;

        $failedPayments = '';

        if ($orderData->payment_status == 2) {
            $failedPayments .= 'Rental, ';
        }

        if ($orderData->insu_status == 2) {
            $failedPayments .= ' Insurance,';
        }

        if ($orderData->dpa_status == 2) {
            $failedPayments .= ' Deposit,';
        }

        if ($orderData->infee_status == 2) {
            $failedPayments .= ' Initial Fee,';
        }

        if ($orderData->dia_insu_status == 2) {
            $failedPayments .= ' EMF Insurance,';
        }

        if ($orderData->emf_status == 2) {
            $failedPayments .= ' EMF';
        }

        if ($orderData->lateness_fee_status == 2) {
            $failedPayments .= ' Late Fee';
        }

        Notifier::createIntercomeUserEvent([
            'event_name' => 'partial_payment',
            'created_at' => time(),
            'external_id' => $orderData->renter_id,
            'user_id' => $orderData->renter_id,
            'metadata' => [
                'id' => $orderData->id,
                'booking_id' => $orderData->increment_id,
                'begin_date' => Carbon::parse($orderData->start_datetime)->setTimezone($orderData->timezone)->format('m/d/Y'),
                'end_date' => Carbon::parse($orderData->end_datetime)->setTimezone($orderData->timezone)->format('m/d/Y'),
                'failed_payments' => $failedPayments,
                'extension_date' => $booking['date'],
                'reason' => $note,
                'Amount_paid' => $res['amt'],
                'from' => 'admin_makeAdvancePayment'
            ]
        ]);

        $unlocked = $this->ActivatePasstimeVehicle($orderData->vehicle_id);

        if ($unlocked) {
            Notifier::createIntercomeUserEvent([
                'event_name' => 'starter_enabled',
                'created_at' => time(),
                'external_id' => $orderData->renter_id,
                'user_id' => $orderData->renter_id,
                'metadata' => [
                    'id' => $orderData->id,
                    'booking_id' => $orderData->increment_id,
                    'extension_date' => $booking['date'],
                    'from' => 'admin_makeAdvancePayment'
                ]
            ]);
        }

        Vehicle::where('id', $orderData->vehicle_id)
            ->update(['passtime_threshold' => $nextLockDate]);

        return response()->json([
            'status' => true,
            'message' => 'Your request processed successfully',
            'result' => []
        ]);
    }
    public function geotabkeylesslock(Request $request)
    {
        $vehicleId = $this->decodeId($request->input('Text.vehicle_id', ''));
        return response()->json($this->_geotabkeylesslock($vehicleId));
    }
    public function geotabkeylessunlock(Request $request)
    {
        $vehicleId = $this->decodeId($request->input('Text.vehicle_id', ''));
        return response()->json($this->_geotabkeylessunlock($vehicleId));
    }
    public function getDeclarationDoc(Request $request)
    {
        $return = [
            'status' => false,
            'message' => "Invalid Booking ID",
            'result' => []
        ];

        $bookingId = $this->decodeId($request->input('orderid'));

        if (!empty($bookingId)) {
            $conditions = ['id' => $bookingId];
            $return = $this->_getDeclarationDoc($conditions);
        }

        return response()->json($return);
    }
    public function overdue_booking_details(Request $request)
    {
        return $this->_overdue_booking_details($request);
    }
    public function updateodometer(Request $request)
    {
        $bookingId = $this->decodeId($request->input('booking'));
        $orderData = CsOrder::select(['id', 'vehicle_id'])->find($bookingId);

        if (!$orderData) {
            return redirect()->back()->with('error', 'Sorry, booking not found');
        }

        return view('bookings._loadvehicleodometer', [
            'booking' => $bookingId,
            'orderData' => $orderData,
        ]);
    }
    public function saveBookingOdometer(Request $request)
    {
        return $this->_saveBookingOdometer($request);
    }
    public function pullVehicleOdometer(Request $request)
    {
        return $this->_pullVehicleOdometer($request);
    }
    public function retrylatefee(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid')));
        $return = [
            'status' => 'error',
            'message' => 'Invalid inputs',
            'result' => []
        ];

        if (!$orderId) {
            return response()->json($return);
        }

        $order = CsOrder::select([
            'id',
            'lateness_fee',
            'renter_id',
            'cc_token_id',
            'user_id',
            'lateness_fee_status',
            'insu_status',
            'payment_status',
            'emf_status',
            'dia_insu_status',
            'infee_status',
            'start_datetime',
            'currency'
        ])
            ->where('id', $orderId)
            ->where('lateness_fee_status', 2)
            ->first();

        if (!$order) {
            return response()->json($return);
        }

        $previousLateFeeStatus = $order->lateness_fee_status;

        $paymentProcessor = new PaymentProcessor();
        $totalPaidLateFee = CsOrderPayment::getTotalPaidLateFee($order->id);
        $pendingLateFee = $order->lateness_fee - $totalPaidLateFee;

        if ($pendingLateFee > 0) {
            $return = $paymentProcessor->retryLatefee($pendingLateFee, $order->toArray());
            $order->lateness_fee_status = ($return['status'] ?? '') === 'success' ? 1 : 2;
        } else {
            $order->lateness_fee_status = 1;
            $return['status'] = 'success';
        }

        $order->save();
        $return['orderid'] = $orderId;

        if ($previousLateFeeStatus == 2 && $order->lateness_fee_status == 1) {
            Notifier::updateUserStatusFromRetryPayment($order->toArray());
            UnlockVehicle::unlock($order->id);
        }

        return response()->json($return);
    }
    public function autocomplete(Request $request)
    {
        return $this->_autocomplete($request);
    }
    public function getVehicleCCMCard(Request $request)
    {
        $return = [
            "status" => false,
            "message" => "Something went wrong"
        ];

        if ($request->isMethod('post') && $request->has('order')) {
            $return = $this->_generateCMMCard('', $request->input('order'));
        }

        return response()->json($return);
    }
    public function sendAxleShareDetails(Request $request)
    {
        $return = [
            'status' => false,
            'message' => 'Sorry, something missing'
        ];

        if (!$request->isMethod('post')) {
            return response()->json($return);
        }

        $orderId = $this->decodeId($request->input('orderid'));

        if (empty($orderId)) {
            return response()->json($return);
        }

        $orderDepositRule = OrderDepositRule::select(['vehicle_reservation_id'])
            ->with(['reservation:id,renter_id'])
            ->where('cs_order_id', $orderId)
            ->first();

        if (!$orderDepositRule || !$orderDepositRule->reservation) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, booking not found'
            ]);
        }

        $reservation = $orderDepositRule->reservation;
        $payload = base64_encode("{$reservation->id}|{$reservation->renter_id}");
        $fullUrl = url("/insurance/roi/diafinancedsaveinsuranceaccount/{$payload}");
        $shortUrl = $this->commonService->makeShortUrl($fullUrl);
        $msg = "DriveItAway needs to reconnect to your insurance provider to verify insurance coverage. Please use the following link so we can make the connection. {$shortUrl}";

        (new Notifier())->notifyByIntercom($reservation->renter_id, $msg);

        return response()->json([
            'status' => true,
            'message' => 'Link is sent successfully'
        ]);
    }
    public function sendDirectAxleLink(Request $request)
    {
        if (!$request->isMethod('post') || !$request->filled('orderid')) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, something missing'
            ]);
        }

        $orderId = $this->decodeId($request->input('orderid'));

        $bookingData = OrderDepositRule::select('id', 'vehicle_reservation_id', 'cs_order_id')
            ->with('reservation:id,renter_id')
            ->where('cs_order_id', $orderId)
            ->first();

        if (!$bookingData || !$bookingData->renter_id) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, booking not found'
            ]);
        }

        $encodedPayload = base64_encode($bookingData->id . '|' . $bookingData->renter_id);
        $axleUrl = URL::to('/axle/axledocs/connect/' . $encodedPayload);
        $shortUrl = $this->commonService->makeshorturl($axleUrl);

        $msg = "DriveItAway needs to reconnect to your insurance provider to verify insurance coverage. Please use the following link so we can make the connection. " . $shortUrl;

        (new Notifier())->notifyByIntercom($bookingData->renter_id, $msg);

        return response()->json([
            'status' => true,
            'message' => 'Link is sent successfully'
        ]);
    }
    public function insurancepopup(Request $request)
    {
        $bookingId = $this->decodeId($request->input('orderid'));
        $trip = null;
        $csOrderId = null;

        if (!empty($bookingId)) {
            $trip = CsOrder::select('id', 'parent_id', 'vehicle_id', 'renter_id')
                ->find($bookingId);

            if ($trip) {
                $csOrderId = $trip->parent_id ?: $trip->id;
                $orderRuleObj = OrderDepositRule::select('id', 'vehicle_reservation_id')
                    ->where('cs_order_id', $csOrderId)
                    ->first();

                $trip->order_deposit_rule = $orderRuleObj;
            }
        }

        return view('bookings._insurance_action_popup', compact('bookingId', 'trip', 'csOrderId'));
    }
}
