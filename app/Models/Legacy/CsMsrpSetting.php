<?php

namespace App\Models\Legacy;

class CsMsrpSetting extends LegacyModel
{
    protected $table = 'cs_msrp_settings';

    protected $fillable = [
        'user_id',
        'msrp_from',
        'msrp_to',
        'credit_score_from',
        'credit_score_to',
        'downpayment',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
