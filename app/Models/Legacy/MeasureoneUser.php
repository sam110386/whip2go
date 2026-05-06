<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class MeasureoneUser extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'measureone_users';

    protected $fillable = [
        'user_id',
        'individual_id',
        'datarequest_id',
        'datasource_id',
        'datasource_name',
        'transaction_id',
        'income',
        'status',
        'paystub',
        'created',
        'modified',
    ];
}
