<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class DealerChargeback extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'dealer_chargebacks';

    protected $fillable = [
        'dealer_id',
        'user_id',
        'amt',
        'txn_id',
        'status',
        'note',
        'created',
    ];
}
