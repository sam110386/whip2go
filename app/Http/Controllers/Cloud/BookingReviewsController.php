<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingReviewsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderReview;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class BookingReviewsController extends LegacyAppController
{
    use BookingReviewsTrait;

    protected bool $shouldLoadLegacyModules = true;

    private $extras = [
        "cancel_insurance" => "Cancel insurance",
        "vehicle_inspection" => "Vehicle inspection",
        "service_needed" => "Service needed",
        "body_damage" => "Any body damage"
    ];

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "CloudBookingReviews::{$action} pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * cloud_nonreview: List bookings waiting for review
     */
    public function cloud_nonreview(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        $query = CsOrder::where('cs_orders.status', 3)
            ->where('cs_orders.review_status', 0)
            ->where('cs_orders.auto_renew', 0)
            ->leftJoin('vehicles as Vehicle', 'cs_orders.vehicle_id', '=', 'Vehicle.id')
            ->select('cs_orders.*', 'Vehicle.vehicle_unique_id');

        $nonreviews = $query->orderBy('cs_orders.id', 'DESC')->paginate(25);

        if ($request->ajax()) {
            return view('cloud.elements.bookingreviews.cloud_nonreview', compact('nonreviews'));
        }

        return view('cloud.bookingreviews.nonreview', compact('nonreviews'));
    }

    /**
     * cloud_initial: Initial inspection view and save
     */
    public function cloud_initial(Request $request, $orderid)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $id = base64_decode($orderid);
        if ($request->isMethod('post')) {
            $data = $request->input('CsOrderReview');
            CsOrderReview::where('id', $data['id'])->update([
                'details' => $data['details'],
                'mileage' => $data['mileage']
            ]);
            Session::flash('success', 'Review data saved successfully');
            return back();
        }

        $data = $this->_getReviewData($id, 1);
        if (!$data) return redirect()->route('cloud.booking_reviews.nonreview');

        $OrderDepositRule = OrderDepositRule::where('cs_order_id', $id)->first();
        $pickup_data = $OrderDepositRule && $OrderDepositRule->pickup_data ? json_decode($OrderDepositRule->pickup_data, true) : [];

        return view('cloud.bookingreviews.initial', array_merge($data, [
            'orderid' => $id,
            'pickup_data' => $pickup_data
        ]));
    }

    /**
     * cloud_finalreview: Final inspection view and complete
     */
    public function cloud_finalreview(Request $request, $orderid)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $id = base64_decode($orderid);
        if ($request->isMethod('post')) {
            $data = $request->input('CsOrderReview');
            $updateReview = [
                'details' => $data['details'],
                'mileage' => $data['mileage'],
                'is_cleaned' => $data['is_cleaned'],
                'service_date' => $data['service_date'],
                'vehicle_service' => $data['vehicle_service'] == 'done' ? 1 : 0,
                'extra' => json_encode($data['extra'] ?? [])
            ];
            
            CsOrderReview::where('id', $data['id'])->update($updateReview);

            if ($request->input('submit') == 'submit') {
                CsOrder::where('id', $id)->update(['review_status' => 1]);
                $CsOrderObj = CsOrder::find($id);
                if ($CsOrderObj) {
                    Vehicle::where('id', $CsOrderObj->vehicle_id)->update(['status' => 1, 'booked' => 0]); 
                }
                Session::flash('success', 'Final review completed successfully');
                return redirect()->route('cloud.booking_reviews.nonreview');
            }

            Session::flash('success', 'Review data saved successfully');
            return back();
        }

        $data = $this->_getReviewData($id, 2);
        if (!$data) return redirect()->route('cloud.booking_reviews.nonreview');

        $extras = $this->extras;
        return view('cloud.bookingreviews.finalreview', array_merge($data, [
            'orderid' => $id,
            'extras' => $extras
        ]));
    }

    public function cloud_saveImage(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['error' => 'Unauthorized'], 403);
        return response()->json($this->_saveReviewImage($request, $request->input('id')));
    }

    public function cloud_deleteImage(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['error' => 'Unauthorized'], 403);
        return response()->json($this->_deleteReviewImage($request->input('key')));
    }

    public function cloud_settlefinaldamage(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        
        $orderId = $request->input('CsOrderReview.cs_order_id');
        $reviewId = $request->input('CsOrderReview.id');
        $refundAmount = (float)$request->input('CsOrderReview.refund');

        return response()->json($this->_settleDamage($orderId, $reviewId, $refundAmount));
    }

    public function cloud_reviewimages(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function cloud_reviewpopup(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
