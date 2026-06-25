<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class EmailQueue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'email_queues';

    protected $fillable = [
        'order_id',
        'amount',
        'payment_id',
        'text',
        'source',
        'status',
        'created',
    ];
}
