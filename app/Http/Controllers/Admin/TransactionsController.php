<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\CsWallet;
use App\Services\Legacy\PaymentProcessor;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class TransactionsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $title = 'Transactions';
        $sessionLimitKey = "transactions_limit";
        $fieldname = $request->input('Search.searchin', $request->input('searchin', ''));
        $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
        $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->input('date_to', ''));
        $status_type = $request->input('Search.status_type', $request->input('status_type', ''));
        $transaction_id = $request->input('Search.transaction_id', $request->input('transaction_id', ''));

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
    public function updatefare($id = null)
    {
        $decodedId = $this->decodeId($id);
        $csorder = null;

        if (!empty($decodedId)) {
            $csorder = CsOrder::findOrFail($decodedId);
        }

        return view('admin.transactions.updatefare', compact('csorder'));
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
    public function adjustTotal(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if ($request->has('CsOrder.id')) {
            $reqData = $request->input('CsOrder');

            $csorder = CsOrder::select('id', 'user_id', 'parent_id', 'renter_id', 'paid_amount', 'note', 'details', 'tax', 'vehicle_id', 'dia_fee')
                ->find($reqData['id']);

            if ($csorder) {
                $lookupId = !empty($csorder->parent_id) ? $csorder->parent_id : $csorder->id;
                $bookingRentalChoice = OrderDepositRule::select('id', 'tax')
                    ->where('cs_order_id', $lookupId)
                    ->first();

                $paymentProcessor = new PaymentProcessor();
                $newPaidAmount = $reqData['rent'] + $reqData['damage_fee'] + $reqData['uncleanness_fee'];
                $newDiaFee = (new DepositRule())->calculateDIAFee($newPaidAmount, $csorder->user_id);
                $taxRate = $bookingRentalChoice ? $bookingRentalChoice->tax : 0;
                $newTax = number_format(((($newPaidAmount + $newDiaFee) * $taxRate) / 100), 2, '.', '');
                $finalNewPaidAmount = number_format(($newPaidAmount + $newTax + $newDiaFee), 2, '.', '');
                $totalPaid = CsOrderPayment::getTotalPaidRental($csorder->id);

                if ($totalPaid > $finalNewPaidAmount && ($totalPaid - $finalNewPaidAmount) >= 1) {
                    $refundableTax = $csorder->tax - $newTax;
                    $refundableDiaFee = $csorder->dia_fee - $newDiaFee;

                    $responsePayload = $paymentProcessor->refundBalanceAmount(
                        ($totalPaid - $finalNewPaidAmount),
                        $csorder->id,
                        $refundableTax,
                        $refundableDiaFee,
                        $csorder->renter_id
                    );

                    if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                        $csorder->update([
                            'tax' => $newTax,
                            'dia_fee' => $newDiaFee,
                            'paid_amount' => $finalNewPaidAmount,
                            'rent' => $reqData['rent'],
                            'damage_fee' => $reqData['damage_fee'],
                            'uncleanness_fee' => $reqData['uncleanness_fee']
                        ]);
                    }
                } elseif ($totalPaid < $finalNewPaidAmount && ($finalNewPaidAmount - $totalPaid) >= 1) {
                    $balanceTax = ($newTax > $csorder->tax) ? ($csorder->tax - $newTax) : ($newTax - $csorder->tax);
                    $balanceDiaFee = ($newDiaFee > $csorder->dia_fee) ? ($csorder->dia_fee - $newDiaFee) : ($newDiaFee - $csorder->dia_fee);

                    $responsePayload = $paymentProcessor->chargeBalanceAmount(
                        ($finalNewPaidAmount - $totalPaid),
                        $csorder->renter_id,
                        $csorder->user_id,
                        $csorder->id,
                        $balanceTax,
                        $balanceDiaFee
                    );

                    if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                        $csorder->update([
                            'tax' => $newTax,
                            'dia_fee' => $newDiaFee,
                            'paid_amount' => $finalNewPaidAmount,
                            'rent' => $reqData['rent'],
                            'damage_fee' => $reqData['damage_fee'],
                            'uncleanness_fee' => $reqData['uncleanness_fee']
                        ]);
                    }
                } else {
                    $responsePayload = [
                        'status' => 'success',
                        'message' => 'Sorry, there is no need to process the payment'
                    ];
                }
            }
        }

        return response()->json($responsePayload);
    }
    public function updateinsurance($id)
    {
        $decodedId = $this->decodeId($id);
        $csorder = null;

        if (!empty($decodedId)) {
            $csorder = CsOrder::findOrFail($decodedId);
        }

        return view('admin.transactions.updateinsurance', compact('csorder'));
    }
    public function adjustInsurance(Request $request)
    {
        $orderId = $request->input('CsOrder.id');

        if (empty($orderId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, order not found'
            ]);
        }

        $csorder = CsOrder::select('id', 'parent_id', 'user_id', 'renter_id', 'insurance_amt', 'insu_status', 'note', 'details', 'start_datetime', 'currency')->find($orderId);

        if (!$csorder) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, order not found']);
        }

        $lookupId = !empty($csorder->parent_id) ? $csorder->parent_id : $csorder->id;
        $bookingRentalChoice = OrderDepositRule::select('insurance_payer')
            ->where('cs_order_id', $lookupId)
            ->first();

        if (!$bookingRentalChoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, Renter booking rent preference data not found',
                'result' => []
            ]);
        }

        $newPaidAmount = number_format($request->input('CsOrder.newtotal'), 2, '.', '');
        $paymentProcessor = new PaymentProcessor();

        if ($csorder->insurance_amt > $newPaidAmount && ($csorder->insurance_amt - $newPaidAmount) >= 1) {
            $responsePayload = $paymentProcessor->refundBalanceInsurance(($csorder->insurance_amt - $newPaidAmount), $csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update(['insurance_amt' => $newPaidAmount]);
            }
        } elseif ($csorder->insurance_amt < $newPaidAmount && ($newPaidAmount - $csorder->insurance_amt) >= 1) {
            $responsePayload = $paymentProcessor->chargeBalanceInsurance(
                ($newPaidAmount - $csorder->insurance_amt),
                $csorder->toArray(),
                $bookingRentalChoice->toArray()
            );

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update(['insurance_amt' => $newPaidAmount]);
            }
        } else {
            $responsePayload = [
                'status' => 'success',
                'message' => 'Sorry, there is no need to process the payment'
            ];
        }

        return response()->json($responsePayload);

    }
    public function insuranceRefund(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (!empty($decodedId)) {
            $csorder = CsOrder::select('id', 'insurance_amt', 'note', 'details')->find($decodedId);

            if ($csorder) {
                $paymentProcessor = new PaymentProcessor();
                $responsePayload = $paymentProcessor->insuranceRefund($csorder->toArray());

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->insurance_amt = 0;
                    $csorder->details = $csorder->details . "\n Insurance Refunded";
                    $csorder->save();
                }
            }
        }

        return response()->json($responsePayload);
    }
    public function updatedeposit($id = null)
    {
        return redirect()->back();
        //not allowed now

        /* Unreachable code preserved from original logic:
        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            $csorder = CsOrder::where('id', $decodedId)
                ->where('deposit_type', 'C')
                ->first();

            if ($csorder) {
                return view('admin.transactions.updatedeposit', compact('csorder'));
            }
        }

        return redirect()->back();
        */
    }
    public function adjustDeposit(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if ($request->has('CsOrder.id')) {
            $reqData = $request->input('CsOrder');

            $csorder = CsOrder::select('id', 'user_id', 'renter_id', 'deposit', 'dpa_status', 'note', 'details', 'deposit_auth')
                ->where('id', $reqData['id'])
                ->where('deposit_type', 'C')
                ->first();

            if ($csorder) {
                $newPaidAmount = number_format($reqData['newtotal'], 2, '.', '');
                $paymentProcessor = new PaymentProcessor();

                if ($csorder->deposit > $newPaidAmount && ($csorder->deposit - $newPaidAmount) >= 1 && $csorder->dpa_status == 1) {
                    $transactionId = $csorder->deposit_auth;
                    $responsePayload = $paymentProcessor->refundBalanceDeposit(($csorder->deposit - $newPaidAmount), $csorder->toArray());

                    if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                        $csorder->update([
                            'deposit' => $newPaidAmount,
                            'details' => $csorder->details . "\n Partial Deposit Refunded from transactiond ID: " . $transactionId
                        ]);
                    }
                } elseif ($csorder->deposit < $newPaidAmount && ($newPaidAmount - $csorder->deposit) >= 1) {
                    $responsePayload = $paymentProcessor->chargeBalanceDeposit(($newPaidAmount - $csorder->deposit), $csorder->renter_id, $csorder->id);

                    if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                        $csorder->update(['deposit' => $newPaidAmount]);
                    }
                } else {
                    $responsePayload = [
                        'status' => 'success',
                        'message' => 'Sorry, there is no need to process the payment'
                    ];
                }
            }
        }

        return response()->json($responsePayload);
    }
    public function depositRefund(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);

        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (!empty($decodedId)) {
            $csorder = CsOrder::select('id', 'deposit', 'note', 'details')
                ->where('id', $decodedId)
                ->where('deposit_type', 'C')
                ->first();

            if ($csorder) {
                $paymentProcessor = new PaymentProcessor();
                $responsePayload = $paymentProcessor->depositRefund($csorder->toArray());

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->deposit = 0;
                    $csorder->details = $csorder->details . "\n Insurance Refunded";
                    $csorder->save();
                }
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustdealerrentaltransfer($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back();
        }

        $csorder = CsOrder::find($decodedId);

        if (!$csorder) {
            return redirect()->back();
        }

        $baseTransactionsQuery = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 2)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '');

        $orderPayments = $baseTransactionsQuery->get();
        $payoutsTotal = $baseTransactionsQuery->sum('amount');
        $transactionIds = $orderPayments->mapWithKeys(function ($item) {
            return [$item->id => "{$item->amount}-> {$item->transfer_id}"];
        })->all();

        return view('admin.transactions.adjustdealerrentaltransfer', compact(
            'orderPayments',
            'payoutsTotal',
            'csorder',
            'transactionIds'
        ));

    }
    public function rentReversetotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => ''
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $orderTransfers = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 2)
            ->get();

        $paymentProcessor = new PaymentProcessor();

        foreach ($orderTransfers as $orderTransfer) {
            $result = $paymentProcessor->DealerFullReverse($orderTransfer->toArray());

            if (isset($result['status']) && $result['status'] === 'success') {
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} reversed.";
            } else {
                $errorMessage = $result['message'] ?? 'Unknown Error';
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} not reversed due to error '{$errorMessage}'";
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustDealerRentalPart(Request $request)
    {
        $responsePayload = ['status' => 'error', 'message' => 'Sorry, order not found'];

        // if ($request->has('CsOrder.id')) {
        //     $orderId = $request->input('CsOrder.id');
        //     $transferredAmount = CsPayoutTransaction::where('cs_order_id', $orderId)
        //         ->where('status', 1)
        //         ->where('type', 2)
        //         ->whereNotNull('transfer_id')
        //         ->where('transfer_id', '!=', '')
        //         ->sum('amount');

        //     $newDealerAmount = $request->input('CsOrder.dealerpart');

        //     if ($transferredAmount > $newDealerAmount) {
        //         $reversableAmount = $transferredAmount - $newDealerAmount;
        //         $paymentProcessor = new PaymentProcessor();

        //         $responsePayload = $paymentProcessor->DealerPartialReverse($reversableAmount, $orderId, 2);
        //     } else {
        //         $responsePayload = [
        //             'status' => 'error',
        //             'message' => 'Sorry, Adjustable amount must be less than transferred amount.'
        //         ];
        //     }
        // }

        return response()->json($responsePayload);
    }
    public function adjustdealerinitialtransfer($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back();
        }

        $csorder = CsOrder::find($decodedId);

        if (!$csorder) {
            return redirect()->back();
        }

        $baseQuery = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 3)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '');
        $orderPayments = $baseQuery->get();
        $payoutsTotal = $baseQuery->sum('amount');
        $transactionIds = $orderPayments->mapWithKeys(function ($item) {
            return [$item->id => "{$item->amount}-> {$item->transfer_id}"];
        })->all();

        return view('admin.transactions.adjustdealerinitialtransfer', compact(
            'orderPayments',
            'payoutsTotal',
            'csorder',
            'transactionIds'
        ));
    }
    public function initialfeeReversetotal(Request $request)
    {

        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry something went wrong'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $paymentProcessor = new PaymentProcessor();
        $orderTransfers = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 3)
            ->get();

        $responsePayload['message'] = '';

        foreach ($orderTransfers as $orderTransfer) {
            $result = $paymentProcessor->DealerFullReverse($orderTransfer->toArray());

            if (isset($result['status']) && $result['status'] === 'success') {
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} reversed.";
            } else {
                $errorMessage = $result['message'] ?? 'Unknown Error';
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} not reversed due to error '{$errorMessage}'";
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustDealerInitialFeePart(Request $request)
    {
        if (!$request->has('CsOrder.id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, order not found'
            ]);
        }

        $orderId = $request->input('CsOrder.id');
        $transferredAmount = CsPayoutTransaction::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 3)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '')
            ->sum('amount');
        $newDealerAmount = $request->input('CsOrder.dealerpart');

        if ($transferredAmount > $newDealerAmount) {
            $reversableAmount = $transferredAmount - $newDealerAmount;
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->DealerPartialReverse($reversableAmount, $orderId, 3);
        } else {
            $responsePayload = [
                'status' => 'error',
                'message' => 'Sorry, Adjustable amount must be less than transferred amount.'
            ];
        }

        return response()->json($responsePayload);
    }
    public function adjustdealerinsurancetransfer($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back();
        }

        $csorder = CsOrder::find($decodedId);

        if (!$csorder) {
            return redirect()->back();
        }

        $baseQuery = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 3)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '');

        $orderPayments = $baseQuery->get();
        $payoutsTotal = $baseQuery->sum('amount');
        $transactionIds = $orderPayments->mapWithKeys(function ($item) {
            return [$item->id => "{$item->amount}-> {$item->transfer_id}"];
        })->all();

        return view('admin.transactions.adjustdealerinsurancetransfer', compact(
            'orderPayments',
            'payoutsTotal',
            'csorder',
            'transactionIds'
        ));
    }
    public function insuranceReversetotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);

        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry something went wrong'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $paymentProcessor = new PaymentProcessor();
        $orderTransfers = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 4)
            ->get();

        $responsePayload['message'] = '';

        foreach ($orderTransfers as $orderTransfer) {
            $result = $paymentProcessor->DealerFullReverse($orderTransfer->toArray());

            if (isset($result['status']) && $result['status'] === 'success') {
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} reversed.";
            } else {
                $errorMessage = $result['message'] ?? 'Unknown Error';
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} not reversed due to error '{$errorMessage}'";
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustDealerInsurancePart(Request $request)
    {
        if (!$request->has('CsOrder.id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, order not found'
            ]);
        }

        $orderId = $request->input('CsOrder.id');
        $transferredAmount = CsPayoutTransaction::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 4)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '')
            ->sum('amount');
        $newDealerAmount = $request->input('CsOrder.dealerpart');

        if ($transferredAmount > $newDealerAmount) {
            $reversableAmount = $transferredAmount - $newDealerAmount;
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->DealerPartialReverse($reversableAmount, $orderId, 3);
        } else {
            $responsePayload = [
                'status' => 'error',
                'message' => 'Sorry, Adjustable amount must be less than transferred amount.'
            ];
        }

        return response()->json($responsePayload);
    }
    public function creditdriver(Request $request, $id = null)
    {
        return redirect('admin/transactions/index')->with('error', 'Sorry, we dont support this feature now.');

        /* ========================================================================
        REFACTORED BACKUP CODE (IF RENTER/DRIVER WALLET FEATURE IS EVER RESTORED)
        ========================================================================

        $decodedId = $this->decodeId($id);
        if (empty($decodedId)) {
            return redirect('admin/transactions/index')->with('error', 'Sorry, something went wrong.');
        }

        $csOrderPaymentSummary = CsOrderPayment::getTotalRentalTax($decodedId);

        if ($request->isMethod('post') && $request->has('CsOrder')) {
            $reqData = $request->input('CsOrder');
            $creditNote = !empty($reqData['note']) ? $reqData['note'] : 'credit given by dealer';
            $successMessage = '';

            $csorder = CsOrder::select('cs_orders.id', 'cs_orders.rent', 'cs_orders.tax', 'cs_orders.renter_id', 'cs_orders.credit_amt', 'rev_settings.rev', 'rev_settings.tax_included')
                ->leftJoin('rev_settings', 'rev_settings.user_id', '=', 'cs_orders.user_id')
                ->find($decodedId);

            $revShare = !empty($csorder->rev) ? $csorder->rev : 85;
            $tmpTotal = [];
            $dealerPart = !empty($csOrderPaymentSummary['rent']) ? sprintf('%0.2f', $csOrderPaymentSummary['rent'] * $revShare / 100) : 0;

            if ($reqData['credit'] < $dealerPart) {
                $driverCredit = $reqData['credit'];
                $orderPayments = CsOrderPayment::where('cs_order_id', $decodedId)
                    ->where('status', 1)
                    ->where('type', 2)
                    ->get();

                $paymentProcessor = new PaymentProcessor();

                foreach ($orderPayments as $orderPayment) {
                    $rentPart = sprintf('%0.2f', ($orderPayment->rent * $revShare / 100));

                    if (!$driverCredit) { 
                        break; 
                    }

                    $amount = ($rentPart <= $driverCredit) ? $rentPart : $driverCredit;
                    $driverCredit = $driverCredit - $amount;

                    if ($orderPayment->cs_transfer) {
                        $orderTransfer = CsPayoutTransaction::where('cs_order_id', $decodedId)
                            ->where('status', 1)
                            ->where('cs_payment_id', $orderPayment->id)
                            ->first();

                        $result = $paymentProcessor->DealerReverseForCredit($orderTransfer->toArray(), $amount);

                        if (isset($result['status']) && $result['status'] === 'success') {
                            $tmpTotal[] = ['amt' => $amount, 'transaction_id' => $orderTransfer->transaction_id];

                            if ($amount < $orderTransfer->amount) {
                                $orderPayment->update(['rent' => ($orderPayment->rent - $amount)]);
                                $orderTransfer->update(['amount' => ($orderTransfer->amount - $amount)]);
                            } else {
                                $orderPayment->update(['status' => 3]);
                                $orderTransfer->update(['status' => 2]);
                            }
                        } else {
                            $successMessage = $result['message'];
                        }
                    } else {
                        if ($amount < $orderPayment->rent) {
                            $orderPayment->update(['rent' => ($orderPayment->rent - $amount)]);
                        } else {
                            $orderPayment->update(['status' => 3]);
                        }
                        $tmpTotal[] = ['amt' => $amount, 'transaction_id' => $orderPayment->transaction_id];
                    }
                }
            } elseif ($reqData['credit'] == $dealerPart) {
                $orderPayments = CsOrderPayment::where('cs_order_id', $decodedId)
                    ->where('status', 1)
                    ->where('type', 2)
                    ->get();

                $paymentProcessor = new PaymentProcessor();

                foreach ($orderPayments as $orderPayment) {
                    if ($orderPayment->cs_transfer) {
                        $orderTransfer = CsPayoutTransaction::where('cs_order_id', $decodedId)
                            ->where('status', 1)
                            ->where('cs_payment_id', $orderPayment->id)
                            ->first();

                        $result = $paymentProcessor->DealerFullReverse($orderTransfer->toArray());

                        if (isset($result['status']) && $result['status'] === 'success') {
                            $tmpTotal[] = ['amt' => $orderTransfer->amount, 'transaction_id' => $orderTransfer->transaction_id];
                            $orderPayment->update(['status' => 3]);
                        } else {
                            $successMessage = $result['message'];
                        }
                    } else {
                        $orderPayment->update(['status' => 3]);
                        $tmpTotal[] = ['amt' => sprintf('%0.2f', ($orderPayment->rent * $revShare / 100)), 'transaction_id' => $orderPayment->transaction_id];
                    }
                }
            } else {
                return redirect()->back()->with('error', 'Please enter amount to credit');
            }

            $totalCredit = 0;
            foreach ($tmpTotal as $tmp) {
                if (!$tmp['amt']) continue;
                $totalCredit += $tmp['amt'];
                CsWallet::addBalance($tmp['amt'], $csorder->renter_id, $tmp['transaction_id'], $creditNote, $csorder->id);
            }

            $csorder->update(['credit_amt' => ($totalCredit + $csorder->credit_amt)]);

            if (!empty($successMessage)) {
                return redirect()->back()->with('error', $successMessage);
            }

            return redirect('admin/transactions/update_transaction', base64_encode($decodedId))
                ->with('success', 'Your request processed successfully.');
        }

        $csorder = CsOrder::select('cs_orders.*', 'rev_settings.rev')
            ->leftJoin('rev_settings', 'rev_settings.user_id', '=', 'cs_orders.user_id')
            ->find($decodedId);

        return view('admin.transactions.creditdriver', compact('csOrderPaymentSummary', 'csorder'));
        */
    }

    public function updateinitialfee($id)
    {
        $decodedId = $this->decodeId($id);
        $csorder = null;

        if (!empty($decodedId)) {
            $csorder = CsOrder::findOrFail($decodedId);
        }

        return view('admin.transactions.updateinitialfee', compact('csorder'));
    }
    public function adjustinitialfee(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($request->input('CsOrder.id'))) {
            return response()->json($responsePayload);
        }

        $orderId = $request->input('CsOrder.id');
        $csorder = CsOrder::select('id', 'parent_id', 'user_id', 'renter_id', 'initial_fee', 'initial_fee_tax', 'infee_status', 'note', 'details')
            ->find($orderId);

        if ($csorder) {
            $newPaidAmount = number_format($request->input('CsOrder.newtotal'), 2, '.', '');
            $lookupId = !empty($csorder->parent_id) ? $csorder->parent_id : $csorder->id;
            $bookingRentalChoice = OrderDepositRule::select('id', 'tax')
                ->where('cs_order_id', $lookupId)
                ->first();

            $taxRate = $bookingRentalChoice ? $bookingRentalChoice->tax : 0;
            $newTax = sprintf('%0.2f', (($newPaidAmount * $taxRate) / 100));

            if ($csorder->initial_fee > $newPaidAmount && ($csorder->initial_fee - $newPaidAmount) >= 1 && $csorder->infee_status == 1) {
                $paymentProcessor = new PaymentProcessor();

                $responsePayload = $paymentProcessor->refundBalanceInitialfee(
                    ($csorder->initial_fee - $newPaidAmount),
                    ($csorder->initial_fee_tax - $newTax),
                    $csorder->id,
                    $csorder->renter_id
                );

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update([
                        'initial_fee' => $newPaidAmount,
                        'initial_fee_tax' => $newTax
                    ]);
                }
            } elseif ($csorder->initial_fee < $newPaidAmount && ($newPaidAmount - $csorder->initial_fee) >= 1) {
                $responsePayload = [
                    'status' => 'error',
                    'message' => 'Sorry, you cant charge more initial fee.'
                ];
            } else {
                $responsePayload = [
                    'status' => 'success',
                    'message' => 'Sorry, there is no need to process the payment'
                ];
            }
        }

        return response()->json($responsePayload);
    }
    public function initialfeeRefund(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $csorder = CsOrder::select('id', 'initial_fee', 'initial_fee_tax', 'note', 'details', 'renter_id', 'user_id', 'infee_status')
            ->find($decodedId);

        if ($csorder) {
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->initialfeeRefund($csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update([
                    'initial_fee' => 0,
                    'initial_fee_tax' => 0
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function updateenddatetime(Request $request)
    {
        $bookingId = $this->decodeId(trim($request->input('booking_id')));
        $csorder = null;

        if (!empty($bookingId)) {
            $csorder = CsOrder::select('id', 'end_timing', 'timezone')->find($bookingId);
        }

        return view('admin.transactions.updateenddatetime', compact('csorder'));
    }
    public function changeendtiming(Request $request)
    {
        $responsePayload = [
            'status' => true,
            'message' => 'Booking has been updated successfully'
        ];
        $inputEndTiming = $request->input('CsOrder.end_timing');
        $orderId = $request->input('CsOrder.id');

        if (!empty($inputEndTiming) && !empty($orderId)) {
            $csorder = CsOrder::select('id', 'end_timing', 'timezone', 'adjusted_actual_time')->find($orderId);

            if ($csorder) {
                $adjustedActualTime = !empty($csorder->adjusted_actual_time) ? $csorder->adjusted_actual_time : $csorder->end_timing;
                $serverTime = Carbon::createFromFormat('Y-m-d H:i:s', $inputEndTiming, $csorder->timezone)
                    ->setTimezone(config('app.timezone'))
                    ->toDateTimeString();

                $csorder->update([
                    'adjusted_actual_time' => $adjustedActualTime,
                    'end_timing' => $serverTime
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function updateemf($id)
    {
        $decodedId = $this->decodeId($id);
        $csorder = null;
        $payment = null;

        if (!empty($decodedId)) {
            $csorder = CsOrder::findOrFail($decodedId);
            $payment = CsOrderPayment::getTotalEmf($decodedId);
        }

        return view('admin.transactions.updateemf', compact('csorder', 'payment'));
    }
    public function emfRefundtotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $csorder = CsOrder::select('id', 'extra_mileage_fee', 'emf_tax', 'renter_id', 'note', 'details', 'user_id')->find($decodedId);

        if ($csorder) {
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->emfRefundtotal($csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update([
                    'extra_mileage_fee' => 0,
                    'emf_tax' => 0,
                    'details' => $csorder->details . "\n Full Rent Refunded"
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustEmf(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($request->input('CsOrder.id'))) {
            return response()->json($responsePayload);
        }

        $orderId = $request->input('CsOrder.id');
        $csorder = CsOrder::select('id', 'user_id', 'parent_id', 'renter_id', 'extra_mileage_fee', 'note', 'details', 'emf_tax', 'vehicle_id', 'currency', 'start_datetime')->find($orderId);

        if ($csorder) {
            $lookupId = !empty($csorder->parent_id) ? $csorder->parent_id : $csorder->id;
            $bookingRentalChoice = OrderDepositRule::select('id', 'tax')
                ->where('cs_order_id', $lookupId)
                ->first();

            $payment = CsOrderPayment::getTotalEmf($orderId);
            $paymentProcessor = new PaymentProcessor();

            $newPaidAmount = $request->input('CsOrder.extra_mileage_fee');
            $taxRate = $bookingRentalChoice ? $bookingRentalChoice->tax : 0;
            $newTax = number_format((($newPaidAmount * $taxRate) / 100), 2, '.', '');

            if ($payment['emf'] > $newPaidAmount || ($payment['tax'] - $newTax) >= 1) {
                $refundableTax = $payment['tax'] - $newTax;
                $responsePayload = $paymentProcessor->refundBalanceEmf(($payment['emf'] - $newPaidAmount), $csorder->id, $refundableTax, $csorder->renter_id);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update([
                        'extra_mileage_fee' => $newPaidAmount,
                        'emf_tax' => $newTax
                    ]);
                }
            } elseif ($payment['emf'] < $newPaidAmount && ($newPaidAmount - $payment['emf']) >= 1) {
                $balanceTax = ($newTax > $payment['tax']) ? ($payment['tax'] - $newTax) : ($newTax - $payment['tax']);
                $responsePayload = $paymentProcessor->chargeBalanceEmf(($newPaidAmount - $payment['emf']), $csorder->toArray(), $balanceTax);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update([
                        'extra_mileage_fee' => $newPaidAmount,
                        'emf_tax' => $newTax
                    ]);
                }
            } else {
                $responsePayload = [
                    'status' => 'success',
                    'message' => 'Sorry, there is no need to process the payment'
                ];
            }
        }

        return response()->json($responsePayload);
    }
    public function updatediainsu($id)
    {
        $decodedId = $this->decodeId($id);
        $csorder = null;

        if (!empty($decodedId)) {
            $csorder = CsOrder::findOrFail($decodedId);
        }

        return view('admin.transactions.updatediainsu', compact('csorder'));
    }
    public function diainsuRefundtotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $csorder = CsOrder::select('id', 'dia_insu', 'renter_id', 'user_id', 'note', 'details')->find($decodedId);

        if ($csorder) {
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->diainsuRefundtotal($csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update([
                    'dia_insu' => 0,
                    'details' => $csorder->details . "\n Full EMF Insu. Refunded"
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustDiainsu(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($request->input('CsOrder.id'))) {
            return response()->json($responsePayload);
        }

        $orderId = $request->input('CsOrder.id');
        $csorder = CsOrder::select('id', 'user_id', 'renter_id', 'dia_insu', 'note', 'details', 'vehicle_id', 'start_datetime', 'currency', 'parent_id')
            ->find($orderId);

        if ($csorder) {
            $lookupId = !empty($csorder->parent_id) ? $csorder->parent_id : $csorder->id;
            $bookingRentalChoice = OrderDepositRule::select('insurance_payer')
                ->where('cs_order_id', $lookupId)
                ->first();

            if (!$bookingRentalChoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sorry, Renter booking rent preference data not found',
                    'result' => []
                ]);
            }

            $csorderArray = $csorder->toArray();
            $csorderArray['insurance_payer'] = $bookingRentalChoice->insurance_payer;

            $newPaidAmount = sprintf('%0.2f', $request->input('CsOrder.dia_insu'));
            $paymentProcessor = new PaymentProcessor();

            if ($csorder->dia_insu > $newPaidAmount && ($csorder->dia_insu - $newPaidAmount) >= 1) {
                $responsePayload = $paymentProcessor->refundBalanceDiainsu(($csorder->dia_insu - $newPaidAmount), $csorder->id, $csorder->renter_id);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update(['dia_insu' => $newPaidAmount]);
                }
            } elseif ($csorder->dia_insu < $newPaidAmount && ($newPaidAmount - $csorder->dia_insu) >= 1) {
                $responsePayload = $paymentProcessor->chargeBalanceDiainsu(($newPaidAmount - $csorder->dia_insu), $csorderArray);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update(['dia_insu' => $newPaidAmount]);
                }
            } else {
                $responsePayload = [
                    'status' => 'success',
                    'message' => 'Sorry, there is no need to process the payment'
                ];
            }
        }

        return response()->json($responsePayload);
    }
    public function usertransactions(Request $request, $driverId = null, $time = '1 day', $returnRaw = false)
    {
        $userId = $driverId;
        $dateFrom = null;
        $dateTo = null;
        $sessionLimitName = "order_transactions_limit";

        if ($request->has('userid')) {
            $userId = strip_tags($request->input('userid'));
        }

        if ($request->has('time')) {
            $time = $request->input('time');
        }

        if (!empty($userId)) {
            $dateFrom = Carbon::now()->sub($time)->format('Y-m-d');
            $dateTo = Carbon::now()->format('Y-m-d');
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom, config('app.timezone'))->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo, config('app.timezone'))->toDateTimeString();
        }


        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitName => $limit]);
        } else {
            $limit = session($sessionLimitName, $this->recordsPerPage);
        }

        $baseQuery = CsOrderPayment::join('cs_orders', 'cs_orders.id', '=', 'cs_order_payments.cs_order_id')
            ->where('cs_orders.renter_id', $userId)
            ->whereNotNull('cs_orders.id')
            ->where('cs_order_payments.status', 1);

        if (!empty($dateFrom)) {
            $baseQuery->where('cs_order_payments.created', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $baseQuery->where('cs_order_payments.created', '<=', $dateTo);
        }

        $reportLists = $baseQuery->select(
            'cs_order_payments.*',
            'cs_orders.increment_id',
            'cs_orders.start_datetime',
            'cs_orders.end_datetime',
            'cs_orders.timezone'
        )
            ->orderBy('cs_order_payments.id', 'DESC')
            ->paginate($limit);

        $total = $baseQuery->sum('cs_order_payments.amount');
        $wallet = CsWallet::where('user_id', $userId)->select('balance')->first();
        $walletBalance = $wallet ? $wallet->balance : 0;

        $viewData = [
            'bookingid' => $request->input('bookingid'),
            'currency' => $request->input('currency'),
            'userid' => $userId,
            'time' => $time,
            'reportlists' => $reportLists,
            'total' => $total,
            'wallet_balance' => $walletBalance,
            'limit' => $limit
        ];

        if ((empty($driverId) || $returnRaw) && $request->ajax()) {
            return view('admin.transactions.elements.transactions.user_transactions', $viewData);
        }

        return view('admin.transactions.usertransactions', $viewData);
    }
    public function adjustdealeremftransfer($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back();
        }

        $csorder = CsOrder::find($decodedId);

        if (!$csorder) {
            return redirect()->back();
        }

        $baseQuery = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 16)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '');

        $orderPayments = $baseQuery->get();
        $payoutsTotal = $baseQuery->sum('amount');
        $transactionIds = $orderPayments->mapWithKeys(function ($item) {
            return [$item->id => "{$item->amount}-> {$item->transfer_id}"];
        })->all();

        return view('admin.transactions.adjustdealeremftransfer', compact(
            'orderPayments',
            'payoutsTotal',
            'csorder',
            'transactionIds'
        ));
    }
    public function emfReversetotal(Request $request)
    {

        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Something went wrong'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $orderTransfers = CsPayoutTransaction::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 16)
            ->get();

        $paymentProcessor = new PaymentProcessor();
        $responsePayload['message'] = '';

        foreach ($orderTransfers as $orderTransfer) {
            $result = $paymentProcessor->DealerFullReverse($orderTransfer->toArray());

            if (isset($result['status']) && $result['status'] === 'success') {
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} reversed.";
            } else {
                $errorMessage = $result['message'] ?? 'Unknown Error';
                $responsePayload['message'] .= "\n{$orderTransfer->transfer_id} => {$orderTransfer->amount} not reversed due to error '{$errorMessage}'";
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustDealerEmfPart(Request $request)
    {
        if (!$request->has('CsOrder.id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, order not found'
            ]);
        }

        $orderId = $request->input('CsOrder.id');
        $transferredAmount = CsPayoutTransaction::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', 16)
            ->whereNotNull('transfer_id')
            ->where('transfer_id', '!=', '')
            ->sum('amount');

        $newDealerAmount = $request->input('CsOrder.dealerpart');

        if ($transferredAmount > $newDealerAmount) {
            $reversableAmount = $transferredAmount - $newDealerAmount;
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->DealerPartialReverse($reversableAmount, $orderId, 16);
        } else {
            $responsePayload = [
                'status' => 'error',
                'message' => 'Sorry, Adjustable amount must be less than transferred amount.'
            ];
        }

        return response()->json($responsePayload);
    }
    public function latefee($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back()->with('error', 'Sorry, not a valid request');
        }

        $csorder = CsOrder::findOrFail($decodedId);
        $totalPaid = CsOrderPayment::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 19)
            ->sum('amount');

        return view('admin.transactions.latefee', compact('totalPaid', 'csorder'));

    }
    public function latefeeRefundtotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $csorder = CsOrder::select('id', 'lateness_fee', 'renter_id', 'note', 'user_id', 'details')->find($decodedId);

        if ($csorder) {
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->lateFeeRefundtotal($csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update([
                    'lateness_fee' => 0,
                    'details' => $csorder->details . "\n Full latefee Refunded"
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function adjustLatefee(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (!$request->has('CsOrder.id')) {
            return response()->json($responsePayload);
        }

        $orderId = $request->input('CsOrder.id');
        $csorder = CsOrder::select('id', 'user_id', 'renter_id', 'currency')->find($orderId);

        if ($csorder) {
            $totalPaid = CsOrderPayment::where('cs_order_id', $orderId)
                ->where('status', 1)
                ->where('type', 19)
                ->sum('amount');

            $newAmount = $request->input('CsOrder.newtotal');
            $paymentProcessor = new PaymentProcessor();

            if ($totalPaid > $newAmount) {
                $responsePayload = $paymentProcessor->refundBalanceLateFee(($totalPaid - $newAmount), $csorder->id, $csorder->renter_id);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update(['lateness_fee' => $newAmount]);
                }
            } elseif ($totalPaid < $newAmount) {
                $responsePayload = [
                    'status' => 'error',
                    'message' => 'Sorry, we cant charge late fee now'
                ];
            } else {
                $responsePayload = [
                    'status' => 'success',
                    'message' => 'Sorry, there is no need to process the payment'
                ];
            }
        }

        return response()->json($responsePayload);
    }
    public function updatetoll($id)
    {
        $decodedId = $this->decodeId($id);

        if (empty($decodedId)) {
            return redirect()->back()->with('error', 'Sorry, not a valid request');
        }

        $csorder = CsOrder::findOrFail($decodedId);
        $totalPaid = CsOrderPayment::where('cs_order_id', $decodedId)
            ->where('status', 1)
            ->where('type', 6)
            ->sum('amount');

        return view('admin.transactions.updatetoll', compact('totalPaid', 'csorder'));
    }
    public function tollRefundtotal(Request $request)
    {
        $orderId = $request->input('orderid');
        $decodedId = $this->decodeId($orderId);
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (empty($decodedId)) {
            return response()->json($responsePayload);
        }

        $csorder = CsOrder::select('id', 'toll', 'renter_id', 'note', 'user_id', 'details')->find($decodedId);

        if ($csorder) {
            $paymentProcessor = new PaymentProcessor();
            $responsePayload = $paymentProcessor->tollRefundtotal($csorder->toArray());

            if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                $csorder->update([
                    'toll' => 0,
                    'details' => $csorder->details . "\n Full toll Refunded"
                ]);
            }
        }

        return response()->json($responsePayload);
    }
    public function adjusttollfee(Request $request)
    {
        $responsePayload = [
            'status' => 'error',
            'message' => 'Sorry, order not found'
        ];

        if (!$request->has('CsOrder.id')) {
            return response()->json($responsePayload);
        }

        $orderId = $request->input('CsOrder.id');
        $csorder = CsOrder::select('id', 'user_id', 'renter_id', 'currency')->find($orderId);

        if ($csorder) {
            $totalPaid = CsOrderPayment::where('cs_order_id', $orderId)
                ->where('status', 1)
                ->where('type', 6)
                ->sum('amount');

            $newAmount = $request->input('CsOrder.toll');
            $paymentProcessor = new PaymentProcessor();

            if ($totalPaid > $newAmount) {
                $responsePayload = $paymentProcessor->refundBalanceToll(($totalPaid - $newAmount), $csorder->id, $csorder->renter_id);

                if (isset($responsePayload['status']) && $responsePayload['status'] === 'success') {
                    $csorder->update([
                        'toll' => $newAmount,
                        'user_id' => $csorder->user_id
                    ]);
                }
            } elseif ($totalPaid < $newAmount) {
                $responsePayload = [
                    'status' => 'error',
                    'message' => 'Sorry, we cant charge toll fee now'
                ];
            } else {
                $responsePayload = [
                    'status' => 'success',
                    'message' => 'Sorry, there is no need to process the payment'
                ];
            }
        }

        return response()->json($responsePayload);
    }
    public function failedtransfer(Request $request)
    {
        $dateFrom = $request->input('Search.date_from', $request->query('date_from'));
        $dateTo = $request->input('Search.date_to', $request->query('date_to'));
        $query = CsOrderPayment::join('cs_orders', 'cs_orders.id', '=', 'cs_order_payments.cs_order_id')
            ->where('cs_order_payments.cs_transfer', 2)
            ->where('cs_order_payments.status', 1);

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        if (!empty($dateFrom)) {
            $serverDateFrom = Carbon::parse($dateFrom, config('app.timezone'))->startOfDay()->toDateTimeString();
            $query->where('cs_order_payments.created', '>=', $serverDateFrom);
        }

        if (!empty($dateTo)) {
            $serverDateTo = Carbon::parse($dateTo, config('app.timezone'))->endOfDay()->toDateTimeString();
            $query->where('cs_order_payments.created', '<=', $serverDateTo);
        }

        $sessionLimitKey = "failed_transfers_limit";
        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, 10);
        }

        $reportLists = $query->select(
            'cs_order_payments.*',
            'cs_orders.increment_id',
            'cs_orders.start_datetime',
            'cs_orders.end_datetime',
            'cs_orders.timezone'
        )
            ->orderBy('cs_order_payments.id', 'DESC')
            ->paginate($limit);

        $viewData = [
            'title' => 'Failed Transfer',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
            'reportlists' => $reportLists
        ];

        if ($request->ajax()) {
            return view('admin.transactions.elements.failedtransfer', $viewData);
        }

        return view('admin.transactions.failedtransfer', $viewData);

    }
    public function requeuefailedtransfer(Request $request)
    {
        if (!$request->has('id')) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, order not found'
            ]);
        }

        $paymentId = $request->input('id');
        $orderPayment = CsOrderPayment::where('id', $paymentId)
            ->where('status', 1)
            ->where('cs_transfer', 2)
            ->first();

        if (empty($orderPayment)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, respective record already processed or we couldnt find.'
            ]);
        }

        $orderPayment->update(['cs_transfer' => 0]);

        return response()->json(['status' => true, 'message' => 'Processed successfully']);
    }
}
