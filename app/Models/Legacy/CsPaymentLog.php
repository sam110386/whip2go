<?php

namespace App\Models\Legacy;

class CsPaymentLog extends LegacyModel
{
    protected $table = 'cs_payment_logs';

    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'transaction_id',
        'old_transaction_id',
        'refund_transaction_id',
        'note',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
