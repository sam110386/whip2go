<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class VehicleIssueLib
{
    public function process(): void
    {
        $vehicles = DB::table('vehicles')
            ->where('booked', 1)
            ->where('total_mileage', '>', 0)
            ->where('last_mile', '>', 0)
            ->whereColumn('last_mile', '>', 'total_mileage')
            ->select('id', 'user_id', 'last_mile', 'total_mileage')
            ->get();

        if ($vehicles->isEmpty()) {
            return;
        }

        $maintenanceMiles = config('legacy.MaintenanceMonitoring.miles', 5000);

        foreach ($vehicles as $vehicle) {
            $csOrderObj = DB::table('cs_orders')
                ->where('status', 1)
                ->where('vehicle_id', $vehicle->id)
                ->select('id', 'renter_id')
                ->first();

            $nextServiceOdometer = $vehicle->last_mile + $maintenanceMiles;

            $issueData = [
                'type'                   => 6,
                'vehicle_id'             => $vehicle->id,
                'user_id'                => $vehicle->user_id,
                'status'                 => 0,
                'maintenance_issue_detail' => 'routine service, vehicle check-up',
                'renter_id'              => $csOrderObj->renter_id ?? 0,
                'cs_order_id'            => $csOrderObj->id ?? null,
                'extra'                  => json_encode([
                    'vehicle_scheduled_for_service' => '',
                    'vehicle_serviced'              => 0,
                    'service_paid'                  => 0,
                    'current_odometer'              => $vehicle->last_mile,
                    'next_service_odometer'         => $nextServiceOdometer,
                ]),
                'created'  => now(),
                'modified' => now(),
            ];

            DB::table('cs_vehicle_issues')->insert($issueData);
            DB::table('vehicles')->where('id', $vehicle->id)
                ->update(['total_mileage' => $nextServiceOdometer]);
        }
    }

    public function createCleanServiceTicket(array $opt = []): void
    {
        DB::table('cs_vehicle_issues')->insert([
            'type'                   => 5,
            'vehicle_id'             => $opt['vehicle_id'],
            'user_id'                => $opt['user_id'],
            'status'                 => 0,
            'roadside_request_detail' => 'Vehicle need to be cleaned',
            'renter_id'              => $opt['renter_id'],
            'cs_order_id'            => $opt['booking_id'],
            'created'                => now(),
            'modified'               => now(),
        ]);
    }

    public function createTicketOnBookingComplete(array $opt = []): void
    {
        if (($opt['type'] ?? 0) == 5) {
            $this->createCleanServiceTicket($opt);
            return;
        }

        $vehicle = DB::table('vehicles')
            ->where('id', $opt['vehicle_id'])
            ->select('id', 'user_id', 'last_mile', 'total_mileage')
            ->first();

        if (empty($vehicle)) {
            return;
        }

        if (($vehicle->total_mileage - $vehicle->last_mile) < 2000) {
            return;
        }

        $maintenanceMiles = config('legacy.MaintenanceMonitoring.miles', 5000);
        $nextServiceOdometer = $vehicle->last_mile + $maintenanceMiles;

        DB::table('cs_vehicle_issues')->insert([
            'type'                     => 6,
            'vehicle_id'               => $vehicle->id,
            'user_id'                  => $vehicle->user_id,
            'status'                   => 0,
            'maintenance_issue_detail' => 'routine service, vehicle check-up',
            'renter_id'                => $opt['renter_id'],
            'cs_order_id'              => $opt['booking_id'],
            'extra'                    => json_encode([
                'vehicle_scheduled_for_service' => '',
                'vehicle_serviced'              => 0,
                'service_paid'                  => 0,
                'current_odometer'              => $vehicle->last_mile,
                'next_service_odometer'         => $nextServiceOdometer,
            ]),
            'created'  => now(),
            'modified' => now(),
        ]);

        DB::table('vehicles')->where('id', $vehicle->id)
            ->update(['total_mileage' => $nextServiceOdometer]);
    }

    public function createPendingBookingTicket(array $opt = []): void
    {
        DB::table('cs_vehicle_issues')->insert([
            'type'                     => 8,
            'vehicle_id'               => $opt['vehicle_id'],
            'user_id'                  => $opt['user_id'],
            'status'                   => 0,
            'maintenance_issue_detail' => 'Pending booking created',
            'renter_id'                => $opt['renter_id'],
            'cs_order_id'              => $opt['id'],
            'extra'                    => json_encode([
                'start_datetime'  => $opt['start_datetime'],
                'end_datetime'    => $opt['end_datetime'],
                'reservation_id'  => $opt['id'],
            ]),
            'created'  => now(),
            'modified' => now(),
        ]);
    }
}
