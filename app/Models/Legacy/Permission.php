<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class Permission extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'permissions';

    protected $fillable = [
        'controller',
        'action',
        'created',
        'updated',
    ];
}
