<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class TinnyUrl extends LegacyModel
{
     
    protected $table = 'tinny_urls';

    protected $fillable = [
        'ukey',
        'target',
        'expire',
    ];
}
