<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CsOrder extends LegacyModel
{
    protected $table = 'cs_orders';

    protected $casts = [
        'id' => 'integer',
        'vehicle_id' => 'integer',
        'user_id' => 'integer',
        'renter_id' => 'integer',
        'parent_id' => 'integer',
        'status' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CsOrderPayment::class, 'cs_order_id');
    }

    public function depositRule(): HasOne
    {
        return $this->hasOne(OrderDepositRule::class, 'cs_order_id');
    }
}
