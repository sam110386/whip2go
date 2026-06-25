<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ElandSetting extends LegacyModel
{
     
    protected $table = 'eland_settings';

    protected $fillable = [
        'user_id',
        'indentifier',
        'jwt_sub',
        'jwt_secret',
        'token',
    ];
}
