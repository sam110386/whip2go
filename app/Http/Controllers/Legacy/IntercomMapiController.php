<?php
namespace App\Http\Controllers\Legacy;

use App\Helpers\Legacy\Number;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\ActiveBookingTotalPending;
use App\Http\Controllers\Traits\MobileApi;
use App\Http\Controllers\Traits\PasstimeActivateVehicle;
use App\Http\Controllers\Traits\ValidateExtensionRequestTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsPaymentLog;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Services\Legacy\EmailQueueService;
use App\Services\Legacy\Notifier;
use App\Services\Legacy\PaymentProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Mirrors legacy Intercom/MservicesController + MapiController flow.
 */
class IntercomMapiController extends LegacyAppController
{
    use ActiveBookingTotalPending, ValidateExtensionRequestTrait, PasstimeActivateVehicle, MobileApi;

    protected bool $shouldLoadLegacyModules = false;
    private string $_security;
    protected static int $_STATUSFAIL = 0;
    protected static int $_STATUSSUCCESS = 1;
    protected $userObj;
    protected array $_userfields = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'photo',
        'contact_number',
        'address',
        'ss_no',
        'dob',
        'city',
        'state',
        'zip',
        'licence_type',
        'licence_number',
        'licence_state',
        'licence_exp_date',
        'is_renter',
        'is_owner',
        'is_driver',
        'is_passenger',
        'license_doc_1',
        'license_doc_2',
        'is_staff',
        'staff_parent',
        'checkr_status',
        'auto_renew',
        'uberlyft_verified',
        'bank',
        'currency',
        'address_doc',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->_security = (string) config('legacy.Intercom.security', '');
    }
    private function authenticateMapiUser(Request $request)
    {
        $xsecurity = $request->header('X-Security', $request->header('x-security', ''));

        if (empty($xsecurity) || strtolower($xsecurity) !== strtolower($this->_security)) {
            return response()->json([
                'status' => self::$_STATUSFAIL,
                'message' => 'Sorry, seems you are spam bot'
            ], 402);
        }

        $userid = $request->input('userid') ?? $request->route('userid');
        try {
            $user = User::where('id', $userid)
                ->where('status', 1)
                ->where('is_verified', 1)
                ->where('is_admin', 0)
                ->select($this->_userfields)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => self::$_STATUSFAIL,
                    'message' => 'Sorry, seems you are also logged in on another device/browser. Please login back'
                ], 402);
            }

            $this->userObj = $user->toArray();
            return null;
        } catch (\Exception $e) {
            return response()->json([
                'status' => self::$_STATUSFAIL,
                'message' => 'Please log back in'
            ], 400);
        }
    }
    public function getMyTransactions(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $userId = $this->userObj['id'];
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 50);
        $offset = ($page - 1) * $limit;

        $payments = CsOrderPayment::where('status', 1)
            ->whereHas('csOrder', function ($query) use ($userId) {
                $query->where('renter_id', $userId);
            })
            ->with(['csOrder:id,increment_id,currency,timezone,start_datetime,end_datetime'])
            ->orderByDesc('created')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($payments->isEmpty()) {
            return response()->json(['error' => __('sorry, no record found.')]);
        }

        $typeTitles = [
            1 => 'Deposit Fee',
            2 => 'Usage Fee',
            3 => 'Initial Fee',
            4 => 'Insurance & Fees',
            5 => 'Cancelation Fee',
            6 => 'Misc/Violations',
            7 => 'Custom Balance Fee',
            14 => 'Ins Extra Usage',
            16 => 'Extra Usage Fee',
            19 => 'Lateness Fee',
        ];

        $result = $payments->map(function ($payment) use ($typeTitles) {
            $order = $payment->csOrder;
            $timezone = $order->timezone ?? config('app.timezone');
            $chargedAt = $payment->charged_at
                ? Carbon::parse($payment->charged_at)->timezone($timezone)->format('m-d-Y')
                : '';
            $startDatetime = $order?->start_datetime
                ? Carbon::parse($order->start_datetime)->timezone($timezone)->format('Y-m-d')
                : '';
            $endDatetime = $order?->end_datetime
                ? Carbon::parse($order->end_datetime)->timezone($timezone)->format('Y-m-d')
                : '';
            $currency = $order->currency ?? '';
            $amountFormatted = is_numeric($payment->amount)
                ? $currency . ' ' . number_format($payment->amount, 2)
                : '';

            return [
                'Booking_Id' => $order->increment_id ?? '',
                'currency' => $currency,
                'amount' => $amountFormatted,
                'charged_at' => $chargedAt,
                'title' => $typeTitles[$payment->type] ?? 'Custom Balance Fee',
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
            ];
        })->toArray();

        return response()->json($result);
    }
    public function checkbalance(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $userId = $this->userObj['id'];

        $orderData = CsOrder::with([
            'vehicle:id,modified,last_mile,passtime_status',
            'owner:id,distance_unit'
        ])
            ->where('renter_id', $userId)
            ->whereIn('status', [0, 1])
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => __('sorry, you dont have any active booking.')]);
        }

        $orderData = $this->getActiveBookingTotalPending($orderData, $orderData);

        if ($orderData->vehicle && $orderData->vehicle->passtime_status == 1) {
            return response()->json(['success' => 'Your car status is active and it should be in ready state']);
        }

        return response()->json([
            'success' => "Your booking {$orderData->increment_id} car status is disabled and you need to pay atleast {$orderData->least_advance_payment} to make your car active.",
            'full_due_detail' => $orderData->due_detail
        ]);
    }
    public function checkBookingExtension(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $userId = $this->userObj['id'];
        $orderData = CsOrder::where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => __('Sorry, you dont have any active booking with us.')]);
        }

        $targetOrderId = $orderData->parent_id ?: $orderData->id;
        $depositRule = OrderDepositRule::where('cs_order_id', $targetOrderId)
            ->select(['rental', 'insurance', 'tax'])
            ->first();

        $rental = $depositRule ? $depositRule->rental : 0;
        $insurance = $depositRule ? $depositRule->insurance : 0;
        $leastPaymentVal = sprintf('%0.2f', ($rental + $insurance) * 2);
        $currency = $orderData->currency ?? '$';
        $leastAdvancePayment = Number::currency($leastPaymentVal, $currency);

        $return = $this->validateExtensionRequest([], $orderData);

        if (!$return['status']) {
            $extendwithpayment = $this->validateExtensionRequest([], $orderData, false);
            $maxDate = !empty($extendwithpayment['result']['allowed_max_date'])
                ? date('m/d/Y', strtotime($extendwithpayment['result']['allowed_max_date']))
                : '';

            return response()->json([
                'error' => $return['message'],
                'extend_with_payment' => "You have to pay at least " . $leastAdvancePayment . " to extend your booking till " . $maxDate,
                'emergency_case' => "You were out of extensions but since support is unavailable we will grant you a 1-hour extension in emergency, to get where you need to go. Please be sure to contact support before you move the car again"
            ]);
        }

        $minDate = !empty($return['result']['allowed_min_date'])
            ? date('m/d/Y', strtotime($return['result']['allowed_min_date']))
            : '';
        $maxDate = !empty($return['result']['allowed_max_date'])
            ? date('m/d/Y', strtotime($return['result']['allowed_max_date']))
            : '';

        return response()->json([
            'success' => "Please choose any date between " . $minDate . " to " . $maxDate . "  to extend your booking, would you like to proceed?"
        ]);
    }
    public function extendBooking(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $postData = json_decode($request->getContent());
        $userId = $this->userObj['id'];

        $emergency = false;
        if (!empty($postData->extdate)) {
            try {
                $d = new \DateTime($postData->extdate);
                $extdate = $d->format('Y-m-d 11:00:00');
            } catch (\Exception $e) {
                return response()->json(['error' => __('Please enter some valid date')]);
            }
        } else {
            $extdate = date('Y-m-d H:00:00', strtotime('+3 hours'));
            $emergency = true;
        }

        $orderData = CsOrder::where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => __('Sorry, you dont have any active booking with us.')]);
        }

        $return = $this->validateExtensionRequest([], $orderData);

        if (!$emergency) {
            $allowed_min_date = $return['result']['allowed_min_date'] ?? '';
            $allowed_max_date = $return['result']['allowed_max_date'] ?? '';
            $selecteddate = date('Y-m-d', strtotime($postData->extdate));

            if ($selecteddate < date('Y-m-d', strtotime($allowed_min_date)) || $selecteddate > date('Y-m-d', strtotime($allowed_max_date))) {
                return response()->json(['error' => __('Sorry, please select date only from available range. You can contact to support team for more info')]);
            }
        }

        $vehicleData = Vehicle::where('id', $orderData->vehicle_id)->first();

        if (empty($vehicleData)) {
            return response()->json(['error' => __('You dont have permission for this request')]);
        }

        if (!empty($vehicleData->passtime_threshold) && strtotime($extdate) < strtotime($vehicleData->passtime_threshold)) {
            $timezone = $orderData->timezone ?? config('app.timezone');
            $formattedThreshold = Carbon::parse($vehicleData->passtime_threshold)->timezone($timezone)->format('m/d/Y');
            return response()->json(['error' => "Please select date next to last schedule request. Your last requested date is " . $formattedThreshold]);
        }

        $depositRule = DepositRule::where('vehicle_id', $orderData->vehicle_id)->first(['lateness_fee']);
        $extFee = ($depositRule && $depositRule->lateness_fee) ? $depositRule->lateness_fee : 0;

        $payreturn = ['status' => 'fail'];
        if ($extFee > 0) {
            $paymentProcessor = app(PaymentProcessor::class);
            $payreturn = $paymentProcessor->chargeAmtToUser($extFee, $userId, "DIA Late Fee", $orderData->currency);
        }

        $note = "I requested extension through chat";

        if ($extFee > 0 && ($payreturn['status'] ?? '') == 'success') {
            CsPaymentLog::savePartialPaymentLog([
                "orderid" => $orderData->id,
                "amount" => $payreturn['amt'] ?? $extFee,
                "transaction_id" => $payreturn['transaction_id'] ?? '',
                "note" => $note
            ], 30);

            $csOrderPayment = new CsOrderPayment();
            $csOrderPayment->orderid = $orderData->id;
            $csOrderPayment->currency = $payreturn['currency'] ?? $orderData->currency;
            $csOrderPayment->renterid = $orderData->renter_id;
            $csOrderPayment->amount = $payreturn['amt'] ?? $extFee;
            $csOrderPayment->transactionid = $payreturn['transaction_id'] ?? '';
            $csOrderPayment->tax = 0;
            $csOrderPayment->dia_fee = 0;
            $csOrderPayment->saveLateFeeTransaction();
        }

        $lateness_fee_status = $orderData->lateness_fee_status;

        if ($extFee > 0 && ($payreturn['status'] ?? '') != 'success') {
            $lateness_fee_status = 2;
        }

        $orderData->lateness_fee = ($orderData->lateness_fee ?? 0) + $extFee;
        $orderData->lateness_fee_status = $lateness_fee_status;
        $orderData->save();

        if ($vehicleData->passtime_status == 0 || $vehicleData->passtime_status == 2) {
            $activated = $this->ActivatePasstimeVehicle($orderData->vehicle_id);
            if ($activated) {
                Vehicle::where('id', $orderData->vehicle_id)->update(['passtime_status' => 1]);
                Notifier::createIntercomeUserEvent([
                    "event_name" => "starter_enabled",
                    "created_at" => time(),
                    "external_id" => $orderData->renter_id,
                    "user_id" => $orderData->renter_id,
                    "metadata" => [
                        "id" => $orderData->id,
                        "booking_id" => $orderData->increment_id,
                        "extension_date" => $extdate,
                        "from" => "extendBooking"
                    ]
                ]);
            }
        }

        Vehicle::where('id', $orderData->vehicle_id)->update(['passtime_threshold' => strtotime($extdate)]);

        OrderExtlog::create([
            'cs_order_id' => $orderData->id,
            'ext_date' => $extdate,
            'note' => $note,
            'owner' => $userId,
            'amt' => 0,
            'admin_count' => $emergency ? 1 : 0,
        ]);

        $faildPayments = $orderData->payment_status == 2 ? "Rental, " : '';
        $faildPayments .= $orderData->insu_status == 2 ? " Insurance," : '';
        $faildPayments .= $orderData->dpa_status == 2 ? " Deposit," : "";
        $faildPayments .= $orderData->infee_status == 2 ? " Initial Fee," : '';
        $faildPayments .= $orderData->dia_insu_status == 2 ? " EMF Insurance," : '';
        $faildPayments .= $orderData->emf_status == 2 ? " EMF" : '';
        $faildPayments .= $orderData->lateness_fee_status == 2 ? " Late Fee" : '';

        $timezone = $orderData->timezone ?? config('app.timezone');
        $beginDate = $orderData->start_datetime
            ? Carbon::parse($orderData->start_datetime)->timezone($timezone)->format('m/d/Y')
            : '';
        $endDate = $orderData->end_datetime
            ? Carbon::parse($orderData->end_datetime)->timezone($timezone)->format('m/d/Y')
            : '';

        Notifier::createIntercomeUserEvent([
            "event_name" => "extension_request",
            "created_at" => time(),
            "external_id" => $orderData->renter_id,
            "user_id" => $orderData->renter_id,
            "metadata" => [
                "id" => $orderData->id,
                "booking_id" => $orderData->increment_id,
                "begin_date" => $beginDate,
                "end_date" => $endDate,
                "failed_payments" => $faildPayments,
                "extension_date" => $extdate,
                "reason" => $note,
                "Amount_to_Pay" => 0,
                "from" => "extendBooking"
            ]
        ]);

        return response()->json(['success' => __('Your vehicle should be enabled shortly')]);
    }
    public function makeAdvancePayment(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $postData = json_decode($request->getContent());

        if (
            empty($postData)
            || empty($postData->amount)
            || (float) $postData->amount == 0
        ) {
            return response()->json(['error' => __('Please enter some valid amount')]);
        }

        $userId = $this->userObj['id'];

        $orderData = CsOrder::with(['vehicle:id,modified,last_mile', 'owner:id,distance_unit'])
            ->where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => __('Sorry, seems you dont have any active booking with us.')]);
        }

        $orderData = $this->getActiveBookingTotalPending($orderData, $orderData);
        $leastAdvancePayment = preg_replace("/[^0-9.]/", "", $orderData->least_advance_payment ?? '');

        if ($leastAdvancePayment > 0 && $postData->amount < $leastAdvancePayment) {
            return response()->json(['error' => "Sorry, please pay at least {$orderData->least_advance_payment}. You can contact to support team for more info"]);
        }

        $return = $this->validateExtensionRequest([], $orderData, false);
        $allowed_min_date = $return['result']['allowed_min_date'] ?? '';
        $allowed_max_date = $return['result']['allowed_max_date'] ?? '';
        $selecteddate = date('Y-m-d', strtotime("+1 day"));
        $timezone = $orderData->timezone ?? config('app.timezone');
        $extdate = Carbon::parse($selecteddate . ' 11:00:00', $timezone)->setTimezone('UTC')->format('Y-m-d H:i:s');

        if (
            $selecteddate < date('Y-m-d', strtotime($allowed_min_date))
            || $selecteddate > date('Y-m-d', strtotime($allowed_max_date))
        ) {
            return response()->json(['error' => __('Sorry, you cant make payment now. You can contact to support team for more info')]);
        }

        try {
            $paymentProcessor = app(PaymentProcessor::class);
            $res = $paymentProcessor->chargeAmtToUser($postData->amount, $userId, 'DIA Partial Pay', $orderData->currency);

            if (($res['status'] ?? '') !== 'success') {
                return response()->json(['error' => $res['message'] ?? __('Payment failed')]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }

        $balance = $postData->balance ?? 0;
        $balanceDate = $postData->balance_date ?? '';
        $note = (!empty($balance))
            ? ("I agree to pay balance " . sprintf('%0.2f', ((float) $balance - (float) $postData->amount)) . " on " . $balanceDate) . '. Current Paid amount=' . $postData->amount
            : "";

        CsPaymentLog::savePartialPaymentLog([
            "orderid" => $orderData->id,
            "amount" => $res['amt'] ?? $postData->amount,
            "transaction_id" => $res['transaction_id'] ?? '',
            "note" => $note
        ], 29);

        CsWallet::addBalance($res['amt'] ?? $postData->amount, $userId, $res['transaction_id'] ?? '', "Advance Payment", $orderData->id, date('Y-m-d H:i:s'));

        $balanceClean = preg_replace("/[^0-9.]/", "", (string) $balance);
        $amountClean = preg_replace("/[^0-9.]/", "", (string) $postData->amount);
        $remainingBalance = sprintf('%0.2f', (float) $balanceClean - (float) $amountClean);
        $msg = "Partial Payment $" . ($res['amt'] ?? $postData->amount) . " was made successfully, and you agreed to pay remaing amount $" . $remainingBalance . ", shortly of your DriveItAway order ";

        (new EmailQueueService())->saveEmailToQueue(null, $res['amt'] ?? $postData->amount, $msg, $orderData->id, 'card');

        $nextLockDate = strtotime($extdate . ' 11:00:00');
        $existingExtlog = OrderExtlog::where('cs_order_id', $orderData->id)
            ->orderBy('id', 'desc')
            ->first();

        if ($existingExtlog && strtotime($existingExtlog->ext_date) > time()) {
            $extDiffHours = (strtotime($existingExtlog->ext_date) - time()) / 3600;
            $extdate = ($extDiffHours >= 24)
                ? date('Y-m-d H:i:s', strtotime($existingExtlog->ext_date))
                : date('Y-m-d H:i:s', strtotime($existingExtlog->ext_date) + 86400);
            $nextLockDate = "";
        }

        OrderExtlog::create([
            'cs_order_id' => $orderData->id,
            'ext_date' => $extdate,
            'note' => '',
            'owner' => $userId,
            'amt' => sprintf('%0.2f', (float) $postData->amount),
        ]);

        if ($orderData->payment_status == 0 && ($orderData->rent ?? 0) > 0) {
            $orderData->payment_status = 2;
        }
        if ($orderData->insu_status == 0 && ($orderData->insurance_amt ?? 0) > 0) {
            $orderData->insu_status = 2;
        }
        if ($orderData->infee_status == 0 && ($orderData->initial_fee ?? 0) > 0) {
            $orderData->infee_status = 2;
        }
        if ($orderData->toll_status == 0 && ($orderData->pending_toll ?? 0) > 0) {
            $orderData->toll_status = 2;
        }
        if ($orderData->dia_insu_status == 0 && ($orderData->dia_insu ?? 0) > 0) {
            $orderData->dia_insu_status = 2;
        }
        if ($orderData->emf_status == 0 && ($orderData->extra_mileage_fee ?? 0) > 0) {
            $orderData->emf_status = 2;
        }
        $orderData->save();

        $this->retryPendingPaymentFromWallet(['CsOrder' => $orderData->toArray()]);

        $faildPayments = $orderData->payment_status == 2 ? "Rental, " : '';
        $faildPayments .= $orderData->insu_status == 2 ? " Insurance," : '';
        $faildPayments .= $orderData->dpa_status == 2 ? " Deposit," : "";
        $faildPayments .= $orderData->infee_status == 2 ? " Initial Fee," : '';
        $faildPayments .= $orderData->dia_insu_status == 2 ? " EMF Insurance," : '';
        $faildPayments .= $orderData->emf_status == 2 ? " EMF" : '';
        $faildPayments .= $orderData->lateness_fee_status == 2 ? " Late Fee" : '';

        $beginDate = $orderData->start_datetime
            ? Carbon::parse($orderData->start_datetime)->timezone($timezone)->format('m/d/Y')
            : '';
        $endDate = $orderData->end_datetime
            ? Carbon::parse($orderData->end_datetime)->timezone($timezone)->format('m/d/Y')
            : '';

        Notifier::createIntercomeUserEvent([
            "event_name" => "partial_payment",
            "created_at" => time(),
            "external_id" => $orderData->renter_id,
            "user_id" => $orderData->renter_id,
            "metadata" => [
                "id" => $orderData->id,
                "booking_id" => $orderData->increment_id,
                "begin_date" => $beginDate,
                "end_date" => $endDate,
                "failed_payments" => $faildPayments,
                "extension_date" => $extdate,
                "reason" => $note,
                "Amount_paid" => $res['amt'] ?? $postData->amount,
                "from" => "makeSomePayment"
            ]
        ]);

        $unlocked = $this->ActivatePasstimeVehicle($orderData->vehicle_id);
        if ($unlocked) {
            Notifier::createIntercomeUserEvent([
                "event_name" => "starter_enabled",
                "created_at" => time(),
                "external_id" => $orderData->renter_id,
                "user_id" => $orderData->renter_id,
                "metadata" => [
                    "id" => $orderData->id,
                    "booking_id" => $orderData->increment_id,
                    "extension_date" => $extdate,
                    "from" => "makeSomePayment"
                ]
            ]);
        }

        if (!empty($nextLockDate)) {
            Vehicle::where('id', $orderData->vehicle_id)->update(['passtime_threshold' => $nextLockDate]);
        }

        return response()->json(['success' => __('Your request processed successfully')]);
    }
    public function emergencyBookingExtend(Request $request)
    {
        if ($error = $this->authenticateMapiUser($request)) {
            return $error;
        }

        $extdate = date('Y-m-d H:00:00', strtotime('+3 hours'));
        $userId = $this->userObj['id'];

        $orderData = CsOrder::where('renter_id', $userId)
            ->where('status', 1)
            ->first();

        if (empty($orderData)) {
            return response()->json(['error' => __('Sorry, you dont have any active booking with us.')]);
        }

        $isAlreadyExtended = OrderExtlog::where('cs_order_id', $orderData->id)
            ->whereDate('ext_date', date('Y-m-d'))
            ->where('admin_count', 1)
            ->exists();

        if ($isAlreadyExtended) {
            return response()->json(['error' => __('Sorry, you already used this feature today.')]);
        }

        $vehicleData = Vehicle::where('id', $orderData->vehicle_id)->first();

        if (empty($vehicleData)) {
            return response()->json(['error' => __('You dont have permission for this request')]);
        }

        if (!empty($vehicleData->passtime_threshold) && strtotime($extdate) < strtotime($vehicleData->passtime_threshold)) {
            $timezone = $orderData->timezone ?? config('app.timezone');
            $formattedThreshold = Carbon::parse($vehicleData->passtime_threshold)->timezone($timezone)->format('m/d/Y');
            return response()->json(['error' => "Please select date next to last schedule request. Your last requested date is " . $formattedThreshold]);
        }

        $depositRule = DepositRule::where('vehicle_id', $orderData->vehicle_id)->first(['lateness_fee']);
        $extFee = ($depositRule && $depositRule->lateness_fee) ? $depositRule->lateness_fee : 0;

        $payreturn = ['status' => 'fail'];

        if ($extFee > 0) {
            $paymentProcessor = app(PaymentProcessor::class);
            $payreturn = $paymentProcessor->chargeAmtToUser($extFee, $userId, "DIA Late Fee", $orderData->currency);
        }

        $note = "I requested extension through chat";

        if ($extFee > 0 && ($payreturn['status'] ?? '') == 'success') {
            CsPaymentLog::savePartialPaymentLog([
                "orderid" => $orderData->id,
                "amount" => $payreturn['amt'] ?? $extFee,
                "transaction_id" => $payreturn['transaction_id'] ?? '',
                "note" => $note
            ], 30);

            $csOrderPayment = new CsOrderPayment();
            $csOrderPayment->orderid = $orderData->id;
            $csOrderPayment->currency = $payreturn['currency'] ?? $orderData->currency;
            $csOrderPayment->renterid = $orderData->renter_id;
            $csOrderPayment->amount = $payreturn['amt'] ?? $extFee;
            $csOrderPayment->transactionid = $payreturn['transaction_id'] ?? '';
            $csOrderPayment->tax = 0;
            $csOrderPayment->dia_fee = 0;
            $csOrderPayment->saveLateFeeTransaction();
        }

        $lateness_fee_status = $orderData->lateness_fee_status;
        if ($extFee > 0 && ($payreturn['status'] ?? '') != 'success') {
            $lateness_fee_status = 2;
        }
        $orderData->lateness_fee = ($orderData->lateness_fee ?? 0) + $extFee;
        $orderData->lateness_fee_status = $lateness_fee_status;
        $orderData->save();

        if ($vehicleData->passtime_status == 0 || $vehicleData->passtime_status == 2) {
            $activated = $this->ActivatePasstimeVehicle($orderData->vehicle_id);
            if ($activated) {
                Vehicle::where('id', $orderData->vehicle_id)->update(['passtime_status' => 1]);
                Notifier::createIntercomeUserEvent([
                    "event_name" => "starter_enabled",
                    "created_at" => time(),
                    "external_id" => $orderData->renter_id,
                    "user_id" => $orderData->renter_id,
                    "metadata" => [
                        "id" => $orderData->id,
                        "booking_id" => $orderData->increment_id,
                        "extension_date" => $extdate,
                        "from" => "emergencyBookingExtend"
                    ]
                ]);
            }
        }

        Vehicle::where('id', $orderData->vehicle_id)->update(['passtime_threshold' => strtotime($extdate)]);

        OrderExtlog::create([
            'cs_order_id' => $orderData->id,
            'ext_date' => $extdate,
            'note' => $note,
            'owner' => $userId,
            'amt' => 0,
            'admin_count' => 1,
        ]);

        $faildPayments = $orderData->payment_status == 2 ? "Rental, " : '';
        $faildPayments .= $orderData->insu_status == 2 ? " Insurance," : '';
        $faildPayments .= $orderData->dpa_status == 2 ? " Deposit," : "";
        $faildPayments .= $orderData->infee_status == 2 ? " Initial Fee," : '';
        $faildPayments .= $orderData->dia_insu_status == 2 ? " EMF Insurance," : '';
        $faildPayments .= $orderData->emf_status == 2 ? " EMF" : '';
        $faildPayments .= $orderData->lateness_fee_status == 2 ? " Late Fee" : '';

        $timezone = $orderData->timezone ?? config('app.timezone');
        $beginDate = $orderData->start_datetime
            ? Carbon::parse($orderData->start_datetime)->timezone($timezone)->format('m/d/Y')
            : '';
        $endDate = $orderData->end_datetime
            ? Carbon::parse($orderData->end_datetime)->timezone($timezone)->format('m/d/Y')
            : '';

        Notifier::createIntercomeUserEvent([
            "event_name" => "extension_request",
            "created_at" => time(),
            "external_id" => $orderData->renter_id,
            "user_id" => $orderData->renter_id,
            "metadata" => [
                "id" => $orderData->id,
                "booking_id" => $orderData->increment_id,
                "begin_date" => $beginDate,
                "end_date" => $endDate,
                "failed_payments" => $faildPayments,
                "extension_date" => $extdate,
                "reason" => $note,
                "Amount_to_Pay" => 0,
                "from" => "emergencyBookingExtend"
            ]
        ]);

        return response()->json(['success' => __('Your vehicle should be enabled shortly')]);
    }
}
