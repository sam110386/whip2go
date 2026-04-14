<?php

namespace App\Models\Legacy;

class CsOrderAvailability extends LegacyModel
{
    protected $table = 'cs_order_availabilities';

    protected $fillable = [
        'lease_id',
        'lease_availability_id',
        'cs_order_id',
        'vehicle_id',
        'vehicle_name',
        'user_id',
        'pickup_address',
        'lat',
        'lng',
        'start_date',
        'start_time',
        'end_time',
        'rate',
        'min_hours',
        'min_rent',
        'details',
        'cancel_time',
        'cancel_note',
        'status',
        'miles',
        'start_timing',
        'end_timing',
        'source',
        'destination',
        'payment_status',
        'sub_total',
        'tax',
        'discount',
        'rent',
        'extra_mileage_fee',
        'total_amt',
        'accepted_time',
        'auto_complete',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
