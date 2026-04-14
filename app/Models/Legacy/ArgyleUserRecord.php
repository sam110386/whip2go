<?php

namespace App\Models\Legacy;

class ArgyleUserRecord extends LegacyModel
{
    protected $table = 'argyle_user_records';

    protected $fillable = [
        'argyle_user_id',
        'user_id',
        'account',
        'account_id',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
