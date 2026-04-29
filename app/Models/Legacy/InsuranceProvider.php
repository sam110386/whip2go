<?php

namespace App\Models\Legacy;

class InsuranceProvider extends LegacyModel
{
    protected $table = 'insurance_providers';
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'logo',
        'link',
        'status',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
