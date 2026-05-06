<?php

namespace App\Models\Legacy;

class CsLeaseAvailability extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_lease_availabilities';

    protected $fillable = [
        'lease_id',
        'vehicle_id',
        'vehicle_unique_id',
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
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
