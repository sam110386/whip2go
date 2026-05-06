<?php

namespace App\Models\Legacy;

class CsLeaseUnavailability extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
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
