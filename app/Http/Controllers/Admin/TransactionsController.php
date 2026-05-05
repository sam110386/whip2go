<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrder as LegacyCsOrder;
use App\Models\Legacy\CsOrderPayment as LegacyCsOrderPayment;
use App\Models\Legacy\CsPayoutTransaction as LegacyCsPayoutTransaction;
use App\Models\Legacy\CsWallet as LegacyCsWallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /**
     * Cake TransactionsController::admin_index — completed/canceled orders (status 2,3) with filters.
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $this->searchInput($request, 'keyword'));
        $fieldname = trim((string) $this->searchInput($request, 'searchin'));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $statusType = trim((string) $this->searchInput($request, 'status_type'));
        $transactionId = trim((string) $this->searchInput($request, 'transaction_id'));

        if ($request->isMethod('POST') && $request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['admin_transactions_limit' => $lim]);
            }
        }
        $limit = (int) session('admin_transactions_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $query = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->select(['o.*', 'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name'])
            ->whereIn('o.status', [2, 3]);

        $hasSearch = $request->isMethod('POST')
            || $request->anyFilled([
                'keyword',
                'searchin',
                'date_from',
                'date_to',
                'status_type',
                'transaction_id',
                'Search.keyword',
                'Search.searchin',
                'Search.date_from',
                'Search.date_to',
                'Search.status_type',
                'Search.transaction_id',
            ]);

        if ($hasSearch) {
            if ($keyword !== '' && $fieldname === '2') {
                $query->where('o.vehicle_name', $keyword);
            }
            if ($keyword !== '' && $fieldname === '3') {
                $query->where('o.increment_id', $keyword);
            }
            if ($dateFrom !== '') {
                try {
                    $df = Carbon::parse($dateFrom)->startOfDay();
                    $query->where('o.start_datetime', '>=', $df->toDateTimeString());
                } catch (\Throwable $e) {
                }
            }
            if ($dateTo !== '') {
                try {
                    $dt = Carbon::parse($dateTo)->endOfDay();
                    $query->where('o.end_datetime', '<=', $dt->toDateTimeString());
                } catch (\Throwable $e) {
                }
            }
            if ($statusType === 'cancel') {
                $query->where('o.status', 2);
            } elseif ($statusType === 'complete') {
                $query->where('o.status', 3);
            } elseif ($statusType === 'incomplete') {
                $query->whereIn('o.status', [0, 1]);
            }
        }

        if ($transactionId !== '') {
            $query->whereExists(function ($q) use ($transactionId) {
                $q->selectRaw('1')
                    ->from('cs_order_payments as op')
                    ->whereColumn('op.cs_order_id', 'o.id')
                    ->where('op.transaction_id', $transactionId);
            });
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');

        $reportlists = $query->orderBy($sort, $direction)->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.transactions.listing', [
                'reportlists' => $reportlists,
            ]);
        }

        return view('admin.transactions.index', [
            'reportlists' => $reportlists,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_type' => $statusType,
            'transaction_id' => $transactionId,
            'limit' => $limit,
        ]);
    }

    /**
     * Cake TransactionsController::admin_usertransactions — driver payment lines (modal + partial refresh).
     *
     * @param  mixed  $partial  Path segment "1" = return table partial only for #transsactionlisting
     */
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
        $walletBalance = LegacyCsWallet::query()->where('user_id', $uid)->value('balance') ?? 0;

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

    /**
     * Read-only order + successful payments (Cake admin_updatetransaction subset).
     */
    /**
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function updatetransaction(Request $request, $id = null)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }

        $order = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->where('o.id', $orderId)
            ->select(['o.*', 'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name'])
            ->first();

        if (!$order) {
            return redirect('/admin/transactions/index');
        }

        $orderPayments = LegacyCsOrderPayment::query()
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $payouts = LegacyCsPayoutTransaction::query()
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('transfer_id', '!=', '')
            ->get();

        // Emulate CakePHP Hash::combine for transferedPayouts
        $transferedPayouts = $payouts->groupBy('type');

        // Emulate CakePHP Hash::combine for transactionIds
        // format: type => [ "amount -> transaction_id", ... ]
        $transactionIds = $orderPayments->groupBy('type')->map(function ($group) {
            return $group->map(function ($p) {
                return $p->amount . '-> ' . $p->transaction_id;
            })->toArray();
        });

        return view('admin.transactions.updatetransaction', [
            'csorder' => (array) $order,
            'order' => $order,
            'payments' => $orderPayments,
            'transferedPayouts' => $transferedPayouts,
            'transactionIds' => $transactionIds,
        ]);
    }


    public function updatefare($id)
    {
        return $this->renderOrderAdjustView($id, 'rent', 'Update Fare');
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

    public function updateenddatetime(Request $request)
    {
        $id = $this->decodeId((string) $request->input('booking_id', ''));
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $order = LegacyCsOrder::query()->find($id, ['id', 'end_timing', 'timezone']);
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
        LegacyCsOrder::query()->whereKey($id)->update(['end_timing' => $endTiming]);

        return response()->json(['status' => true, 'message' => 'Booking has been updated successfully']);
    }

    public function rentRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'paid_amount', 'details', 'Full Rent Refunded');
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
        $row = LegacyCsOrderPayment::query()
            ->whereKey($id)
            ->where('status', 1)
            ->where('cs_transfer', 2)
            ->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Sorry, respective record already processed or we couldnt find.']);
        }
        LegacyCsOrderPayment::query()->whereKey($id)->update(['cs_transfer' => 0]);

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

    private function renderOrderAdjustView($id, string $field, string $title)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }
        $order = LegacyCsOrder::query()->find($orderId);
        if (!$order) {
            return redirect('/admin/transactions/index');
        }

        return view('admin.transactions.adjust', [
            'order' => $order,
            'field' => $field,
            'title' => $title,
        ]);
    }

    private function renderDealerTransferAdjust($id, int $type, string $title)
    {
        $orderId = $this->decodeId((string) $id);
        if (!$orderId) {
            return redirect('/admin/transactions/index');
        }
        $order = LegacyCsOrder::query()->find($orderId);
        if (!$order) {
            return redirect('/admin/transactions/index');
        }
        $payments = LegacyCsPayoutTransaction::query()
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
        $exists = LegacyCsOrder::query()->find($id);
        if (!$exists) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        LegacyCsOrder::query()->whereKey($id)->update([$field => $value]);

        return response()->json(['status' => 'success', 'message' => $successMsg]);
    }

    private function zeroFieldByOrderId(Request $request, string $field, ?string $appendField, string $appendText): JsonResponse
    {
        $orderId = $this->decodeId((string) $request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        $order = LegacyCsOrder::query()->find($orderId);
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        $updates = [$field => 0];
        if ($appendField !== null && $appendField !== '' && $appendText !== '') {
            $updates[$appendField] = trim(((string) ($order->{$appendField} ?? '')) . "\n " . $appendText);
        }
        LegacyCsOrder::query()->whereKey($orderId)->update($updates);

        return response()->json(['status' => 'success', 'message' => 'Processed successfully']);
    }
}
