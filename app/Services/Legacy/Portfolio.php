<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * Port of CakePHP app/Lib/Portfolio.php
 * Generates vehicle portfolio summaries (revenue, insurance, expenses).
 */
class Portfolio
{
    public function getVehiclePortfolio(int $vehicleId, ?string $dateFrom, ?string $dateTo): array
    {
        $query = DB::table('cs_orders')
            ->where('vehicle_id', $vehicleId);

        if (!empty($dateFrom)) {
            $query->where('start_datetime', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->where('end_datetime', '<=', $dateTo);
        }

        $row = $query->selectRaw("
            SUM(rent + initial_fee) as totalrent,
            SUM(DATEDIFF(end_datetime, start_datetime)) as totaldays,
            SUM(extra_mileage_fee) as extra_mileage_fee,
            SUM(lateness_fee) as lateness_fee
        ")
        ->groupBy('vehicle_id')
        ->first();

        if ($row) {
            return [
                'totalrent'         => $row->totalrent ?? 0.00,
                'totaldays'         => $row->totaldays ?? 0.00,
                'extra_mileage_fee' => $row->extra_mileage_fee ?? 0.00,
                'lateness_fee'      => $row->lateness_fee ?? 0.00,
            ];
        }

        return [
            'totalrent' => 0.00, 'totaldays' => 0.00,
            'deposit' => 0.00, 'initial_fee' => 0.00,
            'extra_mileage_fee' => 0.00, 'lateness_fee' => 0.00, 'toll' => 0.00,
        ];
    }

    public function getVehicleInsurance(int $vehicleId, ?string $dateFrom, ?string $dateTo): array
    {
        $query = DB::table('cs_orders')
            ->leftJoin('cs_order_payments', function ($join) {
                $join->on('cs_order_payments.cs_order_id', '=', 'cs_orders.id')
                     ->where('cs_order_payments.type', 4)
                     ->whereColumn('cs_order_payments.payer_id', 'cs_orders.user_id');
            })
            ->where('cs_orders.vehicle_id', $vehicleId);

        if (!empty($dateFrom)) {
            $query->where('cs_orders.start_datetime', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->where('cs_orders.end_datetime', '<=', $dateTo);
        }

        $row = $query->selectRaw('SUM(cs_order_payments.amount) as total')
            ->groupBy('cs_orders.vehicle_id')
            ->first();

        return ($row && !empty($row->total)) ? ['total' => $row->total] : ['total' => 0.00];
    }

    public function getVehicleExpenses(int $vehicleId, ?string $dateFrom, ?string $dateTo): array
    {
        $return = ['depreciation' => 0.00, 'bodydamage' => 0, 'mechdamage' => 0.00, 'maintenance' => 0.00, 'toll' => 0.00];

        $query = DB::table('cs_vehicle_expenses')
            ->where('vehicle_id', $vehicleId);

        if (!empty($dateFrom)) {
            $query->where('created', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->where('created', '<=', $dateTo);
        }

        $rows = $query->selectRaw('SUM(amount) as total, type')
            ->groupBy('vehicle_id', 'type')
            ->get();

        foreach ($rows as $row) {
            match ((int)$row->type) {
                1 => $return['depreciation'] = $row->total,
                2 => $return['mechdamage'] = $row->total,
                3 => $return['bodydamage'] = $row->total,
                4 => $return['maintenance'] = $row->total,
                5 => $return['toll'] = $row->total,
                default => null,
            };
        }

        return $return;
    }
}
