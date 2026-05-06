<?php

namespace App\Models\Legacy;

class Timezone extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
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
