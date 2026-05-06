<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class TransactionMismatchesView extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'transaction_mismatches_view';

    protected $fillable = [
        'cpl_order_id',
        'cpl_created',
        'cpl_amount',
        'cpl_transaction_id',
        'amount',
        'transaction_id',
        'cs_order_id',
        'charged_at',
        'twilio_settings',
        'twilio_settings',
        'dispacher_id',
        'status',
        'award_default',
        'twilio_sid',
        'twilio_authtoken',
        'twilio_from',
        'arrive_msg',
        'pickup_msg',
        'drop_msg',
        'kiosk_msg',
        'driver_ref_msg',
        'created',
    ];
}
