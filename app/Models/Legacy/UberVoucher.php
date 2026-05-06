<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UberVoucher extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'uber_vouchers';

    protected $fillable = [
        'user_id',
        'voucher',
        'rule_data',
        'status',
        'created',
        'updated',
    ];
}
