<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class CsVehicleIssue extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_vehicle_issues';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'renter_id',
        'cs_order_id',
        'type',
        'maintenance_issue_detail',
        'roadside_request_detail',
        'accident_datetime',
        'accident_location',
        'police_reported',
        'police_reportno',
        'police_dept_name',
        'accident_description',
        'on_way_tolift',
        'have_passenger',
        'vehicle_damage_description',
        'vehicle_damage_location',
        'vehicle_seen_date',
        'vehicle_insurance',
        'vehicle_insurance_company_name',
        'claim_number',
        'vehicle_other_insurance',
        'other_vehicle_involved',
        'other_party_vehi_make',
        'other_party_vehi_model',
        'other_party_vehi_year',
        'other_party_vehi_vin',
        'other_party_vehi_insurancecompany',
        'other_party_vehi_insurance',
        'other_party_vehi_insurance_claim',
        'other_party_vehi_insuranceexp',
        'other_party_nameaddress',
        'other_party_phone',
        'other_party_driver',
        'other_party_driverphone',
        'other_party_driveradress',
        'other_party_driverlicense',
        'other_party_driverlicstate',
        'other_party_driverlicexpdate',
        'other_party_vehiclelocation',
        'other_party_damage_detail',
        'other_party_injury_details',
        'injury',
        'witness',
        'working_with_delivery',
        'orders_from_delivery',
        'way_to_drop_off_delivery',
        'status',
        'extra',
        'ccm_claim_number',
        'total_damage',
        'insurance_coverage',
        'company_cost',
        'intercom_id',
        'amount',
        'violationType',
        'created',
        'updated',
    ];
}
