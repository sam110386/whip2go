<?php

namespace App\Models\Legacy;

class TwilioSetting extends LegacyModel
{
    protected $table = 'twilio_settings';

    protected $fillable = [
        'dispacher_id',
        'status',
        'award_default',
        'twilio_sid',
        'twilio_authtoken',
        'twilio_from',
        'arrive_msg',
        'pickup_msg',
        'drop_msg',
        'kiosk_msg',
        'driver_ref_msg',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
