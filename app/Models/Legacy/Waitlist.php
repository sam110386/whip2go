<?php

namespace App\Models\Legacy;

class Waitlist extends LegacyModel
{
    protected $table = 'waitlist';

    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'status',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
