<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class IntercomeOrder extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'intercome_orders';

    protected $fillable = [
        'order_id',
        'conversation_id',
        'created',
    ];
}
