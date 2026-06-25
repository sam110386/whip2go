<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AtomicUser extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'atomic_users';

    protected $fillable = [
        'user_id',
        'linkedAccount',
        'token',
        'income',
        'trash',
        'company',
        'customerId',
        'payrollId',
        'companyId',
        'created',
    ];
}
