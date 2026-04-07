<?php

namespace App\Models\Legacy;

class AdminModule extends LegacyModel
{
    protected $table = 'admin_modules';

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'order' => 'integer',
        'status' => 'integer',
    ];
}

