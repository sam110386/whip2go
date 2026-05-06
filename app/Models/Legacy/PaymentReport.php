<?php

namespace App\Models\Legacy;

class PaymentReport extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'payment_reports';

    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'transaction_id',
        'payer_id',
        'txn_type',
        'source',
        'description',
        'currency',
        'charged_at',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
