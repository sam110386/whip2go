<?php

namespace App\Models\Legacy;

class DepositRule extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_deposit_rules';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'initial_fee',
        'initial_event',
        'deposit_amt',
        'deposit_event',
        'deposit_type',
        'charge_rent',
        'emf',
        'emf_insu',
        'tax',
        'min_rent',
        'lateness_fee',
        'cancellation_fee',
        'insurance_fee',
        'insurance_event',
        'deposit_amt_opt',
        'total_deposit_amt',
        'initial_fee_opt',
        'total_initial_fee',
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
        'lender_anticipated_date',
        'prepaid_initial_fee',
        'prepaid_initial_fee_data',
        'program_length',
        'capitalize_starting_fee',
        'incentive',
        'doc_fee',
        'free_two_move',
        'return_fee',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
