<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderReview;
use App\Models\Legacy\CsOrderReviewImage;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait BookingReviewsTrait {

    /**
     * _getReviewData: Fetch order and review record (initial or final)
     */
    public function _getReviewData($orderId, $event, $userId = null) {
        $query = CsOrder::where('id', $orderId);
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $csOrder = $query->first();

        if (!$csOrder) return null;

        $review = CsOrderReview::where('cs_order_id', $orderId)
            ->where('event', $event)
            ->first();

        if (!$review) {
            $review = CsOrderReview::create([
                'cs_order_id' => $orderId,
                'event' => $event,
                'status' => 0
            ]);
        }

        return [
            'order' => $csOrder,
            'review' => $review
        ];
    }

    /**
     * _saveReviewImage: Handle inspection image uploads
     */
    public function _saveReviewImage(Request $request, $reviewId) {
        $request->validate([
            'reviewimage' => 'required|image|max:5120', // 5MB limit
        ]);

        $file = $request->file('reviewimage');
        $imageCount = CsOrderReviewImage::where('cs_order_review_id', $reviewId)->count() + 1;
        $filename = "review_{$reviewId}_{$imageCount}." . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('reviewimages', $filename, 'public');

        if ($path) {
            $imageRecord = CsOrderReviewImage::create([
                'cs_order_review_id' => $reviewId,
                'image' => $filename
            ]);
            return ['success' => true, 'key' => $imageRecord->id];
        }

        return ['error' => 'Could not save uploaded file.'];
    }

    /**
     * _deleteReviewImage: Handle inspection image deletion
     */
    public function _deleteReviewImage($imageId) {
        $image = CsOrderReviewImage::find($imageId);
        if ($image) {
            Storage::disk('public')->delete('reviewimages/' . $image->image);
            $image->delete();
            return ['success' => true];
        }
        return ['success' => false];
    }

    /**
     * _settleDamage: Process damage settlement from security deposit
     */
    public function _settleDamage($orderId, $reviewId, $refundAmount, $userId = null) {
        try {
            return DB::transaction(function() use ($orderId, $reviewId, $refundAmount, $userId) {
                $csOrder = CsOrder::findOrFail($orderId);
                if ($userId && $csOrder->user_id != $userId) {
                    throw new \Exception("Unauthorized");
                }

                $deposit = (float)$csOrder->deposit;
                if ($refundAmount > $deposit) {
                    throw new \Exception("Refund can't be more than deposit.");
                }

                // Placeholder for PaymentProcessor::refundBalanceDeposit
                Log::info("Settling damage for order $orderId: refund $refundAmount from deposit $deposit");

                $csOrder->update([
                    'review_status' => 1,
                    'deposit' => $deposit - $refundAmount
                ]);

                CsOrderReview::where('id', $reviewId)->update([
                    'original_amt' => $deposit,
                    'refund_amt' => $refundAmount
                ]);

                return ['status' => 'success', 'message' => "Transaction settled successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error settling damage: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
