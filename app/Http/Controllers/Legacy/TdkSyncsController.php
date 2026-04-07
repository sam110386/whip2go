<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TdkSyncsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private string $tdkToken = '33b178f9865924e858681463a9d4ac19fd89d979';

    public function syncMyVehicle()
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return response()->json([
            'status' => false,
            'message' => 'TDK sync component is not migrated yet.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function get_post_data(Request $request): string
    {
        $postData = (string) $request->getContent();
        if ($postData === '') {
            return '';
        }

        $logfile = storage_path('logs/TDKCharge_' . date('Y-m-d') . '.log');
        @error_log("\n" . date('Y-m-d H:i:s') . '=' . $request->path() . '=:' . $postData, 3, $logfile);

        return $postData;
    }

    public function chargepayment(Request $request)
    {
        $token = trim(str_replace('Basic', '', (string) $request->header('Authorization', '')));
        if ($token !== $this->tdkToken) {
            return response()->json(['status' => false, 'message' => 'Token does not match'])
                ->setStatusCode(400)
                ->header('Content-Type', 'application/json; charset=utf-8');
        }

        $return = ['status' => false, 'message' => 'Invalid json input', 'result' => []];
        $dataValues = json_decode($this->get_post_data($request), true);

        if (!is_array($dataValues)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rentalIdRaw = (string) ($dataValues['rentalId'] ?? '');
        $customerId = (int) ($dataValues['customerId'] ?? 0);
        $partnerCarId = (int) ($dataValues['partnercarId'] ?? 0);
        $amount = (float) ($dataValues['amount'] ?? 0);

        if ($rentalIdRaw === '' || $customerId <= 0 || $partnerCarId <= 0 || $amount <= 0) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rentalObj = explode('-', $rentalIdRaw);
        $rentalId = (int) ($rentalObj[0] ?? $rentalIdRaw);

        $booking = DB::table('cs_orders')
            ->where('id', $rentalId)
            ->where('renter_id', $customerId)
            ->orderByDesc('id')
            ->first();

        if (empty($booking)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect booking ID Or Customer ID',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        return response()->json([
            'status' => false,
            'message' => 'PaymentProcessor bridge is pending migration.',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function pushIssue(Request $request)
    {
        $return = ['status' => false, 'message' => 'Invalid json input', 'result' => []];
        $dataValues = json_decode($this->get_post_data($request), true);
        if (!is_array($dataValues)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rentalIdRaw = (string) ($dataValues['rentalId'] ?? '');
        $customerId = (int) ($dataValues['customerId'] ?? 0);
        $amount = (float) ($dataValues['amount'] ?? 0);
        if ($rentalIdRaw === '' || $customerId <= 0 || $amount <= 0) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $rentalObj = explode('-', $rentalIdRaw);
        $rentalId = (int) ($rentalObj[0] ?? $rentalIdRaw);
        $booking = DB::table('cs_orders')
            ->where('id', $rentalId)
            ->where('renter_id', $customerId)
            ->orderByDesc('id')
            ->first();

        if (empty($booking)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect booking ID Or Customer ID',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $statusMap = [
            'Pending' => 4,
            'LiabilityTransferredToDriver' => 5,
            'DirectlyPaidByDriver' => 6,
            'ChargedToDriverByDIA' => 7,
            'PaidByOwner' => 8,
        ];
        $statusText = (string) ($dataValues['status'] ?? '');
        if (!isset($statusMap[$statusText])) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, unknown status value. Please pass correct status',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $existing = DB::table('cs_vehicle_issues')
            ->where('vehicle_id', (int) ($dataValues['partnercarId'] ?? 0))
            ->where('renter_id', $customerId)
            ->where('cs_order_id', $rentalId)
            ->first();

        $payload = [
            'user_id' => (int) ($booking->user_id ?? 0),
            'renter_id' => (int) ($booking->renter_id ?? 0),
            'vehicle_id' => (int) ($booking->vehicle_id ?? 0),
            'cs_order_id' => $rentalId,
            'amount' => $amount,
            'type' => 4,
            'status' => $statusMap[$statusText],
            'violationType' => (string) ($dataValues['violationType'] ?? ''),
            'maintenance_issue_detail' => (string) ($dataValues['note'] ?? ''),
            'updated_at' => now(),
        ];

        if (!empty($existing)) {
            DB::table('cs_vehicle_issues')->where('id', (int) $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('cs_vehicle_issues')->insert($payload);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your request processed successfully',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }
}
