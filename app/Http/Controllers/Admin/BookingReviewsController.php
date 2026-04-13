<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `BookingReviewsController` — admin (and shared) booking / reservation review flows.
 */
class BookingReviewsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /** @var array<string, string> */
    protected array $extrasLabels = [
        'cancel_insurance' => 'Cancel insurance',
        'vehicle_inspection' => 'Vehicle inspection',
        'service_needed' => 'Service needed',
        'body_damage' => 'Any body damage',
    ];

    protected function reviewImageDir(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'webroot'
            . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'reviewimages';
    }

    protected function bookingReviewsBasePath(): string
    {
        return '/admin/booking_reviews';
    }

    /**
     * Admin UI uses {@see ensureAdminSession()}; Cloud controller overrides to {@see ensureCloudAdminSession()}.
     */
    protected function bookingReviewGuard(): ?RedirectResponse
    {
        return $this->ensureAdminSession();
    }

    public function admin_nonreview(Request $request)
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return $redirect;
        }

        $limit = $this->resolveNonreviewLimit($request, 'booking_reviews_limit');
        $query = $this->nonreviewOrdersQuery(null);
        $nonreviews = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.booking_reviews._nonreview_table', [
                'nonreviews' => $nonreviews,
                'basePath' => $this->bookingReviewsBasePath(),
            ]);
        }

        return view('admin.booking_reviews.admin_nonreview', [
            'nonreviews' => $nonreviews,
            'limit' => $limit,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function admin_initial(Request $request, $orderid = null)
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return $redirect;
        }

        $orderId = $this->decodeB64Id($orderid);
        if (!$orderId) {
            return redirect($this->bookingReviewsBasePath() . '/nonreview');
        }

        if ($request->isMethod('POST')) {
            $reviewId = (int)$request->input('CsOrderReview.id', 0);
            $this->saveOrderReviewFields($request, $reviewId, ['details', 'mileage']);

            return $this->refererRedirect($request, $this->bookingReviewsBasePath() . '/nonreview');
        }

        $csOrder = DB::table('cs_orders')->where('id', $orderId)->where('auto_renew', 0)->first();
        if (!$csOrder) {
            return redirect($this->bookingReviewsBasePath() . '/nonreview');
        }

        $odr = DB::table('cs_order_deposit_rules')->where('cs_order_id', $orderId)->first();
        $review = $this->findOrCreateInitialReview($orderId, $odr);
        $pickupData = [];
        if ($odr && !empty($odr->pickup_data)) {
            $decoded = json_decode((string)$odr->pickup_data, true);
            $pickupData = is_array($decoded) ? $decoded : [];
        }

        return view('admin.booking_reviews.admin_initial', [
            'CsOrder' => ['CsOrder' => (array)$csOrder],
            'CsOrderReview' => ['CsOrderReview' => (array)$review],
            'orderid' => $orderId,
            'pickup_data' => $pickupData,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function admin_finalreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return $redirect;
        }

        $orderId = $this->decodeB64Id($orderid);
        if (!$orderId) {
            return redirect($this->bookingReviewsBasePath() . '/nonreview');
        }

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('CsOrderReview', []);
            $reviewId = (int)($payload['id'] ?? 0);
            if ($reviewId <= 0) {
                $existing = DB::table('cs_order_reviews')
                    ->where('cs_order_id', $orderId)
                    ->where('event', 2)
                    ->first();
                if ($existing) {
                    $reviewId = (int)$existing->id;
                } else {
                    $reviewId = DB::table('cs_order_reviews')->insertGetId([
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
            }
            $submit = (string)$request->input('submit', '');

            $extra = isset($payload['extra']) && is_array($payload['extra']) ? $payload['extra'] : [];
            $save = [
                'details' => (string)($payload['details'] ?? ''),
                'mileage' => (int)($payload['mileage'] ?? 0),
                'is_cleaned' => (int)($payload['is_cleaned'] ?? 0),
                'service_date' => $payload['service_date'] ?? null,
                'vehicle_service' => (($payload['vehicle_service'] ?? '') === 'done') ? 1 : 0,
                'extra' => json_encode($extra),
            ];

            DB::table('cs_order_reviews')->where('id', $reviewId)->update(array_merge($save, [
                'modified' => now()->toDateTimeString(),
            ]));

            if ($submit === 'save') {
                return redirect($this->bookingReviewsBasePath() . '/nonreview')->with('success', 'Review data saved successfully');
            }

            DB::table('cs_orders')->where('id', $orderId)->update(['review_status' => 1]);

            $orderRow = DB::table('cs_orders')->where('id', $orderId)->first();
            if ($orderRow) {
                $this->insertBookingCloseEvent($orderId, (int)$orderRow->user_id);
                DB::table('vehicles')->where('id', (int)$orderRow->vehicle_id)->update(['status' => 1]);
                // VehicleIssueLib tickets omitted (not ported).
            }

            return redirect($this->bookingReviewsBasePath() . '/nonreview')->with('success', 'Final review completed successfully');
        }

        $csOrder = DB::table('cs_orders')->where('id', $orderId)->where('auto_renew', 0)->first();
        if (!$csOrder) {
            return redirect($this->bookingReviewsBasePath() . '/nonreview');
        }

        $review = DB::table('cs_order_reviews')
            ->where('cs_order_id', $orderId)
            ->where('event', 2)
            ->first();

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

        $reviewImages = DB::table('cs_order_review_images')
            ->where('cs_order_review_id', (int)($reviewArr['id'] ?? 0))
            ->orderBy('id')
            ->get();

        return view('admin.booking_reviews.admin_finalreview', [
            'CsOrder' => ['CsOrder' => (array)$csOrder],
            'CsOrderReview' => ['CsOrderReview' => $reviewArr],
            'CsOrderReviewImages' => $reviewImages,
            'orderid' => $orderId,
            'extras' => $this->extrasLabels,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function admin_reservationreview(Request $request, $orderid = null)
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return $redirect;
        }

        $reservationId = $this->decodeB64Id($orderid);
        if (!$reservationId) {
            return redirect('/admin/vehicle_reservations/index');
        }

        if ($request->isMethod('POST')) {
            $reviewId = (int)$request->input('CsOrderReview.id', 0);
            $this->saveOrderReviewFields($request, $reviewId, ['details', 'mileage']);

            return redirect('/admin/vehicle_reservations/index')->with('success', 'Review data saved successfully');
        }

        $reservation = DB::table('vehicle_reservations')
            ->where('id', $reservationId)
            ->where('status', 0)
            ->first();
        if (!$reservation) {
            return redirect('/admin/vehicle_reservations/index');
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

        return view('admin.booking_reviews.admin_reservationreview', [
            'CsOrder' => null,
            'CsOrderReview' => ['CsOrderReview' => (array)$review],
            'orderid' => $reservationId,
            'pickup_data' => $pickupData,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function admin_saveImage(Request $request): JsonResponse
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->jsonHandleUpload($request);
    }

    public function admin_deleteImage(Request $request): JsonResponse
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response()->json(['success' => false, 'key' => ''], 401);
        }

        return $this->jsonDeleteReviewImage($request);
    }

    public function admin_settlefinaldamage(Request $request): JsonResponse
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized']);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Deposit settlement via payment processor is not wired in Laravel yet. Use legacy app or extend PaymentProcessor port.',
        ]);
    }

    public function admin_reviewimages(Request $request, $orderid = null): Response
    {
        if ($redirect = $this->bookingReviewGuard()) {
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

        return response()->view('admin.booking_reviews.admin_reviewimages', ['result' => $result]);
    }

    public function admin_reviewpopup(Request $request): Response
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response('Unauthorized', 401);
        }

        $orderid = trim((string)$request->input('orderid', ''));

        return response()->view('admin.booking_reviews.admin_reviewpopup', ['orderid' => $orderid]);
    }

    public function admin_reopenbookingpopup(Request $request): Response
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response('Unauthorized', 401);
        }

        $orderid = $this->decodeB64Id((string)$request->input('BookingReview.orderid', $request->input('orderid', '')));
        if (!$orderid) {
            return response('Sorry, something went wrong, please try again later.', 400);
        }
        $order = DB::table('cs_orders')->where('id', $orderid)->first();
        if (!$order) {
            return response('Sorry, booking not found', 404);
        }

        return response()->view('admin.booking_reviews._reopenpopup', [
            'orderid' => $orderid,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function admin_reopenbooking(Request $request): JsonResponse
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }

        $data = (array)$request->input('BookingReview', []);
        $orderid = $this->decodeB64Id((string)($data['orderid'] ?? ''));
        if (!$orderid) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong, please try again later.']);
        }

        $order = DB::table('cs_orders')->where('id', $orderid)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Sorry, booking not found']);
        }

        DB::table('cs_orders')->where('id', $orderid)->update([
            'status' => 1,
            'bad_debt' => 0,
            'dia_bad_debt' => 0,
        ]);

        if (!empty($order->vehicle_id)) {
            DB::table('vehicles')->where('id', (int)$order->vehicle_id)->update(['booked' => 1]);
        }

        if ((!empty($data['reset_bad_debt']) || !empty($data['remove_wallet_debt'])) && Schema::hasTable('cs_wallet_transactions')) {
            $this->removeWalletDebtForOrder($orderid, (int)$order->renter_id);
        }

        $message = 'Your request processed successfully';
        if (!empty($data['refund_py'])) {
            $message .= ' (dealer-paid insurance refund not executed in Laravel — use legacy or wire PaymentProcessor.)';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'orderid' => $orderid,
        ]);
    }

    public function admin_pullVehicleOdometer(Request $request): JsonResponse
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'result' => []]);
        }

        $vehicleId = $this->decodeB64Id((string)$request->input('vehicle', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle.', 'result' => []]);
        }

        $lastMile = DB::table('vehicles')->where('id', $vehicleId)->value('last_mile');

        return response()->json([
            'status' => true,
            'message' => '',
            'miles' => $lastMile !== null ? (int)$lastMile : 0,
            'result' => [],
        ]);
    }

    /**
     * Cake URL used `saveImage` / `deleteImage` under /admin/ — dispatcher resolves admin_saveImage.
     * Alias for JS that posts to `admin/booking_reviews/saveImage`.
     */
    public function saveImage(Request $request): JsonResponse
    {
        return $this->admin_saveImage($request);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        return $this->admin_deleteImage($request);
    }

    public function settlefinaldamage(Request $request): JsonResponse
    {
        return $this->admin_settlefinaldamage($request);
    }

    public function pullVehicleOdometer(Request $request): JsonResponse
    {
        return $this->admin_pullVehicleOdometer($request);
    }

    protected function nonreviewOrdersQuery(?array $dealerUserIds)
    {
        $q = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->where('o.status', 3)
            ->where('o.review_status', 0)
            ->where('o.auto_renew', 0)
            ->orderByDesc('o.id')
            ->select([
                'o.*',
                'v.vehicle_unique_id',
                DB::raw("TRIM(CONCAT(COALESCE(renter.first_name,''),' ',COALESCE(renter.last_name,''))) as renter_name"),
            ]);

        if ($dealerUserIds !== null) {
            if ($dealerUserIds === []) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn('o.user_id', $dealerUserIds);
            }
        }

        return $q;
    }

    protected function resolveNonreviewLimit(Request $request, string $sessionKey): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int)$fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put($sessionKey, $lim);

                return $lim;
            }
        }
        $sess = (int)session()->get($sessionKey, 0);

        return in_array($sess, $allowed, true) ? $sess : 25;
    }

    protected function decodeB64Id(?string $value): ?int
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

    protected function refererRedirect(Request $request, string $fallback): RedirectResponse
    {
        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }

        return redirect($fallback)->with('success', 'Saved.');
    }

    /**
     * @param object|null $odr deposit rule row
     * @return object cs_order_reviews row
     */
    protected function findOrCreateInitialReview(int $orderId, $odr): object
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

    protected function saveOrderReviewFields(Request $request, int $reviewId, array $fields): void
    {
        if ($reviewId <= 0) {
            return;
        }
        $payload = (array)$request->input('CsOrderReview', []);
        $save = ['modified' => now()->toDateTimeString()];
        foreach ($fields as $f) {
            if (array_key_exists($f, $payload)) {
                $save[$f] = $payload[$f];
            }
        }
        if (array_key_exists('mileage', $save)) {
            $save['mileage'] = (int)$save['mileage'];
        }
        DB::table('cs_order_reviews')->where('id', $reviewId)->update($save);
    }

    protected function insertBookingCloseEvent(int $orderId, int $userId): void
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

    protected function jsonHandleUpload(Request $request): JsonResponse
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

    protected function jsonDeleteReviewImage(Request $request): JsonResponse
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

    protected function removeWalletDebtForOrder(int $orderId, int $renterId): void
    {
        if (!Schema::hasTable('cs_wallet_transactions') || !Schema::hasTable('cs_wallets')) {
            return;
        }
        $rows = DB::table('cs_wallet_transactions')
            ->where('cs_order_id', $orderId)
            ->where('balance', '<', 0)
            ->where('type', 1)
            ->get();

        $totalDebt = 0.0;
        foreach ($rows as $r) {
            $amt = (float)($r->amt ?? 0);
            if ($amt == 0.0) {
                $amt = (float)($r->amount ?? 0);
            }
            $totalDebt += $amt;
            DB::table('cs_wallet_transactions')->where('id', $r->id)->delete();
        }
        if ($totalDebt != 0.0) {
            DB::table('cs_wallets')->where('user_id', $renterId)->increment('balance', $totalDebt);
        }
    }
}
