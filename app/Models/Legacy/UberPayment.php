<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberPayment extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'uber_payments';

    protected $fillable = [
        'trip_id',
        'amount',
        'transaction_id',
        'payer_id',
        'txntype',
        'status',
        'created',
    ];
}
