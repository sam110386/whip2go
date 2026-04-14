<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait LoadsMvrActiveBookings
{
    /**
     * @return array{0: Collection<int, object>, 1: Collection<int, object>}
     */
    protected function mvrActiveBookingsForRenter(int $renterId): array
    {
        $bookings = DB::table('cs_orders')
            ->where('renter_id', $renterId)
            ->whereIn('status', [0, 1])
            ->orderByDesc('id')
            ->get();

        $reservations = DB::table('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->where('vr.renter_id', $renterId)
            ->where('vr.status', 0)
            ->select('vr.*', 'v.vehicle_name')
            ->orderByDesc('vr.id')
            ->get();

        return [$bookings, $reservations];
    }

    protected function mvrFormatDateTime(?string $value, ?string $timezone): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            $tz = ($timezone !== null && $timezone !== '') ? $timezone : (string) config('app.timezone');

            return Carbon::parse($value)->timezone($tz)->format('Y-m-d h:i A');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
