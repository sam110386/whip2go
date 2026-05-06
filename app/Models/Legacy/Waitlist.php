<?php

namespace App\Models\Legacy;

class Waitlist extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'waitlist';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
