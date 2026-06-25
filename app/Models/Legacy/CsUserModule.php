<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsUserModule extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_user_modules';

    protected $fillable = [
        'parent_id',
        'module',
        'module_url',
        'order',
        'html_id',
        'status',
        'icon',
        'created',
        'modified',
    ];
}
