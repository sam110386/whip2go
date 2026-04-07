<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends LegacyModel
{
    protected $table = 'wishlists';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'vehicle_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
