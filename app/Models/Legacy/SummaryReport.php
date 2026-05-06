<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class SummaryReport extends LegacyModel
{
     
    protected $table = 'summary_reports';

    protected $fillable = [
        'user_id',
        'increment_id',
        'start_datetime',
        'end_datetime',
        'rent',
        'past_m_rent',
        'differ_m_rent',
        'lateness_fee',
        'past_m_lateness_fee',
        'differ_m_lateness_fee',
        'dia_fee',
        'past_m_dia_fee',
        'differ_m_dia_fee',
        'initial_fee',
        'past_m_initial_fee',
        'differ_m_initial_fee',
        'extra_mileage_fee',
        'past_m_emf',
        'differ_m_emf',
        'tax',
        'past_m_tax',
        'differ_m_tax',
        'dia_insu',
        'past_m_dia_insu',
        'differ_m_dia_insu',
        'total_collected',
        'past_m_total_collected',
        'differ_m_total_collected',
        'insurance_amt',
        'past_m_insurance_amt',
        'differ_m_insurance_amt',
        'insurance_collected',
        'past_m_insurance_collected',
        'differ_m_insurance_collected',
        'dia_insu_collected',
        'past_m_dia_insu_collected',
        'differ_m_dia_insu_collected',
        'rev_share',
        'dealer_payout',
        'differ_m_dealer_payout',
        'past_m_payout',
        'total_payout',
        'paid_payout',
        'net_paid_payout',
        'differ_paid_payout',
        'booking_status',
        'wallet_refund',
        'stripe_refund',
        'rent_wallet_refund',
        'insu_wallet_refund',
        'rent_stripe_refund',
        'insu_stripe_refund',
        'date_from',
        'date_to',
        'processed',
    ];
}
