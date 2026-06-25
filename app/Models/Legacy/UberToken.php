<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberToken extends LegacyModel
{
     
    protected $table = 'uber_tokens';

    protected $fillable = [
        'token',
        'expire_on',
        'updated',
    ];
}
