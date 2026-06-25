<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class InsurancePayer extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'insurance_payers';

    protected $fillable = [
        'order_deposit_rule_id',
        'premium_total',
        'premium_finance_total',
        'policy_number',
        'begin_date',
        'exp_date',
        'daily_rate',
        'declaration_doc',
        'insurance_card',
        'stripe_key',
        'frequency',
        'last_attempt',
        'next',
        'attepmt',
        'updated',
        'created',
    ];
}
