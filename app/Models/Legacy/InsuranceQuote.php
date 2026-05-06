<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class InsuranceQuote extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'insurance_quotes';

    protected $fillable = [
        'provider_id',
        'order_id',
        'quote_amount',
        'daily_rate',
        'total_limit',
        'policy_doc',
        'docusign_envelope_id',
        'selected',
        'updated',
        'created',
    ];
}
