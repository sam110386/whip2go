<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrated from: app/Plugin/VehicleAlert/Controller/VehicleAlertHooksController.php
 *
 * Webhook receiver for OneStepGPS vehicle alerts (harsh driving events).
 * Currently disabled per business decision ("as Adam said we dont need").
 */
class VehicleAlertHooksController extends Controller
{
    private string $webhookSecret = 'GJHGJHGHG788768UYT';

    private array $alertNames = ['Harsh Cornering', 'Harsh Braking', 'Harsh Acceleration'];

    public function record(Request $request): Response
    {
        // Disabled per business decision
        return response('', 200);

        // @codeCoverageIgnoreStart
        $authorization = $request->header('Authorization', $request->header('authorization', ''));

        $postData = $request->getContent();
        Log::channel('daily')->info('onestepgps_webhook', [
            'body'    => $postData,
            'headers' => $request->headers->all(),
        ]);

        if ($authorization !== $this->webhookSecret) {
            return response('dont do anything, signature dont matched', 200);
        }

        $data = json_decode($postData, true);
        if (empty($data)) {
            return response('sorry, wrong attempt', 200);
        }

        if (!in_array($data['alert_name'] ?? '', $this->alertNames)) {
            return response('sorry, alert ' . ($data['alert_name'] ?? '') . ' not allowed', 200);
        }

        $vehicle = DB::table('vehicles')
            ->where('vin_no', $data['vin'])
            ->where('booked', 1)
            ->select('id')
            ->first();

        if (empty($vehicle)) {
            return response('sorry, wrong attempt, vehicle not found', 200);
        }

        $dataToSave = [
            'vehicle_id' => $vehicle->id,
            'type'       => $data['alert_name'],
            'geo'        => $data['lat'] . ',' . $data['lng'],
            'speed'      => $data['speed_mph'],
            'note'       => '',
            'created'    => now()->toDateTimeString(),
        ];

        try {
            DB::table('vehicle_alerts')->insert($dataToSave);
            $this->countAndSavePenalty($vehicle->id);
        } catch (\Exception $e) {
            return response($e->getMessage(), 200);
        }

        return response('finished', 200);
        // @codeCoverageIgnoreEnd
    }

    private function countAndSavePenalty(int $vehicleId): void
    {
        $count = DB::table('vehicle_alerts')
            ->where('vehicle_id', $vehicleId)
            ->where('created', '>', date('Y-m-d 00:00:00'))
            ->where('created', '<', date('Y-m-d 23:59:59'))
            ->count();

        $allowed = config('legacy.VehicleAlert.allowed', 5);
        if ($count <= $allowed) {
            return;
        }

        $order = DB::table('cs_orders')
            ->where('status', 1)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('id')
            ->select('id', 'renter_id', 'user_id')
            ->first();

        if (empty($order)) {
            return;
        }

        $penaltyAmount = config('legacy.VehicleAlert.amount', 0);

        DB::table('cs_user_balance_logs')->insert([
            'user_id'  => $order->renter_id,
            'credit'   => $penaltyAmount,
            'type'     => 18,
            'owner_id' => $order->user_id,
            'note'     => 'Penalty for Vehicle Alert',
        ]);

        DB::table('cs_user_balances')->insert([
            'owner_id'         => $order->user_id,
            'user_id'          => $order->renter_id,
            'note'             => 'Penalty for Vehicle Alert',
            'credit'           => $penaltyAmount,
            'balance'          => $penaltyAmount,
            'debit'            => 0,
            'type'             => 18,
            'chargetype'       => 'lumpsum',
            'installment_type' => 'daily',
            'installment_day'  => null,
            'installment'      => 0,
        ]);
    }
}
