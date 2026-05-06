<?php

namespace App\Models\Legacy;

class CsEavSetting extends LegacyModel
{
     
    protected $table = 'cs_eav_settings';

    protected $fillable = [
        'entity',
        'val',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
