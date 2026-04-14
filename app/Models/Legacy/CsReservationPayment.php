<?php

namespace App\Models\Legacy;

class CsReservationPayment extends LegacyModel
{
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
