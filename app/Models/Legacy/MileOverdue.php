<?php

namespace App\Models\Legacy;

class MileOverdue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'mile_overdues';

    protected $fillable = [
        'order_id',
        'type',
        'start_odometer',
        'end_odometer',
        'renter_id',
        'allowed_miles',
        'battery',
        'last_percentage',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
