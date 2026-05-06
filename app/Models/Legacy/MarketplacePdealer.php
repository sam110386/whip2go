<?php

namespace App\Models\Legacy;

class MarketplacePdealer extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
