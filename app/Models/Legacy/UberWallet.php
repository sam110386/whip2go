<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberWallet extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'uber_wallets';

    protected $fillable = [
        'user_id',
        'balance',
        'created',
        'updated',
    ];
}
