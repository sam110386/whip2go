<?php

namespace App\Models\Legacy;

class TdkVehicle extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'tdk_vehicles';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'tdk_id',
        'status',
        'note',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
