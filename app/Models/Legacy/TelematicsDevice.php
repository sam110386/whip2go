<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class TelematicsDevice extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'telematics_devices';

    protected $fillable = [
        'sub_id',
        'device_name',
        'gps_serialno',
        'status',
        'created',
        'updated',
    ];
}
