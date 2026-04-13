<?php

namespace App\Models\Legacy;

class UserLicenseDetail extends LegacyModel
{
    protected $table = 'cs_user_license_details';

    protected $fillable = [
        'user_id',
        'jurisdictionEndorsementCodes',
        'jurisdictionRestrictionCodes',
        'dateOfExpiry',
        'lastName',
        'givenName',
        'dateOfIssue',
        'dateOfBirth',
        'sex',
        'eyeColor',
        'height',
        'addressStreet',
        'addressCity',
        'addressState',
        'addressPostalCode',
        'documentNumber',
        'documentDiscriminator',
        'issuer',
        'created',
        'modified',
    ];

    protected $guarded = [
        'id',
    ];
}
