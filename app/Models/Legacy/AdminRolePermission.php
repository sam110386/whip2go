<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRolePermission extends LegacyModel
{
     
    protected $table = 'admin_role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
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
