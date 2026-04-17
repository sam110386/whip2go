<?php

namespace App\Models\Legacy;

class CsTrackVehicle extends LegacyModel
{
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

}
