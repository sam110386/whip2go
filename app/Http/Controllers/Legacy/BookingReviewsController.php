<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingReviewsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderReview;
use App\Models\Legacy\CsOrderReviewImage;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class BookingReviewsController extends LegacyAppController
{
    use BookingReviewsTrait;

    private $extras = [
        "cancel_insurance" => "Cancel insurance",
        "vehicle_inspection" => "Vehicle inspection",
        "service_needed" => "Service needed",
        "body_damage" => "Any body damage"
    ];

    /**
     * nonreview: List bookings waiting for review
     */
    public function nonreview(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        $nonreviews = CsOrder::where('cs_orders.user_id', $userId)
            ->where('cs_orders.status', 3)
            ->where('cs_orders.review_status', 0)
            ->where('cs_orders.auto_renew', 0)
            ->leftJoin('vehicles as Vehicle', 'cs_orders.vehicle_id', '=', 'Vehicle.id')
            ->select('cs_orders.*', 'Vehicle.vehicle_unique_id')
            ->orderBy('cs_orders.id', 'DESC')
            ->get();

        if ($request->ajax()) {
            return view('legacy.elements.bookingreviews.nonreview', compact('nonreviews'));
        }

        return view('legacy.bookingreviews.nonreview', compact('nonreviews', 'userId'));
    }

    /**
     * initial: Initial inspection view and save
     */
    public function initial(Request $request, $orderid)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userId = Session::get('userParentId') ?: Session::get('userid');
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

        $data = $this->_getReviewData($id, 1, $userId);
        if (!$data) return redirect()->route('legacy.booking_reviews.nonreview');

        $OrderDepositRule = OrderDepositRule::where('cs_order_id', $id)->first();
        $pickup_data = $OrderDepositRule && $OrderDepositRule->pickup_data ? json_decode($OrderDepositRule->pickup_data, true) : [];

        return view('legacy.bookingreviews.initial', array_merge($data, [
            'orderid' => $id,
            'pickup_data' => $pickup_data
        ]));
    }

    /**
     * finalreview: Final inspection view and complete
     */
    public function finalreview(Request $request, $orderid)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $id = base64_decode($orderid);

        if ($request->isMethod('post')) {
            $data = $request->input('CsOrderReview');
            CsOrderReview::where('id', $data['id'])->update([
                'details' => $data['details'],
                'mileage' => $data['mileage'],
                'is_cleaned' => $data['is_cleaned'],
                'service_date' => $data['service_date'],
                'vehicle_service' => $data['vehicle_service'] == 'done' ? 1 : 0,
                'extra' => json_encode($data['extra'] ?? [])
            ]);

            CsOrder::where('id', $id)->update(['review_status' => 1]);
            
            $CsOrderObj = CsOrder::find($id);
            if ($CsOrderObj) {
                Vehicle::where('id', $CsOrderObj->vehicle_id)->update(['status' => 1, 'booked' => 0]); 
            }

            Session::flash('success', 'Final review completed successfully');
            return redirect()->route('legacy.booking_reviews.nonreview');
        }

        $data = $this->_getReviewData($id, 2, $userId);
        if (!$data) return redirect()->route('legacy.booking_reviews.nonreview');

        $extras = $this->extras;
        return view('legacy.bookingreviews.finalreview', array_merge($data, [
            'orderid' => $id,
            'extras' => $extras
        ]));
    }

    public function saveImage(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        return response()->json($this->_saveReviewImage($request, $request->input('id')));
    }

    public function deleteImage(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        return response()->json($this->_deleteReviewImage($request->input('key')));
    }
}
