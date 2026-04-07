<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends LegacyModel
{
    protected $table = 'vehicles';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
        'booked' => 'integer',
        'trash' => 'integer',
        'waitlist' => 'integer',
        'passtime_status' => 'integer',
        'multi_location' => 'integer',
        'is_featured' => 'integer',
        'visibility' => 'integer',
        'from_feed' => 'integer',
        'doors' => 'integer',
        'total_mileage' => 'integer',
        'last_mile' => 'integer',
        'rate' => 'float',
        'day_rent' => 'float',
        'allowed_miles' => 'float',
        'msrp' => 'float',
        'premium_msrp' => 'float',
        'kbbnadaWholesaleBook' => 'float',
        'vehicleCostInclRecon' => 'float',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'vehicle_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'vehicle_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class, 'vehicle_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(VehicleLocation::class, 'vehicle_id');
    }

    public function vehicleSetting(): HasOne
    {
        return $this->hasOne(VehicleSetting::class, 'vehicle_id');
    }

    public function depositRule(): HasOne
    {
        return $this->hasOne(DepositRule::class, 'vehicle_id');
    }
}
