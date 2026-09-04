<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderReview;
use App\Models\Legacy\CsOrderReviewImage;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\CsOrderStatuslog;
use App\Models\Legacy\CsWalletTransaction;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\RevSetting;
use App\Services\Legacy\VehicleIssueLib;
use App\Services\Legacy\PaymentProcessor;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingReviewsTrait;

/**
 * CakePHP `BookingReviewsController` — admin (and shared) booking / reservation review flows.
 */
class BookingReviewsController extends LegacyAppController
{
    use BookingReviewsTrait;
    protected array $extrasLabels = [
        'cancel_insurance' => 'Cancel insurance',
        'vehicle_inspection' => 'Vehicle inspection',
        'service_needed' => 'Service needed',
        'body_damage' => 'Any body damage',
    ];
    public function nonreview(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Review Waiting Orders";
        $sessLimitName = "admin_booking_reviews_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage ?? 50;
        }

        $nonreviews = CsOrder::with('vehicle:id,vehicle_unique_id')
            ->where('status', 3)
            ->where('review_status', 0)
            ->where('auto_renew', 0)
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($request->ajax()) {
            return view('admin.booking_reviews.elements.nonreview', [
                'nonreviews' => $nonreviews,
                'limit' => $limit
            ]);
        }

        return view('admin.booking_reviews.nonreview', [
            'title' => $title,
            'nonreviews' => $nonreviews,
            'limit' => $limit
        ]);
    }
    public function reviewpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response('Unauthorized', 401);
        }

        $orderid = trim((string) $request->input('orderid', ''));

        return response()->view('admin.booking_reviews.reviewpopup', ['orderid' => $orderid]);
    }
    public function initial(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Initial booking review';
        $orderid = $this->decodeId($orderid);

        if (!$orderid) {
            return redirect('/admin/booking_reviews/nonreview');
        }

        if ($request->isMethod('post')) {
            $data = $request->input('CsOrderReview');
            CsOrderReview::where('id', $data['id'])->update([
                'details' => $data['details'] ?? null,
                'mileage' => $data['mileage'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Review data saved successfully');
        }

        $csOrder = CsOrder::where('id', $orderid)
            ->where('auto_renew', 0)
            ->first();

        if (!$csOrder) {
            return redirect('/admin/booking_reviews/nonreview');
        }

        $orderDepositRule = OrderDepositRule::select(['vehicle_reservation_id', 'cs_order_id', 'pickup_data'])
            ->where('cs_order_id', $orderid)
            ->first();

        $query = CsOrderReview::with('csOrderReviewImages')->where('event', 1);

        if ($orderDepositRule && !empty($orderDepositRule->vehicle_reservation_id)) {
            $query->where(function ($q) use ($orderid, $orderDepositRule) {
                $q->where('cs_order_id', $orderid)
                    ->orWhere('reservation_id', $orderDepositRule->vehicle_reservation_id);
            });
        } else {
            $query->where('cs_order_id', $orderid);
        }

        $csOrderReview = $query->first();

        if (!$csOrderReview) {
            $csOrderReview = CsOrderReview::create([
                'cs_order_id' => $orderid,
                'event' => 1,
            ]);
        }

        if (empty($csOrderReview->cs_order_id)) {
            $csOrderReview->update(['cs_order_id' => $orderid]);
        }

        $pickupData = [];

        if (!empty($orderDepositRule->pickup_data)) {
            $pickupData = is_array($orderDepositRule->pickup_data)
                ? $orderDepositRule->pickup_data
                : json_decode($orderDepositRule->pickup_data, true);
        }

        return view('admin.booking_reviews.initial', compact(
            'title',
            'csOrderReview',
            'orderid',
            'pickupData'
        ));
    }
    public function finalreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Final booking review';
        $orderid = $this->decodeId($orderid);

        if (!$orderid) {
            return redirect('/admin/booking_reviews/nonreview');
        }

        if ($request->isMethod('POST')) {
            $data = $request->input('CsOrderReview', []);
            $isCleaned = $data['is_cleaned'] ?? 0;
            $vehicleService = ($data['vehicle_service'] ?? '') === 'done' ? 1 : 0;
            $extraData = is_array($data['extra'] ?? null) ? json_encode($data['extra']) : ($data['extra'] ?? null);

            $csOrderReview = CsOrderReview::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'details' => $data['details'] ?? null,
                    'mileage' => $data['mileage'] ?? null,
                    'is_cleaned' => $isCleaned,
                    'service_date' => $data['service_date'] ?? null,
                    'vehicle_service' => $vehicleService,
                    'extra' => $extraData,
                ]
            );

            if ($request->input('submit') === 'save') {
                return redirect('/admin/booking_reviews/nonreview')
                    ->with('success', 'Review data saved successfully');
            }


            CsOrder::where('id', $orderid)->update(['review_status' => 1]);

            $csOrderObj = CsOrder::select(['user_id', 'vehicle_id', 'renter_id'])
                ->find($orderid);

            if ($csOrderObj) {
                (new CsOrderStatuslog())->saveBookingCloseEvent($orderid, $csOrderObj->user_id);

                Vehicle::where('id', $csOrderObj->vehicle_id)->update(['status' => 1]);

                $vehicleIssueLib = new VehicleIssueLib();

                if ((int) $isCleaned === 0) {
                    $vehicleIssueLib->createTicketOnBookingComplete([
                        'type' => 5,
                        'vehicle_id' => $csOrderObj->vehicle_id,
                        'user_id' => $csOrderObj->user_id,
                        'renter_id' => $csOrderObj->renter_id,
                        'booking_id' => $orderid,
                    ]);
                }

                if ($vehicleService === 0) {
                    $vehicleIssueLib->createTicketOnBookingComplete([
                        'type' => 6,
                        'vehicle_id' => $csOrderObj->vehicle_id,
                        'user_id' => $csOrderObj->user_id,
                        'renter_id' => $csOrderObj->renter_id,
                        'booking_id' => $orderid,
                    ]);
                }
            }

            return redirect('/admin/booking_reviews/nonreview')
                ->with('success', 'Final review completed successfully');
        }

        $csOrder = CsOrder::where('id', $orderid)
            ->where('auto_renew', 0)
            ->first();

        if (!$csOrder) {
            return redirect('/admin/booking_reviews/nonreview');
        }

        $csOrderReview = CsOrderReview::with('csOrderReviewImages')->firstOrCreate(
            [
                'cs_order_id' => $orderid,
                'event' => 2,
            ]
        );

        if (!is_array($csOrderReview->extra)) {
            $csOrderReview->extra = json_decode($csOrderReview->extra, true) ?? [];
        }

        $extras = $this->extrasLabels;

        return view('admin.booking_reviews.finalreview', compact(
            'title',
            'orderid',
            'csOrder',
            'csOrderReview',
            'extras'
        ));
    }
    public function saveImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('reviewimage') || !$request->filled('id')) {
            return response()->json(['error' => 'No files were uploaded or missing ID.']);
        }

        $file = $request->file('reviewimage');
        $reviewId = $request->input('id');
        $result = $this->handleUpload($file, $reviewId);
        return response()->json($result);
    }
    public function deleteImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $key = $request->input('key');

        if (!$key) {
            return response()->json(['success' => false, 'key' => '']);
        }

        $reviewImage = CsOrderReviewImage::find($key);

        if ($reviewImage) {
            $filePath = public_path('files/reviewimages/' . $reviewImage->image);

            if (!empty($reviewImage->image) && file_exists($filePath)) {
                @unlink($filePath);
            }

            $reviewImage->delete();

            return response()->json(['success' => true, 'key' => '']);
        }

        return response()->json(['success' => false, 'key' => '']);
    }
    public function settlefinaldamage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $return = [
            'status' => 'error',
            'message' => 'Sorry, you are not authorized for this action now.',
        ];

        $data = $request->input('CsOrderReview', []);

        if (empty($data) || empty($data['cs_order_id']) || empty($data['id'])) {
            return response()->json($return);
        }

        $csOrderReview = CsOrderReview::where('cs_order_id', $data['cs_order_id'])
            ->where('event', 2)
            ->where('id', $data['id'])
            ->first();

        if (!$csOrderReview) {
            return response()->json($return);
        }

        $csOrder = CsOrder::where('id', $data['cs_order_id'])
            ->where('deposit_type', 'C')
            ->first();

        if (!$csOrder) {
            return response()->json($return);
        }

        $paymentProcessor = new PaymentProcessor();
        $refundAmount = floatval($data['refund'] ?? 0);
        $currentDeposit = floatval($csOrder->deposit ?? 0);

        if ($refundAmount <= $currentDeposit) {
            if ($refundAmount > 0) {
                $return = $paymentProcessor->refundBalanceDeposit($refundAmount, $csOrder->toArray());
            }

            if ($refundAmount == 0 || ($return['status'] ?? '') === 'success') {
                $balanceRefund = $currentDeposit - $refundAmount;

                $csOrder->update([
                    'review_status' => 1,
                    'deposit' => $refundAmount == 0 ? 0 : $balanceRefund,
                ]);

                $csOrderReview->update([
                    'original_amt' => $currentDeposit,
                    'refund_amt' => $refundAmount,
                    'details' => $data['details'] ?? null,
                    'mileage' => $data['mileage'] ?? null,
                ]);

                $return['status'] = 'success';

                if ($refundAmount < $currentDeposit) {
                    $revSetting = RevSetting::where('user_id', $csOrder->user_id)->first();
                    $revShare = $revSetting ? $revSetting->rev : config('legacy.OWNER_PART');

                    $transferAmount = sprintf('%0.2f', ($balanceRefund * $revShare) / 100);
                    $transferResp = $paymentProcessor->transferDepositToDealer($transferAmount, $csOrder->toArray());

                    if (($transferResp['status'] ?? '') === 'error') {
                        $return['message'] = 'Customer refund is done but Dealer transfer is not done. Please contact to administrator.';
                    } else {
                        $return['message'] = 'Transaction settled successfully';
                    }
                }
            }
        } else {
            $return['message'] = "Sorry, refund can't be more than deposit.";
        }

        return response()->json($return);
    }
    public function reviewimages($orderid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orderid = $this->decodeId($orderid);

        if (!$orderid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $CsOrderReview = CsOrderReview::with('csOrderReviewImages')
            ->where('cs_order_id', $orderid)
            ->get();

        $result = [];

        foreach ($CsOrderReview as $review) {
            $key = ($review->event == 1) ? 'initial' : 'final';
            $result[$key] = $review->toArray();
        }

        return response()->view('admin.booking_reviews.reviewimages', compact('result'));
    }
    public function reopenbookingpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orderid = $this->decodeId($request->input('BookingReview.orderid', $request->input('orderid', '')));

        if (!$orderid) {
            return response()->json(['error' => 'Sorry, something went wrong, please try again later.'], 400);
        }

        $csOrder = CsOrder::where('id', $orderid)->first();

        if (!$csOrder) {
            return response('Sorry, booking not found', 444);
        }

        return view('admin.booking_reviews._reopenpopup', compact('orderid'));
    }
    public function reopenbooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }

        $data = $request->input('BookingReview', []);
        $return = [
            'status' => false,
            'message' => 'Sorry, something went wrong, please try again later.'
        ];

        $orderid = $data['orderid'] ?? null;
        $orderid = $this->decodeId($orderid);

        if (empty($orderid)) {
            return response()->json($return);
        }

        $csOrder = CsOrder::select(['id', 'user_id', 'renter_id', 'status', 'vehicle_id'])
            ->find($orderid);

        if (!$csOrder) {
            return response()->json(['status' => false, 'message' => 'Sorry, booking not found']);
        }

        $csOrder->update([
            'status' => 1,
            'bad_debt' => 0,
            'dia_bad_debt' => 0,
        ]);

        Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 1]);

        $response = [
            'status' => true,
            'message' => 'Your request processed successfully',
            'orderid' => $orderid,
        ];

        if (!empty($data['remove_wallet_debt'])) {
            $walletTransactions = CsWalletTransaction::where('cs_order_id', $orderid)
                ->where('balance', '<', 0)
                ->where('type', 1)
                ->get();

            $totalDebt = 0;
            foreach ($walletTransactions as $transaction) {
                $totalDebt += (float) $transaction->amount;
                $transaction->delete();
            }

            if ($totalDebt > 0 && !empty($csOrder->renter_id)) {
                CsWallet::where('user_id', $csOrder->renter_id)
                    ->increment('balance', $totalDebt);
            }
        }

        if (!empty($data['refund_py'])) {
            $paymentProcessor = new PaymentProcessor();
            $paymentResult = $paymentProcessor->deailerPaidInsuranceRefund($csOrder->toArray(), true);

            if (($paymentResult['status'] ?? '') === 'success') {
                $response['status'] = true;
                $response['message'] = 'Booking reopened successfully and dealer paid insurance refunded successfully';
            } else {
                $response['status'] = false;
                $response['message'] = $paymentResult['message'] ?? 'Insurance refund failed.';
            }
        }

        return response()->json($response);
    }
    public function reservationreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Reservation pickup review';
        $orderid = $this->decodeId($orderid);

        if (!$orderid) {
            return redirect('/admin/vehicle_reservations/index');
        }

        if ($request->isMethod('POST')) {
            $data = $request->input('CsOrderReview', []);

            CsOrderReview::where('id', $data['id'] ?? null)->update([
                'details' => $data['details'] ?? null,
                'mileage' => $data['mileage'] ?? null,
            ]);

            return redirect('/admin/vehicle_reservations/index')
                ->with('success', 'Review data saved successfully');
        }

        $vehicleReservation = VehicleReservation::where('id', $orderid)
            ->where('status', 0)
            ->first();

        if (!$vehicleReservation) {
            return redirect('/admin/vehicle_reservations/index');
        }

        $csOrderReview = CsOrderReview::with('csOrderReviewImages')->firstOrCreate(
            [
                'reservation_id' => $orderid,
                'event' => 1,
            ],
            [
                'cs_order_id' => null,
            ]
        );

        $orderDepositRule = OrderDepositRule::select(['pickup_data'])
            ->where('vehicle_reservation_id', $orderid)
            ->first();

        $pickupData = [];
        if (!empty($orderDepositRule->pickup_data)) {
            $pickupData = is_array($orderDepositRule->pickup_data)
                ? $orderDepositRule->pickup_data
                : json_decode($orderDepositRule->pickup_data, true);
        }

        return view('admin.booking_reviews.reservationreview', compact(
            'title',
            'csOrderReview',
            'orderid',
            'pickupData',
        ));
    }
    public function pullVehicleOdometer(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []]);
        }

        return $this->_pullVehicleOdometer($request);
    }
}
