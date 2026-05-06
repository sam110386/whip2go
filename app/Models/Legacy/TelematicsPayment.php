<?php

namespace App\Models\Legacy;

class TelematicsPayment extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'telematics_payments';

    protected $fillable = [
        'telematics_id',
        'amt',
        'txn_id',
        'status',
        'created',
        'last_processed',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
