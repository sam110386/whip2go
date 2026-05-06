<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsOrderToll extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_tolls';

    protected $fillable = [
        'cs_order_id',
        'vehicle_id',
        'toll',
        'paid',
        'start',
        'end',
        'attached_order_id',
        'created',
    ];
}
