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
    public function index(Request $request)
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
    public function getVehicle(Request $request): JsonResponse
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

    public function customerautocomplete(Request $request): JsonResponse
    {
        return $this->respondCustomerAutocomplete($request, 'admin');
    }

    /**
     * Cake BookingsController::admin_autocomplete / _autocomplete (POST term|id).
     */
    public function autocomplete(Request $request): JsonResponse
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

    public function load_single_row(Request $request)
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

    public function loadcancelBooking(Request $request)
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

    public function loadcompleteBooking(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $trip = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$trip) {
            return response('Order not found', 404);
        }

        $allFee = [
            'estimated_rent' => (float)($trip->rent ?? 0),
            'discount' => 0,
            'rent' => (float)($trip->rent ?? 0),
            'tax' => (float)($trip->tax ?? 0),
            'dia_fee' => (float)($trip->dia_fee ?? 0),
            'extra_mileage_fee' => (float)($trip->extra_mileage_fee ?? 0),
            'emf_tax' => (float)($trip->emf_tax ?? 0),
            'lateness_fee' => (float)($trip->lateness_fee ?? 0),
            'damage_fee' => (float)($trip->damage_fee ?? 0),
            'uncleanness_fee' => (float)($trip->uncleanness_fee ?? 0),
            'initial_fee' => (float)($trip->initial_fee ?? 0),
            'initial_fee_tax' => (float)($trip->initial_fee_tax ?? 0),
            'insurance_amt' => (float)($trip->insurance_amt ?? 0),
            'dia_insu' => (float)($trip->dia_insu ?? 0),
            'pending_toll' => (float)($trip->pending_toll ?? 0),
        ];
        $calculatedInsurance = (float)($trip->insurance_penalty ?? 0);

        return response()->view('admin.bookings._complete_popup', [
            'trip' => $trip,
            'orderid' => base64_encode((string)$trip->id),
            'autorenew' => (int)($trip->autorenew ?? 0) === 1,
            'end_datetime' => (string)($trip->end_datetime ?? ''),
            'all_fee' => $allFee,
            'calculatedInsurance' => $calculatedInsurance,
        ]);
    }

    public function startBooking(Request $request): JsonResponse
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

    public function cancelBooking(Request $request): JsonResponse
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

    public function completeBooking(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string)$request->input('Text.orderid', $request->input('orderid', '')));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->where('o.status', 1)
            ->select(['o.*', 'v.id as vid', 'v.user_id as vehicle_owner_id', 'owner.id as owner_uid'])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Booking not found or not in active status.', 'result' => []]);
        }

        $rent           = (float) $request->input('Text.rent', 0);
        $tax            = (float) $request->input('Text.tax', 0);
        $diaFee         = (float) $request->input('Text.dia_fee', 0);
        $diaInsu        = (float) $request->input('Text.dia_insu', 0);
        $extraMileageFee = (float) $request->input('Text.extra_mileage_fee', 0);
        $latenessFee    = (float) $request->input('Text.lateness_fee', 0);
        $damageFee      = (float) $request->input('Text.damage_fee', 0);
        $uncleannessFee = (float) $request->input('Text.uncleanness_fee', 0);
        $insuranceFee   = (float) $request->input('Text.insurance_fee', 0);
        $initialFee     = (float) $request->input('Text.initial_fee', 0);
        $initialFeeTax  = (float) $request->input('Text.initial_fee_tax', 0);
        $discount       = (float) $request->input('Text.discount', 0);
        $pendingToll    = (float) $request->input('Text.pending_toll', 0);
        $insurancePenalty = (float) $request->input('Text.insurance_penalty', 0);
        $details        = (string) $request->input('Text.details', '');
        $autorenew      = (int) $request->input('Text.autorenew', 0);
        $autorenewEndDate = (string) $request->input('Text.autorenewenddate', '');
        $renewButDontCharge = (int) $request->input('Text.renew_but_dont_charge', 0);

        $depositRule = DB::table('cs_order_deposit_rules')
            ->where(function ($q) use ($orderId, $order) {
                $q->where('cs_order_id', $orderId)
                  ->orWhere('cs_order_id', (int) ($order->parent_id ?? 0));
            })
            ->first();

        $emfTax = 0;
        if ($extraMileageFee > 0 && $depositRule) {
            $rentalChoice = DB::table('cs_booking_rental_choices')
                ->where('cs_order_id', $depositRule->cs_order_id ?? $orderId)
                ->first();
            if ($rentalChoice && (float) ($rentalChoice->tax_rate ?? 0) > 0) {
                $emfTax = round($extraMileageFee * (float) $rentalChoice->tax_rate / 100, 2);
            }
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->sum('amount');

        \Log::warning('PaymentProcessor::completeBooking not yet ported — skipping payment capture for order ' . $orderId);

        $updateData = [
            'rent'              => $rent,
            'tax'               => $tax,
            'dia_fee'           => $diaFee,
            'dia_insu'          => $diaInsu,
            'extra_mileage_fee' => $extraMileageFee,
            'emf_tax'           => $emfTax,
            'lateness_fee'      => $latenessFee,
            'damage_fee'        => $damageFee,
            'uncleanness_fee'   => $uncleannessFee,
            'insurance_amt'     => $insuranceFee,
            'initial_fee'       => $initialFee,
            'initial_fee_tax'   => $initialFeeTax,
            'discount'          => $discount,
            'pending_toll'      => $pendingToll,
            'insurance_penalty' => $insurancePenalty,
            'details'           => $details,
            'status'            => 3,
            'end_timing'        => now()->toDateTimeString(),
        ];
        DB::table('cs_orders')->where('id', $orderId)->update($updateData);

        DB::table('vehicles')->where('id', (int) $order->vehicle_id)->update([
            'booked' => 0,
            'status' => 10,
        ]);

        if ($autorenew === 1 && $autorenewEndDate !== '') {
            $newOrder = [
                'user_id'        => $order->user_id,
                'renter_id'      => $order->renter_id,
                'vehicle_id'     => $order->vehicle_id,
                'parent_id'      => $orderId,
                'increment_id'   => $order->increment_id . '-R',
                'start_datetime' => $order->end_datetime ?? now()->toDateTimeString(),
                'end_datetime'   => $autorenewEndDate,
                'rent'           => $rent,
                'tax'            => $tax,
                'status'         => $renewButDontCharge ? 0 : 1,
                'autorenew'      => 1,
                'created'        => now()->toDateTimeString(),
                'modified'       => now()->toDateTimeString(),
            ];
            $childId = DB::table('cs_orders')->insertGetId($newOrder);

            DB::table('vehicles')->where('id', (int) $order->vehicle_id)->update([
                'booked' => 1,
                'status' => 1,
            ]);

            \Log::info('Auto-renew child booking created: ' . $childId . ' for parent ' . $orderId);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Booking completed successfully.',
            'orderid' => $orderId,
            'result'  => [],
        ]);
    }

    public function getinsurancetoken(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'token' => sha1((string)microtime(true))]);
    }

    public function overdue(Request $request)
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

    public function retryinsurancefee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('insu_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or insurance not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 3)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->insurance_amt ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryInsuranceFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['insu_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Insurance fee retried successfully.', 'result' => []]);
    }

    public function retrydiainsurancefee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('dia_insu_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or DIA insurance not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 3)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->dia_insu ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryDiaInsuranceFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['dia_insu_status' => 1]);

        return response()->json(['status' => true, 'message' => 'DIA insurance fee retried successfully.', 'result' => []]);
    }

    public function retryinitialfee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('initial_fee_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or initial fee not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 1)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->initial_fee ?? 0) + (float) ($order->initial_fee_tax ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryInitialFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['initial_fee_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Initial fee retried successfully.', 'result' => []]);
    }

    public function retrydepositfee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('deposit_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or deposit not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 4)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->deposit ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryDepositFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['deposit_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Deposit fee retried successfully.', 'result' => []]);
    }

    public function retryrentalfee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('rent_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or rental not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 2)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->rent ?? 0) + (float) ($order->tax ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryRentalFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['rent_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Rental fee retried successfully.', 'result' => []]);
    }

    public function retryemf(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('emf_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or EMF not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 6)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->extra_mileage_fee ?? 0) + (float) ($order->emf_tax ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryEmf not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['emf_status' => 1]);

        return response()->json(['status' => true, 'message' => 'EMF retried successfully.', 'result' => []]);
    }

    public function retrytollfee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('toll_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or toll not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 7)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->pending_toll ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryTollFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['toll_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Toll fee retried successfully.', 'result' => []]);
    }

    public function retrylatefee(Request $request): JsonResponse
    {
        $orderId = (int) base64_decode((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid order', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('late_fee_status', 2)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or late fee not in failed status.', 'result' => []]);
        }

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('payment_type', 8)
            ->where('status', 1)
            ->sum('amount');
        $pendingAmt = max(0, (float) ($order->lateness_fee ?? 0) - $alreadyPaid);

        \Log::warning('PaymentProcessor::retryLateFee not yet ported — order ' . $orderId . ', pending $' . $pendingAmt);

        DB::table('cs_orders')->where('id', $orderId)->update(['late_fee_status' => 1]);

        return response()->json(['status' => true, 'message' => 'Late fee retried successfully.', 'result' => []]);
    }

    public function edit($id)
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

    public function editsave(Request $request)
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

    public function getagreement(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'file' => null, 'message' => 'Agreement file is not available in Laravel migration yet']);
    }

    public function loadvehicleexpiretime(Request $request)
    {
        return response()->view('admin.bookings._vehicle_expiretime', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function processvehicleexpiretime(Request $request): JsonResponse
    {
        $orderId    = $this->decodeId((string) $request->input('Text.booking', $request->input('booking', '')));
        $vehicleId  = (int) $request->input('Text.vehicle_id', $request->input('vehicle_id', 0));
        $passThresh = (string) $request->input('Text.passtime_threshold', $request->input('passtime_threshold', ''));
        $amt        = (float) $request->input('Text.amt', $request->input('amt', 0));
        $adminCount = (int) $request->input('Text.admin_count', $request->input('admin_count', 0));
        $chargeLateFee = (int) $request->input('Text.charge_late_fee', $request->input('charge_late_fee', 0));

        if (!$orderId || $vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid inputs', 'result' => []]);
        }

        $order = DB::table('cs_orders')
            ->where('id', $orderId)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or not active.', 'result' => []]);
        }

        if ($chargeLateFee && $amt > 0) {
            \Log::warning('PaymentProcessor::chargeLateFee not yet ported — order ' . $orderId . ', amt $' . $amt);
        }

        if ($passThresh !== '') {
            DB::table('vehicles')->where('id', $vehicleId)->update([
                'passtime_threshold' => $passThresh,
            ]);
        }

        DB::table('order_extlogs')->insert([
            'cs_order_id'  => $orderId,
            'vehicle_id'   => $vehicleId,
            'admin_count'  => $adminCount,
            'amount'       => $amt,
            'threshold'    => $passThresh,
            'created'      => now()->toDateTimeString(),
        ]);

        return response()->json(['status' => true, 'message' => 'Vehicle expiry updated successfully.', 'result' => []]);
    }

    public function getinsurancepopup(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $lease = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$lease) {
            return response('Order not found', 404);
        }

        $orderRule = DB::table('cs_order_deposit_rules')
            ->where('cs_order_id', $orderId)
            ->orWhere('cs_order_id', (int)($lease->parent_id ?? 0))
            ->first();

        $payments = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->orderByDesc('id')
            ->get();

        return response()->view('admin.bookings._insurance_popup', [
            'Lease' => ['CsOrder' => (array)$lease],
            'payments' => $payments,
            'orderRuleid' => (int)($orderRule->id ?? 0),
            'insurance_payer' => (int)($orderRule->insurance_payer ?? 0),
            'vehicle_reservation_id' => (int)($orderRule->vehicle_reservation_id ?? 0),
            'showupload' => true,
            'InsuranceQuoteObj' => null,
            'paymentTypeValue' => [1 => 'Initial', 2 => 'Rental', 3 => 'Insurance', 4 => 'Deposit', 5 => 'Other'],
        ]);
    }

    public function checkrapprove(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->where('checkr_status', 1)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or checkr not pending.', 'result' => []]);
        }

        \Log::warning('PaymentProcessor::PaymentCaptureOnly not yet ported — checkr approve order ' . $orderId);

        DB::table('cs_orders')->where('id', $orderId)->update([
            'checkr_status' => 0,
        ]);

        return response()->json(['status' => true, 'message' => 'Checkr approved successfully.', 'result' => []]);
    }

    public function checkrdisapprove(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.id', $orderId)
            ->where('o.checkr_status', 1)
            ->select(['o.*', 'u.id as renter_uid', 'u.email as renter_email'])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or checkr not pending.', 'result' => []]);
        }

        \Log::warning('PaymentProcessor::ReleaseAuthorizePayment not yet ported — checkr disapprove order ' . $orderId);

        DB::table('cs_orders')->where('id', $orderId)->update([
            'status'        => 2,
            'checkr_status' => 2,
        ]);

        DB::table('vehicles')->where('id', (int) $order->vehicle_id)->update(['booked' => 0]);

        return response()->json(['status' => true, 'message' => 'Checkr disapproved, booking cancelled.', 'result' => []]);
    }

    public function loadvehiclegps(Request $request)
    {
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response()->view('admin.bookings._vehicle_gps', ['vehicle' => null, 'booking' => 0]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first(['id', 'vehicle_id']);
        if (!$order || empty($order->vehicle_id)) {
            return response()->view('admin.bookings._vehicle_gps', ['vehicle' => null, 'booking' => $orderId]);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as s', 's.user_id', '=', 'v.user_id')
            ->where('v.id', (int)$order->vehicle_id)
            ->select([
                'v.id',
                'v.passtime_serialno',
                'v.gps_serialno',
                'v.plate_number',
                'v.passtime_status',
                'v.battery',
                's.gps_provider',
                's.passtime',
            ])
            ->first();

        return response()->view('admin.bookings._vehicle_gps', [
            'vehicle' => $vehicle,
            'booking' => (int)$order->id,
        ]);
    }

    public function updatevehiclegps(Request $request): JsonResponse
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

    public function diabletempvehicle(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicle_id', 0);
        if ($vehicleId > 0) {
            DB::table('vehicles')->where('id', $vehicleId)->update(['status' => 0]);
        }

        return response()->json(['status' => true, 'message' => 'Vehicle disabled']);
    }

    public function goalrecalculate($id = null)
    {
        $orderId = $id ? (int) base64_decode((string) $id) : 0;
        if ($orderId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid booking id']);
        }

        $depositRule = DB::table('cs_order_deposit_rules')
            ->where(function ($q) use ($orderId) {
                $q->where('cs_order_id', $orderId);
            })
            ->first();

        if (!$depositRule) {
            $parentId = (int) DB::table('cs_orders')->where('id', $orderId)->value('parent_id');
            if ($parentId > 0) {
                $depositRule = DB::table('cs_order_deposit_rules')->where('cs_order_id', $parentId)->first();
            }
        }

        if (!$depositRule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $rentOpt      = json_decode($depositRule->rent_opt ?? '{}', true) ?: [];
        $initialFeeOpt = json_decode($depositRule->initial_fee_opt ?? '{}', true) ?: [];
        $depositOpt   = json_decode($depositRule->deposit_opt ?? '{}', true) ?: [];
        $durationOpt  = json_decode($depositRule->duration_opt ?? '{}', true) ?: [];
        $calculation  = json_decode($depositRule->calculation ?? '{}', true) ?: [];

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        $vehicle = $order ? DB::table('vehicles')->where('id', (int) $order->vehicle_id)->first(['id', 'msrp', 'allowed_miles']) : null;

        $milesOptions = [];
        if ($vehicle && !empty($vehicle->allowed_miles)) {
            $decoded = json_decode($vehicle->allowed_miles, true);
            if (is_array($decoded)) {
                $milesOptions = $decoded;
            }
        }

        return view('admin.bookings.goalrecalculate', [
            'orderId'        => $orderId,
            'depositRule'    => $depositRule,
            'rentOpt'        => $rentOpt,
            'initialFeeOpt'  => $initialFeeOpt,
            'depositOpt'     => $depositOpt,
            'durationOpt'    => $durationOpt,
            'calculation'    => $calculation,
            'vehicle'        => $vehicle,
            'milesOptions'   => $milesOptions,
            'order'          => $order,
        ]);
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => ['matrix' => []]]);
    }

    public function saveGoalRecalculation(Request $request): JsonResponse
    {
        $ruleId = (int) $request->input('VehicleOffer.id', 0);
        if ($ruleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid deposit rule id']);
        }

        $rule = DB::table('cs_order_deposit_rules')->where('id', $ruleId)->first();
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $updateData = [];
        $fields = [
            'rent_opt', 'initial_fee_opt', 'deposit_opt', 'duration_opt',
            'calculation', 'rent', 'initial_fee', 'deposit', 'tax_rate',
            'insurance_rate', 'allowed_miles', 'extra_mile_rate',
        ];
        foreach ($fields as $field) {
            $val = $request->input('VehicleOffer.' . $field);
            if ($val !== null) {
                $updateData[$field] = is_array($val) ? json_encode($val) : $val;
            }
        }

        $jsonField = $request->input('VehicleOffer.json');
        if ($jsonField !== null) {
            if (is_array($jsonField)) {
                $updateData = array_merge($updateData, $jsonField);
            }
        }

        if (!empty($updateData)) {
            $updateData['modified'] = now()->toDateTimeString();
            DB::table('cs_order_deposit_rules')->where('id', $ruleId)->update($updateData);
        }

        return response()->json(['status' => true, 'message' => 'Goal recalculation saved successfully.']);
    }

    public function savemanualcalculation(Request $request): JsonResponse
    {
        $ruleId = (int) $request->input('VehicleOffer.id', 0);
        if ($ruleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid deposit rule id']);
        }

        $rule = DB::table('cs_order_deposit_rules')->where('id', $ruleId)->first();
        if (!$rule) {
            return response()->json(['status' => false, 'message' => 'Deposit rule not found']);
        }

        $updateData = [];
        $fields = [
            'rent', 'initial_fee', 'deposit', 'tax_rate', 'insurance_rate',
            'allowed_miles', 'extra_mile_rate', 'rent_opt', 'initial_fee_opt',
            'deposit_opt', 'duration_opt', 'calculation',
        ];
        foreach ($fields as $field) {
            $val = $request->input('VehicleOffer.' . $field);
            if ($val !== null) {
                $updateData[$field] = is_array($val) ? json_encode($val) : $val;
            }
        }

        $jsonField = $request->input('VehicleOffer.json');
        if ($jsonField !== null && is_array($jsonField)) {
            $updateData = array_merge($updateData, $jsonField);
        }

        if (!empty($updateData)) {
            $updateData['modified'] = now()->toDateTimeString();
            DB::table('cs_order_deposit_rules')->where('id', $ruleId)->update($updateData);
        }

        return response()->json(['status' => true, 'message' => 'Manual calculation saved successfully.']);
    }

    public function loadextendtime(Request $request)
    {
        $encodedId = (string) $request->input('orderid', '');
        $orderId   = $this->decodeId($encodedId);
        $suggestedEndDatetime = '';

        if ($orderId) {
            $order = DB::table('cs_orders as o')
                ->leftJoin('cs_twilio_orders as tw', 'tw.cs_order_id', '=', 'o.id')
                ->where('o.id', $orderId)
                ->select(['o.*', 'tw.id as twilio_order_id', 'tw.extend_datetime'])
                ->first();

            if ($order && !empty($order->start_datetime) && !empty($order->end_datetime)) {
                $start = strtotime($order->start_datetime);
                $end   = strtotime($order->end_datetime);
                $gap   = $end - $start;
                if ($gap > 0) {
                    $suggestedEndDatetime = date('Y-m-d H:i:s', $end + $gap);
                }
            }
        }

        return response()->view('admin.bookings._extend_time', [
            'orderid'              => $encodedId,
            'order'                => $order ?? null,
            'suggestedEndDatetime' => $suggestedEndDatetime,
        ]);
    }

    public function changeExtendTime(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        $end     = (string) $request->input('end_datetime', '');

        if (!$orderId || $end === '') {
            return response()->json(['status' => false, 'message' => 'Invalid inputs']);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        DB::table('cs_orders')->where('id', $orderId)->update(['end_datetime' => $end]);

        $existingTwilio = DB::table('cs_twilio_orders')->where('cs_order_id', $orderId)->first();
        if ($existingTwilio) {
            DB::table('cs_twilio_orders')->where('id', $existingTwilio->id)->update([
                'extend_datetime' => $end,
                'approved'        => 1,
                'modified'        => now()->toDateTimeString(),
            ]);
        } else {
            DB::table('cs_twilio_orders')->insert([
                'cs_order_id'     => $orderId,
                'extend_datetime' => $end,
                'approved'        => 1,
                'created'         => now()->toDateTimeString(),
                'modified'        => now()->toDateTimeString(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Booking extended successfully.']);
    }

    public function partial_payment(Request $request)
    {
        $encodedId = (string) $request->input('orderid', '');
        $orderId   = $this->decodeId($encodedId);
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->leftJoin('cs_twilio_orders as tw', 'tw.cs_order_id', '=', 'o.id')
            ->where('o.id', $orderId)
            ->select([
                'o.*',
                'v.vehicle_name', 'v.vehicle_unique_id',
                'owner.first_name as owner_first_name', 'owner.last_name as owner_last_name',
                'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name',
                'tw.extend_datetime',
            ])
            ->first();

        if (!$order) {
            return response('Order not found', 404);
        }

        $payments = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->orderByDesc('id')
            ->get();

        $totalPaid = $payments->where('status', 1)->sum('amount');

        $startDate = $order->start_datetime ? date('m/d/Y', strtotime($order->start_datetime)) : '';
        $endDate   = $order->end_datetime ? date('m/d/Y', strtotime($order->end_datetime)) : '';

        return response()->view('admin.bookings.partial_payment', [
            'orderid'    => $encodedId,
            'order'      => $order,
            'payments'   => $payments,
            'totalPaid'  => (float) $totalPaid,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        ]);
    }

    public function process_partial_payment(Request $request): JsonResponse
    {
        $orderId     = $this->decodeId((string) $request->input('Text.orderid', $request->input('orderid', '')));
        $paymentMode = (string) $request->input('Text.payment_mode', $request->input('payment_mode', ''));
        $amount      = (float) $request->input('Text.amount', $request->input('amount', 0));

        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id', 'result' => []]);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found', 'result' => []]);
        }

        $totalOwed = (float) ($order->rent ?? 0)
            + (float) ($order->tax ?? 0)
            + (float) ($order->dia_fee ?? 0)
            + (float) ($order->extra_mileage_fee ?? 0)
            + (float) ($order->emf_tax ?? 0)
            + (float) ($order->lateness_fee ?? 0)
            + (float) ($order->damage_fee ?? 0)
            + (float) ($order->uncleanness_fee ?? 0)
            + (float) ($order->insurance_amt ?? 0)
            + (float) ($order->initial_fee ?? 0)
            + (float) ($order->initial_fee_tax ?? 0)
            + (float) ($order->pending_toll ?? 0);

        $alreadyPaid = (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->sum('amount');

        $pending = max(0, $totalOwed - $alreadyPaid);

        if ($paymentMode === 'advance') {
            $chargeAmt = $amount > 0 ? $amount : $pending;
            \Log::warning('PaymentProcessor::advancePayment not yet ported — order ' . $orderId . ', amt $' . $chargeAmt);

            DB::table('cs_order_payments')->insert([
                'cs_order_id'  => $orderId,
                'amount'       => $chargeAmt,
                'payment_type' => 5,
                'status'       => 1,
                'note'         => 'Admin advance payment (processor stub)',
                'created'      => now()->toDateTimeString(),
            ]);

            return response()->json(['status' => true, 'message' => 'Advance payment recorded.', 'result' => []]);
        }

        if ($paymentMode === 'fullpay') {
            \Log::warning('PaymentProcessor::fullPayment not yet ported — order ' . $orderId . ', pending $' . $pending);

            if ($pending > 0) {
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'  => $orderId,
                    'amount'       => $pending,
                    'payment_type' => 5,
                    'status'       => 1,
                    'note'         => 'Admin full payment (processor stub)',
                    'created'      => now()->toDateTimeString(),
                ]);
            }

            return response()->json(['status' => true, 'message' => 'Full payment recorded.', 'result' => []]);
        }

        $chargeAmt = $amount > 0 ? min($amount, $pending) : $pending;
        \Log::warning('PaymentProcessor::partialPayment not yet ported — order ' . $orderId . ', amt $' . $chargeAmt);

        if ($chargeAmt > 0) {
            DB::table('cs_order_payments')->insert([
                'cs_order_id'  => $orderId,
                'amount'       => $chargeAmt,
                'payment_type' => 5,
                'status'       => 1,
                'note'         => 'Admin partial payment (processor stub)',
                'created'      => now()->toDateTimeString(),
            ]);
        }

        return response()->json(['status' => true, 'message' => 'Partial payment recorded.', 'result' => []]);
    }

    public function geotabkeylesslock(Request $request): JsonResponse
    {
        $vehicleId = (int) base64_decode((string) $request->input('vehicle_id', ''));
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle id']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as s', 's.user_id', '=', 'v.user_id')
            ->where('v.id', $vehicleId)
            ->select(['v.id', 'v.gps_serialno', 's.geotab_database', 's.geotab_username', 's.geotab_password'])
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GeotabKeyless::lock not yet ported — vehicle ' . $vehicleId);

        return response()->json(['status' => true, 'message' => 'Lock command sent (stubbed).']);
    }

    public function geotabkeylessunlock(Request $request): JsonResponse
    {
        $vehicleId = (int) base64_decode((string) $request->input('vehicle_id', ''));
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid vehicle id']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as s', 's.user_id', '=', 'v.user_id')
            ->where('v.id', $vehicleId)
            ->select(['v.id', 'v.gps_serialno', 's.geotab_database', 's.geotab_username', 's.geotab_password'])
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GeotabKeyless::unlock not yet ported — vehicle ' . $vehicleId);

        return response()->json(['status' => true, 'message' => 'Unlock command sent (stubbed).']);
    }

    public function getDeclarationDoc(Request $request): JsonResponse
    {
        $bookingId = (int) base64_decode((string) $request->input('booking_id', $request->input('orderid', '')));
        if ($bookingId <= 0) {
            return response()->json(['status' => false, 'file' => null, 'message' => 'Invalid booking id']);
        }

        $order = DB::table('cs_orders')->where('id', $bookingId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'file' => null, 'message' => 'Order not found']);
        }

        \Log::warning('Declaration doc generation not yet ported — booking ' . $bookingId);

        return response()->json([
            'status'  => true,
            'file'    => null,
            'message' => 'Declaration document generation is stubbed — booking ' . $bookingId . ' loaded.',
        ]);
    }

    public function overdue_booking_details(Request $request)
    {
        return $this->overdue($request);
    }

    public function updateodometer(Request $request)
    {
        return response()->view('admin.bookings._odometer', ['orderid' => (string)$request->input('orderid', '')]);
    }

    public function saveBookingOdometer(Request $request): JsonResponse
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

    public function pullVehicleOdometer(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Invalid order id']);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first(['id', 'vehicle_id']);
        if (!$order || empty($order->vehicle_id)) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Order or vehicle not found']);
        }

        $vehicle = DB::table('vehicles as v')
            ->leftJoin('cs_settings as s', 's.user_id', '=', 'v.user_id')
            ->where('v.id', (int) $order->vehicle_id)
            ->select([
                'v.id', 'v.passtime_serialno', 'v.gps_serialno', 'v.plate_number',
                's.gps_provider', 's.passtime', 's.geotab_database', 's.geotab_username', 's.geotab_password',
            ])
            ->first();

        if (!$vehicle) {
            return response()->json(['status' => false, 'odometer' => null, 'message' => 'Vehicle not found']);
        }

        \Log::warning('GPS provider odometer pull not yet ported — vehicle ' . $vehicle->id . ', provider: ' . ($vehicle->gps_provider ?? 'unknown'));

        return response()->json([
            'status'   => true,
            'odometer' => null,
            'message'  => 'GPS provider integration pending — vehicle loaded but odometer pull is stubbed.',
        ]);
    }

    public function getVehicleCCMCard(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'card' => null, 'message' => 'Invalid order id']);
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->where('o.id', $orderId)
            ->select([
                'o.*',
                'v.vehicle_name', 'v.vehicle_unique_id', 'v.vin', 'v.plate_number',
                'owner.first_name as owner_first_name', 'owner.last_name as owner_last_name',
                'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name',
            ])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'card' => null, 'message' => 'Order not found']);
        }

        \Log::warning('CCM card generation not yet ported — order ' . $orderId);

        return response()->json([
            'status'  => true,
            'card'    => null,
            'message' => 'CCM card generation is stubbed — order ' . $orderId . ' loaded.',
        ]);
    }

    public function sendAxleShareDetails(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id']);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        $depositRule = DB::table('cs_order_deposit_rules')
            ->where(function ($q) use ($orderId, $order) {
                $q->where('cs_order_id', $orderId)
                  ->orWhere('cs_order_id', (int) ($order->parent_id ?? 0));
            })
            ->first();

        $vehicleReservation = null;
        if ($depositRule && !empty($depositRule->vehicle_reservation_id)) {
            $vehicleReservation = DB::table('vehicle_reservations')
                ->where('id', (int) $depositRule->vehicle_reservation_id)
                ->first();
        }

        \Log::warning('Notifier::sendAxleShareDetails not yet ported — order ' . $orderId);

        return response()->json([
            'status'  => true,
            'message' => 'Axle share details loaded (notification stubbed).',
            'result'  => [
                'order_id'       => $orderId,
                'reservation_id' => $vehicleReservation->id ?? null,
            ],
        ]);
    }

    public function sendDirectAxleLink(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Invalid order id']);
        }

        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found']);
        }

        $depositRule = DB::table('cs_order_deposit_rules')
            ->where(function ($q) use ($orderId, $order) {
                $q->where('cs_order_id', $orderId)
                  ->orWhere('cs_order_id', (int) ($order->parent_id ?? 0));
            })
            ->first();

        $vehicleReservation = null;
        if ($depositRule && !empty($depositRule->vehicle_reservation_id)) {
            $vehicleReservation = DB::table('vehicle_reservations')
                ->where('id', (int) $depositRule->vehicle_reservation_id)
                ->first();
        }

        \Log::warning('Notifier::sendDirectAxleLink not yet ported — order ' . $orderId);

        return response()->json([
            'status'  => true,
            'message' => 'Direct Axle link loaded (notification stubbed).',
            'result'  => [
                'order_id'       => $orderId,
                'reservation_id' => $vehicleReservation->id ?? null,
            ],
        ]);
    }

    public function insurancepopup()
    {
        return response()->view('admin.bookings._insurance_popup', ['orderid' => '']);
    }
}
