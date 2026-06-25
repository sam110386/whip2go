<?php

namespace App\Models\Legacy;

class VehicleLocation extends LegacyModel
{
     
    protected $table = 'vehicle_locations';

    protected $fillable = [
        'vehicle_id',
        'lat',
        'lng',
        'geo',
        'address',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
