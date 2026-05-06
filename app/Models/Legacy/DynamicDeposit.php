<?php

namespace App\Models\Legacy;

class DynamicDeposit extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_dynamic_deposits';

    protected $fillable = [
        'cs_order_id',
        'amount',
        'process_on',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
