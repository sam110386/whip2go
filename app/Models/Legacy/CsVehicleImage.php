<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsVehicleImage extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_vehicle_images';

    protected $fillable = [
        'vehicle_id',
        'filename',
        'iorder',
        'remote',
        'created',
        'updated',
    ];
}
