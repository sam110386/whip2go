<?php

namespace App\Models\Legacy;

class CsUserConvertibility extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
