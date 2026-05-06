<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class DriverCredit extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'driver_credits';

    protected $fillable = [
        'renter_id',
        'owner_id',
        'by_admin',
        'cs_order_id',
        'amount',
        'note',
        'created',
        'updated',
    ];
}
