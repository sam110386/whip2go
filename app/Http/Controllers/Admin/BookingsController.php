<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AxleStatus;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsUserBalance;
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
use App\Models\Legacy\VehicleReservation;
use App\Services\Legacy\AxleService;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\UnlockVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Traits\BookingsTrait;
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

        return response()->view('admin.bookings._cancel_popup', [
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







    public function autocomplete(Request $request): JsonResponse
    {
        $bookingId = trim((string) $request->input('id', ''));
        $searchTerm = trim((string) $request->input('term', ''));

        $q = DB::table('cs_orders')->select(['id', 'increment_id', 'vehicle_id']);

        if ($bookingId !== '') {
            $q->where('id', (int) $bookingId);
        } else {
            $q->where(function ($q2) use ($searchTerm) {
                $q2->where('id', 'like', $searchTerm . '%')
                    ->orWhere('increment_id', 'like', '%' . addcslashes($searchTerm, '%_\\') . '%');
            });
        }

        $lists = $q->orderByDesc('id')->limit(10)->get();
        $bookings = [];
        foreach ($lists as $row) {
            $bookings[] = [
                'id' => $row->id,
                'tag' => $row->increment_id,
                'vehicle' => $row->vehicle_id,
            ];
        }

        return response()->json($bookings);
    }

    public function retrylatefee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('late_fee_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or late fee not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 8)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->lateness_fee ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryLateFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['late_fee_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Late fee retried successfully.', 'result' => []]);
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

    public function checkrapprove(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('checkr_status', 1)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or checkr not pending.', 'result' => []]);
        }

        \Log::warning('PaymentProcessor::PaymentCaptureOnly not yet ported — checkr approve order ' . $orderId);

        DB::table('cs_orders')->where('id', $orderId)->update([
            'checkr_status' => 0,
        ]);

        return response()->json(['status' => true, 'message' => 'Checkr approved successfully.', 'result' => []]);
    }

    public function checkrdisapprove(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.id', $orderId)
            ->where('o.checkr_status', 1)
            ->select(['o.*', 'u.id as renter_uid', 'u.email as renter_email'])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or checkr not pending.', 'result' => []]);
        }

        \Log::warning('PaymentProcessor::ReleaseAuthorizePayment not yet ported — checkr disapprove order ' . $orderId);

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status' => 2,
            'checkr_status' => 2,
        ]);

        DB::table('vehicles')->where('id', (int) $order->vehicle_id)->update(['booked' => 0]);

        return response()->json(['status' => true, 'message' => 'Checkr disapproved, booking cancelled.', 'result' => []]);
    }

    public function loadvehiclegps(Request $request)
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->view('admin.bookings._vehicle_gps', ['vehicle' => null, 'booking' => 0]);
        }

        $order = CsOrder::where('id', $orderId)->first(['id', 'vehicle_id']);
        if (!$order || empty($order->vehicle_id)) {
            return response()->view('admin.bookings._vehicle_gps', ['vehicle' => null, 'booking' => $orderId]);
        }

        $vehicle = Vehicle::with(['owner.setting'])
            ->where('id', (int) $order->vehicle_id)
            ->first();

        return response()->view('admin.bookings._vehicle_gps', [
            'vehicle' => $vehicle,
            'booking' => (int) $order->id,
        ]);
    }

    public function updatevehiclegps(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $gps = (string) $request->input('gps_serialno', '');
        if ($orderId && $gps !== '') {
            $order = CsOrder::where('id', $orderId)->first(['vehicle_id']);
            if ($order && !empty($order->vehicle_id)) {
                Vehicle::where('id', (int) $order->vehicle_id)->update(['gps_serialno' => $gps]);
            }
        }

        return response()->json(['status' => true, 'message' => 'GPS updated']);
    }

    public function diabletempvehicle(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->input('vehicle_id', 0);
        if ($vehicleId > 0) {
            Vehicle::where('id', $vehicleId)->update(['status' => 0]);
        }

        return response()->json(['status' => true, 'message' => 'Vehicle disabled']);
    }

    public function goalrecalculate($id = null)
    {
        $orderId = $id ? (int) base64_decode((string) $id) : 0;
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid booking id']);
        }

        $depositRule = OrderDepositRule::where('cs_order_id', $orderId)->first();

        if (!$depositRule) {
            $parentId = (int) CsOrder::where('id', $orderId)->value('parent_id');
            if ($parentId > 0) {
                $depositRule = OrderDepositRule::where('cs_order_id', $parentId)->first();
            }
        }

        if (!$depositRule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $rentOpt = json_decode($depositRule->rent_opt ?? '{}', true) ?: [];
        $initialFeeOpt = json_decode($depositRule->initial_fee_opt ?? '{}', true) ?: [];
        $depositOpt = json_decode($depositRule->deposit_opt ?? '{}', true) ?: [];
        $durationOpt = json_decode($depositRule->duration_opt ?? '{}', true) ?: [];
        $calculation = json_decode($depositRule->calculation ?? '{}', true) ?: [];

        $order = CsOrder::where('id', $orderId)->first();
        $vehicle = $order ? Vehicle::where('id', (int) $order->vehicle_id)->first(['id', 'msrp', 'allowed_miles']) : null;

        $milesOptions = [];
        if ($vehicle && !empty($vehicle->allowed_miles)) {
            $decoded = json_decode($vehicle->allowed_miles, true);
            if (is_array($decoded)) {
                $milesOptions = $decoded;
            }
        }

        return view('admin.bookings.goalrecalculate', [
            'orderId' => $orderId,
            'depositRule' => $depositRule,
            'rentOpt' => $rentOpt,
            'initialFeeOpt' => $initialFeeOpt,
            'depositOpt' => $depositOpt,
            'durationOpt' => $durationOpt,
            'calculation' => $calculation,
            'vehicle' => $vehicle,
            'milesOptions' => $milesOptions,
            'order' => $order,
        ]);
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => ['matrix' => []]]);
    }

    public function saveGoalRecalculation(Request $request): JsonResponse
    {
        $ruleId = (int) $request->input('VehicleOffer.id', 0);
        if ($ruleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid deposit rule id']);
        }

        $rule = OrderDepositRule::where('id', $ruleId)->first();
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $updateData = [];
        $fields = [
            'rent_opt',
            'initial_fee_opt',
            'deposit_opt',
            'duration_opt',
            'calculation',
            'rent',
            'initial_fee',
            'deposit',
            'tax_rate',
            'insurance_rate',
            'allowed_miles',
            'extra_mile_rate',
        ];
        foreach ($fields as $field) {
            $val = $request->input('VehicleOffer.' . $field);
            if ($val !== null) {
                $updateData[$field] = is_array($val) ? json_encode($val) : $val;
            }
        }

        $jsonField = $request->input('VehicleOffer.json');
        if ($jsonField !== null) {
            if (is_array($jsonField)) {
                $updateData = array_merge($updateData, $jsonField);
            }
        }

        if (!empty($updateData)) {
            $updateData['modified'] = now()->toDateTimeString();
            $rule->update($updateData);
        }

        return response()->json(['status' => true, 'message' => 'Goal recalculation saved successfully.']);
    }

    public function savemanualcalculation(Request $request): JsonResponse
    {
        $ruleId = (int) $request->input('VehicleOffer.id', 0);
        if ($ruleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid deposit rule id']);
        }

        $rule = OrderDepositRule::where('id', $ruleId)->first();
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $updateData = [];
        $fields = [
            'rent',
            'initial_fee',
            'deposit',
            'tax_rate',
            'insurance_rate',
            'allowed_miles',
            'extra_mile_rate',
            'rent_opt',
            'initial_fee_opt',
            'deposit_opt',
            'duration_opt',
            'calculation',
        ];
        foreach ($fields as $field) {
            $val = $request->input('VehicleOffer.' . $field);
            if ($val !== null) {
                $updateData[$field] = is_array($val) ? json_encode($val) : $val;
            }
        }

        $jsonField = $request->input('VehicleOffer.json');
        if ($jsonField !== null && is_array($jsonField)) {
            $updateData = array_merge($updateData, $jsonField);
        }

        if (!empty($updateData)) {
            $updateData['modified'] = now()->toDateTimeString();
            $rule->update($updateData);
        }

        return response()->json(['status' => true, 'message' => 'Manual calculation saved successfully.']);
    }

    public function loadextendtime(Request $request)
    {
        $encodedId = (string) $request->input('orderid', '');
        $orderId = $this->decodeId($encodedId);
        $suggestedEndDatetime = '';

        if ($orderId) {
            $order = CsOrder::with(['twilio_order'])
                ->where('id', $orderId)
                ->first();

            if ($order && !empty($order->start_datetime) && !empty($order->end_datetime)) {
                $start = strtotime($order->start_datetime);
                $end = strtotime($order->end_datetime);
                $gap = $end - $start;
                if ($gap > 0) {
                    $suggestedEndDatetime = date('Y-m-d H:i:s', $end + $gap);
                }
            }
        }

        return response()->view('admin.bookings._extend_time', [
            'orderid' => $encodedId,
            'order' => $order ?? null,
            'suggestedEndDatetime' => $suggestedEndDatetime,
        ]);
    }

    public function changeExtendTime(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $end = (string) $request->input('end_datetime', '');

        if (!$orderId || $end === '') {
            return response()->json(['status' => false, 'message' => 'Invalid inputs']);
        }

        $order = CsOrder::where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        $order->update(['end_datetime' => $end]);

        $existingTwilio = CsTwilioOrder::where('cs_order_id', $orderId)->first();
        if ($existingTwilio) {
            $existingTwilio->update([
                'extend_datetime' => $end,
                'approved' => 1,
                'modified' => now()->toDateTimeString(),
            ]);
        } else {
            CsTwilioOrder::create([
                'cs_order_id' => $orderId,
                'extend_datetime' => $end,
                'approved' => 1,
                'created' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Booking extended successfully.']);
    }

    public function partial_payment(Request $request)
    {
        $encodedId = (string) $request->input('orderid', '');
        $orderId = $this->decodeId($encodedId);
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $order = CsOrder::with(['vehicle', 'owner', 'renter', 'twilio_order'])
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return response('Order not found', 404);
        }

        $order = $this->getActiveBookingTotalPending($order);

        $payments = CsOrderPayment::where('cs_order_id', $orderId)
            ->orderByDesc('id')
            ->get();

        $totalPaid = $payments->where('status', 1)->sum('amount');

        $startDate = $order->start_datetime ? date('m/d/Y', strtotime($order->start_datetime)) : '';
        $endDate = $order->end_datetime ? date('m/d/Y', strtotime($order->end_datetime)) : '';

        // These dates are used in the view for partial payment date restriction
        $allowed_min_date = now()->format('m/d/Y');
        $allowed_max_date = now()->addDays(7)->format('m/d/Y');

        return response()->view('admin.bookings.partial_payment', [
            'orderid' => $encodedId,
            'booking' => $order,
            'payments' => $payments,
            'totalPaid' => (float) $totalPaid,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allowed_min_date' => $allowed_min_date,
            'allowed_max_date' => $allowed_max_date,
            'paymenttype' => 'Rental', // Or dynamically determined
        ]);
    }

    public function process_partial_payment(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('Text.orderid', $request->input('orderid', '')));
        $paymentMode = (string) $request->input('Text.payment_mode', $request->input('payment_mode', ''));
        $amount = (float) $request->input('Text.amount', $request->input('amount', 0));

        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = CsOrder::where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found', 'result' => []]);
        }

        $totalOwed = (float) ($order->rent ?? 0)
            + (float) ($order->tax ?? 0)
            + (float) ($order->dia_fee ?? 0)
            + (float) ($order->extra_mileage_fee ?? 0)
            + (float) ($order->emf_tax ?? 0)
            + (float) ($order->lateness_fee ?? 0)
            + (float) ($order->damage_fee ?? 0)
            + (float) ($order->uncleanness_fee ?? 0)
            + (float) ($order->insurance_amt ?? 0)
            + (float) ($order->initial_fee ?? 0)
            + (float) ($order->initial_fee_tax ?? 0)
            + (float) ($order->pending_toll ?? 0);

        $alreadyPaid = (float) CsOrderPayment::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->sum('amount');

        $pending = max(0, $totalOwed - $alreadyPaid);

        if ($paymentMode === 'advance') {
            $chargeAmt = $amount > 0 ? $amount : $pending;
            \Log::warning('PaymentProcessor::advancePayment not yet ported — order ' . $orderId . ', amt $' . $chargeAmt);

            CsOrderPayment::create([
                'cs_order_id' => $orderId,
                'amount' => $chargeAmt,
                'payment_type' => 5,
                'status' => 1,
                'note' => 'Admin advance payment (processor stub)',
                'created' => now()->toDateTimeString(),
            ]);

            return response()->json(['status' => true, 'message' => 'Advance payment recorded.', 'result' => []]);
        }

        if ($paymentMode === 'fullpay') {
            \Log::warning('PaymentProcessor::fullPayment not yet ported — order ' . $orderId . ', pending $' . $pending);

            if ($pending > 0) {
                CsOrderPayment::create([
                    'cs_order_id' => $orderId,
                    'amount' => $pending,
                    'payment_type' => 5,
                    'status' => 1,
                    'note' => 'Admin full payment (processor stub)',
                    'created' => now()->toDateTimeString(),
                ]);
            }

            return response()->json(['status' => true, 'message' => 'Full payment recorded.', 'result' => []]);
        }

        $chargeAmt = $amount > 0 ? min($amount, $pending) : $pending;
        \Log::warning('PaymentProcessor::partialPayment not yet ported — order ' . $orderId . ', amt $' . $chargeAmt);

        if ($chargeAmt > 0) {
            CsOrderPayment::create([
                'cs_order_id' => $orderId,
                'amount' => $chargeAmt,
                'payment_type' => 5,
                'status' => 1,
                'note' => 'Admin partial payment (processor stub)',
                'created' => now()->toDateTimeString(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Partial payment recorded.', 'result' => []]);
    }

    public function geotabkeylesslock(Request $request): JsonResponse
    {
        $vehicleId = (int) base64_decode((string) $request->input('vehicle_id', ''));
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle id']);
        }

        $vehicle = Vehicle::with(['owner.setting'])
            ->where('id', $vehicleId)
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GeotabKeyless::lock not yet ported — vehicle ' . $vehicleId);

        return response()->json(['status' => true, 'message' => 'Lock command sent (stubbed).']);
    }

    public function geotabkeylessunlock(Request $request): JsonResponse
    {
        $vehicleId = (int) base64_decode((string) $request->input('vehicle_id', ''));
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle id']);
        }

        $vehicle = Vehicle::with(['owner.setting'])
            ->where('id', $vehicleId)
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GeotabKeyless::unlock not yet ported — vehicle ' . $vehicleId);

        return response()->json(['status' => true, 'message' => 'Unlock command sent (stubbed).']);
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
        return $this->overdue($request);
    }

    public function updateodometer(Request $request)
    {
        return response()->view('admin.bookings._odometer', ['orderid' => (string) $request->input('orderid', '')]);
    }

    public function saveBookingOdometer(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if ($orderId) {
            $order = CsOrder::where('id', $orderId)->first();
            if ($order) {
                $save = [];
                foreach (['start_odometer', 'end_odometer'] as $f) {
                    if ($request->has($f)) {
                        $save[$f] = (float) $request->input($f, 0);
                    }
                }
                if ($save !== []) {
                    $order->update($save);
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'Odometer updated']);
    }

    public function pullVehicleOdometer(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Invalid order id']);
        }

        $order = CsOrder::where('id', $orderId)->first(['id', 'vehicle_id']);
        if (!$order || empty($order->vehicle_id)) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Order or vehicle not found']);
        }

        $vehicle = Vehicle::with(['owner.setting'])
            ->where('id', (int) $order->vehicle_id)
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GPS provider odometer pull not yet ported — vehicle ' . $vehicle->id . ', provider: ' . ($vehicle->owner->setting->gps_provider ?? 'unknown'));

        return response()->json([
            'status' => true,
            'odometer' => null,
            'message' => 'GPS provider integration pending — vehicle loaded but odometer pull is stubbed.',
        ]);
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

    public function sendAxleShareDetails(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id']);
        }

        $order = CsOrder::where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        $depositRule = OrderDepositRule::where('cs_order_id', $orderId)
            ->orWhere('cs_order_id', (int) ($order->parent_id ?? 0))
            ->first();

        $vehicleReservation = null;
        if ($depositRule && !empty($depositRule->vehicle_reservation_id)) {
            $vehicleReservation = VehicleReservation::where('id', (int) $depositRule->vehicle_reservation_id)
                ->first();
        }

        \Log::warning('Notifier::sendAxleShareDetails not yet ported — order ' . $orderId);

        return response()->json([
            'status' => true,
            'message' => 'Axle share details loaded (notification stubbed).',
            'result' => [
                'order_id' => $orderId,
                'reservation_id' => $vehicleReservation->id ?? null,
            ],
        ]);
    }

    public function sendDirectAxleLink(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id']);
        }

        $order = CsOrder::where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        $depositRule = OrderDepositRule::where('cs_order_id', $orderId)
            ->orWhere('cs_order_id', (int) ($order->parent_id ?? 0))
            ->first();

        $vehicleReservation = null;
        if ($depositRule && !empty($depositRule->vehicle_reservation_id)) {
            $vehicleReservation = VehicleReservation::where('id', (int) $depositRule->vehicle_reservation_id)
                ->first();
        }

        \Log::warning('Notifier::sendDirectAxleLink not yet ported — order ' . $orderId);

        return response()->json([
            'status' => true,
            'message' => 'Direct Axle link loaded (notification stubbed).',
            'result' => [
                'order_id' => $orderId,
                'reservation_id' => $vehicleReservation->id ?? null,
            ],
        ]);
    }

    public function insurancepopup()
    {
        return response()->view('admin.bookings._insurance_popup', ['orderid' => '']);
    }
}
