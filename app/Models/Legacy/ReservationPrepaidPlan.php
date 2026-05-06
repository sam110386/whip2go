<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ReservationPrepaidPlan extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'reservation_prepaid_plans';

    protected $fillable = [
        'reservation_id',
        'amount',
        'charged_on',
        'last_attempt',
        'last_attempt_error',
        'status',
        'created',
    ];
}
