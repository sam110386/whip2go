<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsVehicleIssueImage extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_vehicle_issue_images';

    protected $fillable = [
        'cs_vehicle_issue_id',
        'image',
        'type',
        'created',
    ];
}
