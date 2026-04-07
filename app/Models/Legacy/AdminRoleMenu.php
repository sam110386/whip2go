<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRoleMenu extends LegacyModel
{
    protected $table = 'admin_role_menu';

    protected $casts = [
        'id' => 'integer',
        'role_id' => 'integer',
        'menu_id' => 'integer',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }
}
