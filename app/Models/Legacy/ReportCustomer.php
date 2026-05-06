<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class ReportCustomer extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'report_customers';

    protected $fillable = [
        'user_id',
        'renter_id',
        'cs_order_id',
        'increment_id',
        'vehicle_id',
        'days',
        'miles',
        'rent',
        'extra_mile_fee',
        'tax',
        'dia_fee',
        'fixed_amt',
        'total_rent',
        'calculated_insurance',
        'insurance',
        'insurance_driver',
        'total_billed',
        'uncollected',
        'total_collected',
        'emf_collected',
        'tax_collected',
        'dia_fee_collected',
        'revpart',
        'gross_revenue',
        'driver_credit',
        'total_net_pay',
        'transferred',
        'net_transferred',
        'revshare',
        'tax_included',
        'pending',
        'start_datetime',
        'end_datetime',
        'total_program_cost',
        'down_payment_goal',
        'write_down_allocation',
        'finance_allocation',
        'maintenance_allocation',
        'disposition_fee',
        'stripe_fee',
        'total_latefee',
        'collected_latefee',
        'status',
        'timezone',
        'last_executed',
        'created',
        'updated',
    ];
}
