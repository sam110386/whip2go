<?php

namespace App\Models\Legacy;

class Tracking extends LegacyModel
{
    protected $table = 'trackings';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
