<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PaymentLogsTrait;
use Illuminate\Http\Request;

class PaymentLogsController extends LegacyAppController
{
    use PaymentLogsTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderId = (int)base64_decode(trim($request->input('orderid', '')));

        ['logs' => $logs] = $this->fetchPaymentLogs($orderId, 200, false);

        return view('admin.payment_logs.index', [
            'logs'             => $logs,
            'paymentTypeValue' => [],
        ]);
    }
}
