<?php

namespace App\Models\Legacy;

class CsOrderStatuslog extends LegacyModel
{
    protected $table = 'cs_order_statuslogs';

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
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
