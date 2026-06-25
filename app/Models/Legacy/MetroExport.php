<?php

namespace App\Models\Legacy;

class MetroExport extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
