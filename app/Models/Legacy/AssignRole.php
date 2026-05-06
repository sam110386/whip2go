<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class AssignRole extends LegacyModel
{
     
    protected $table = 'assign_roles';

    protected $fillable = [
        'role_id',
        'permission_id',
    ];
}
