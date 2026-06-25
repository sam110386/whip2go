<?php

namespace App\Models\Legacy;

class AdminRoleUser extends LegacyModel
{
     
    protected $table = 'admin_role_users';

    protected $fillable = [
        'role_id',
        'user_id',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
