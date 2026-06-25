<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AuditReportLog extends LegacyModel
{
     
    protected $table = 'audit_report_logs';

    protected $fillable = [
        'cs_order_id',
        'increment_id',
        'user_id',
        'start_datetime',
        'end_datetime',
        'first_name',
        'last_name',
        'report_id',
        'status',
    ];
}
