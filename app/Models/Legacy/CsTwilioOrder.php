<?php

namespace App\Models\Legacy;

class CsTwilioOrder extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_twilio_orders';

    protected $fillable = [
        'cs_order_id',
        'reservation_id',
        'user_id',
        'renter_id',
        'renter_phone',
        'vehicle_id',
        'start_datetime',
        'end_datetime',
        'extend',
        'status',
        'approved',
        'short_url',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
