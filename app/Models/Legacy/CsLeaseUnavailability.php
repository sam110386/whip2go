<?php

namespace App\Models\Legacy;

class CsLeaseUnavailability extends LegacyModel
{
    protected $table = 'cs_lease_unavailabilities';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'date',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
