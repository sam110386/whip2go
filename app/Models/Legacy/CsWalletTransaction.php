<?php

namespace App\Models\Legacy;

class CsWalletTransaction extends LegacyModel
{
    protected $table = 'cs_wallet_transactions';

    protected $fillable = [
        'cs_wallet_id',
        'amount',
        'amt',
        'transaction_id',
        'cs_order_id',
        'note',
        'status',
        'type',
        'balance',
        'currency',
        'charged_at',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
