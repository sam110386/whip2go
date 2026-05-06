<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ReportQueue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'report_queues';

    protected $fillable = [
        'order_id',
        'created',
        'updated',
    ];
}
