<?php

namespace App\Models\Legacy;

class VehicleVariation extends LegacyModel
{
    protected $table = 'vehicle_variations';

    protected $fillable = [
        'vehicle_id',
    ];
    protected $hidden = [];
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

}
