<?php

namespace App\Models\Legacy;

class AdminModule extends LegacyModel
{
    protected $table = 'admin_modules';

    protected $fillable = [
        'parent_id',
        'module',
        'module_url',
        'order',
        'html_id',
        'status',
        'icon',
        'created',
        'modified'
    ];

    protected $guarded = [
        'id',
    ];
}
