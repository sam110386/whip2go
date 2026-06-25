<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsUserLicenseDetail extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_user_license_details';

    protected $fillable = [
        'user_id',
        'jurisdictionRestrictionCodes',
        'jurisdictionEndorsementCodes',
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
}
