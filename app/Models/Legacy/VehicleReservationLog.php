<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class VehicleReservationLog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'vehicle_reservation_logs';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'status',
        'note',
        'created',
        'updated',
    ];
}
