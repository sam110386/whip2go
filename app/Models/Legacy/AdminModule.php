<?php

namespace App\Models\Legacy;

class AdminModule extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
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
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
