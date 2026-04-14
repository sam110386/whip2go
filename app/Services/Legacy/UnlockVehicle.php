<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/UnlockVehicle.php
 * Unlocks a vehicle (via Passtime) when all payment statuses are cleared.
 */
class UnlockVehicle
{
    public static function unlock(int $orderId): void
    {
        $order = DB::table('cs_orders as CsOrder')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'CsOrder.user_id')
            ->leftJoin('vehicle_settings as VehicleSetting', 'VehicleSetting.vehicle_id', '=', 'Vehicle.id')
            ->where('CsOrder.id', $orderId)
            ->where('CsOrder.status', 1)
            ->whereIn('CsOrder.payment_status', [0, 1])
            ->whereIn('CsOrder.insu_status', [0, 1])
            ->whereIn('CsOrder.dpa_status', [0, 1])
            ->whereIn('CsOrder.infee_status', [0, 1])
            ->whereIn('CsOrder.dia_insu_status', [0, 1])
            ->where('Vehicle.passtime_status', 0)
            ->where('Vehicle.passtime_serialno', '!=', '')
            ->select([
                'CsOrder.id', 'CsOrder.user_id', 'CsOrder.renter_id', 'CsOrder.vehicle_id',
                'CsOrder.payment_status', 'CsOrder.insu_status', 'CsOrder.dpa_status',
                'CsOrder.infee_status', 'CsOrder.dia_insu_status', 'CsOrder.emf_status',
                'CsOrder.start_datetime', 'CsOrder.end_datetime', 'CsOrder.increment_id',
                'CsOrder.timezone', 'Vehicle.passtime_serialno', 'Vehicle.autopi_unit_id',
                'Vehicle.user_id as vehicle_owner_id',
            ])
            ->first();

        if (empty($order)) {
            return;
        }

        // Passtime activation stub – wire real integration later
        Log::info("UnlockVehicle::unlock – activating Passtime for order {$orderId}");

        DB::table('vehicles')
            ->where('id', $order->vehicle_id)
            ->update(['passtime_status' => 1]);

        $failed = '';
        $failed .= ($order->payment_status == 2) ? 'Rental, ' : '';
        $failed .= ($order->insu_status == 2) ? ' Insurance,' : '';
        $failed .= ($order->dpa_status == 2) ? ' Deposit,' : '';
        $failed .= ($order->infee_status == 2) ? ' Initial Fee,' : '';
        $failed .= ($order->dia_insu_status == 2) ? ' EMF Insurance,' : '';
        $failed .= ($order->emf_status == 2) ? ' EMF' : '';

        Notifier::createIntercomeUserEvent([
            'event_name'  => 'starter_enabled',
            'created_at'  => time(),
            'external_id' => $order->renter_id,
            'user_id'     => $order->renter_id,
            'metadata'    => [
                'id'              => $order->id,
                'booking_id'      => $order->increment_id,
                'begin_date'      => date('m/d/Y', strtotime($order->start_datetime)),
                'end_date'        => date('m/d/Y', strtotime($order->end_datetime)),
                'failed_payments' => $failed,
                'type'            => 'UnlockVehicle::unlock',
            ],
        ]);
    }
}
