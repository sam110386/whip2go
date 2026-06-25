<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ElandLead extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'eland_leads';

    protected $fillable = [
        'user_id',
        'eland_id',
        'data',
        'created',
        'updated',
    ];
}
