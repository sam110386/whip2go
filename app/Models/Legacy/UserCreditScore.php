<?php

namespace App\Models\Legacy;

class UserCreditScore extends LegacyModel
{
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
