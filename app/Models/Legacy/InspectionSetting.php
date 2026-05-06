<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class InspectionSetting extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'inspection_settings';

    protected $fillable = [
        'status',
        'schedule',
        'created',
    ];
}
