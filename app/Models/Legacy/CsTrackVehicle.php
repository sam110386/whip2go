<?php

namespace App\Models\Legacy;

use Carbon\Carbon;

class CsTrackVehicle extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_track_vehicles';

    protected $fillable = [
        'cs_order_id',
        'vehicle_id',
        'user_id',
        'lat',
        'lng',
        'lockedtime',
        'proccessed',
        'last_mile',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];


    public static function getLastMileFromHistory($vehicle_id, $datetime)
    {
        $carbonTime = Carbon::parse($datetime);
        $rangestart = $carbonTime->copy()->startOfDay()->toDateTimeString();
        $rangeend = $carbonTime->copy()->endOfDay()->toDateTimeString();
        $timestamp = $carbonTime->timestamp;

        $obj = self::where('vehicle_id', $vehicle_id)
            ->where('last_mile', '>', 0)
            ->whereBetween('lockedtime', [$rangestart, $rangeend])
            ->orderByRaw("ABS(UNIX_TIMESTAMP(lockedtime) - ?)", [$timestamp])
            ->first();

        if ($obj) {
            return $obj->last_mile;
        }

        $obj = self::where('vehicle_id', $vehicle_id)
            ->where('last_mile', '>', 0)
            ->whereBetween('created', [$rangestart, $rangeend])
            ->orderByRaw("ABS(UNIX_TIMESTAMP(created) - ?)", [$timestamp])
            ->first();

        if ($obj) {
            return $obj->last_mile;
        }

        return 0;
    }

    public static function getVehicleMovementFromHistory($vehicle_id)
    {
        $rangestart = Carbon::now()->subDay()->startOfDay()->toDateTimeString();
        $rangeend = Carbon::now()->endOfDay()->toDateTimeString();

        $latestObj = self::where('vehicle_id', $vehicle_id)
            ->whereBetween('created', [$rangestart, $rangeend])
            ->orderBy('id', 'DESC')
            ->first();

        if (!$latestObj) {
            return 'N/A';
        }

        $oldestObj = self::where('vehicle_id', $vehicle_id)
            ->whereBetween('created', [$rangestart, $rangeend])
            ->orderBy('id', 'ASC')
            ->first();

        $distance = self::getDistance(
            $latestObj->lat,
            $latestObj->lng,
            $oldestObj->lat,
            $oldestObj->lng
        );

        return ($distance > 200) ? "Yes" : "No";
    }

    private static function getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius; // in meters
    }

}
