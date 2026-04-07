<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\RespondsWithCustomerAutocomplete;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingsController extends LegacyAppController
{
    use RespondsWithCustomerAutocomplete;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * Cake BookingsController::admin_index — in-progress / active orders (status not 2 or 3).
     */
    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function admin_index(Request $request)
    {
        $admin = $this->getAdminUserid();
        if (empty($admin['administrator'])) {
            return redirect('/admin/users/index');
        }

        $query = DB::table('cs_orders as o')
            ->whereNotIn('o.status', [2, 3])
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('users as driver', 'driver.id', '=', 'o.renter_id')
            ->select([
                'o.*',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'driver.first_name as driver_first_name',
                'driver.last_name as driver_last_name',
            ])
            ->selectRaw('(select insurance_payer from cs_order_deposit_rules where cs_order_id = o.id or cs_order_id = o.parent_id limit 1) as insurance_payer');

        $trips = $query->orderByDesc('o.id')->paginate(100)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.bookings.booking_table', ['trips' => $trips]);
        }

        return view('admin.bookings.index', ['trips' => $trips]);
    }

    /**
     * Cake BookingsController::admin_getVehicle
     */
    public function admin_getVehicle(Request $request): JsonResponse
    {
        $q = LegacyVehicle::query()
            ->where('status', 1)
            ->where('trash', 0)
            ->where(function ($q2) {
                $q2->where('booked', 0)->orWhere('type', 'demo');
            })
            ->select(['id', 'vehicle_unique_id', 'vehicle_name', 'address', 'rate', 'lat', 'lng']);

        if ($request->filled('id')) {
            $q->where('id', (int)$request->query('id'));
        } else {
            $term = (string)$request->query('term', '');
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $q->where(function ($q2) use ($like) {
                $q2->where('vehicle_unique_id', 'like', $like)
                    ->orWhere('vehicle_name', 'like', $like);
            });
        }

        $rows = $q->orderBy('vehicle_unique_id')->limit(10)->get();
        $out = [];
        foreach ($rows as $v) {
            $out[] = [
                'id' => $v->id,
                'tag' => $v->vehicle_unique_id . '-' . $v->vehicle_name,
                'address' => $v->address,
                'lat' => $v->lat,
                'lng' => $v->lng,
                'rate' => $v->rate,
            ];
        }

        return response()->json($out);
    }

    public function admin_customerautocomplete(Request $request): JsonResponse
    {
        return $this->respondCustomerAutocomplete($request, 'admin');
    }

    /**
     * Cake BookingsController::admin_autocomplete / _autocomplete (POST term|id).
     */
    public function admin_autocomplete(Request $request): JsonResponse
    {
        $bookingId = trim((string)$request->input('id', ''));
        $searchTerm = trim((string)$request->input('term', ''));

        $q = DB::table('cs_orders')->select(['id', 'increment_id', 'vehicle_id']);

        if ($bookingId !== '') {
            $q->where('id', (int)$bookingId);
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
}
