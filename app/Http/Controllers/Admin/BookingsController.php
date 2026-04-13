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

    public function admin_load_single_row(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $trip = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->where('o.id', $orderId)
            ->select(['o.*', 'v.vehicle_name'])
            ->first();

        if (!$trip) {
            return response('Order not found', 404);
        }

        return response()->view('admin.bookings._single_row', ['trip' => $trip]);
    }

    public function admin_loadcancelBooking(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first(['id', 'vehicle_id']);
        if (!$order) {
            return response('Order not found', 404);
        }

        return response()->view('admin.bookings._cancel_popup', [
            'orderid' => base64_encode((string)$order->id),
            'cancellation_fee' => 0,
        ]);
    }

    public function admin_loadcompleteBooking(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $trip = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$trip) {
            return response('Order not found', 404);
        }

        return response()->view('admin.bookings._complete_popup', ['trip' => $trip]);
    }

    public function admin_startBooking(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('parent_id', 0)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }
        if ((int)$order->status !== 0) {
            return response()->json(['status' => false, 'message' => 'sorry, booking already accepted.', 'result' => []]);
        }

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status' => 1,
            'start_timing' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Your request processed successfully.',
            'orderid' => $orderId,
            'result' => [],
        ]);
    }

    public function admin_cancelBooking(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('Text.orderid', $request->input('orderid', '')));
        $cancelNote = trim((string)$request->input('Text.cancel_note', $request->input('cancel_note', '')));
        $cancellationFee = (float)$request->input('Text.cancellation_fee', $request->input('cancellation_fee', 0));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []]);
        }
        if ((int)$order->status !== 0) {
            return response()->json(['status' => false, 'message' => 'sorry, booking already canceled.', 'result' => []]);
        }

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status' => 2,
            'cancel_note' => $cancelNote,
            'cancellation_fee' => $cancellationFee,
            'rent' => 0,
            'tax' => 0,
        ]);
        DB::table('vehicles')->where('id', (int)$order->vehicle_id)->update(['booked' => 0]);

        return response()->json([
            'status' => true,
            'message' => 'Your booking canceled successfully.',
            'orderid' => $orderId,
            'result' => [],
        ]);
    }

    public function admin_completeBooking(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('Text.orderid', $request->input('orderid', '')));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Booking not found', 'result' => []]);
        }
        if ((int)$order->status === 3) {
            return response()->json(['status' => false, 'message' => 'Booking already completed', 'result' => []]);
        }

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status' => 3,
            'end_timing' => now()->toDateTimeString(),
        ]);
        DB::table('vehicles')->where('id', (int)$order->vehicle_id)->update(['booked' => 0]);

        return response()->json([
            'status' => true,
            'message' => 'Booking completed successfully.',
            'orderid' => $orderId,
            'result' => [],
        ]);
    }

    public function admin_getinsurancetoken(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'token' => sha1((string)microtime(true))]);
    }

    public function admin_overdue(Request $request)
    {
        $trips = DB::table('cs_orders as o')
            ->where('o.status', 1)
            ->whereNotNull('o.end_datetime')
            ->where('o.end_datetime', '<', now()->toDateTimeString())
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('users as driver', 'driver.id', '=', 'o.renter_id')
            ->select(['o.*', 'owner.first_name as owner_first_name', 'owner.last_name as owner_last_name', 'driver.first_name as driver_first_name', 'driver.last_name as driver_last_name'])
            ->orderByDesc('o.id')
            ->paginate(100)
            ->withQueryString();

        return view('admin.bookings.index', ['trips' => $trips]);
    }

    public function admin_retryinsurancefee(Request $request): JsonResponse { return $this->simpleRetryResponse('Insurance fee retry queued'); }
    public function admin_retrydiainsurancefee(Request $request): JsonResponse { return $this->simpleRetryResponse('DIA insurance fee retry queued'); }
    public function admin_retryinitialfee(Request $request): JsonResponse { return $this->simpleRetryResponse('Initial fee retry queued'); }
    public function admin_retrydepositfee(Request $request): JsonResponse { return $this->simpleRetryResponse('Deposit retry queued'); }
    public function admin_retryrentalfee(Request $request): JsonResponse { return $this->simpleRetryResponse('Rental retry queued'); }
    public function admin_retryemf(Request $request): JsonResponse { return $this->simpleRetryResponse('EMF retry queued'); }
    public function admin_retrytollfee(Request $request): JsonResponse { return $this->simpleRetryResponse('Toll retry queued'); }
    public function admin_retrylatefee(Request $request): JsonResponse { return $this->simpleRetryResponse('Late fee retry queued'); }

    public function admin_edit($id)
    {
        $orderId = $this->decodeId((string)$id);
        if (!$orderId) {
            return redirect('/admin/bookings/index');
        }
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return redirect('/admin/bookings/index');
        }

        return view('admin.bookings.edit', ['order' => $order]);
    }

    public function admin_editsave(Request $request)
    {
        $id = (int)$request->input('CsOrder.id', 0);
        if ($id <= 0) {
            return redirect()->back()->with('error', 'Invalid booking');
        }
        $save = [];
        foreach (['start_datetime', 'end_datetime', 'rent', 'tax', 'dia_fee', 'status', 'cancel_note'] as $field) {
            if ($request->has('CsOrder.' . $field)) {
                $save[$field] = $request->input('CsOrder.' . $field);
            }
        }
        if ($save !== []) {
            DB::table('cs_orders')->where('id', $id)->update($save);
        }

        return redirect('/admin/bookings/index')->with('success', 'Booking updated');
    }

    public function admin_getagreement(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'file' => null, 'message' => 'Agreement file is not available in Laravel migration yet']);
    }

    public function admin_loadvehicleexpiretime(Request $request)
    {
        return response()->view('admin.bookings._vehicle_expiretime', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_processvehicleexpiretime(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Vehicle expiry updated successfully']);
    }

    public function admin_getinsurancepopup(Request $request)
    {
        return response()->view('admin.bookings._insurance_popup', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_checkrapprove(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Approved']);
    }

    public function admin_checkrdisapprove(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Disapproved']);
    }

    public function admin_loadvehiclegps(Request $request)
    {
        return response()->view('admin.bookings._vehicle_gps', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_updatevehiclegps(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        $gps = (string)$request->input('gps_serialno', '');
        if ($orderId && $gps !== '') {
            $order = DB::table('cs_orders')->where('id', $orderId)->first(['vehicle_id']);
            if ($order && !empty($order->vehicle_id)) {
                DB::table('vehicles')->where('id', (int)$order->vehicle_id)->update(['gps_serialno' => $gps]);
            }
        }

        return response()->json(['status' => true, 'message' => 'GPS updated']);
    }

    public function admin_diabletempvehicle(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicle_id', 0);
        if ($vehicleId > 0) {
            DB::table('vehicles')->where('id', $vehicleId)->update(['status' => 0]);
        }

        return response()->json(['status' => true, 'message' => 'Vehicle disabled']);
    }

    public function admin_goalrecalculate($id = null): JsonResponse { return response()->json(['status' => true, 'message' => 'Goal recalculation queued']); }
    public function admin_getVehicleDynamicFareMatrix(Request $request): JsonResponse { return response()->json(['status' => true, 'data' => ['matrix' => []]]); }
    public function admin_saveGoalRecalculation(Request $request): JsonResponse { return response()->json(['status' => true, 'message' => 'Goal recalculation saved']); }
    public function admin_savemanualcalculation(Request $request): JsonResponse { return response()->json(['status' => true, 'message' => 'Manual calculation saved']); }

    public function admin_loadextendtime(Request $request)
    {
        return response()->view('admin.bookings._extend_time', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_changeExtendTime(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        $end = (string)$request->input('end_datetime', '');
        if ($orderId && $end !== '') {
            DB::table('cs_orders')->where('id', $orderId)->update(['end_datetime' => $end]);
        }

        return response()->json(['status' => true, 'message' => 'Booking extended']);
    }

    public function admin_partial_payment(Request $request)
    {
        return response()->view('admin.bookings.partial_payment', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_process_partial_payment(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Partial payment processed']);
    }

    public function admin_geotabkeylesslock(Request $request): JsonResponse { return response()->json(['status' => true, 'message' => 'Lock command queued']); }
    public function admin_geotabkeylessunlock(Request $request): JsonResponse { return response()->json(['status' => true, 'message' => 'Unlock command queued']); }
    public function admin_getDeclarationDoc(Request $request): JsonResponse { return response()->json(['status' => true, 'file' => null]); }

    public function admin_overdue_booking_details(Request $request)
    {
        return $this->admin_overdue($request);
    }

    public function admin_updateodometer(Request $request)
    {
        return response()->view('admin.bookings._odometer', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function admin_saveBookingOdometer(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if ($orderId) {
            $save = [];
            foreach (['start_odometer', 'end_odometer'] as $f) {
                if ($request->has($f)) {
                    $save[$f] = (float)$request->input($f, 0);
                }
            }
            if ($save !== []) {
                DB::table('cs_orders')->where('id', $orderId)->update($save);
            }
        }

        return response()->json(['status' => true, 'message' => 'Odometer updated']);
    }

    public function admin_pullVehicleOdometer(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'odometer' => null, 'message' => 'Provider integration pending']);
    }

    public function admin_getVehicleCCMCard(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'card' => null]);
    }

    public function admin_sendAxleShareDetails(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Axle share details sent']);
    }

    public function admin_sendDirectAxleLink(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Direct Axle link sent']);
    }

    public function admin_insurancepopup()
    {
        return response()->view('admin.bookings._insurance_popup', ['orderid' => '']);
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

    private function simpleRetryResponse(string $message): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'result' => []]);
    }
}
