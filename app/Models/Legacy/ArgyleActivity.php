<?php

namespace App\Models\Legacy;

class ArgyleActivity extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
