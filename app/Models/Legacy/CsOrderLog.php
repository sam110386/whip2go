<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsOrderLog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_logs';

    protected $fillable = [
        'cs_order_id',
        'vehicle_id',
        'user_id',
        'status',
        'request',
        'requestStatus',
        'response',
        'responseStatus',
        'target',
        'created',
        'updated',
    ];
}
