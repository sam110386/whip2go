<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberTrip extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'uber_trips';

    protected $fillable = [
        'user_id',
        'cs_order_id',
        'revervation_id',
        'renter_id',
        'email',
        'fname',
        'lname',
        'phone',
        'start_latitude',
        'start_longitude',
        'start_address',
        'end_latitude',
        'end_longitude',
        'end_address',
        'pickup_time',
        'sender_name',
        'note',
        'status',
        'trip_id',
        'uber_status',
        'uber_data',
        'pickup_timing',
        'drop_timing',
        'payer_id',
        'fare',
        'created',
        'updated',
    ];
}
