<?php

namespace App\Models\Legacy;

class PlaidUser extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
