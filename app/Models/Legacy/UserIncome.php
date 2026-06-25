<?php

namespace App\Models\Legacy;

class UserIncome extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'user_incomes';

    protected $fillable = [
        'user_id',
        'income',
        'provenincome',
        'pay_stub',
        'pay_stub_2',
        'utility_bill',
        'utility_bill_2',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
