<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PaymentLogsTrait;
use Illuminate\Http\Request;

class PaymentLogsController extends LegacyAppController
{
    use PaymentLogsTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $orderId = (int)base64_decode(trim($request->input('orderid', '')));

        ['logs' => $logs] = $this->fetchPaymentLogs($orderId, 200, false);

        return view('cloud.payment_logs.index', [
            'logs'             => $logs,
            'paymentTypeValue' => [],
        ]);
    }
}
