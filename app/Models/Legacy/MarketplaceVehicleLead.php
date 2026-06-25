<?php

namespace App\Models\Legacy;

class MarketplaceVehicleLead extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'marketplace_vehicle_leads';

    protected $fillable = [
        'user_id',
        'renter_id',
        'list_id',
        'program',
        'options',
        'vin',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
