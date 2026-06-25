<?php

namespace App\Models\Legacy;

class SavvyDealer extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'savvy_dealers';

    protected $fillable = [
        'user_id',
        'search_url',
        'status',
        'filters',
        'last_processed',
        'created',
        'updated',
        'run_now',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
