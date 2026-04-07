<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\MessageHistoriesTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsTwilioLog;
use App\Models\Legacy\CsTwilioOrder;
use Illuminate\Http\Request;

class MessageHistoriesController extends LegacyAppController
{
    use MessageHistoriesTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function resolveUserId(): int
    {
        $userId = (int)session('userParentId', 0);
        return $userId === 0 ? (int)session('userid', 0) : $userId;
    }

    // ─── index ────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId     = $this->resolveUserId();
        $searchData = $request->input('Search', []);
        $namedData  = $request->query();

        $fieldname = $namedData['searchin'] ?? $searchData['searchin'] ?? '';
        $value     = $namedData['keyword']  ?? $searchData['keyword']  ?? '';
        $type      = $namedData['type']     ?? $searchData['type']     ?? '';

        $query = CsTwilioLog::query()
            ->from('cs_twilio_logs as CsTwilioLog')
            ->leftJoin('cs_twilio_orders as CsTwilioOrder', 'CsTwilioOrder.id', '=', 'CsTwilioLog.cs_twilio_order_id')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsTwilioOrder.cs_order_id')
            ->select('CsTwilioLog.*', 'CsOrder.increment_id')
            ->where('CsTwilioLog.user_id', $userId);

        if ($value !== '') {
            $v      = strip_tags($value);
            $fname  = empty($fieldname) ? 'All' : $fieldname;

            if ($fname === 'All') {
                $query->where(function ($q) use ($v) {
                    $q->where('CsTwilioLog.renter_phone', 'LIKE', "%{$v}%")
                      ->orWhere('CsOrder.increment_id',    'LIKE', "%{$v}%");
                });
            } elseif ($fname === 'renter_phone') {
                $query->where('CsTwilioLog.renter_phone', 'LIKE', "%{$v}%");
            } elseif ($fname === 'orderid') {
                $query->where('CsOrder.increment_id', 'LIKE', "%{$v}%");
            }
        }

        if ((string)$type === '1') {
            $query->where('CsTwilioLog.type', 1);
        } elseif ((string)$type === '2') {
            $query->where('CsTwilioLog.type', 2);
        }

        $sessionLimitKey  = 'MessageHistories_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $CsTwilioLogs = $query->orderBy('CsTwilioLog.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.message_histories.index', [
            'title_for_layout' => 'Message History',
            'keyword'          => $value,
            'type'             => $type,
            'fieldname'        => $fieldname,
            'options'          => ['renter_phone' => 'Phone #', 'orderid' => 'Booking #'],
            'CsTwilioLogs'     => $CsTwilioLogs,
        ]);
    }

    // ─── loadmessagehistory ───────────────────────────────────────────────────
    public function loadmessagehistory(Request $request)
    {
        $userId  = $this->resolveUserId();
        $orderId = (int)base64_decode(trim($request->input('orderid', '')));

        $CsTwilioOrder = $this->getTwilioOrder($orderId, $userId);

        return view('legacy.message_histories.load_message_history', [
            'CsTwilioOrder' => $CsTwilioOrder,
            'orderid'       => base64_encode((string)$orderId),
        ]);
    }

    // ─── loadnewmessage ───────────────────────────────────────────────────────
    public function loadnewmessage(Request $request)
    {
        $orderId = (int)base64_decode(trim($request->input('orderid', '')));
        $userId  = $this->resolveUserId();

        $CsTwilioOrder = $this->getTwilioOrder($orderId);
        $CsOrder       = $this->getOrderWithRenter($orderId);

        return view('legacy.message_histories.load_new_message', [
            'CsTwilioOrder' => $CsTwilioOrder,
            'CsOrder'       => $CsOrder,
            'orderid'       => base64_encode((string)$orderId),
        ]);
    }

    // ─── sendnewmessage ───────────────────────────────────────────────────────
    public function sendnewmessage(Request $request)
    {
        return $this->processSendNewMessage($request);
    }
}
