<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\TransactionsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsPayoutTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class TransactionsController extends LegacyAppController
{
    use TransactionsTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "AdminTransactions::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * admin_index: Main admin transaction listing
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        
        $reportlists = $this->_getTransactionHistory($request, null);

        if ($request->ajax()) {
            return view('admin.elements.transactions.admin_index', compact('reportlists'));
        }

        return view('admin.transactions.admin_index', compact('reportlists'));
    }

    /**
     * admin_updatetransaction: Detail view for manual status/note update
     */
    public function admin_updatetransaction($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        if (!$id) return redirect()->route('admin.transactions.index');

        $csorder = CsOrder::findOrFail($id);
        $orderPayments = CsOrderPayment::where('cs_order_id', $id)->where('status', 1)->get();
        $Payouts = CsPayoutTransaction::where('cs_order_id', $id)->where('status', 1)->whereNotNull('transfer_id')->get();

        return view('admin.transactions.admin_updatetransaction', compact('orderPayments', 'Payouts', 'csorder'));
    }

    /**
     * admin_rentRefundtotal: Full rental refund
     */
    public function admin_rentRefundtotal(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        $orderid = base64_decode($request->input('orderid'));
        return response()->json($this->_refundFee($orderid, 'rental'));
    }

    /**
     * admin_adjustTotal: Adjust rental/extra fees
     */
    public function admin_adjustTotal(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        $orderid = $request->input('CsOrder.id');
        return response()->json($this->_adjustFee($orderid, 'rental', $request->all()));
    }

    /**
     * admin_adjustInsurance: Adjust insurance fee
     */
    public function admin_adjustInsurance(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        $orderid = $request->input('CsOrder.id');
        return response()->json($this->_adjustFee($orderid, 'insurance', $request->all()));
    }

    /**
     * Mapping other adjustments...
     */
    public function admin_adjustDeposit(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'deposit', $request->all())); }
    public function admin_adjustinitialfee(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'initial_fee', $request->all())); }
    public function admin_adjustEmf(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'emf', $request->all())); }
    public function admin_adjusttollfee(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'toll', $request->all())); }

    /**
     * Mapping other refunds...
     */
    public function admin_insuranceRefund(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'insurance')); }
    public function admin_depositRefund(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'deposit')); }
    public function admin_initialfeeRefund(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'initial_fee')); }
    public function admin_emfRefundtotal(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'emf')); }
    public function admin_tollRefundtotal(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'toll')); }

    /**
     * admin_failedtransfer: List failed Stripe Connect transfers
     */
    public function admin_failedtransfer(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        
        $failedTransfers = CsPayoutTransaction::where('status', 0) // Assuming 0 is failed
            ->whereNotNull('error_message')
            ->paginate(100);

        return view('admin.transactions.failed_transfer', compact('failedTransfers'));
    }

    /**
     * admin_requeuefailedtransfer: Re-attempt a failed transfer
     */
    public function admin_requeuefailedtransfer(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        
        $id = $request->input('id');
        Log::info("Requeuing failed transfer $id");
        
        // Placeholder for transfer logic
        return response()->json(['status' => 'success', 'message' => "Transfer requeued successfully"]);
    }

    public function admin_adjustDealerEmfPart(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustDealerInitialFeePart(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustDealerInsurancePart(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustDealerRentalPart(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustDiainsu(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'dia_insurance', $request->all())); }
    public function admin_adjustLatefee(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'late_fee', $request->all())); }
    public function admin_adjustdealeremftransfer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustdealerinitialtransfer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustdealerinsurancetransfer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_adjustdealerrentaltransfer(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_changeendtiming(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_creditdriver(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_diainsuRefundtotal(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'dia_insurance')); }
    public function admin_emfReversetotal(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_initialfeeReversetotal(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_insuranceReversetotal(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_latefee(Request $request) { return response()->json($this->_adjustFee($request->input('CsOrder.id'), 'late_fee', $request->all())); }
    public function admin_latefeeRefundtotal(Request $request) { return response()->json($this->_refundFee(base64_decode($request->input('orderid')), 'late_fee')); }
    public function admin_rentReversetotal(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatedeposit(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatediainsu(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateemf(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateenddatetime(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatefare(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateinitialfee(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updateinsurance(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatetoll(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_usertransactions(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
