<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\RespondsWithCustomerAutocomplete;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LinkedBookingsController extends LegacyAppController
{
    use RespondsWithCustomerAutocomplete;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/bookings/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $dealerIds = $this->dealerIdsFromAdmin((int)($admin['parent_id'] ?? 0));
        $trips = $this->queryTrips($dealerIds, [0, 1])->orderByDesc('o.id')
            ->paginate(100)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.linked_bookings.booking_table', ['trips' => $trips]);
        }

        return view('admin.linked_bookings.index', ['trips' => $trips]);
    }

    public function cloud_load_single_row(Request $request)
    {
        $id = $this->decodeId((string)$request->input('orderid', ''));
        if (!$id) {
            return response('Invalid order id', 400);
        }
        $trip = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->where('o.id', $id)
            ->select(['o.*', 'v.vehicle_name'])
            ->first();
        if (!$trip) {
            return response('Order not found', 404);
        }

        return response()->view('admin.linked_bookings._single_row', ['trip' => $trip]);
    }

    public function cloud_getVehicle(Request $request): JsonResponse
    {
        $q = LegacyVehicle::query()
            ->where('status', 1)
            ->where('trash', 0)
            ->where(function ($q2) {
                $q2->where('booked', 0)->orWhere('type', 'demo');
            })
            ->select(['id', 'vehicle_unique_id', 'vehicle_name', 'address', 'rate', 'lat', 'lng']);

        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIdsFromAdmin((int)($admin['parent_id'] ?? 0));
        if ($dealerIds !== []) {
            $q->whereIn('user_id', $dealerIds);
        } else {
            $q->whereRaw('1=0');
        }

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

    public function cloud_customerautocomplete(Request $request): JsonResponse
    {
        return $this->respondCustomerAutocomplete($request, 'cloud');
    }

    public function cloud_startBooking(Request $request): JsonResponse
    {
        return $this->changeOrderStatus($request, 1, 'Your request processed successfully.');
    }

    public function cloud_loadcancelBooking(Request $request)
    {
        return response()->view('admin.bookings._cancel_popup', ['orderid' => (string)$request->input('orderid', ''), 'cancellation_fee' => 0]);
    }

    public function cloud_cancelBooking(Request $request): JsonResponse
    {
        return $this->changeOrderStatus($request, 2, 'Your booking canceled successfully.', true);
    }

    public function cloud_loadcompleteBooking(Request $request)
    {
        return response()->view('admin.bookings._complete_popup', ['trip' => (object)['id' => $request->input('orderid'), 'status' => 1, 'vehicle_name' => '']]);
    }

    public function cloud_completeBooking(Request $request): JsonResponse
    {
        return $this->changeOrderStatus($request, 3, 'Booking completed successfully.', true);
    }

    public function cloud_getinsurancetoken(Request $request): JsonResponse { return response()->json(['status' => true, 'token' => sha1((string)microtime(true))]); }
    public function cloud_retryinsurancefee(Request $request): JsonResponse { return $this->simpleOk('Insurance fee retry queued'); }
    public function cloud_retryinitialfee(Request $request): JsonResponse { return $this->simpleOk('Initial fee retry queued'); }
    public function cloud_retrydepositfee(Request $request): JsonResponse { return $this->simpleOk('Deposit retry queued'); }
    public function cloud_retryrentalfee(Request $request): JsonResponse { return $this->simpleOk('Rental retry queued'); }
    public function cloud_retryemf(Request $request): JsonResponse { return $this->simpleOk('EMF retry queued'); }
    public function cloud_retrytollfee(Request $request): JsonResponse { return $this->simpleOk('Toll retry queued'); }

    public function cloud_edit($id)
    {
        $orderId = $this->decodeId((string)$id);
        if (!$orderId) {
            return redirect('/cloud/linked_bookings/index');
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return redirect('/cloud/linked_bookings/index');
        }

        return view('admin.bookings.edit', ['order' => $order]);
    }

    public function cloud_editsave(Request $request)
    {
        $id = (int)$request->input('CsOrder.id', 0);
        if ($id > 0) {
            $save = [];
            foreach (['start_datetime', 'end_datetime', 'rent', 'tax', 'dia_fee', 'status', 'cancel_note'] as $field) {
                if ($request->has('CsOrder.' . $field)) {
                    $save[$field] = $request->input('CsOrder.' . $field);
                }
            }
            if ($save !== []) {
                DB::table('cs_orders')->where('id', $id)->update($save);
            }
        }

        return redirect('/cloud/linked_bookings/index')->with('success', 'Booking updated');
    }

    public function cloud_getagreement(Request $request): JsonResponse { return response()->json(['status' => true, 'file' => null]); }
    public function cloud_loadvehicleexpiretime(Request $request) { return response()->view('admin.bookings._vehicle_expiretime', ['orderid' => (string)$request->input('orderid', '')]); }
    public function cloud_processvehicleexpiretime(Request $request): JsonResponse { return $this->simpleOk('Vehicle expiry updated successfully'); }
    public function cloud_getinsurancepopup(Request $request) { return response()->view('admin.bookings._insurance_popup', ['orderid' => (string)$request->input('orderid', '')]); }
    public function cloud_loadvehiclegps(Request $request) { return response()->view('admin.bookings._vehicle_gps', ['orderid' => (string)$request->input('orderid', '')]); }
    public function cloud_updatevehiclegps(Request $request): JsonResponse { return $this->simpleOk('GPS updated'); }
    public function cloud_updatestartodometer(Request $request): JsonResponse { return $this->simpleOk('Start odometer updated'); }

    public function cloud_overdue(Request $request)
    {
        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIdsFromAdmin((int)($admin['parent_id'] ?? 0));
        $trips = $this->queryTrips($dealerIds, [1])
            ->whereNotNull('o.end_datetime')
            ->where('o.end_datetime', '<', now()->toDateTimeString())
            ->orderByDesc('o.id')
            ->paginate(100)
            ->withQueryString();

        return view('admin.linked_bookings.index', ['trips' => $trips]);
    }

    private function queryTrips(array $dealerIds, array $statuses)
    {
        return DB::table('cs_orders as o')
            ->whereIn('o.user_id', $dealerIds === [] ? [0] : $dealerIds)
            ->whereIn('o.status', $statuses)
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
    }

    private function dealerIdsFromAdmin(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        return AdminUserAssociation::query()
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int)$id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function decodeId(string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }

    private function changeOrderStatus(Request $request, int $status, string $message, bool $releaseVehicle = false): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('Text.orderid', $request->input('orderid', '')));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }
        $admin = $this->getAdminUserid();
        $dealerIds = $this->dealerIdsFromAdmin((int)($admin['parent_id'] ?? 0));
        $order = DB::table('cs_orders')->where('id', $orderId)->whereIn('user_id', $dealerIds === [] ? [0] : $dealerIds)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }

        $update = ['status' => $status];
        if ($status === 1) {
            $update['start_timing'] = now()->toDateTimeString();
        }
        if ($status === 3) {
            $update['end_timing'] = now()->toDateTimeString();
        }
        if ($status === 2) {
            $update['cancel_note'] = (string)$request->input('Text.cancel_note', $request->input('cancel_note', ''));
            $update['cancellation_fee'] = (float)$request->input('Text.cancellation_fee', $request->input('cancellation_fee', 0));
        }
        DB::table('cs_orders')->where('id', $orderId)->update($update);

        if ($releaseVehicle && !empty($order->vehicle_id)) {
            DB::table('vehicles')->where('id', (int)$order->vehicle_id)->update(['booked' => 0]);
        }

        return response()->json(['status' => true, 'message' => $message, 'orderid' => $orderId, 'result' => []]);
    }

    private function simpleOk(string $message): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'result' => []]);
    }
}

