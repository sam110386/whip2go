<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\PaymentLogsTrait;
use Illuminate\Http\Request;

class PaymentLogsController extends LegacyAppController
{
    use PaymentLogsTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function resolveUserId(): int
    {
        $userId = (int)session('userParentId', 0);
        return $userId === 0 ? (int)session('userid', 0) : $userId;
    }

    // ─── index (AJAX modal — order siblings + logs) ───────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = (int)base64_decode(trim($request->input('orderid', '')));
        $userId  = $this->resolveUserId();

        ['logs' => $logs, 'allSiblings' => $allSiblings] = $this->fetchPaymentLogs(
            $orderId, 200, true, $userId
        );

        return view('legacy.payment_logs.index', [
            'logs'             => $logs,
            'allSiblings'      => $allSiblings,
            'orderid'          => $orderId,
            'paymentTypeValue' => $this->getPaymentTypeValue(),
        ]);
    }

    // ─── all (full paginated log view for a booking) ──────────────────────────
    public function all(Request $request, $orderid)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = (int)base64_decode($orderid);
        $userId  = $this->resolveUserId();

        $namedData  = $request->query();
        $searchData = $request->input('Search', []);

        $dateFrom = $namedData['date_from'] ?? $searchData['date_from'] ?? '';
        $dateTo   = $namedData['date_to']   ?? $searchData['date_to']   ?? '';
        $status   = $namedData['status']    ?? $searchData['status']    ?? '';

        $sessionLimitKey  = 'PaymentLogs_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $search = compact('dateFrom', 'dateTo', 'status');
        $logs   = $this->buildPaymentLogQuery($orderId, $userId, [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'status'    => $status !== '' ? $status : null,
        ]);

        $allSiblings = \App\Models\Legacy\CsOrder::where('user_id', $userId)
            ->whereRaw("(id = ? OR parent_id = ?)", [$orderId, $orderId])
            ->select('id', 'increment_id', 'start_datetime', 'end_datetime', 'timezone')
            ->get()->keyBy('id');

        return view('legacy.payment_logs.all', [
            'title_for_layout' => 'Payment Transaction Logs',
            'logs'             => $logs,
            'allSiblings'      => $allSiblings,
            'orderid'          => $orderId,
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'status'           => $status,
            'paymentTypeValue' => $this->getPaymentTypeValue(),
        ]);
    }

    private function getPaymentTypeValue(): array
    {
        // Stub — wire up to Common helper or config
        return [];
    }
}
