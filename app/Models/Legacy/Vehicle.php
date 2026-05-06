<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'vehicles';

    protected $fillable = [
        'user_id',
        'cab_type',
        'vehicle_unique_id',
        'vehicle_name',
        'stock_no',
        'status',
        'waitlist',
        'details',
        'plate_number',
        'make',
        'model',
        'color',
        'year',
        'vin_no',
        'insurance_company',
        'insurance_policy_no',
        'insurance_policy_date',
        'insurance_policy_exp_date',
        'inspection_exp_date',
        'state_insp_exp_date',
        'registered_name',
        'registered_state',
        'reg_name_date',
        'reg_name_exp_date',
        'transmition_type',
        'multi_location',
        'address',
        'lat',
        'lng',
        'odometer',
        'rate',
        'fare_type',
        'day_rent',
        'rent_opt',
        'allowed_miles',
        'passtime_serialno',
        'gps_serialno',
        'wireless_gps_serial',
        'passtime_status',
        'registration_image',
        'insurance_image',
        'inspection_image',
        'roadside_assistance_included',
        'maintenance_included_fee',
        'sharing_allowed',
        'booked',
        'total_mileage',
        'last_mile',
        'toll_enabled',
        'passtime_threshold',
        'msrp',
        'premium_msrp',
        'kbbnadaWholesaleBook',
        'vehicleCostInclRecon',
        'interior_color',
        'trim',
        'engine',
        'mpg_city',
        'mpg_hwy',
        'doors',
        'equipment',
        'accudata',
        'disclosure',
        'pto',
        'rideshare',
        'financing',
        'auth_require',
        'battery',
        'availability_date',
        'trash',
        'created',
        'modified',
        'from_feed',
        'sort_order',
        'ccm_auth_no',
        'type',
        'config',
        'visibility',
        'homenet_msrp',
        'homenet_modelnumber',
        'autopi_unit_id',
        'f2m_monthly_price',
        'f2m_residual_amount',
        'is_featured',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
        'booked' => 'integer',
        'trash' => 'integer',
        'waitlist' => 'integer',
        'passtime_status' => 'integer',
        'multi_location' => 'integer',
        'is_featured' => 'integer',
        'visibility' => 'integer',
        'from_feed' => 'integer',
        'doors' => 'integer',
        'total_mileage' => 'integer',
        'last_mile' => 'integer',
        'rate' => 'float',
        'day_rent' => 'float',
        'allowed_miles' => 'float',
        'msrp' => 'float',
        'premium_msrp' => 'float',
        'kbbnadaWholesaleBook' => 'float',
        'vehicleCostInclRecon' => 'float',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'vehicle_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'vehicle_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class, 'vehicle_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(VehicleLocation::class, 'vehicle_id');
    }

    public function vehicleSetting(): HasOne
    {
        return $this->hasOne(VehicleSetting::class, 'vehicle_id');
    }

    public function depositRule(): HasOne
    {
        return $this->hasOne(DepositRule::class, 'vehicle_id');
    }
}
