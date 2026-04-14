<?php

namespace App\Models\Legacy;

class Timezone extends LegacyModel
{
    protected $table = 'timezones';

    protected $fillable = [
        'dispacher_id',
        'time_zone',
        'created',
        'modified',
        'network',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
