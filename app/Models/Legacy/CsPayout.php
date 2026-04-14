<?php

namespace App\Models\Legacy;

class CsPayout extends LegacyModel
{
    protected $table = 'cs_payouts';

    protected $fillable = [
        'user_id',
        'stripe_key',
        'transaction_id',
        'amount',
        'processed_on',
        'balance_transaction',
        'status',
        'created',
        'modified',
    ];
    protected $hidden = [
        'stripe_key',
    ];
    protected $guarded = [
        'id',
    ];

}
