<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class LegacyModel extends Model
{
    /**
     * Cake tables mostly do not rely on Laravel timestamps.
     */
    public $timestamps = false;

    /**
     * Keep mass assignment open for parity with legacy data writes.
     */
    protected $guarded = [];
}

