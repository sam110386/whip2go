<?php

namespace App\Models\Legacy;

class OrderExtlog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'cs_order_id',
        'ext_date',
        'note',
        'amt',
        'owner',
        'admin_count',
        'created',
    ];
    protected $table = 'cs_order_extlogs';
}
