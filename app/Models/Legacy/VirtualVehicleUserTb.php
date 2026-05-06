<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class VirtualVehicleUserTb extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'virtual_vehicle_user_tbs';

    protected $fillable = [
        'user_id',
        'virtual_vehicle_id',
        'created',
        'updated',
    ];
}
