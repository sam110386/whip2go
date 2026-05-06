<?php

namespace App\Models\Legacy;

class UserCreditScore extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'user_credit_scores';

    protected $fillable = [
        'user_id',
        'status',
        'score',
        'repossession',
        'data',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
