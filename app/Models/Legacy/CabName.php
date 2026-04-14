<?php

namespace App\Models\Legacy;

class CabName extends LegacyModel
{
    protected $table = 'cab_names';

    protected $fillable = [
        'cab_name',
        'order',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
