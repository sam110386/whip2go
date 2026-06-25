<?php

namespace App\Models\Legacy;

class TelematicsSubscription extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'telematics_subscriptions';

    protected $fillable = [
        'user_id',
        'units',
        'upfront_amt',
        'amt',
        'status',
        'next_on',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
