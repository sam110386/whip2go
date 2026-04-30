<?php

namespace App\Models\Legacy;

class TelematicsPayment extends LegacyModel
{
    protected $table = 'telematics_payments';

    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'telematics_id',
        'amt',
        'txn_id',
        'status',
        'last_processed',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
