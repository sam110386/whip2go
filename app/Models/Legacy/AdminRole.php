<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends LegacyModel
{
    protected $table = 'admin_roles';

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminPermission::class,
            (new AdminRolePermission())->getTable(),
            'role_id',
            'permission_id'
        );
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }

    public function roleMenus(): HasMany
    {
        return $this->hasMany(AdminRoleMenu::class, 'role_id');
    }
}
