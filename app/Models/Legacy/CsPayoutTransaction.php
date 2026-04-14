<?php

namespace App\Models\Legacy;

class CsPayoutTransaction extends LegacyModel
{
    protected $table = 'cs_payout_transactions';

    protected $fillable = [
        'cs_order_id',
        'cs_payment_id',
        'user_id',
        'type',
        'amount',
        'refund',
        'currency',
        'base_amt',
        'base_currency',
        'stripe_amt',
        'stripe_fee',
        'transaction_id',
        'transfer_id',
        'balance_transaction',
        'destination_payment',
        'cs_payout_id',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
