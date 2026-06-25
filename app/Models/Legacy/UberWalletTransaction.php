<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberWalletTransaction extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'uber_wallet_transactions';

    protected $fillable = [
        'uber_wallet_id',
        'amount',
        'amt',
        'transaction_id',
        'uber_trip_id',
        'note',
        'status',
        'type',
        'created',
    ];
}
