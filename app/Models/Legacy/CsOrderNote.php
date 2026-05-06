<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsOrderNote extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_notes';

    protected $fillable = [
        'parent_order_id',
        'order_id',
        'user_id',
        'msg',
        'created',
    ];
}
