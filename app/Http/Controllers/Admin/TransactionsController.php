<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\CsWallet;
use Carbon\Carbon;
use App\Services\Legacy\PaymentProcessor;

class TransactionsController extends LegacyAppController
{

    public function index(Request $request)
    {
        $title = 'Transactions';
        $sessionLimitKey = "transactions_limit";
        $fieldname = $request->input('Search.searchin', '');
        $keyword = $request->input('Search.keyword', '');
        $date_from = $request->input('Search.date_from', '');
        $date_to = $request->input('Search.date_to', '');
        $status_type = $request->input('Search.status_type', '');
        $transaction_id = $request->input('Search.transaction_id', '');

        $query = CsOrder::with('user:id,first_name,last_name')
            ->whereIn('status', [2, 3]);

        if (!empty($keyword)) {
            if ($fieldname == "2") {
                $query->where('vehicle_name', $keyword);
            } elseif ($fieldname == "3") {
                $query->where('increment_id', $keyword);
            }
        }

        if (!empty($date_from)) {
            $formattedDateFrom = Carbon::parse($date_from)->toDateTimeString();
            $query->where('start_datetime', '>=', $formattedDateFrom);

            if (empty($date_to)) {
                $date_to = Carbon::now()->format('Y-m-d');
            }
        }

        if (!empty($date_to)) {
            $formattedDateTo = Carbon::parse($date_to)->toDateTimeString();
            $query->where('end_datetime', '<=', $formattedDateTo);
        }

        if (!empty($status_type)) {
            if ($status_type == "cancel") {
                $query->where('status', 2);
            } elseif ($status_type == "complete") {
                $query->where('status', 3);
            } elseif ($status_type == "incomplete") {
                $query->whereIn('status', [0, 1]);
            }
        }

        if (!empty($transaction_id)) {
            $query->whereHas('payments', function ($q) use ($transaction_id) {
                $q->where('transaction_id', $transaction_id);
            });
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage ?? 50);
        }

        $reportlists = $query->paginate($limit);

        if ($request->ajax()) {
            return view('admin.transactions.listing', compact('title', 'reportlists', 'keyword', 'fieldname', 'date_from', 'date_to', 'status_type', 'transaction_id', 'limit'));
        }

        return view('admin.transactions.index', compact('title', 'reportlists', 'keyword', 'fieldname', 'date_from', 'date_to', 'status_type', 'transaction_id', 'limit'));
    }

    public function updatetransaction($id = null)
    {
        $orderId = $this->decodeId($id);

        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }

        $csorder = CsOrder::where('id', $orderId)->first();

        $orderPayments = CsOrderPayment::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->get();

        $payouts = CsPayoutTransaction::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('transfer_id', '!=', '')
            ->get();

        $transferedPayouts = $payouts->groupBy('type')->map(function ($group) {
            return $group->keyBy('id');
        })->toArray();

        $transactionIds = $orderPayments->groupBy('type')->map(function ($group) {
            return $group->keyBy('id')->map(function ($item) {
                return "{$item->amount}-> {$item->transaction_id}";
            });
        })->toArray();

        return view('admin.transactions.updatetransaction', compact(
            'orderPayments',
            'transferedPayouts',
            'csorder',
            'transactionIds'
        ));
    }

    public function updatefare($id)
    {
        return $this->renderOrderAdjustView($id, 'rent', 'Update Fare');
    }

    public function rentRefundtotal(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));

        $responseBody = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (!empty($orderid)) {
            $csorder = CsOrder::select('id', 'paid_amount', 'note', 'details')->find($orderid);

            if ($csorder) {
                $paymentProcessor = new PaymentProcessor();
                $responseBody = $paymentProcessor->rentRefundtotal($csorder);

                if (isset($responseBody['status']) && $responseBody['status'] === 'success') {
                    $csorder->paid_amount = 0;
                    $csorder->details = $csorder->details . "\n Full Rent Refunded";
                    $csorder->save();
                }
            }

        }
        return response()->json($responseBody);
    }



    public function updateinsurance($id)
    {
        return $this->renderOrderAdjustView($id, 'insurance_amt', 'Update Insurance');
    }

    public function updateinitialfee($id)
    {
        return $this->renderOrderAdjustView($id, 'initial_fee', 'Update Initial Fee');
    }

    public function updateemf($id)
    {
        return $this->renderOrderAdjustView($id, 'extra_mileage_fee', 'Update EMF');
    }

    public function updatediainsu($id)
    {
        return $this->renderOrderAdjustView($id, 'dia_insu', 'Update DIA Insurance');
    }

    public function latefee($id)
    {
        return $this->renderOrderAdjustView($id, 'lateness_fee', 'Update Late Fee');
    }

    public function updatetoll($id)
    {
        return $this->renderOrderAdjustView($id, 'toll', 'Update Toll');
    }

    private function renderOrderAdjustView($id, string $field, string $title)
    {
        $orderId = $this->decodeId((string) $id);

        if (!$orderId) {
            return redirect('/admin/transactions/index')->with('error', 'Invalid Order ID');
        }

        $order = CsOrder::where('id', $orderId)->first();

        if (!$order) {
            return redirect('/admin/transactions/index')->with('error', 'Order not found');
        }

        return view('admin.transactions.adjust', [
            'order' => $order,
            'field' => $field,
            'title' => $title,
        ]);
    }






    public function usertransactions(Request $request, $userid = null, $time = '1 day', $partial = null)
    {
        $uid = (int) ($userid ?? $request->input('userid') ?? 0);
        if ($uid <= 0) {
            return response('Invalid user', 400);
        }

        $timeStr = trim((string) ($time ?: '1 day'));
        if ($timeStr === '') {
            $timeStr = '1 day';
        }

        $bookingid = (string) $request->input('bookingid', '');
        $currency = (string) $request->input('currency', 'USD');

        $dateFrom = Carbon::now()->modify('-' . $timeStr)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $lim = (int) session('admin_transactions_limit', 50);
        if ($lim < 1) {
            $lim = 50;
        }

        $basePayments = DB::table('cs_order_payments as p')
            ->join('cs_orders as o', 'o.id', '=', 'p.cs_order_id')
            ->where('o.renter_id', $uid)
            ->where('p.status', 1)
            ->whereDate('p.created', '>=', $dateFrom)
            ->whereDate('p.created', '<=', $dateTo);

        $total = (float) (clone $basePayments)->sum('p.amount');
        $reportlists = (clone $basePayments)
            ->select([
                'p.*',
                'o.increment_id',
                'o.start_datetime',
                'o.end_datetime',
                'o.timezone',
            ])
            ->orderByDesc('p.id')
            ->limit(min($lim, 500))
            ->get();
        $walletBalance = CsWallet::query()->where('user_id', $uid)->value('balance') ?? 0;

        $listVars = [
            'rows' => $reportlists,
            'total' => $total,
            'userid' => $uid,
        ];

        $partialOnly = $partial !== null && $partial !== '' && (string) $partial === '1';

        if ($partialOnly) {
            return view('admin.transactions.usertransactions_list', $listVars);
        }

        return view('admin.transactions.usertransactions', [
            'rows' => $reportlists,
            'total' => $total,
            'wallet_balance' => $walletBalance,
            'userid' => $uid,
            'time' => $timeStr,
            'bookingid' => $bookingid,
            'currency' => $currency,
        ]);
    }





    public function updateenddatetime(Request $request)
    {
        $id = $this->decodeId((string) $request->input('booking_id', ''));
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $order = CsOrder::query()->find($id, ['id', 'end_timing', 'timezone']);
        if (!$order) {
            return response('Booking not found', 404);
        }

        return view('admin.transactions.update_endtime', ['order' => $order]);
    }

    public function changeendtiming(Request $request): JsonResponse
    {
        $id = (int) $request->input('CsOrder.id', 0);
        $endTiming = (string) $request->input('CsOrder.end_timing', '');
        if ($id <= 0 || $endTiming === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        CsOrder::query()->whereKey($id)->update(['end_timing' => $endTiming]);

        return response()->json(['status' => true, 'message' => 'Booking has been updated successfully']);
    }



    public function adjustTotal(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'rent', 'Rent adjusted successfully');
    }

    public function adjustInsurance(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'insurance_amt', 'Insurance adjusted successfully', 'newtotal');
    }

    public function insuranceRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'insurance_amt', 'details', 'Insurance Refunded');
    }

    public function adjustDeposit(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'deposit', 'Deposit adjusted successfully', 'newtotal');
    }

    public function depositRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'deposit', 'details', 'Deposit Refunded');
    }

    public function adjustinitialfee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'initial_fee', 'Initial fee adjusted successfully', 'newtotal');
    }

    public function initialfeeRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'initial_fee', null, '');
    }

    public function emfRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'extra_mileage_fee', null, '');
    }

    public function adjustEmf(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'extra_mileage_fee', 'EMF adjusted successfully');
    }

    public function diainsuRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'dia_insu', null, '');
    }

    public function adjustDiainsu(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'dia_insu', 'DIA insurance adjusted successfully');
    }

    public function latefeeRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'lateness_fee', null, '');
    }

    public function adjustLatefee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'lateness_fee', 'Late fee adjusted successfully', 'newtotal');
    }

    public function tollRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'toll', null, '');
    }

    public function adjusttollfee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'toll', 'Toll adjusted successfully');
    }

    public function failedtransfer(Request $request)
    {
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));

        $q = DB::table('cs_order_payments as p')
            ->leftJoin('cs_orders as o', 'o.id', '=', 'p.cs_order_id')
            ->where('p.cs_transfer', 2)
            ->where('p.status', 1)
            ->select(['p.*', 'o.increment_id', 'o.start_datetime', 'o.end_datetime', 'o.timezone']);

        if ($dateFrom !== '') {
            $q->whereDate('p.created', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $q->whereDate('p.created', '<=', $dateTo);
        }

        $limit = (int) session('admin_transactions_limit', 50);
        $reportlists = $q->orderByDesc('p.id')->paginate($limit)->withQueryString();

        return view('admin.transactions.failedtransfer', [
            'reportlists' => $reportlists,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    public function requeuefailedtransfer(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        $row = CsOrderPayment::query()
            ->whereKey($id)
            ->where('status', 1)
            ->where('cs_transfer', 2)
            ->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Sorry, respective record already processed or we couldnt find.']);
        }
        CsOrderPayment::query()->whereKey($id)->update(['cs_transfer' => 0]);

        return response()->json(['status' => true, 'message' => 'Processed successfully']);
    }

    public function adjustdealerrentaltransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 2, 'Adjust Dealer Rental Transfer');
    }

    public function adjustdealerinitialtransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 3, 'Adjust Dealer Initial Fee Transfer');
    }

    public function adjustdealerinsurancetransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 4, 'Adjust Dealer Insurance Transfer');
    }

    public function adjustdealeremftransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 16, 'Adjust Dealer EMF Transfer');
    }

    // ── Deposit update ──────────────────────────────────────────────

    public function updatedeposit($id)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }

        $order = DB::table('cs_orders')
            ->where('id', $orderId)
            ->where('deposit_type', 'C')
            ->first();

        if (!$order) {
            return redirect('/admin/transactions/index');
        }

        $payments = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        return view('admin.transactions.updatedeposit', [
            'order' => $order,
            'payments' => $payments,
        ]);
    }

    // ── Dealer transfer reversal methods ─────────────────────────────

    public function rentReversetotal(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid order']);
        }

        $transfers = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 2)
            ->get();

        $messages = '';
        foreach ($transfers as $t) {
            \Log::warning("rentReversetotal: PaymentProcessor->DealerFullReverse stubbed for transfer {$t->transfer_id}, amount {$t->amount}.");
            $messages .= "\n{$t->transfer_id} => {$t->amount} reversal stubbed (PaymentProcessor not yet ported).";
        }

        return response()->json(['status' => 'error', 'message' => trim($messages) ?: 'No transfers found']);
    }

    public function adjustDealerRentalPart(Request $request): JsonResponse
    {
        \Log::warning('adjustDealerRentalPart: PaymentProcessor->DealerPartialReverse stubbed.');

        return response()->json(['status' => 'error', 'message' => 'Dealer rental part adjustment not yet ported']);
    }

    public function initialfeeReversetotal(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $transfers = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 3)
            ->get();

        $messages = '';
        foreach ($transfers as $t) {
            \Log::warning("initialfeeReversetotal: PaymentProcessor->DealerFullReverse stubbed for transfer {$t->transfer_id}, amount {$t->amount}.");
            $messages .= "\n{$t->transfer_id} => {$t->amount} reversal stubbed (PaymentProcessor not yet ported).";
        }

        return response()->json(['status' => 'error', 'message' => trim($messages) ?: 'No transfers found']);
    }

    public function adjustDealerInitialFeePart(Request $request): JsonResponse
    {
        $orderId = (int) $request->input('CsOrder.id', 0);
        $newDealerAmount = (float) $request->input('CsOrder.dealerpart', 0);

        if ($orderId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $transferedAmount = (float) DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 3)
            ->where('transfer_id', '!=', '')
            ->sum('amount');

        if ($transferedAmount > $newDealerAmount) {
            $reversableAmount = $transferedAmount - $newDealerAmount;
            \Log::warning("adjustDealerInitialFeePart: PaymentProcessor->DealerPartialReverse stubbed for order {$orderId}, reversable={$reversableAmount}.");

            return response()->json(['status' => 'error', 'message' => 'Dealer partial reverse not yet ported (PaymentProcessor stubbed)']);
        }

        return response()->json(['status' => 'error', 'message' => 'Adjustable amount must be less than transferred amount.']);
    }

    public function insuranceReversetotal(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $transfers = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 4)
            ->get();

        $messages = '';
        foreach ($transfers as $t) {
            \Log::warning("insuranceReversetotal: PaymentProcessor->DealerFullReverse stubbed for transfer {$t->transfer_id}, amount {$t->amount}.");
            $messages .= "\n{$t->transfer_id} => {$t->amount} reversal stubbed (PaymentProcessor not yet ported).";
        }

        return response()->json(['status' => 'error', 'message' => trim($messages) ?: 'No transfers found']);
    }

    public function adjustDealerInsurancePart(Request $request): JsonResponse
    {
        $orderId = (int) $request->input('CsOrder.id', 0);
        $newDealerAmount = (float) $request->input('CsOrder.dealerpart', 0);

        if ($orderId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $transferedAmount = (float) DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 4)
            ->where('transfer_id', '!=', '')
            ->sum('amount');

        if ($transferedAmount > $newDealerAmount) {
            $reversableAmount = $transferedAmount - $newDealerAmount;
            \Log::warning("adjustDealerInsurancePart: PaymentProcessor->DealerPartialReverse stubbed for order {$orderId}, reversable={$reversableAmount}.");

            return response()->json(['status' => 'error', 'message' => 'Dealer partial reverse not yet ported (PaymentProcessor stubbed)']);
        }

        return response()->json(['status' => 'error', 'message' => 'Adjustable amount must be less than transferred amount.']);
    }

    public function emfReversetotal(Request $request): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }

        $transfers = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 16)
            ->get();

        $messages = '';
        foreach ($transfers as $t) {
            \Log::warning("emfReversetotal: PaymentProcessor->DealerFullReverse stubbed for transfer {$t->transfer_id}, amount {$t->amount}.");
            $messages .= "\n{$t->transfer_id} => {$t->amount} reversal stubbed (PaymentProcessor not yet ported).";
        }

        return response()->json(['status' => 'error', 'message' => trim($messages) ?: 'No transfers found']);
    }

    public function adjustDealerEmfPart(Request $request): JsonResponse
    {
        $orderId = (int) $request->input('CsOrder.id', 0);
        $newDealerAmount = (float) $request->input('CsOrder.dealerpart', 0);

        if ($orderId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $transferedAmount = (float) DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 16)
            ->where('transfer_id', '!=', '')
            ->sum('amount');

        if ($transferedAmount > $newDealerAmount) {
            $reversableAmount = $transferedAmount - $newDealerAmount;
            \Log::warning("adjustDealerEmfPart: PaymentProcessor->DealerPartialReverse stubbed for order {$orderId}, reversable={$reversableAmount}.");

            return response()->json(['status' => 'error', 'message' => 'Dealer partial reverse not yet ported (PaymentProcessor stubbed)']);
        }

        return response()->json(['status' => 'error', 'message' => 'Adjustable amount must be less than transferred amount.']);
    }

    // ── Credit driver ────────────────────────────────────────────────

    public function creditdriver(Request $request, $id = null)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index')
                ->with('error', 'Sorry, something went wrong.');
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('rev_settings as rs', 'rs.user_id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->first([
                'o.*',
                'v.vehicle_name',
                'v.vin_no',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'rs.rev',
                'rs.tax_included',
            ]);

        if (!$order) {
            return redirect('/admin/transactions/index')
                ->with('error', 'Order not found.');
        }

        $payments = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $payouts = DB::table('cs_payout_transactions')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $rentalPayments = $payments->where('type', 2);
        $totalRent = (float) $rentalPayments->sum('rent');
        $totalTax = (float) $rentalPayments->sum('tax');
        $revShare = (float) ($order->rev ?? 85);
        $dealerPart = $totalRent > 0 ? sprintf('%0.2f', $totalRent * $revShare / 100) : 0;

        return view('admin.transactions.creditdriver', [
            'order' => $order,
            'payments' => $payments,
            'payouts' => $payouts,
            'totalRent' => $totalRent,
            'totalTax' => $totalTax,
            'dealerPart' => $dealerPart,
            'revShare' => $revShare,
        ]);
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string) $v;
        }

        return $request->input($key);
    }



    private function renderDealerTransferAdjust($id, int $type, string $title)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }
        $order = CsOrder::query()->find($orderId);
        if (!$order) {
            return redirect('/admin/transactions/index');
        }
        $payments = CsPayoutTransaction::query()
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', $type)
            ->where('transfer_id', '!=', '')
            ->orderByDesc('id')
            ->get();
        $total = (float) $payments->sum('amount');

        return view('admin.transactions.adjust_dealer_transfer', [
            'order' => $order,
            'payments' => $payments,
            'total' => $total,
            'type' => $type,
            'title' => $title,
        ]);
    }

    private function adjustField(Request $request, string $field, string $successMsg, string $newKey = 'value'): JsonResponse
    {
        $id = (int) $request->input('CsOrder.id', 0);
        $value = (float) $request->input('CsOrder.' . $newKey, 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        $exists = CsOrder::query()->find($id);
        if (!$exists) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        CsOrder::query()->whereKey($id)->update([$field => $value]);

        return response()->json(['status' => 'success', 'message' => $successMsg]);
    }

    private function zeroFieldByOrderId(Request $request, string $field, ?string $appendField, string $appendText)
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));

        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $order = CsOrder::query()->find($orderId);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $updates = [$field => 0];

        if ($appendField !== null && $appendField !== '' && $appendText !== '') {
            $updates[$appendField] = trim(((string) ($order->{$appendField} ?? '')) . "\n " . $appendText);
        }

        CsOrder::query()->whereKey($orderId)->update($updates);

        return response()->json(['status' => 'success', 'message' => 'Processed successfully']);
    }
}
