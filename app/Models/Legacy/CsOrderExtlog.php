<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsOrderExtlog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_extlogs';

    protected $fillable = [
        'cs_order_id',
        'ext_date',
        'note',
        'amt',
        'owner',
        'admin_count',
        'created',
    ];
}
