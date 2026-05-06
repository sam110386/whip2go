<?php

namespace App\Models\Legacy;

class CsReservationPayment extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_reservation_payments';

    protected $fillable = [
        'reservation_id',
        'type',
        'amount',
        'rent',
        'tax',
        'dia_fee',
        'currency',
        'transaction_id',
        'payer_id',
        'txntype',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
