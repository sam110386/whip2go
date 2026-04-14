<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CakePHP `AccutradeController` — Accutrade API (JWT and vehicle writes stubbed).
 */
class AccutradeController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function getAuthToken(Request $request): JsonResponse
    {
        return $this->stubAuthJson();
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return $this->stubAuthJson();
    }

    public function addVehicle(Request $request): JsonResponse
    {
        return $this->stubVehicleJson();
    }

    public function addVehicles(Request $request): JsonResponse
    {
        return $this->stubVehicleJson();
    }

    public function removeVehicle(Request $request): JsonResponse
    {
        return $this->stubVehicleJson();
    }

    public function removeVehicles(Request $request): JsonResponse
    {
        return $this->stubVehicleJson();
    }

    public function checkVehicleStatus(Request $request): JsonResponse
    {
        $dataValues = $this->decodeJsonBody($request);
        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];

        if (empty($dataValues['vin'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vin = (string) $dataValues['vin'];
        $vehicle = DB::table('vehicles')->where('vin_no', $vin)->first();

        if (empty($vehicle)) {
            return response()->json([
                'status' => 1,
                'message' => 'Success',
                'result' => ['status' => 'Unlisted'],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $passtime = isset($vehicle->passtime_status) ? (int) $vehicle->passtime_status : null;
        $booked = isset($vehicle->booked) ? (int) $vehicle->booked : 0;
        $statusCode = isset($vehicle->status) ? (string) $vehicle->status : '1';

        if ($passtime === 0) {
            $statusLabel = 'Starter Disabled';
        } elseif ($passtime === 1 && $booked === 1) {
            $statusLabel = 'Booked';
        } else {
            $map = $this->vehicleStatusMap();
            $statusLabel = $map[$statusCode] ?? 'Active';
        }

        return response()->json([
            'status' => 1,
            'message' => 'Success',
            'result' => ['status' => $statusLabel],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function checkDealerExists(Request $request): JsonResponse
    {
        $dataValues = $this->decodeJsonBody($request);
        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];

        if (empty($dataValues['phone'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $dataValues['phone']);
        $phone = (string) $phone;
        if ($phone === '') {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $dealer = DB::table('users')
            ->select(['id', 'is_dealer'])
            ->where('username', $phone)
            ->first();

        if (!empty($dealer)) {
            if (!empty($dealer->is_dealer)) {
                $return = ['status' => 1, 'message' => 'Dealer found', 'result' => ['dealer_id' => (int) $dealer->id]];
            } else {
                $return['message'] = 'Sorry, Phone is registered but user didnt complete profile till dealer account complete';
            }
        } else {
            $return['message'] = 'Sorry, dealer is not registered yet';
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    private function stubAuthJson(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => 'Accutrade JWT auth not yet ported to Laravel',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    private function stubVehicleJson(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => 'Accutrade vehicle API not yet ported',
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonBody(Request $request): array
    {
        $raw = (string) $request->getContent();
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Mirrors `Common::getVehicleStatus()` (Cake) / `AccutradeApiController::vehicleStatusMap()`.
     *
     * @return array<string, string>
     */
    private function vehicleStatusMap(): array
    {
        return [
            '0' => 'Unlisted',
            '1' => 'Active',
            '4' => 'Inactive',
            '2' => 'In Body Shop',
            '3' => 'In Maintenance',
            '5' => 'Maintenance Issues',
            '6' => 'Booked',
            '8' => 'Starter Disabled',
            '9' => 'Starter Enabled',
            '10' => 'Waiting For Review',
            '11' => 'Deleted',
            '12' => 'Undo Deleted',
        ];
    }
}
