<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ArgyleLog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'argyle_logs';

    protected $fillable = [
        'user_id',
        'argyle_user_id',
        'record_id',
        'income',
        'platform',
        'extra',
        'created',
        'updated',
    ];
}
