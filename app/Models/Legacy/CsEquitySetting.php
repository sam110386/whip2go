<?php

namespace App\Models\Legacy;

class CsEquitySetting extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_equity_settings';

    protected $fillable = [
        'user_id',
        'share',
        'other_vhshare',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
