<?php

namespace App\Models\Legacy;

class VehicleImage extends LegacyModel
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
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
