<?php

namespace App\Models\Legacy;

class CsInsuranceTemplate extends LegacyModel
{
    protected $table = 'cs_insurance_templates';

    protected $fillable = [
        'user_id',
        'program',
        'insurance_policy_no',
        'insurance_company',
        'insurance_policy_date',
        'insurance_policy_exp_date',
        'status',
        'insu_token_name',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
