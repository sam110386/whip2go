<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsPaymentLog;
use Illuminate\Http\Request;

class PaymentLogsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        $logs = collect();
        $allSiblings = [];

        if ($orderId) {
            $allSiblings = CsOrder::query()
                ->where(function ($q) use ($orderId) {
                    $q->where('id', $orderId)->orWhere('parent_id', $orderId);
                })
                ->orderByDesc('id')
                ->limit(200)
                ->pluck('increment_id', 'id')
                ->mapWithKeys(static fn ($incrementId, $id) => [(int)$id => (string)$incrementId])
                ->all();

            $ids = array_keys($allSiblings);
            if ($ids !== []) {
                $logs = CsPaymentLog::query()
                    ->whereIn('cs_order_id', $ids)
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get();
            }
        }

        return response()->view('payment_logs.index', [
            'logs' => $logs,
            'paymentTypeValue' => self::paymentTypeLabels(),
            'allSiblings' => $allSiblings,
            'orderid' => $orderId,
        ]);
    }

    public function all(Request $request, $orderid = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string)$orderid);
        if (!$orderId) {
            return redirect('/dashboard/index')->with('error', 'Invalid order id');
        }

        $userId = (int)session('userParentId', 0);
        if ($userId === 0) {
            $userId = (int)session('userid', 0);
        }

        $dateFrom = trim((string)$request->input('Search.date_from', $request->input('date_from', '')));
        $dateTo = trim((string)$request->input('Search.date_to', $request->input('date_to', '')));
        $status = trim((string)$request->input('Search.status', $request->input('status', '')));
        $limit = $this->resolveLimit($request, 'payment_logs_limit');

        $allSiblings = CsOrder::query()
            ->where('user_id', $userId)
            ->where(function ($q) use ($orderId) {
                $q->where('id', $orderId)->orWhere('parent_id', $orderId);
            })
            ->get(['id', 'increment_id', 'start_datetime', 'end_datetime', 'timezone'])
            ->keyBy('id');

        $q = CsPaymentLog::query()->orderByDesc('id');
        if ($allSiblings->isEmpty()) {
            $q->whereRaw('1=0');
        } else {
            $q->whereIn('cs_order_id', $allSiblings->keys()->all());
        }

        if ($dateFrom !== '') {
            $q->whereDate('created', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $q->whereDate('created', '<=', $dateTo);
        }
        if ($status !== '') {
            $q->where('status', (int)$status);
        }

        $logs = $q->paginate($limit)->withQueryString();

        return view('payment_logs.all', [
            'logs' => $logs,
            'paymentTypeValue' => self::paymentTypeLabels(),
            'allSiblings' => $allSiblings,
            'orderid' => $orderId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $status,
            'title_for_layout' => 'Payment Transaction Logs',
            'limit' => $limit,
        ]);
    }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session([$sessionKey => $lim]);
            }
        }
        $limit = (int)session($sessionKey, 50);

        return $limit > 0 ? $limit : 50;
    }

    private static function paymentTypeLabels(): array
    {
        return [
            1 => 'Deposit Payment',
            2 => 'Deposit Retry',
            3 => 'Deposit Refund',
            4 => 'Deposit Partial Refund',
            5 => 'Rental Payment',
            6 => 'Rental Retry',
            7 => 'Rental Refund',
            8 => 'Rental Partial Refund',
            9 => 'Rental Partial Charge',
            10 => 'Insurance Payment',
            11 => 'Insurance Refund',
            12 => 'Insurance Retry',
            13 => 'Insurance Partial Refund',
            14 => 'Insurance Partial Charge',
            15 => 'Initial Fee Payment',
            16 => 'Initial Fee Refund',
            17 => 'Initial Fee Retry',
            18 => 'Initial Fee Partial Refund',
            19 => 'Initial Fee Partial Charge',
            20 => 'Deposit Partial Charge',
            21 => 'Deposit Dealer Transfer',
            22 => 'Toll Payment',
            23 => 'Toll Retry',
            24 => 'Customer Balance Charge',
            25 => 'TDK Violation charges',
            26 => 'Insurance EMF',
            27 => 'Extra Mile fee',
            28 => 'Extra Mile fee Retry',
            29 => 'Partial Payment',
            30 => 'Extenion Request Payment',
            31 => 'Refund EMF',
            32 => 'Refund Insurance EMF',
            33 => 'Wallet Refund',
            34 => 'DIA Telematics Charge',
            35 => 'Uber Booking Charge',
            36 => 'Uber Booking Refund',
            37 => 'Toll Refund',
        ];
    }
}
