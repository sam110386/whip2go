<?php

namespace App\Models\Legacy;

class VehicleOffer extends LegacyModel
{
    protected $table = 'vehicle_offers';

    protected $fillable = [
        'admin_id',
        'dealer_id',
        'user_id',
        'vehicle_id',
        'initial_fee',
        'deposit_amt',
        'insurance',
        'emf',
        'miles',
        'day_rent',
        'rent_opt',
        'initial_fee_opt',
        'deposit_opt',
        'duration_opt',
        'total_initial_fee',
        'total_deposit_amt',
        'driver_phone',
        'start_datetime',
        'totalcost',
        'downpayment',
        'equityshare',
        'program_fee',
        'total_insurance',
        'total_program_cost',
        'days',
        'target_days',
        'duration',
        'rideshare',
        'pto',
        'goal',
        'fare_type',
        'write_down_allocation',
        'finance_allocation',
        'maintenance_allocation',
        'disposition_fee',
        'insurance_supplier',
        'status',
        'calculation',
        'financing',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
