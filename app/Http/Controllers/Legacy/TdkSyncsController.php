<?php

namespace App\Http\Controllers\Legacy;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CakePHP `TdkSyncsController` — TDK integration (partial stub; external TDK API not ported).
 */
class TdkSyncsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private const BASIC_TOKEN = '33b178f9865924e858681463a9d4ac19fd89d979';

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function syncMyVehicle(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();

        DB::table('vehicles as v')
            ->leftJoin('users as u', 'u.id', '=', 'v.user_id')
            ->where('v.user_id', $userId)
            ->get([
                'v.id',
                'v.vehicle_unique_id',
                'v.vin_no',
                'v.plate_number',
                'u.id as owner_id',
                'u.email',
                'u.first_name',
                'u.last_name',
                'u.address',
                'u.city',
                'u.state',
                'u.zip',
            ]);

        return response()->json([
            'status' => true,
            'message' => 'TDK sync not yet ported to Laravel',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function chargepayment(Request $request): JsonResponse
    {
        if ($deny = $this->requireTdkBasicToken($request)) {
            return $deny;
        }

        $this->get_post_data($request);

        return response()->json([
            'status' => false,
            'message' => 'TDK charge payment not yet ported',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function pushIssue(Request $request): JsonResponse
    {
        if ($deny = $this->requireTdkBasicToken($request)) {
            return $deny;
        }

        $postData = $this->get_post_data($request);
        $dataValues = json_decode($postData, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => false, 'message' => 'Invalid json input', 'result' => []];

        $rentalIdRaw = $dataValues['rentalId'] ?? null;
        $customerId = isset($dataValues['customerId']) ? (int) $dataValues['customerId'] : 0;
        $partnercarId = isset($dataValues['partnercarId']) ? (int) $dataValues['partnercarId'] : 0;
        $amount = isset($dataValues['amount']) ? (float) $dataValues['amount'] : 0.0;
        $statusKey = isset($dataValues['status']) ? (string) $dataValues['status'] : '';

        if ($rentalIdRaw === null || $rentalIdRaw === '' || $customerId <= 0 || $partnercarId <= 0 || $amount <= 0) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $return = ['status' => false, 'message' => 'Incorrect booking ID Or Customer ID', 'result' => []];

        $rentalParts = explode('-', (string) $rentalIdRaw);
        $rentalId = isset($rentalParts[0]) && $rentalParts[0] !== '' ? (int) $rentalParts[0] : (int) $rentalIdRaw;

        $booking = DB::table('cs_orders')
            ->where('id', $rentalId)
            ->where('renter_id', $customerId)
            ->orderByDesc('id')
            ->first();

        if (empty($booking)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $statusMap = [
            'Pending' => 4,
            'LiabilityTransferredToDriver' => 5,
            'DirectlyPaidByDriver' => 6,
            'ChargedToDriverByDIA' => 7,
            'PaidByOwner' => 8,
        ];

        $return['message'] = 'Sorry, unknown status value. Please pass correct status';
        if (!isset($statusMap[$statusKey])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $mystatus = $statusMap[$statusKey];
        $violationType = isset($dataValues['violationType']) ? (string) $dataValues['violationType'] : '';
        $note = isset($dataValues['note']) ? (string) $dataValues['note'] : '';

        $already = DB::table('cs_vehicle_issues')
            ->where('vehicle_id', $partnercarId)
            ->where('renter_id', $customerId)
            ->where('cs_order_id', $rentalId)
            ->first();

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $payload = [
            'user_id' => (int) $booking->user_id,
            'renter_id' => (int) $booking->renter_id,
            'vehicle_id' => (int) $booking->vehicle_id,
            'cs_order_id' => (int) $booking->id,
            'amount' => $amount,
            'type' => 4,
            'status' => $mystatus,
            'violationType' => $violationType,
            'maintenance_issue_detail' => $note,
        ];

        if (!empty($already)) {
            DB::table('cs_vehicle_issues')->where('id', (int) $already->id)->update($payload);
        } else {
            $payload['created'] = $now;
            DB::table('cs_vehicle_issues')->insert($payload);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Read raw body and append to daily TDK charge log (Cake `get_post_data`).
     */
    private function get_post_data(Request $request): string
    {
        $postData = (string) $request->getContent();
        $path = storage_path('logs/TDKCharge_' . date('Y-m-d') . '.log');
        $line = "\n" . date('Y-m-d H:i:s') . '=' . $request->fullUrl() . '=:' . $postData;
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);

        return $postData;
    }

    private function requireTdkBasicToken(Request $request): ?JsonResponse
    {
        $auth = (string) $request->header('Authorization', '');
        if ($auth === '') {
            $auth = (string) $request->header('authorization', '');
        }
        $token = trim(str_replace('Basic', '', $auth));
        if ($token !== self::BASIC_TOKEN) {
            return response()->json([
                'status' => false,
                'message' => 'Token does not match',
            ], 400)->header('Content-Type', 'application/json; charset=utf-8');
        }

        return null;
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent > 0 ? $parent : (int) session()->get('userid', 0);
    }
}
