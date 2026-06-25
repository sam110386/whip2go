<?php

namespace App\Models\Legacy;

class CsWorkingHour extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_working_hours';

    protected $fillable = [
        'user_id',
        'sunday',
        'sun_start',
        'sun_end',
        'monday',
        'mon_start',
        'mon_end',
        'tuesday',
        'tue_start',
        'tue_end',
        'wednesday',
        'wed_start',
        'wed_end',
        'thursday',
        'thu_start',
        'thu_end',
        'friday',
        'fri_start',
        'fri_end',
        'saturday',
        'sat_start',
        'sat_end',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
