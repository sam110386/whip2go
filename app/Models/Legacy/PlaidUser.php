<?php

namespace App\Models\Legacy;

class PlaidUser extends LegacyModel
{
    protected $table = 'plaid_users';

    protected $fillable = [
        'user_id',
        'token',
        'user_token',
        'metadata',
        'link_session_id',
        'link_token',
        'plaid_user_id',
        'paystub',
        'created',
    ];
    protected $hidden = [
        'token',
    ];
    protected $guarded = [
        'id',
    ];

}
