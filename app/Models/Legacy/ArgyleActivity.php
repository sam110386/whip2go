<?php

namespace App\Models\Legacy;

class ArgyleActivity extends LegacyModel
{
    protected $table = 'argyle_activities';

    protected $fillable = [
        'user_id',
        'argyle_user_id',
        'record_id',
        'income',
        'platform',
        'extra',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
