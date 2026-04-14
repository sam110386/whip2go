<?php

namespace App\Models\Legacy;

class PopularMarket extends LegacyModel
{
    protected $table = 'popular_markets';

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'radius',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
