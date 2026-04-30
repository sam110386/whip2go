<?php

namespace App\Models\Legacy;

class SavvyDealer extends LegacyModel
{
    protected $table = 'savvy_dealers';

    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'user_id',
        'search_url',
        'status',
        'filters',
        'last_processed',
        'run_now',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
