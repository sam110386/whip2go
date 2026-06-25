<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class UserNote extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'user_notes';

    protected $fillable = [
        'user_id',
        'admin_id',
        'note',
        'created',
    ];
}
