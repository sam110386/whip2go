<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class InsurancePayerPayment extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'insurance_payer_payments';

    protected $fillable = [
        'order_rule_id',
        'amount',
        'transaction_id',
        'txntype',
        'status',
        'created',
    ];
}
