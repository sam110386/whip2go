<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AirwallexCredit extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'airwallex_credits';

    protected $fillable = [
        'user_id',
        'current_limit',
        'amount',
        'card_number',
        'exp',
        'cvv',
        'status',
        'cardholder_id',
        'card_id',
        'extra',
        'created',
    ];
}
