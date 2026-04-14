<?php

namespace App\Models\Legacy;

class VehicleSetting extends LegacyModel
{
    protected $table = 'vehicle_settings';

    protected $fillable = [
        'vehicle_id',
        'data',
        'financing',
        'financing_type',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
