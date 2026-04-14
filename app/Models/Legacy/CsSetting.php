<?php

namespace App\Models\Legacy;

class CsSetting extends LegacyModel
{
    protected $table = 'cs_settings';

    protected $fillable = [
        'user_id',
        'address',
        'address_lat',
        'address_lng',
        'rental_threshold',
        'vh_mileage_threshold',
        'passtime_threshold',
        'passtime_tolls',
        'passtime_dealerid',
        'passtime',
        'gps_provider',
        'ituran_usr',
        'ituran_pwd',
        'tdk_setting',
        'vehicle_financing',
        'vehicle_program',
        'marketplace_auth_require',
        'rental_with_insurance',
        'delivery',
        'geotab_server',
        'geotab_user',
        'geotab_pwd',
        'geotab_db',
        'onestepgps',
        'smartcar_client_id',
        'smartcar_secret',
        'allowed_miles',
        'subscription_allowed_miles',
        'min_rental_period',
        'max_rental_period',
        'max_stripe_balance',
        'preparation_time',
        'booking_validation',
        'driver_checker',
        'locations',
        'autopi_token',
        'listing_rule',
        'unlist_rules',
        'insurance_payer',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
