<?php

namespace App\Models\Legacy;

class RevSetting extends LegacyModel
{
    protected $table = 'rev_settings';

    protected $fillable = [
        'user_id',
        'rev',
        'rental_rev',
        'tax_included',
        'dia_fee',
        'transfer_rev',
        'transfer_insu',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
