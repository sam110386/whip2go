<?php

namespace App\Models\Legacy;

class State extends LegacyModel
{
     
    protected $table = 'states';

    protected $fillable = [
        'country_id',
        'state_name',
        'state_3_code',
        'state_code',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
