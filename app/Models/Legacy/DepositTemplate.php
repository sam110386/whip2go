<?php

namespace App\Models\Legacy;

class DepositTemplate extends LegacyModel
{
    protected $table = 'cs_deposit_templates';

    protected $fillable = [
        'user_id',
        'initial_event',
        'deposit_event',
        'deposit_type',
        'deposit_title',
        'is_deposit_refundable',
        'charge_rent',
        'fare_type',
        'emf',
        'emf_insu',
        'tax',
        'lateness_fee',
        'cancellation_fee',
        'insurance_fee',
        'insurance_event',
        'deposit_amt',
        'total_deposit_amt',
        'deposit_amt_opt',
        'initial_fee',
        'initial_fee_opt',
        'total_initial_fee',
        'buy_fee',
        'max_extramile_fee',
        'depreciation_rate',
        'financing',
        'financing_type',
        'monthly_maintenance',
        'disposition_fee',
        'write_down_allocation',
        'lender_fee',
        'lender_type',
        'insurance_payer',
        'insurance_lender',
        'program_length',
        'fixed_program_cost',
        'prepaid_initial_fee',
        'prepaid_initial_fee_data',
        'selling_premium',
        'capitalize_starting_fee',
        'incentives',
        'doc_fee',
        'roadside_assistance_included',
        'maintenance_included_fee',
        'deposit_amt_des',
        'return_fee',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
