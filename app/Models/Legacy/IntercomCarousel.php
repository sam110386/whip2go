<?php

namespace App\Models\Legacy;

class IntercomCarousel extends LegacyModel
{
    protected $table = 'intercom_carousels';
    
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'screen',
        'intercom',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
