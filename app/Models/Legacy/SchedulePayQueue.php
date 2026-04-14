<?php

namespace App\Models\Legacy;

class SchedulePayQueue extends LegacyModel
{
    protected $table = 'schedule_pay_queues';

    protected $fillable = [
        'cs_order_id',
        'initial_fee_opt',
        'deposit_opt',
        'insurance',
        'start_date',
        'status',
        'process_after',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
