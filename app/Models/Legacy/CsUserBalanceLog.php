<?php

namespace App\Models\Legacy;

class CsUserBalanceLog extends LegacyModel
{
    protected $table = 'cs_user_balance_logs';

    protected $fillable = [
        'owner_id',
        'user_id',
        'credit',
        'debit',
        'type',
        'note',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
