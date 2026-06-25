<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsTollsmartQueue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_tollsmart_queues';

    protected $fillable = [
        'cs_order_id',
        'vehicle_id',
        'toll',
        'proccessed',
        'created',
    ];
}
