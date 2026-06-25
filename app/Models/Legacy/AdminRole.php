<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends LegacyModel
{
     
    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'parent_id',
        'slug',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
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
