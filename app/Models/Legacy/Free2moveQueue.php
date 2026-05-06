<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class Free2moveQueue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'free2move_queue';

    protected $fillable = [
        'data',
        'status',
        'created',
    ];
}
