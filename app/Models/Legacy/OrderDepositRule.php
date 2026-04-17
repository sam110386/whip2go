<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDepositRule extends LegacyModel
{
    protected $table = 'cs_order_deposit_rules';

    protected $fillable = [
        'vehicle_reservation_id',
        'cs_order_id',
        'start_datetime',
        'initial_fee',
        'deposit_amt',
        'base_rent',
        'rental',
        'emf',
        'emf_rate',
        'emf_insu_rate',
        'miles',
        'insurance',
        'rental_opt',
        'initial_fee_opt',
        'deposit_opt',
        'duration_opt',
        'duration',
        'tax',
        'totalcost',
        'total_program_cost',
        'downpayment',
        'equityshare',
        'total_equity',
        'num_of_days',
        'total_initial_fee',
        'total_deposit_amt',
        'goal',
        'total_paid',
        'insurance_payer',
        'insurance_lender',
        'write_down_allocation',
        'finance_allocation',
        'maintenance_allocation',
        'disposition_fee',
        'insu_agreed',
        'calculation',
        'financing',
        'msrp',
        'premium_msrp',
        'minimum_payment',
        'minimum_payment_exp_date',
        'selling_option',
        'pickup_data',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

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
