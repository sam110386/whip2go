<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class SmartCar extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'smart_cars';

    protected $fillable = [
        'user_id',
        'expire_at',
        'token',
        'refresh_token',
        'created',
    ];
}
