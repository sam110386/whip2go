<?php

namespace App\Models\Legacy;

class Tracking extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'trackings';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
