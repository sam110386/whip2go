<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends LegacyModel
{
    protected $table = 'users';

    protected $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'is_admin' => 'integer',
        'is_verified' => 'integer',
        'trash' => 'integer',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    public function ordersAsRenter(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'renter_id');
    }

    public function ordersAsOwner(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'user_id');
    }

    public function reservationsAsRenter(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'renter_id');
    }

    public function reservationsAsOwner(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'user_id');
    }

    public function defaultCard(): HasOne
    {
        return $this->hasOne(UserCcToken::class, 'id', 'cc_token_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }
}
