<?php

namespace App\Models\Legacy;

class UserReport extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'user_reports';

    protected $fillable = [
        'user_id',
        'channel',
        'checkr_id',
        'checkr_reportid',
        'motor_vehicle_report_id',
        'status',
        'report',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
