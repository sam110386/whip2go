<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRolePermission extends LegacyModel
{
    protected $table = 'admin_role_permissions';

    protected $casts = [
        'id' => 'integer',
        'role_id' => 'integer',
        'permission_id' => 'integer',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(AdminPermission::class, 'permission_id');
    }
}
