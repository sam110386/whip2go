<?php

namespace App\Models\Legacy;

use App\Models\Legacy\User;

class OrderExtlog extends LegacyModel
{
    protected $table = 'cs_order_extlogs';
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;

    protected $fillable = [
        'cs_order_id',
        'ext_date',
        'note',
        'amt',
        'owner',
        'admin_count',
        'created',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner', 'id');
    }
    public function csOrder()
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id', 'id');
    }
}
