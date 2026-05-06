<?php

namespace App\Models\Legacy;

use App\Models\Legacy\AdminRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password',
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
        // 'password',
        'ss_no',
        'ein_no',
        'verify_token',
        'token',
        'stripe_key',
        'cc_token_id',
    ];
    protected $guarded = [
        'id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function userLicenseDetail(): HasOne
    {
        return $this->hasOne(UserLicenseDetail::class, 'user_id');
    }
}
