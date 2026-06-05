<?php

namespace App\Models\Legacy;

class VehicleVariation extends LegacyModel
{
    protected $table = 'vehicle_variations';
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'vehicle_id',
        'variant_id',
    ];
    protected $hidden = [];
    protected $guarded = [];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function variant()
    {
        return $this->belongsTo(Vehicle::class, 'variant_id');
    }
}
