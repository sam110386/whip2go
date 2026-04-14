<?php

namespace App\Models\Legacy;

class CsUserBalance extends LegacyModel
{
    protected $table = 'cs_user_balances';

    protected $fillable = [
        'owner_id',
        'user_id',
        'credit',
        'debit',
        'balance',
        'chargetype',
        'installment_type',
        'installment',
        'installment_day',
        'last_processed',
        'type',
        'note',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
