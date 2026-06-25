<?php

namespace App\Models\Legacy;

class CsPaymentRetry extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
