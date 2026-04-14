<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsOrderPayment extends LegacyModel
{
    protected $table = 'cs_order_payments';

    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'rent',
        'tax',
        'dia_fee',
        'currency',
        'dealer_amt',
        'transaction_id',
        'payer_id',
        'txntype',
        'cs_transfer',
        'owner_payout_id',
        'status',
        'charged_at',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

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
