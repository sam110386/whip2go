<?php

namespace App\Models\Legacy;

class MarketplacePdealer extends LegacyModel
{
    protected $table = 'marketplace_pdealers';

    protected $fillable = [
        'phone',
        'name',
        'address',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
