<?php

namespace App\Models\Legacy;

class CsUserConvertibility extends LegacyModel
{
    protected $table = 'cs_user_convertibilities';

    protected $fillable = [
        'user_id',
        'reference_id',
        'score',
        'scorechange',
        'target_score',
        'notified',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
