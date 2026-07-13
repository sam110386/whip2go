<?php
namespace App\Services\Legacy;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;

/**
 * Port of CakePHP app/Lib/UnlockVehicle.php
 */
class UnlockVehicle
{
    public static function unlock(int $orderId): void
    {
        $order = CsOrder::select('id', 'user_id', 'renter_id', 'vehicle_id', 'payment_status', 'insu_status', 'dpa_status', 'infee_status', 'dia_insu_status', 'emf_status', 'start_datetime', 'end_datetime', 'increment_id', 'timezone')
            ->with([
                'vehicle' => function ($query) {
                    $query->with('vehicleSetting')
                        ->where('passtime_status', 0)
                        ->whereNotNull('passtime_serialno')
                        ->where('passtime_serialno', '!=', '')
                        ->select([
                            'id',
                            'passtime_status',
                            'passtime_serialno',
                            'autopi_unit_id',
                            'user_id'
                        ]);
                },
                'csSetting'
            ])
            ->where('id', $orderId)
            ->where('status', 1)
            ->whereIn('payment_status', [0, 1])
            ->whereIn('insu_status', [0, 1])
            ->whereIn('dpa_status', [0, 1])
            ->whereIn('infee_status', [0, 1])
            ->whereIn('dia_insu_status', [0, 1])
            ->first();

        if (!$order || !$order->vehicle) {
            return;
        }

        (new Passtime())->ActivateVehicle($order->toArray());

        Vehicle::where('id', $order->vehicle_id)->update(['passtime_status' => 1]);

        $failed = '';
        $failed .= ($order->payment_status == 2) ? 'Rental, ' : '';
        $failed .= ($order->insu_status == 2) ? ' Insurance,' : '';
        $failed .= ($order->dpa_status == 2) ? ' Deposit,' : '';
        $failed .= ($order->infee_status == 2) ? ' Initial Fee,' : '';
        $failed .= ($order->dia_insu_status == 2) ? ' EMF Insurance,' : '';
        $failed .= ($order->emf_status == 2) ? ' EMF' : '';

        Notifier::createIntercomeUserEvent([
            'event_name' => 'starter_enabled',
            'created_at' => time(),
            'external_id' => $order->renter_id,
            'user_id' => $order->renter_id,
            'metadata' => [
                'id' => $order->id,
                'booking_id' => $order->increment_id,
                'begin_date' => date('m/d/Y', strtotime($order->start_datetime)),
                'end_date' => date('m/d/Y', strtotime($order->end_datetime)),
                'failed_payments' => $failed,
                'type' => 'UnlockVehicle::unlock',
            ],
        ]);

    }
}
