<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsTwilioLog;
use App\Models\Legacy\CsTwilioOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageHistoriesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->legacyOwnerUserId();
        $keyword = trim((string)$request->input('Search.keyword', $request->input('keyword', '')));
        $fieldname = trim((string)$request->input('Search.searchin', $request->input('searchin', '')));
        $type = trim((string)$request->input('Search.type', $request->input('type', '')));
        $limit = $this->resolveLimit($request, 'message_histories_limit');

        $q = DB::table('cs_twilio_logs as tl')
            ->leftJoin('cs_twilio_orders as to', 'to.id', '=', 'tl.cs_twilio_order_id')
            ->leftJoin('cs_orders as o', 'o.id', '=', 'to.cs_order_id')
            ->where('tl.user_id', $userId)
            ->select(['tl.*', 'o.increment_id'])
            ->orderByDesc('tl.id');

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($fieldname === 'renter_phone') {
                $q->where('tl.renter_phone', 'like', $like);
            } elseif ($fieldname === 'orderid') {
                $q->where('o.increment_id', 'like', $like);
            } else {
                $q->where(function ($qq) use ($like) {
                    $qq->where('tl.renter_phone', 'like', $like)
                        ->orWhere('o.increment_id', 'like', $like);
                });
            }
        }

        if ($type === '1' || $type === '2') {
            $q->where('tl.type', (int)$type);
        }

        $logs = $q->paginate($limit)->withQueryString();

        return view('message_histories.index', [
            'title_for_layout' => 'Message History',
            'options' => ['renter_phone' => 'Phone #', 'orderid' => 'Booking #'],
            'keyword' => $keyword,
            'type' => $type,
            'fieldname' => $fieldname,
            'CsTwilioLogs' => $logs,
            'limit' => $limit,
        ]);
    }

    public function loadmessagehistory(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }
        $userId = $this->legacyOwnerUserId();

        $twilioOrder = CsTwilioOrder::query()
            ->where('cs_order_id', $orderId)
            ->where('user_id', $userId)
            ->first();

        $logs = collect();
        $phone = '';
        if ($twilioOrder) {
            $logs = CsTwilioLog::query()
                ->where('cs_twilio_order_id', (int)$twilioOrder->id)
                ->orderBy('id')
                ->get();
            $first = $logs->first();
            $phone = (string)($first->renter_phone ?? '');
        }

        return view('message_histories.loadmessagehistory', [
            'logs' => $logs,
            'phone' => $phone,
            'orderid' => base64_encode((string)$orderId),
        ]);
    }

    public function loadnewmessage(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $orderId = $this->decodeId((string)$request->input('orderid', ''));
        if (!$orderId) {
            return response('Invalid order id', 400);
        }

        $twilioOrder = CsTwilioOrder::query()->where('cs_order_id', $orderId)->first();
        $order = CsOrder::query()
            ->leftJoin('users as u', 'u.id', '=', 'cs_orders.renter_id')
            ->where('cs_orders.id', $orderId)
            ->select(['cs_orders.id', 'u.contact_number'])
            ->first();

        return view('message_histories.loadnewmessage', [
            'orderid' => base64_encode((string)$orderId),
            'contact_number' => (string)($order->contact_number ?? ''),
            'twilio_order_id' => (int)($twilioOrder->id ?? 0),
        ]);
    }

    public function sendnewmessage(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }

        $orderId = $this->decodeId((string)$request->input('CsTwilioOrder.cs_order_id', ''));
        $msg = trim((string)$request->input('CsTwilioOrder.details', ''));
        if (!$orderId) {
            return response()->json(['status' => false, 'message' => 'Sorry, related order not found.']);
        }

        $order = CsOrder::query()
            ->leftJoin('users as u', 'u.id', '=', 'cs_orders.renter_id')
            ->where('cs_orders.id', $orderId)
            ->select(['cs_orders.*', 'u.contact_number'])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Sorry, related order not found.']);
        }
        if ($msg === '' || empty($order->contact_number)) {
            return response()->json(['status' => false, 'message' => 'Sorry, required data not passed.']);
        }

        $twilioOrder = CsTwilioOrder::query()->where('cs_order_id', $orderId)->first();
        if (!$twilioOrder) {
            $twilioOrderId = DB::table('cs_twilio_orders')->insertGetId([
                'cs_order_id' => (int)$orderId,
                'user_id' => (int)$order->user_id,
                'renter_phone' => (string)$order->contact_number,
                'status' => 0,
                'created' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
            ]);
        } else {
            $twilioOrderId = (int)$twilioOrder->id;
        }

        DB::table('cs_twilio_logs')->insert([
            'cs_twilio_order_id' => $twilioOrderId,
            'renter_phone' => (string)$order->contact_number,
            'user_id' => (int)$order->user_id,
            'msg' => $msg,
            'type' => 1,
            'created' => now()->toDateTimeString(),
            'modified' => now()->toDateTimeString(),
        ]);

        return response()->json(['status' => true, 'message' => 'Your message is sent successfully.']);
    }

    private function legacyOwnerUserId(): int
    {
        $parent = (int)session('userParentId', 0);

        return $parent !== 0 ? $parent : (int)session('userid', 0);
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
        $limit = (int)session($sessionKey, 25);

        return $limit > 0 ? $limit : 25;
    }
}
