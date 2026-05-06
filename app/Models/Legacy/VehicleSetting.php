<?php

namespace App\Models\Legacy;

class VehicleSetting extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
