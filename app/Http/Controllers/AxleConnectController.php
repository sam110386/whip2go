<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\AxleService;
use Illuminate\Support\Facades\DB;

class AxleConnectController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function connect($orderandusers)
    {
        $decoded = base64_decode($orderandusers);
        $parts = explode('|', $decoded);
        $orderid = $parts[0] ?? '';
        $user = $parts[1] ?? '';

        if (empty($orderid) || empty($user)) {
            abort(400, 'sorry wrong attempt');
        }

        $axleStatusObj = DB::table('axle_status')->where('order_id', $orderid)->first();
        if (empty($axleStatusObj) || !in_array($axleStatusObj->axle_status ?? 0, [2])) {
            $odr = DB::table('order_deposit_rules as OrderDepositRule')
                ->leftJoin('vehicle_reservations as VehicleReservation', 'VehicleReservation.id', '=', 'OrderDepositRule.vehicle_reservation_id')
                ->leftJoin('users as Renter', 'Renter.id', '=', 'VehicleReservation.renter_id')
                ->where('OrderDepositRule.id', $orderid)
                ->select('OrderDepositRule.id', 'Renter.id as renter_id', 'Renter.first_name', 'Renter.last_name')
                ->first();

            $dataToPass = [
                "order_id" => $odr->id,
                'renter_id' => $odr->renter_id,
                'first_name' => $odr->first_name,
                'last_name' => $odr->last_name,
                'x-access-token' => $axleStatusObj->access_token ?? '',
            ];
            $resp = (new AxleService())->startIgnition($dataToPass);
            if (isset($resp['success']) && $resp['success'] == 1) {
                return redirect($resp['data']['ignitionUri']);
            }
            abort(500, 'Failed to start Axle ignition');
        }
        abort(400, 'Sorry, this record already connected with Axle');
    }
}
