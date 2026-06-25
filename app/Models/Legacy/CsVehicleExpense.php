<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsVehicleExpense extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_vehicle_expenses';

    protected $fillable = [
        'vehicle_issues_id',
        'vehicle_id',
        'amount',
        'type',
        'created',
        'updated',
    ];
}
