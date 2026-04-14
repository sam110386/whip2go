<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\BookingReviewsController as AdminBookingReviewsController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookingReviewsController extends AdminBookingReviewsController
{
    protected function bookingReviewGuard(): ?RedirectResponse
    {
        return $this->ensureCloudAdminSession();
    }

    protected function bookingReviewsBasePath(): string
    {
        return '/cloud/booking_reviews';
    }

    public function nonreview(Request $request)
    {
        if ($redirect = $this->bookingReviewGuard()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/booking_reviews/nonreview')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }
        $parentId = (int)($admin['parent_id'] ?? 0);
        $dealerIds = DB::table('admin_user_associations')
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->toArray();

        $limit = $this->resolveNonreviewLimit($request, 'booking_reviews_cloud_limit');
        $nonreviews = $this->nonreviewOrdersQuery($dealerIds)->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('cloud.booking_reviews._nonreview_table', [
                'nonreviews' => $nonreviews,
                'basePath' => $this->bookingReviewsBasePath(),
            ]);
        }

        return view('cloud.booking_reviews.nonreview', [
            'nonreviews' => $nonreviews,
            'limit' => $limit,
            'basePath' => $this->bookingReviewsBasePath(),
        ]);
    }

    public function initial(Request $request, $orderid = null)
    {
        return parent::initial($request, $orderid);
    }

    public function finalreview(Request $request, $orderid = null)
    {
        return parent::finalreview($request, $orderid);
    }

    public function saveImage(Request $request): JsonResponse
    {
        return parent::saveImage($request);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        return parent::deleteImage($request);
    }

    public function settlefinaldamage(Request $request): JsonResponse
    {
        return parent::settlefinaldamage($request);
    }

    public function reviewimages(Request $request, $orderid = null): Response
    {
        return parent::reviewimages($request, $orderid);
    }

    public function reviewpopup(Request $request): Response
    {
        return parent::reviewpopup($request);
    }
}
