<?php

namespace App\Models\Legacy;

class CsOwnerPayout extends LegacyModel
{
    protected $table = 'cs_owner_payouts';

    protected $fillable = [
        'transaction_id',
        'amount',
        'arrival_date',
        'balance_transaction',
        'status',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
