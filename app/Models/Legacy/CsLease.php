<?php

namespace App\Models\Legacy;

class CsLease extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_leases';

    protected $fillable = [
        'vehicle_id',
        'vehicle_unique_id',
        'vehicle_name',
        'user_id',
        'pickup_address',
        'lat',
        'lng',
        'start_date',
        'end_date',
        'dropoff_address',
        'rate',
        'min_hour',
        'max_hour',
        'details',
        'status',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
