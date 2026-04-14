<?php

namespace App\Models\Legacy;

class CsPaymentRetry extends LegacyModel
{
    protected $table = 'cs_payment_retries';

    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
