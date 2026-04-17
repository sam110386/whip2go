<?php

namespace App\Models\Legacy;

class ArgyleUser extends LegacyModel
{
    protected $table = 'argyle_users';

    protected $fillable = [
        'user_id',
        'argyle_user_id',
        'auth_token',
        'income',
        'trash',
        'uber_account_id',
        'lyft_account_id',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
