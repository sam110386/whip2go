<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class RevenueReport extends LegacyModel
{
     
    protected $table = 'revenue_reports';

    protected $fillable = [
        'vehicle_id',
        'vehicle_name',
        'month',
        'bookings',
        'days',
        'revenue_for_month',
        'odometer_for_month',
    ];
}
