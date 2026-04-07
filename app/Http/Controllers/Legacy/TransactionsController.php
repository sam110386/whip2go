<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\TransactionsTrait;
use App\Models\Legacy\CsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TransactionsController extends LegacyAppController
{
    use TransactionsTrait;

    /**
     * index: List transaction history
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $reportlists = $this->_getTransactionHistory($request, $userId);

        if ($request->ajax()) {
            return view('legacy.elements.transactions.index', compact('reportlists'));
        }

        return view('legacy.transactions.index', compact('reportlists'));
    }

    /**
     * details: View transaction details
     */
    public function details($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $id = base64_decode($id);
        $userId = Session::get('userParentId') ?: Session::get('userid');

        $csorder = CsOrder::where('cs_orders.id', $id)
            ->where('cs_orders.user_id', $userId)
            ->leftJoin('users as User', 'User.id', '=', 'cs_orders.renter_id')
            ->select('cs_orders.*', 'User.first_name', 'User.last_name')
            ->first();

        if (!$csorder) return redirect()->route('legacy.transactions.index');

        return view('legacy.transactions.details', compact('csorder'));
    }

    /**
     * updatetransaction: Edit view for manual status/note update
     */
    public function updatetransaction($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        return $this->details($id); // In legacy, updatetransaction and details share the view
    }

    /**
     * rentRefundtotal: Refund rental fee
     */
    public function rentRefundtotal(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        return response()->json($this->_refundFee($orderid, 'rental', $userId));
    }

    /**
     * adjustTotal: Adjust rental fee
     */
    public function adjustTotal(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        
        $orderid = $request->input('CsOrder.id');
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        return response()->json($this->_adjustFee($orderid, 'rental', $request->all(), $userId));
    }

    /**
     * adjustInsurance: Adjust insurance fee
     */
    public function adjustInsurance(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['status' => 'error', 'message' => "Unauthorized"], 403);
        
        $orderid = $request->input('CsOrder.id');
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        return response()->json($this->_adjustFee($orderid, 'insurance', $request->all(), $userId));
    }
}
