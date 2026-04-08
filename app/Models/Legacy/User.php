<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends LegacyModel
{
    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'first_name',
        'middle_name',
        'last_name',
        'business_name',
        'photo',
        'contact_number',
        'address',
        'address_lat',
        'address_lng',
        'licence_number',
        'licence_state',
        'ss_no',
        'ein_no',
        'dob',
        'city',
        'state',
        'zip',
        'licence_type',
        'licence_exp_date',
        'created',
        'modified',
        'verify_token',
        'is_verified',
        'token',
        'status',
        'trash',
        'is_renter',
        'is_owner',
        'is_driver',
        'is_passenger',
        'is_staff',
        'staff_parent',
        'is_dealer',
        'dealer_id',
        'stripe_key',
        'auto_start',
        'cc_token_id',
        'license_doc_1',
        'license_doc_2',
        'details',
        'checkr_status',
        'notify_email',
        'uberlyft_verified',
        'uber_lyft',
        'auto_renew',
        'bank',
        'is_admin',
        'role_id',
        'timezone',
        'parent_id',
        'currency',
        'distance_unit',
        'address_doc',
        'representative_name',
        'representative_role',
        'representative_sign',
        'company_name',
        'company_address',
        'company_city',
        'company_state',
        'company_zip',
        'company_country',

    ];

    protected $hidden = [
        'password'
    ];

    protected $guarded = [
        'id',
        'is_admin'
    ];



    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    public function ordersAsRenter(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'renter_id');
    }

    public function ordersAsOwner(): HasMany
    {
        return $this->hasMany(CsOrder::class, 'user_id');
    }

    public function reservationsAsRenter(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'renter_id');
    }

    public function reservationsAsOwner(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'user_id');
    }

    public function defaultCard(): HasOne
    {
        return $this->hasOne(UserCcToken::class, 'id', 'cc_token_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }
}
