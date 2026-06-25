<?php

namespace App\Models\Legacy;

class CabName extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
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
