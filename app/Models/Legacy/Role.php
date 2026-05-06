<?php

namespace App\Models\Legacy;

class Role extends LegacyModel
{
     
    protected $table = 'roles';

    protected $fillable = [
        'title',
        'status',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
