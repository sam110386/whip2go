<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AxleStatu extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'axle_status';

    protected $fillable = [
        'order_id',
        'type',
        'axle_client',
        'access_token',
        'account_id',
        'policy',
        'axle_status',
        'extra',
        'expired_on',
        'calculated_insurance',
        'policy_details',
        'updated',
        'created',
    ];
}
