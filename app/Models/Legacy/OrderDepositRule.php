<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDepositRule extends LegacyModel
{
    protected $table = 'cs_order_deposit_rules';

    protected $casts = [
        'id' => 'integer',
        'cs_order_id' => 'integer',
        'vehicle_reservation_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(VehicleReservation::class, 'vehicle_reservation_id');
    }
}
