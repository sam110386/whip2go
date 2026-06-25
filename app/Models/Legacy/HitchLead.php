<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class HitchLead extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'hitch_leads';

    protected $fillable = [
        'dealer_id',
        'user_id',
        'phone',
        'first_name',
        'last_name',
        'email',
        'payroll',
        'status',
        'created',
        'updated',
    ];
}
