<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CakeSession extends LegacyModel
{
     
    protected $table = 'cake_sessions';

    protected $fillable = [
        'data',
        'expires',
    ];
}
