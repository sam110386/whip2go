<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsOrderPayment extends LegacyModel
{
    protected $table = 'cs_order_payments';

    protected $casts = [
        'id' => 'integer',
        'cs_order_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }
}
