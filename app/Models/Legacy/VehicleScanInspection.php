<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class VehicleScanInspection extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'vehicle_scan_inspections';

    protected $fillable = [
        'case_id',
        'vehicle_id',
        'parent_order_id',
        'order_id',
        'token',
        'status',
        'created',
    ];
}
