<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class Report extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'reports';

    protected $fillable = [
        'user_id',
        'category',
        'rtype',
        'type',
        'cs_order_id',
        'amt',
        'transaction_id',
        'source',
        'note',
        'created',
        'updated',
    ];
}
