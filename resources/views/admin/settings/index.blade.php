@extends('admin.layouts.app')

@section('title', 'Account General Setting')

@php
    $rentalThresholds = [];
    $rentalThresholds[0] = 'Disable';
    for ($h = 1; $h <= 48; $h++) {
        $rentalThresholds[$h] = $h . ' Hour' . ($h === 1 ? '' : 's');
    }
    $financingOptions = ['1' => 'Rent', '2' => 'Rent To Own', '3' => 'Lease', '4' => 'Lease To Own'];
    $bv = [];
    if ($setting && is_array($setting->booking_validation ?? null)) {
        $bv = $setting->booking_validation;
    }
    $gps = data_get($setting, 'gps_provider', 'geotab');
    $pst = data_get($setting, 'passtime', 'passtime');
    $userId ??= 0;
    $locationRows ??= [];
    $depositTemplate ??= null;
    $googleMapsKey ??= '';
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Account General</span>
                    Setting
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-heading">
            <h5 class="panel-title">Basic Setting</h5>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ url('admin/settings/index', base64_encode((string)$userId)) }}" id="SettingAdminIndexForm" class="form-horizontal settings-grid">
                @csrf
                <fieldset class="settings-row">
                    <legend><strong>Basic setting</strong></legend>

                    <label>Rental threshold *</label>
                    <select name="CsSetting[rental_threshold]" id="CsSettingRentalThreshold" class="form-control" required>
                        @foreach ($rentalThresholds as $val => $label)
                            <option value="{{ $val }}" @selected((int)data_get($setting, 'rental_threshold', 0) === (int)$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Hours before a notification if a booking expires without completion (0 = disable).</p>

                    <label>Vehicle mileage threshold *</label>
                    <input type="number" min="0" name="CsSetting[vh_mileage_threshold]" id="CsSettingVhMileageThreshold" class="form-control" required
                           value="{{ data_get($setting, 'vh_mileage_threshold', 0) }}">
                    <p class="hint">Notify when mileage exceeds this value; 0 disables.</p>

                    <label>GPS provider</label>
                    <select name="CsSetting[gps_provider]" id="CsSettingGpsProvider" class="form-control">
                        @foreach (['geotab' => 'GeoTab', 'passtime' => 'Passtime', 'ituran' => 'Ituran', 'onestepgps' => 'One Step GPS', 'autopi' => 'AutoPi'] as $val => $label)
                            <option value="{{ $val }}" @selected($gps === $val)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <label>GPS starter</label>
                    <select name="CsSetting[passtime]" id="CsSettingPasstime" class="form-control">
                        @foreach (['geotab' => 'GeoTab', 'passtime' => 'Passtime', 'ituran' => 'Ituran', 'onestepgps' => 'One Step GPS', 'autopi' => 'AutoPi', 'geotabkeyless' => 'GeoTab Keyless'] as $val => $label)
                            <option value="{{ $val }}" @selected($pst === $val)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="form-group autopi">
                        <label>AutoPi API token *</label>
                        <input type="text" name="CsSetting[autopi_token]" id="CsSettingAutopiToken" class="form-control" value="{{ data_get($setting, 'autopi_token', '') }}">
                    </div>
                    <div class="form-group smartcar">
                        <label>SmartCar client ID</label>
                        <input type="text" name="CsSetting[smartcar_client_id]" id="CsSettingSmartcarClientId" class="form-control" value="{{ data_get($setting, 'smartcar_client_id', '') }}">
                    </div>
                    <div class="form-group smartcar">
                        <label>SmartCar secret</label>
                        <input type="text" name="CsSetting[smartcar_secret]" id="CsSettingSmartcarSecret" class="form-control" value="{{ data_get($setting, 'smartcar_secret', '') }}">
                    </div>
                    <div class="form-group passtime">
                        <label>Passtime dealer #</label>
                        <input type="text" name="CsSetting[passtime_dealerid]" id="CsSettingPasstimeDealerid" class="form-control" value="{{ data_get($setting, 'passtime_dealerid', '') }}">
                    </div>
                    <div class="form-group ituran">
                        <label>Ituran username</label>
                        <input type="text" name="CsSetting[ituran_usr]" id="CsSettingIturanUsr" class="form-control" value="{{ data_get($setting, 'ituran_usr', '') }}">
                    </div>
                    <div class="form-group ituran">
                        <label>Ituran password</label>
                        <input type="text" name="CsSetting[ituran_pwd]" id="CsSettingIturanPwd" class="form-control" value="{{ data_get($setting, 'ituran_pwd', '') }}">
                    </div>
                    <div class="form-group geotab">
                        <label>GeoTab server name *</label>
                        <input type="text" name="CsSetting[geotab_server]" id="CsSettingGeotabServer" class="form-control" value="{{ data_get($setting, 'geotab_server', '') }}">
                    </div>
                    <div class="form-group geotab">
                        <label>GeoTab username *</label>
                        <input type="text" name="CsSetting[geotab_user]" id="CsSettingGeotabUser" class="form-control" value="{{ data_get($setting, 'geotab_user', '') }}">
                    </div>
                    <div class="form-group geotab">
                        <label>GeoTab password *</label>
                        <input type="password" name="CsSetting[geotab_pwd]" id="CsSettingGeotabPwd" class="form-control" value="{{ data_get($setting, 'geotab_pwd', '') }}" autocomplete="off">
                    </div>
                    <div class="form-group geotab">
                        <label>GeoTab database *</label>
                        <input type="text" name="CsSetting[geotab_db]" id="CsSettingGeotabDb" class="form-control" value="{{ data_get($setting, 'geotab_db', '') }}">
                    </div>
                    <div class="form-group geotab">
                        <button type="button" class="btn btn-primary" onclick="validateGeoTab()">Validate Geotab settings</button>
                    </div>
                    <div class="form-group onestepgps">
                        <label>One Step GPS key</label>
                        <input type="text" name="CsSetting[onestepgps]" id="CsSettingOnestepgps" class="form-control" value="{{ data_get($setting, 'onestepgps', '') }}">
                    </div>
                    <div class="form-group onestepgps">
                        <button type="button" class="btn btn-primary" onclick="validateOneStepGPSKey()">Validate One Step GPS key</button>
                    </div>
                    @if ($gps === 'geotab' || $pst === 'geotab')
                        <div class="form-group geotab">
                            <button type="button" class="btn btn-primary" id="syncDeviceWithGeotab" onclick="syncVehicleWithGeotab()">Sync Geotab vehicles</button>
                        </div>
                    @endif
                    @if ($gps === 'onestepgps' || $pst === 'onestepgps')
                        <div class="form-group onestepgps">
                            <button type="button" class="btn btn-primary" onclick="syncVehicleWithOnestep()">Sync OneStep vehicles</button>
                        </div>
                    @endif
                    @if ($gps === 'autopi' || $pst === 'autopi')
                        <div class="form-group autopi">
                            <button type="button" class="btn btn-primary" id="sycnAutoPiVehicle" onclick="pullAutoPiVehicle()">Sync AutoPi vehicles</button>
                        </div>
                    @endif

                    <label>Vehicle lock threshold (hours) *</label>
                    <input type="number" min="0" max="999" name="CsSetting[passtime_threshold]" id="CsSettingPasstimeThreshold" class="form-control" required
                           value="{{ data_get($setting, 'passtime_threshold', 0) }}">

                    <label>Finance type *</label>
                    <div class="settings-row flex">
                        <select name="CsSetting[vehicle_financing]" id="CsSettingVehicleFinancing" class="form-control" style="flex:1; min-width:200px;" required>
                            @foreach ($financingOptions as $val => $label)
                                <option value="{{ $val }}" @selected((string)data_get($setting, 'vehicle_financing', '1') === (string)$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-warning" onclick="SyncVehicleFinancing()">Update all vehicles</button>
                    </div>

                    <label>Vehicle booking *</label>
                    <select name="CsSetting[marketplace_auth_require]" id="CsSettingMarketplaceAuthRequire" class="form-control" required>
                        <option value="0" @selected((int)data_get($setting, 'marketplace_auth_require', 0) === 0)>Capture payment</option>
                        <option value="1" @selected((int)data_get($setting, 'marketplace_auth_require', 0) === 1)>Authorize payment</option>
                    </select>

                    <label>Delivery option *</label>
                    <select name="CsSetting[delivery]" id="CsSettingDelivery" class="form-control" required>
                        <option value="0" @selected((int)data_get($setting, 'delivery', 0) === 0)>Inactive</option>
                        <option value="1" @selected((int)data_get($setting, 'delivery', 0) === 1)>Active</option>
                    </select>

                    <label>Vehicle allowed miles (day) *</label>
                    <div class="settings-row flex">
                        <input type="number" step="0.01" name="CsSetting[allowed_miles]" id="CsSettingAllowedMiles" class="form-control" style="flex:1; min-width:160px;" required
                               value="{{ data_get($setting, 'allowed_miles', 150) }}">
                        <button type="button" class="btn btn-warning" onclick="SyncVehicleAllowedMiles()">Update all vehicles</button>
                    </div>

                    <label>Max negative Stripe balance *</label>
                    <input type="number" name="CsSetting[max_stripe_balance]" id="CsSettingMaxStripeBalance" class="form-control" required
                           value="{{ data_get($setting, 'max_stripe_balance', 1000) }}">

                    <label>Preparation time (hours) *</label>
                    <input type="number" name="CsSetting[preparation_time]" id="CsSettingPreparationTime" class="form-control" required
                           value="{{ data_get($setting, 'preparation_time', 0) }}">

                    <label>Driver background check</label>
                    <select name="CsSetting[driver_checker]" id="CsSettingDriverChecker" class="form-control">
                        <option value="DIG" @selected(data_get($setting, 'driver_checker', 'DIG') === 'DIG')>Digisure API</option>
                        <option value="CKR" @selected(data_get($setting, 'driver_checker', 'DIG') === 'CKR')>Checkr API</option>
                    </select>

                    @if ($depositTemplate)
                        <label>Roadside assistance included in fee *</label>
                        <div class="settings-row flex">
                            <select name="DepositTemplate[roadside_assistance_included]" id="DepositTemplateRoadsideAssistanceIncluded" class="form-control" style="flex:1;">
                                <option value="1" @selected((int)$depositTemplate->roadside_assistance_included === 1)>Yes</option>
                                <option value="0" @selected((int)$depositTemplate->roadside_assistance_included === 0)>No</option>
                            </select>
                            <button type="button" class="btn btn-warning" onclick="updateFareType('roadside_assistance_included')">Sync</button>
                        </div>

                        <label>Maintenance included in fee *</label>
                        <div class="settings-row flex">
                            <select name="DepositTemplate[maintenance_included_fee]" id="DepositTemplateMaintenanceIncludedFee" class="form-control" style="flex:1;">
                                <option value="1" @selected((int)$depositTemplate->maintenance_included_fee === 1)>Yes</option>
                                <option value="0" @selected((int)$depositTemplate->maintenance_included_fee === 0)>No</option>
                            </select>
                            <button type="button" class="btn btn-warning" onclick="updateFareType('maintenance_included_fee')">Sync</button>
                        </div>
                    @endif

                    <label>Default address</label>
                    <div class="settings-row flex" style="align-items:flex-start;">
                        <div style="flex:1;">
                            <input type="text" name="CsSetting[address]" id="CsSettingAddress" class="form-control geocode-main" placeholder="Vehicle address"
                                   value="{{ data_get($setting, 'address', '') }}" style="width:100%;">
                            <input type="hidden" name="CsSetting[address_lat]" id="CsSettingAddressLat" value="{{ data_get($setting, 'address_lat', '') }}">
                            <input type="hidden" name="CsSetting[address_lng]" id="CsSettingAddressLng" value="{{ data_get($setting, 'address_lng', '') }}">
                        </div>
                        <button type="button" class="btn btn-warning" onclick="SyncVehicleDefaultAddress()">Sync all vehicles</button>
                    </div>
                </fieldset>

                <fieldset class="settings-row panelbody" data-rel-address="{{ count($locationRows) }}">
                    <legend><strong>Vehicle address (multi-location)</strong></legend>
                    <label>Enable multilocation</label>
                    <select name="CsSetting[multi_location]" id="CsSettingMultiLocation" class="form-control">
                        <option value="1" @selected((int)data_get($setting, 'multi_location', 0) === 1)>Yes</option>
                        <option value="0" @selected((int)data_get($setting, 'multi_location', 0) === 0)>No</option>
                    </select>

                    <div id="address_more">
                        @foreach ($locationRows as $k => $loc)
                            <div class="settings-row" id="ele-{{ $k }}" style="border-top:1px solid #eee; padding-top:10px;">
                                <label>Address {{ $k }}</label>
                                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    <input type="text" name="VehicleLocation[{{ $k }}][address]" class="form-control geocodeinput" placeholder="Pickup address"
                                           value="{{ data_get($loc, 'address', '') }}" style="flex:1; min-width:220px;">
                                    <input type="hidden" name="VehicleLocation[{{ $k }}][lat]" id="VehicleLocation{{ $k }}Lat" value="{{ data_get($loc, 'lat', '') }}">
                                    <input type="hidden" name="VehicleLocation[{{ $k }}][lng]" id="VehicleLocation{{ $k }}Lng" value="{{ data_get($loc, 'lng', '') }}">
                                    @if ($k === array_key_first($locationRows))
                                        <button type="button" class="btn btn-default" onclick="address_more(true)">+</button>
                                        <button type="button" class="btn btn-warning" onclick="SyncVehicleAddress()">Update all vehicles</button>
                                    @else
                                        <button type="button" class="btn btn-default" onclick="address_more(false)">−</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="settings-row">
                    <legend><strong>Validate pending booking for</strong></legend>
                    <label><input type="checkbox" name="CsSetting[booking_validation][registration]" value="1" @checked(!empty($bv['registration']))> Required vehicle registration document?</label>
                    <label><input type="checkbox" name="CsSetting[booking_validation][inspection]" value="1" @checked(!empty($bv['inspection']))> Required vehicle inspection document?</label>
                    <label><input type="checkbox" name="CsSetting[booking_validation][income_threshold]" value="1" @checked(!empty($bv['income_threshold']))> Required income threshold?</label>
                    <label><input type="checkbox" name="CsSetting[booking_validation][residency_proof]" value="1" @checked(!empty($bv['residency_proof']))> Require proof of residency?</label>
                </fieldset>

                <input type="hidden" name="CsSetting[id]" id="CsSettingId" value="{{ data_get($setting, 'id', '') }}">
                <input type="hidden" name="CsSetting[user_id]" id="CsSettingUserId" value="{{ $userId }}">
                <input type="hidden" name="CsSetting[encode_user_id]" id="CsSettingEncodeUserId" value="{{ $userId }}">
                @if ($depositTemplate)
                    <input type="hidden" name="DepositTemplate[id]" value="{{ $depositTemplate->id }}">
                @endif

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .settings-grid label { display: block; font-weight: 600; margin-top: 10px; }
        .settings-grid .hint { font-size: 12px; color: #555; margin: 4px 0 0; }
        .settings-row { margin-bottom: 14px; max-width: 720px; }
        .settings-row.flex { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .passtime, .geotab, .geotabkeyless, .ituran, .onestepgps, .smartcar, .autopi { display: none; }
        .form-actions { margin-top: 18px; }
    </style>
@endpush

@push('scripts')
    @if (!empty($googleMapsKey))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places"></script>
    @endif
    <script>
        var autocomplete = [];
        var gPlaceOptions = { types: ['geocode'] };

        function setupCsSettingAddress() {
            var el = document.getElementById('CsSettingAddress');
            if (!el || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
            var autAddress = new google.maps.places.Autocomplete(el, gPlaceOptions);
            google.maps.event.addListener(autAddress, 'place_changed', function () {
                var placeorg = autAddress.getPlace();
                if (!placeorg.geometry) return;
                $('#CsSettingAddressLat').val(placeorg.geometry.location.lat());
                $('#CsSettingAddressLng').val(placeorg.geometry.location.lng());
            });
        }

        function setupAutocomplete(inputs, i) {
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;
            autocomplete.push(new google.maps.places.Autocomplete(inputs[i], gPlaceOptions));
            var idx = autocomplete.length - 1;
            google.maps.event.addListener(autocomplete[idx], 'place_changed', function () {
                var placeorg = autocomplete[idx].getPlace();
                if (!placeorg.geometry) return;
                var n = idx + 1;
                $('#VehicleLocation' + n + 'Lat').val(placeorg.geometry.location.lat());
                $('#VehicleLocation' + n + 'Lng').val(placeorg.geometry.location.lng());
            });
        }

        function initiategplace() {
            autocomplete = [];
            var inputs = document.getElementsByClassName('geocodeinput');
            for (var i = 0; i < inputs.length; i++) {
                setupAutocomplete(inputs, i);
            }
        }

        function address_more(v) {
            var $panel = $('.panelbody');
            var elem = parseInt($panel.attr('data-rel-address'), 10) || 1;
            if (v) {
                if (elem === 5) {
                    alert('Sorry, you cant add more than 5 reccords');
                    return;
                }
                elem++;
                var element = '<div class="settings-row" id="ele-' + elem + '" style="border-top:1px solid #eee;padding-top:10px;">' +
                    '<label>Address ' + elem + '</label>' +
                    '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">' +
                    '<input name="VehicleLocation[' + elem + '][address]" class="form-control geocodeinput" placeholder="Pickup address" type="text" style="flex:1;min-width:220px;">' +
                    '<input id="VehicleLocation' + elem + 'Lat" name="VehicleLocation[' + elem + '][lat]" type="hidden" value="">' +
                    '<input id="VehicleLocation' + elem + 'Lng" name="VehicleLocation[' + elem + '][lng]" type="hidden" value="">' +
                    '<button type="button" class="btn btn-default" onclick="address_more(false)">−</button></div></div>';
                $('#address_more').append(element);
                initiategplace();
            } else {
                $('#address_more #ele-' + elem).remove();
                elem--;
            }
            $panel.attr('data-rel-address', elem);
        }

        jQuery(document).ready(function () {
            initiategplace();
            setupCsSettingAddress();
            jQuery.ajaxSetup({ cache: false });
        });
    </script>
    <script src="{{ asset('js/admin_setting.js') }}"></script>
@endpush
