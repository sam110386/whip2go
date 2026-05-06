<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AuditReport extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'audit_reports';

    protected $fillable = [
        'type',
        'status',
        'start_date',
        'end_date',
        'file_name',
        'created',
    ];
}
