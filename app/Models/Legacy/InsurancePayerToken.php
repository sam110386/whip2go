<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class InsurancePayerToken extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'insurance_payer_tokens';

    protected $fillable = [
        'user_id',
        'order_rule_id',
        'is_default',
        'card_funding',
        'card',
        'stripe_token',
        'card_id',
        'country',
        'created',
        'modified',
    ];
}
