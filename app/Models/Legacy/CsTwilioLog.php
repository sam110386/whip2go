<?php

namespace App\Models\Legacy;

class CsTwilioLog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_twilio_logs';

    protected $fillable = [
        'cs_twilio_order_id',
        'user_id',
        'renter_phone',
        'msg',
        'type',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
