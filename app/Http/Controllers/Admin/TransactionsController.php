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
    public function admin_index(Request $request)
    {
        $keyword = trim((string)$this->searchInput($request, 'keyword'));
        $fieldname = trim((string)$this->searchInput($request, 'searchin'));
        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));
        $statusType = trim((string)$this->searchInput($request, 'status_type'));
        $transactionId = trim((string)$this->searchInput($request, 'transaction_id'));

        if ($request->isMethod('POST') && $request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['admin_transactions_limit' => $lim]);
            }
        }
        $limit = (int)session('admin_transactions_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $query = DB::table('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->select(['o.*', 'renter.first_name as renter_first_name', 'renter.last_name as renter_last_name'])
            ->whereIn('o.status', [2, 3]);

        $hasSearch = $request->isMethod('POST')
            || $request->anyFilled([
                'keyword', 'searchin', 'date_from', 'date_to', 'status_type', 'transaction_id',
                'Search.keyword', 'Search.searchin', 'Search.date_from', 'Search.date_to', 'Search.status_type', 'Search.transaction_id',
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

        $reportlists = $query->orderByDesc('o.id')->paginate($limit)->withQueryString();

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
    public function admin_usertransactions(Request $request, $userid = null, $time = '1 day', $partial = null)
    {
        $uid = (int)($userid ?? $request->input('userid') ?? 0);
        if ($uid <= 0) {
            return response('Invalid user', 400);
        }

        $timeStr = trim((string)($time ?: '1 day'));
        if ($timeStr === '') {
            $timeStr = '1 day';
        }

        $bookingid = (string)$request->input('bookingid', '');
        $currency = (string)$request->input('currency', 'USD');

        $dateFrom = Carbon::now()->modify('-' . $timeStr)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $lim = (int)session('admin_transactions_limit', 50);
        if ($lim < 1) {
            $lim = 50;
        }

        $basePayments = DB::table('cs_order_payments as p')
            ->join('cs_orders as o', 'o.id', '=', 'p.cs_order_id')
            ->where('o.renter_id', $uid)
            ->where('p.status', 1)
            ->whereDate('p.created', '>=', $dateFrom)
            ->whereDate('p.created', '<=', $dateTo);

        $total = (float)(clone $basePayments)->sum('p.amount');
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

        $partialOnly = $partial !== null && $partial !== '' && (string)$partial === '1';

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
    public function admin_updatetransaction(Request $request, $id = null)
    {
        $orderId = $this->decodeId((string)$id);
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

        $payments = LegacyCsOrderPayment::query()
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        return view('admin.transactions.updatetransaction', [
            'order' => $order,
            'payments' => $payments,
        ]);
    }

    public function admin_updatefare($id)
    {
        return $this->renderOrderAdjustView($id, 'rent', 'Update Fare');
    }

    public function admin_updateinsurance($id)
    {
        return $this->renderOrderAdjustView($id, 'insurance_amt', 'Update Insurance');
    }

    public function admin_updateinitialfee($id)
    {
        return $this->renderOrderAdjustView($id, 'initial_fee', 'Update Initial Fee');
    }

    public function admin_updateemf($id)
    {
        return $this->renderOrderAdjustView($id, 'extra_mileage_fee', 'Update EMF');
    }

    public function admin_updatediainsu($id)
    {
        return $this->renderOrderAdjustView($id, 'dia_insu', 'Update DIA Insurance');
    }

    public function admin_latefee($id)
    {
        return $this->renderOrderAdjustView($id, 'lateness_fee', 'Update Late Fee');
    }

    public function admin_updatetoll($id)
    {
        return $this->renderOrderAdjustView($id, 'toll', 'Update Toll');
    }

    public function admin_updateenddatetime(Request $request)
    {
        $id = $this->decodeId((string)$request->input('booking_id', ''));
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $order = LegacyCsOrder::query()->find($id, ['id', 'end_timing', 'timezone']);
        if (!$order) {
            return response('Booking not found', 404);
        }

        return view('admin.transactions.update_endtime', ['order' => $order]);
    }

    public function admin_changeendtiming(Request $request): JsonResponse
    {
        $id = (int)$request->input('CsOrder.id', 0);
        $endTiming = (string)$request->input('CsOrder.end_timing', '');
        if ($id <= 0 || $endTiming === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        LegacyCsOrder::query()->whereKey($id)->update(['end_timing' => $endTiming]);

        return response()->json(['status' => true, 'message' => 'Booking has been updated successfully']);
    }

    public function admin_rentRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'paid_amount', 'details', 'Full Rent Refunded');
    }

    public function admin_adjustTotal(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'rent', 'Rent adjusted successfully');
    }

    public function admin_adjustInsurance(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'insurance_amt', 'Insurance adjusted successfully', 'newtotal');
    }

    public function admin_insuranceRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'insurance_amt', 'details', 'Insurance Refunded');
    }

    public function admin_adjustDeposit(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'deposit', 'Deposit adjusted successfully', 'newtotal');
    }

    public function admin_depositRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'deposit', 'details', 'Deposit Refunded');
    }

    public function admin_adjustinitialfee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'initial_fee', 'Initial fee adjusted successfully', 'newtotal');
    }

    public function admin_initialfeeRefund(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'initial_fee', null, '');
    }

    public function admin_emfRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'extra_mileage_fee', null, '');
    }

    public function admin_adjustEmf(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'extra_mileage_fee', 'EMF adjusted successfully');
    }

    public function admin_diainsuRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'dia_insu', null, '');
    }

    public function admin_adjustDiainsu(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'dia_insu', 'DIA insurance adjusted successfully');
    }

    public function admin_latefeeRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'lateness_fee', null, '');
    }

    public function admin_adjustLatefee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'lateness_fee', 'Late fee adjusted successfully', 'newtotal');
    }

    public function admin_tollRefundtotal(Request $request): JsonResponse
    {
        return $this->zeroFieldByOrderId($request, 'toll', null, '');
    }

    public function admin_adjusttollfee(Request $request): JsonResponse
    {
        return $this->adjustField($request, 'toll', 'Toll adjusted successfully');
    }

    public function admin_failedtransfer(Request $request)
    {
        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));

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

        $limit = (int)session('admin_transactions_limit', 50);
        $reportlists = $q->orderByDesc('p.id')->paginate($limit)->withQueryString();

        return view('admin.transactions.failedtransfer', [
            'reportlists' => $reportlists,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    public function admin_requeuefailedtransfer(Request $request): JsonResponse
    {
        $id = (int)$request->input('id', 0);
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

    public function admin_adjustdealerrentaltransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 2, 'Adjust Dealer Rental Transfer');
    }

    public function admin_adjustdealerinitialtransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 3, 'Adjust Dealer Initial Fee Transfer');
    }

    public function admin_adjustdealerinsurancetransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 4, 'Adjust Dealer Insurance Transfer');
    }

    public function admin_adjustdealeremftransfer($id)
    {
        return $this->renderDealerTransferAdjust($id, 16, 'Adjust Dealer EMF Transfer');
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
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

    private function renderOrderAdjustView($id, string $field, string $title)
    {
        $orderId = $this->decodeId((string)$id);
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
        $orderId = $this->decodeId((string)$id);
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
        $total = (float)$payments->sum('amount');

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
        $id = (int)$request->input('CsOrder.id', 0);
        $value = (float)$request->input('CsOrder.' . $newKey, 0);
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
        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        $order = LegacyCsOrder::query()->find($orderId);
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }
        $updates = [$field => 0];
        if ($appendField !== null && $appendField !== '' && $appendText !== '') {
            $updates[$appendField] = trim(((string)($order->{$appendField} ?? '')) . "\n " . $appendText);
        }
        LegacyCsOrder::query()->whereKey($orderId)->update($updates);

        return response()->json(['status' => 'success', 'message' => 'Processed successfully']);
    }
}
