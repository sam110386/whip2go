<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsPaymentLog;
use Illuminate\Http\Request;

class PaymentLogsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));
        $logs = [];

        if (!empty($orderid)) {
            $logs = CsPaymentLog::where('cs_order_id', $orderid)
                ->latest('id')
                ->limit(200)
                ->get();
        }

        $paymentTypeValue = $this->commonService->getpaymentTypeValue(true, false, false);
        return view('payment_logs.index', compact('orderid', 'logs', 'paymentTypeValue'));
    }
}

