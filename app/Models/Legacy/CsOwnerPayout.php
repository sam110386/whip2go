<?php

namespace App\Models\Legacy;

class CsOwnerPayout extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
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
