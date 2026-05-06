<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class VehicleAlert extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'vehicle_alerts';

    protected $fillable = [
        'vehicle_id',
        'order_id',
        'type',
        'geo',
        'speed',
        'note',
        'created',
    ];
}
