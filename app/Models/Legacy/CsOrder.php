<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CsOrder extends LegacyModel
{
    protected $table = 'cs_orders';

    protected $fillable = [
        'increment_id',
        'user_id',
        'renter_id',
        'vehicle_id',
        'vehicle_name',
        'pickup_address',
        'lat',
        'lng',
        'start_datetime',
        'end_datetime',
        'status',
        'start_timing',
        'end_timing',
        'start_odometer',
        'end_odometer',
        'accepted_time',
        'auto_renew',
        'parent_id',
        'rent',
        'tax',
        'dia_fee',
        'extra_mileage_fee',
        'emf_tax',
        'emf_status',
        'damage_fee',
        'lateness_fee',
        'lateness_fee_status',
        'uncleanness_fee',
        'cancellation_fee',
        'cancel_note',
        'discount',
        'credit_amt',
        'misc',
        'paid_amount',
        'bad_debt',
        'dia_bad_debt',
        'details',
        'note',
        'cc_no',
        'cc_token_id',
        'payment_status',
        'deposit',
        'dpa_status',
        'deposit_type',
        'insurance_amt',
        'insu_status',
        'initial_fee',
        'initial_fee_tax',
        'initial_discount',
        'infee_status',
        'toll',
        'pending_toll',
        'toll_status',
        'notified',
        'dia_insu',
        'dia_insu_status',
        'timezone',
        'currency',
        'review_status',
        'checkr_status',
        'payout_id',
        'user_ip',
        'pto',
        'delivery',
        'adjusted_actual_time',
        'created',
        'modified',
    ];
    protected $hidden = [
        'cc_token_id',
    ];
    protected $guarded = [
        'id',
    ];

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
