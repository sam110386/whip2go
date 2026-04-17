<?php

namespace App\Models\Legacy;

class CsOrderReviewImage extends LegacyModel
{
    protected $table = 'cs_order_review_images';

    protected $fillable = [
        'cs_order_review_id',
        'image',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
