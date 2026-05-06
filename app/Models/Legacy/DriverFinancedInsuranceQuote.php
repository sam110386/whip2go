<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class DriverFinancedInsuranceQuote extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'driver_financed_insurance_quotes';

    protected $fillable = [
        'order_id',
        'premium_total',
        'premium_finance_total',
        'total_amount',
        'daily_rate',
        'docusign_envelope_id',
        'docusign_status',
        'declaration_doc',
        'insurance_card',
        'apply_with_credee',
        'quote',
        'quote_approved',
        'credit_card',
        'provider_account',
        'provider_name',
        'policy_number',
        'begin_date',
        'end_date',
        'updated',
        'created',
    ];
}
