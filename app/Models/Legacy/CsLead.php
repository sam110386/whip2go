<?php

namespace App\Models\Legacy;

class CsLead extends LegacyModel
{
    protected $table = 'cs_leads';

    protected $fillable = [
        'admin_id',
        'sub_admin_id',
        'type',
        'phone',
        'first_name',
        'last_name',
        'dealer_name',
        'email',
        'address',
        'city',
        'state',
        'postal',
        'status',
        'user_id',
        'intercom_id',
        'created',
        'updated',
    ];

    protected $guarded = [
        'id',
    ];
}
