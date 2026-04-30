<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\TelematicsSubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\TelematicsSubscription;
use App\Models\Legacy\TelematicsPayment;

class TelematicsSubscriptionsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $status_type = $date_from = $date_to = $dealerid = '';
        $conditions = [];

        if ($request->has('Search') || $request->hasAny(['date_from', 'date_to', 'status_type', 'dealer_id'])) {
            $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
            $date_to = $request->input('Search.date_to', $request->input('date_to', ''));
            $status_type = $request->input('Search.status_type', $request->input('status_type', ''));
            $dealerid = $request->input('Search.dealer_id', $request->input('dealer_id', ''));

            if (!empty($date_from) && empty($date_to)) {
                $date_to = date('Y-m-d');
            }
        }

        $query = TelematicsSubscription::query()->from('telematics_subscriptions as TelematicsSubscription')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'TelematicsSubscription.user_id')
            ->select('TelematicsSubscription.*', 'Owner.first_name', 'Owner.last_name');

        if (!empty($date_from)) {
            $query->where('TelematicsSubscription.created', '>=', Carbon::parse($date_from)->format('Y-m-d'));
        }
        if (!empty($date_to)) {
            $query->where('TelematicsSubscription.created', '<=', Carbon::parse($date_to)->format('Y-m-d'));
        }
        if (!empty($status_type)) {
            $statusMap = ['cancel' => 2, 'new' => 0, 'active' => 1];
            if (isset($statusMap[$status_type])) {
                $query->where('TelematicsSubscription.status', $statusMap[$status_type]);
            }
        }
        if (!empty($dealerid)) {
            $query->where('TelematicsSubscription.user_id', $dealerid);
        }

        $sessLimitKey = 'telematics_subscriptions_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'id';
        }

        $records = $query->orderBy($sort === 'id' ? 'TelematicsSubscription.id' : $sort, $direction)
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'records' => $records,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'status_type' => $status_type,
            'dealerid' => $dealerid,
            'title_for_layout' => 'Telematics Subscriptions',
        ];

        if ($request->ajax()) {
            return response()->view('admin.telematics.subscriptions._table', $viewData);
        }

        return view('admin.telematics.subscriptions.index', $viewData);
    }

    public function paymentretry(Request $request): JsonResponse
    {
        $paymentid = $this->decodeId($request->input('paymentid'));
        $uInfo = ['status' => false, 'message' => 'invalid request'];

        if (empty($paymentid)) {
            return response()->json($uInfo);
        }

        $Payment = TelematicsPayment::where('status', 0)
            ->where('id', $paymentid)
            ->first();

        if (empty($Payment)) {
            $uInfo['message'] = 'Payment not found or seems already paid';
            return response()->json($uInfo);
        }

        $service = new TelematicsSubscriptionPayment();
        $resp = $service->chargePayment((array) $Payment);

        return response()->json($resp);
    }

    public function payments(Request $request, $subid)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $decodedSubid = $this->decodeId($subid);
        $conditions = [
            ['TelematicsPayment.telematics_id', '=', $decodedSubid],
        ];

        $sessLimitKey = 'telematics_payments_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $records = TelematicsPayment::query()->from('telematics_payments as TelematicsPayment')
            ->where($conditions)
            ->orderByDesc('TelematicsPayment.id')
            ->paginate($limit)
            ->withQueryString();

        return response()->view('admin.telematics.subscriptions._payments', [
            'records' => $records,
            'subid' => $subid,
        ]);
    }
}
