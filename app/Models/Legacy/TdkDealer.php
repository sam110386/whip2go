<?php

namespace App\Models\Legacy;

class TdkDealer extends LegacyModel
{
    protected $table = 'tdk_dealers';

    protected $fillable = [
        'user_id',
        'metro_city',
        'metro_state',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
