<?php

namespace App\Models\Legacy;

class AdminRoleMenu extends LegacyModel
{
    protected $table = 'admin_role_menu';

    protected $fillable = [
        'role_id',
        'menu_id',
        'created_at',
        'updated_at'
    ];

    protected $guarded = [];
}
