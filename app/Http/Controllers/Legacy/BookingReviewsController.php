<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `BookingReviewsController` — front-end (logged-in dealer / user) review flows.
 */
class BookingReviewsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private function legacyOwnerUserId(): int
    {
        $parent = (int)session()->get('userParentId', 0);

        return $parent !== 0 ? $parent : (int)session()->get('userid', 0);
    }

    private function reviewImageDir(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'webroot'
            . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'reviewimages';
    }

    public function nonreview(Request $request, $ajax_flag = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $userId = $this->legacyOwnerUserId();
        $nonreviews = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->where('o.user_id', $userId)
            ->where('o.status', 3)
            ->where('o.review_status', 0)
            ->where('o.auto_renew', 0)
            ->orderByDesc('o.id')
            ->limit(200)
            ->select(['o.*', 'v.vehicle_unique_id'])
            ->get();

        if ((string)$ajax_flag === '1') {
            return view('booking_reviews.elements.nonreview', ['nonreviews' => $nonreviews]);
        }

        return view('booking_reviews.nonreview', ['nonreviews' => $nonreviews]);
    }

    public function reviewpopup(Request $request): Response
    {
        if ($redirect = $this->ensureUserSession()) {
            return response('Unauthorized', 401);
        }
        $orderid = trim((string)$request->input('orderid', ''));

        return response()->view('booking_reviews.reviewpopup', compact('orderid'));
    }

    public function initial(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $userId = $this->legacyOwnerUserId();
        $orderId = $this->decodeB64Id($orderid);
        if (!$orderId) {
            return redirect('/booking_reviews/nonreview');
        }

        if ($request->isMethod('POST')) {
            $reviewId = (int)$request->input('CsOrderReview.id', 0);
            $this->saveReviewFields($reviewId, (array)$request->input('CsOrderReview', []));

            return $this->refererOr('/booking_reviews/nonreview');
        }

        $csOrder = DB::table('cs_orders')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('auto_renew', 0)
            ->first();
        if (!$csOrder) {
            return redirect('/booking_reviews/nonreview');
        }

        $odr = DB::table('cs_order_deposit_rules')->where('cs_order_id', $orderId)->first();
        $review = $this->findOrCreateInitialReviewRow($orderId, $odr);
        $pickupData = [];
        if ($odr && !empty($odr->pickup_data)) {
            $decoded = json_decode((string)$odr->pickup_data, true);
            $pickupData = is_array($decoded) ? $decoded : [];
        }

        return view('booking_reviews.initial', [
            'CsOrder' => ['CsOrder' => (array)$csOrder],
            'CsOrderReview' => ['CsOrderReview' => (array)$review],
            'orderid' => $orderId,
            'pickup_data' => $pickupData,
        ]);
    }

    public function finalreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $userId = $this->legacyOwnerUserId();
        $orderId = $this->decodeB64Id($orderid);
        if (!$orderId) {
            return redirect('/booking_reviews/nonreview');
        }

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('CsOrderReview', []);
            $reviewId = (int)($payload['id'] ?? 0);
            if ($reviewId <= 0) {
                $existing = DB::table('cs_order_reviews')->where('cs_order_id', $orderId)->where('event', 2)->first();
                $reviewId = $existing ? (int)$existing->id : DB::table('cs_order_reviews')->insertGetId([
                    'cs_order_id' => $orderId,
                    'reservation_id' => null,
                    'event' => 2,
                    'details' => '',
                    'mileage' => 0,
                    'is_cleaned' => 0,
                    'vehicle_service' => 0,
                    'extra' => null,
                    'created' => now()->toDateTimeString(),
                    'modified' => now()->toDateTimeString(),
                ]);
            }
            $extra = isset($payload['extra']) && is_array($payload['extra']) ? $payload['extra'] : [];
            DB::table('cs_order_reviews')->where('id', $reviewId)->update([
                'details' => (string)($payload['details'] ?? ''),
                'mileage' => (int)($payload['mileage'] ?? 0),
                'is_cleaned' => (int)($payload['is_cleaned'] ?? 0),
                'service_date' => $payload['service_date'] ?? null,
                'vehicle_service' => (($payload['vehicle_service'] ?? '') === 'done') ? 1 : 0,
                'extra' => json_encode($extra),
                'modified' => now()->toDateTimeString(),
            ]);

            DB::table('cs_orders')->where('id', $orderId)->update(['review_status' => 1]);
            $orderRow = DB::table('cs_orders')->where('id', $orderId)->first();
            if ($orderRow) {
                $this->insertBookingCloseEvent((int)$orderId, (int)$orderRow->user_id);
                DB::table('vehicles')->where('id', (int)$orderRow->vehicle_id)->update(['status' => 1]);
            }

            return redirect('/booking_reviews/nonreview')->with('success', 'Final review completed successfully');
        }

        $csOrder = DB::table('cs_orders')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('auto_renew', 0)
            ->first();
        if (!$csOrder) {
            return redirect('/booking_reviews/nonreview');
        }

        $review = DB::table('cs_order_reviews')->where('cs_order_id', $orderId)->where('event', 2)->first();
        if (!$review) {
            $newId = DB::table('cs_order_reviews')->insertGetId([
                'cs_order_id' => $orderId,
                'reservation_id' => null,
                'event' => 2,
                'details' => '',
                'mileage' => 0,
                'is_cleaned' => 0,
                'vehicle_service' => 0,
                'extra' => null,
                'created' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
            ]);
            $review = DB::table('cs_order_reviews')->where('id', $newId)->first();
        }
        $reviewArr = (array)$review;
        if (!empty($reviewArr['extra'])) {
            $decoded = json_decode((string)$reviewArr['extra'], true);
            $reviewArr['extra'] = is_array($decoded) ? $decoded : [];
        } else {
            $reviewArr['extra'] = [];
        }

        $extras = [
            'cancel_insurance' => 'Cancel insurance',
            'vehicle_inspection' => 'Vehicle inspection',
            'service_needed' => 'Service needed',
            'body_damage' => 'Any body damage',
        ];

        return view('booking_reviews.finalreview', [
            'CsOrder' => ['CsOrder' => (array)$csOrder],
            'CsOrderReview' => ['CsOrderReview' => $reviewArr],
            'orderid' => $orderId,
            'extras' => $extras,
        ]);
    }

    public function reservationreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $userId = $this->legacyOwnerUserId();
        $reservationId = $this->decodeB64Id($orderid);
        if (!$reservationId) {
            return redirect('/vehicle_reservations/index');
        }

        if ($request->isMethod('POST')) {
            $reviewId = (int)$request->input('CsOrderReview.id', 0);
            $this->saveReviewFields($reviewId, (array)$request->input('CsOrderReview', []));

            return redirect('/vehicle_reservations/index')->with('success', 'Review data saved successfully');
        }

        $reservation = DB::table('vehicle_reservations')
            ->where('id', $reservationId)
            ->where('user_id', $userId)
            ->where('status', 0)
            ->first();
        if (!$reservation) {
            return redirect('/vehicle_reservations/index');
        }

        $review = DB::table('cs_order_reviews')
            ->where('reservation_id', $reservationId)
            ->where('event', 1)
            ->first();
        if (!$review) {
            $newId = DB::table('cs_order_reviews')->insertGetId([
                'cs_order_id' => null,
                'reservation_id' => $reservationId,
                'event' => 1,
                'details' => '',
                'mileage' => 0,
                'created' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
            ]);
            $review = DB::table('cs_order_reviews')->where('id', $newId)->first();
        }

        $odr = DB::table('cs_order_deposit_rules')->where('vehicle_reservation_id', $reservationId)->first();
        $pickupData = [];
        if ($odr && !empty($odr->pickup_data)) {
            $decoded = json_decode((string)$odr->pickup_data, true);
            $pickupData = is_array($decoded) ? $decoded : [];
        }

        return view('booking_reviews.reservationreview', [
            'CsOrderReview' => ['CsOrderReview' => (array)$review],
            'orderid' => $reservationId,
            'pickup_data' => $pickupData,
        ]);
    }

    public function saveImage(Request $request): JsonResponse
    {
        if ($this->ensureUserSession()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->jsonHandleUpload($request);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        if ($this->ensureUserSession()) {
            return response()->json(['success' => false, 'key' => ''], 401);
        }

        return $this->jsonDeleteReviewImage($request);
    }

    public function settlefinaldamage(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Sorry, you are not authorized for this action now.',
        ]);
    }

    public function reviewimages(Request $request, $orderid = null): Response
    {
        if ($redirect = $this->ensureUserSession()) {
            return response('Unauthorized', 401);
        }
        $orderId = $this->decodeB64Id($orderid);
        if (!$orderId) {
            return response('', 400);
        }
        $rows = DB::table('cs_order_reviews')->where('cs_order_id', $orderId)->get();
        $result = [];
        foreach ($rows as $row) {
            $title = ((int)$row->event === 1) ? 'initial' : 'final';
            $result[$title] = ['CsOrderReview' => (array)$row];
        }

        return response()->view('booking_reviews.reviewimages', ['result' => $result]);
    }

    public function pullVehicleOdometer(Request $request): JsonResponse
    {
        if ($this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []]);
        }
        $vehicleId = $this->decodeB64Id((string)$request->input('vehicle', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle.', 'result' => []]);
        }
        $userId = $this->legacyOwnerUserId();
        $owns = DB::table('vehicles')->where('id', $vehicleId)->where('user_id', $userId)->exists();
        if (!$owns) {
            return response()->json(['status' => false, 'message' => 'Sorry, you are not authorized user for this action.', 'result' => []]);
        }
        $lastMile = DB::table('vehicles')->where('id', $vehicleId)->value('last_mile');

        return response()->json([
            'status' => true,
            'message' => '',
            'miles' => $lastMile !== null ? (int)$lastMile : 0,
            'result' => [],
        ]);
    }

    private function decodeB64Id(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $tmp = base64_decode($value, true);
        if ($tmp !== false && ctype_digit((string)$tmp)) {
            return (int)$tmp;
        }
        if (ctype_digit((string)$value)) {
            return (int)$value;
        }

        return null;
    }

    private function refererOr(string $fallback): \Illuminate\Http\RedirectResponse
    {
        $referer = request()->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }

        return redirect($fallback)->with('success', 'Review data saved successfully');
    }

    private function findOrCreateInitialReviewRow(int $orderId, $odr): object
    {
        $review = null;
        if ($odr && !empty($odr->vehicle_reservation_id)) {
            $resId = (int)$odr->vehicle_reservation_id;
            $review = DB::table('cs_order_reviews')
                ->where('event', 1)
                ->where(function ($q) use ($orderId, $resId) {
                    $q->where('cs_order_id', $orderId)->orWhere('reservation_id', $resId);
                })
                ->first();
        } else {
            $review = DB::table('cs_order_reviews')
                ->where('cs_order_id', $orderId)
                ->where('event', 1)
                ->first();
        }
        if (!$review) {
            $newId = DB::table('cs_order_reviews')->insertGetId([
                'cs_order_id' => $orderId,
                'reservation_id' => null,
                'event' => 1,
                'details' => '',
                'mileage' => 0,
                'created' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
            ]);
            $review = DB::table('cs_order_reviews')->where('id', $newId)->first();
        } elseif (empty($review->cs_order_id)) {
            DB::table('cs_order_reviews')->where('id', $review->id)->update([
                'cs_order_id' => $orderId,
                'modified' => now()->toDateTimeString(),
            ]);
            $review = DB::table('cs_order_reviews')->where('id', $review->id)->first();
        }

        return $review;
    }

    private function saveReviewFields(int $reviewId, array $payload): void
    {
        if ($reviewId <= 0) {
            return;
        }
        DB::table('cs_order_reviews')->where('id', $reviewId)->update([
            'details' => (string)($payload['details'] ?? ''),
            'mileage' => (int)($payload['mileage'] ?? 0),
            'modified' => now()->toDateTimeString(),
        ]);
    }

    private function insertBookingCloseEvent(int $orderId, int $userId): void
    {
        if (!Schema::hasTable('cs_order_statuslogs')) {
            return;
        }
        DB::table('cs_order_statuslogs')->insert([
            'cs_order_id' => $orderId,
            'vehicle_id' => null,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 4,
            'target' => 'SF',
            'created' => now()->toDateTimeString(),
        ]);
    }

    private function jsonHandleUpload(Request $request): JsonResponse
    {
        $reviewId = (int)$request->input('id', 0);
        if ($reviewId <= 0 || !$request->hasFile('reviewimage')) {
            return response()->json(['error' => 'Invalid upload.']);
        }
        $file = $request->file('reviewimage');
        if (!$file->isValid()) {
            return response()->json(['error' => 'Upload error #' . (int)$file->getError()]);
        }
        $ext = strtolower((string)$file->getClientOriginalExtension());
        $allowed = ['jpeg', 'jpg', 'png', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            return response()->json(['error' => 'File has an invalid extension.']);
        }
        $dir = $this->reviewImageDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $count = 1 + (int)DB::table('cs_order_review_images')->where('cs_order_review_id', $reviewId)->count();
        $basename = 'review_' . $reviewId . '_' . $count . '.' . $ext;
        $file->move($dir, $basename);
        $imageId = DB::table('cs_order_review_images')->insertGetId([
            'cs_order_review_id' => $reviewId,
            'image' => $basename,
            'created' => now()->toDateTimeString(),
            'modified' => now()->toDateTimeString(),
        ]);

        return response()->json(['success' => true, 'key' => $imageId]);
    }

    private function jsonDeleteReviewImage(Request $request): JsonResponse
    {
        $key = (int)$request->input('key', 0);
        if ($key <= 0) {
            return response()->json(['success' => false, 'key' => '']);
        }
        $row = DB::table('cs_order_review_images')->where('id', $key)->first();
        if ($row && !empty($row->image)) {
            $path = $this->reviewImageDir() . DIRECTORY_SEPARATOR . $row->image;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        DB::table('cs_order_review_images')->where('id', $key)->delete();

        return response()->json(['success' => true, 'key' => '']);
    }
}
