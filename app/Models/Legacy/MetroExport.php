<?php

namespace App\Models\Legacy;

class MetroExport extends LegacyModel
{
    protected $table = 'metro_exports';

    protected $fillable = [
        'filename',
        'start',
        'end',
        'offset',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
