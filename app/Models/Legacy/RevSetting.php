<?php

namespace App\Models\Legacy;

class RevSetting extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
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
