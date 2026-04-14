<?php

namespace App\Models\Legacy;

class CsWallet extends LegacyModel
{
    protected $table = 'cs_wallets';

    protected $fillable = [
        'user_id',
        'balance',
        'term',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
