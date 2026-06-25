<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\TelematicsSubscriptionPayment;
use App\Models\Legacy\TelematicsSubscription;
use App\Models\Legacy\TelematicsPayment;
use Carbon\Carbon;

class TelematicsSubscriptionsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Telematics Subscriptions";
        $sessionKey = 'telematics_subscriptions_limit';

        $date_from = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', ''));
        $status_type = $request->input('Search.status_type', $request->query('status_type', ''));
        $dealer_id = $request->input('Search.dealer_id', $request->query('dealer_id', ''));

        if (!empty($date_from) && empty($date_to)) {
            $date_to = Carbon::now()->format('Y-m-d');
        }

        $statusMap = [
            'cancel' => 2,
            'new' => 0,
            'active' => 1,
        ];

        $query = TelematicsSubscription::with('owner:id,first_name,last_name')->latest('id');

        if (!empty($date_from)) {
            $query->whereDate('created', '>=', Carbon::parse($date_from)->toDateString());
        }

        if (!empty($date_to)) {
            $query->whereDate('created', '<=', Carbon::parse($date_to)->toDateString());
        }

        if (!empty($status_type) && array_key_exists($status_type, $statusMap)) {
            $query->where('status', $statusMap[$status_type]);
        }

        if (!empty($dealer_id)) {
            $query->where('user_id', $dealer_id);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionKey => $limit]);
        } else {
            $limit = session($sessionKey, $this->recordsPerPage);
        }

        $records = $query->paginate($limit);

        if ($request->ajax()) {
            return view('admin.telematics.subscriptions.elements.index', compact(
                'records',
                'date_from',
                'date_to',
                'status_type',
                'dealer_id',
                'limit'
            ));
        }

        return view('admin.telematics.subscriptions.index', compact(
            'title',
            'records',
            'date_from',
            'date_to',
            'status_type',
            'dealer_id',
            'limit'
        ));
    }
    public function paymentretry(Request $request)
    {
        $paymentId = $this->decodeId($request->input('paymentid'));

        if (empty($paymentId)) {
            return response()->json([
                'status' => false,
                'message' => 'invalid request'
            ]);
        }

        $payment = TelematicsPayment::where('status', 0)
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found or seems already paid'
            ]);
        }

        $service = new TelematicsSubscriptionPayment();
        $resp = $service->chargePayment($payment->toArray());
        return response()->json($resp);
    }
    public function payments(Request $request, $subid)
    {
        if (!$request->ajax()) {
            abort(403, 'Unauthorized action.');
        }

        $decodedSubId = $this->decodeId($subid);
        $sessionKey = 'telematics_subscriptions_limit';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionKey => $limit]);
        } else {
            $limit = session($sessionKey, $this->recordsPerPage);
        }

        $records = TelematicsPayment::where('telematics_id', $decodedSubId)
            ->latest('id')
            ->paginate($limit);

        return view('admin.telematics.subscriptions.elements.payments', compact('records', 'subid', 'limit'));
    }
}
