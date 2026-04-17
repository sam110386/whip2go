<?php

namespace App\Models\Legacy;

class CsOrderReview extends LegacyModel
{
    protected $table = 'cs_order_reviews';

    protected $fillable = [
        'cs_order_id',
        'reservation_id',
        'event',
        'details',
        'original_amt',
        'refund_amt',
        'mileage',
        'is_cleaned',
        'vehicle_service',
        'service_date',
        'extra',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
