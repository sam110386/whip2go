<?php

namespace App\Models\Legacy;

class IntercomCarousel extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'intercom_carousels';

    protected $fillable = [
        'screen',
        'intercom',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
