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

    public function cloud_nonreview(Request $request)
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

    public function cloud_initial(Request $request, $orderid = null)
    {
        return $this->admin_initial($request, $orderid);
    }

    public function cloud_finalreview(Request $request, $orderid = null)
    {
        return $this->admin_finalreview($request, $orderid);
    }

    public function cloud_saveImage(Request $request): JsonResponse
    {
        return $this->admin_saveImage($request);
    }

    public function cloud_deleteImage(Request $request): JsonResponse
    {
        return $this->admin_deleteImage($request);
    }

    public function cloud_settlefinaldamage(Request $request): JsonResponse
    {
        return $this->admin_settlefinaldamage($request);
    }

    public function cloud_reviewimages(Request $request, $orderid = null): Response
    {
        return $this->admin_reviewimages($request, $orderid);
    }

    public function cloud_reviewpopup(Request $request): Response
    {
        return $this->admin_reviewpopup($request);
    }
}
