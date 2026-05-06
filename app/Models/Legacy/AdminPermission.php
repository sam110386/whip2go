<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminPermission extends LegacyModel
{

    protected $table = 'admin_permissions';

    protected $fillable = [
        'name',
        'type',
        'permissions',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            (new AdminRolePermission())->getTable(),
            'permission_id',
            'role_id'
        );
    }
}
