<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class Loan extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'loans';

    protected $fillable = [
        'user_id',
        'loan_amt',
        'status',
        'created',
        'updated',
    ];
}
