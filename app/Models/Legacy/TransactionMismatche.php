<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class TransactionMismatche extends LegacyModel
{
     
    protected $table = 'transaction_mismatches';

    protected $fillable = [
        'cs_order_id',
        'charged_at',
        'cpl_amount',
        'cpl_transaction_id',
        'c_amount',
        'c_transaction_id',
    ];
}
