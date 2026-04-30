<?php

namespace App\Models\Legacy;

class TelematicsSubscription extends LegacyModel
{
    protected $table = 'telematics_subscriptions';

    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'user_id',
        'units',
        'upfront_amt',
        'amt',
        'status',
        'next_on',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
