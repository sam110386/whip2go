<?php

namespace App\Models\Legacy;

class PtoSetting extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'pto_settings';

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
