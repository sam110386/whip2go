@extends('layouts.admin')

@section('title', $listTitle ?? 'Vehicle')

@if (!$v && empty($lockedDealerId ?? null))
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@endif

@php
    use App\Support\VehicleAdminSave;
    $v = $vehicle;
    $du = $owner?->distance_unit ?? 'MI';
    $formBase = $vehicleFormActionBase ?? '/admin/vehicles/add';
@endphp

@section('content')
    <h1>{{ $listTitle ?? 'Vehicle' }}</h1>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $v ? $formBase . '/' . base64_encode((string)$v->id) : $formBase }}"
          id="vehicleAdminForm" style="max-width:920px;">
        @csrf

        @if ($v)
            <input type="hidden" name="Vehicle[id]" value="{{ $v->id }}">
        @endif

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">1. Details</legend>
            <div style="margin-bottom:10px;">
                <label>Dealer (owner)*</label><br>
                @if (!empty($lockedDealerId ?? null))
                    <input type="hidden" name="Vehicle[user_id]" value="{{ $lockedDealerId }}">
                    <span>User id {{ $lockedDealerId }} (your dealer account)</span>
                @elseif ($v)
                    <input type="hidden" name="Vehicle[user_id]" value="{{ $v->user_id }}">
                    <span>{{ $v->user_id }}</span>
                @else
                    <div style="max-width:420px;">
                        <select name="Vehicle[user_id]" id="vehicle_user_id" style="width:100%;" required></select>
                    </div>
                    <span style="font-size:12px;color:#666;">Search by name, phone, or email (dealers only).</span>
                @endif
            </div>
            <div style="margin-bottom:10px;">
                <label>Listing type*</label><br>
                <select name="Vehicle[type]">
                    <option value="real" @selected(old('Vehicle.type', data_get($v, 'type', 'demo')) === 'real')>Real</option>
                    <option value="demo" @selected(old('Vehicle.type', data_get($v, 'type', 'demo')) === 'demo')>Demo</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Availability*</label><br>
                <select name="Vehicle[waitlist]">
                    @foreach ($availabilityOptions as $ok => $olab)
                        <option value="{{ $ok }}" @selected((string)old('Vehicle.waitlist', data_get($v, 'waitlist', 2)) === (string)$ok)>{{ $olab }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Availability date (m/d/Y)</label><br>
                <input type="text" name="Vehicle[availability_date]" value="{{ old('Vehicle.availability_date', VehicleAdminSave::formatDateInput(data_get($v, 'availability_date'))) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Stock #*</label><br>
                <input type="text" name="Vehicle[stock_no]" maxlength="20" value="{{ old('Vehicle.stock_no', data_get($v, 'stock_no')) }}" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Model number</label><br>
                <input type="text" name="Vehicle[homenet_modelnumber]" maxlength="50" value="{{ old('Vehicle.homenet_modelnumber', data_get($v, 'homenet_modelnumber')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>VIN*</label><br>
                <input type="text" name="Vehicle[vin_no]" maxlength="17" style="text-transform:uppercase;width:260px;" value="{{ old('Vehicle.vin_no', data_get($v, 'vin_no')) }}" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Make*</label> <input type="text" name="Vehicle[make]" value="{{ old('Vehicle.make', data_get($v, 'make')) }}" required>
                <label style="margin-left:12px;">Model*</label> <input type="text" name="Vehicle[model]" value="{{ old('Vehicle.model', data_get($v, 'model')) }}" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Year</label><br>
                <input type="text" name="Vehicle[year]" value="{{ old('Vehicle.year', data_get($v, 'year')) }}" placeholder="YYYY">
            </div>
            <div style="margin-bottom:10px;">
                <label>Trim</label><br>
                <input type="text" name="Vehicle[trim]" value="{{ old('Vehicle.trim', data_get($v, 'trim')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Engine</label><br>
                <input type="text" name="Vehicle[engine]" value="{{ old('Vehicle.engine', data_get($v, 'engine')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Transmission</label><br>
                <select name="Vehicle[transmition_type]">
                    <option value="M" @selected(old('Vehicle.transmition_type', data_get($v, 'transmition_type', 'M')) === 'M')>Manual</option>
                    <option value="A" @selected(old('Vehicle.transmition_type', data_get($v, 'transmition_type')) === 'A')>Automatic</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Vehicle type (cab)*</label><br>
                <input type="text" name="Vehicle[cab_type]" value="{{ old('Vehicle.cab_type', data_get($v, 'cab_type', 'Regular Sedan')) }}" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Exterior color</label><br>
                <select name="Vehicle[color]">
                    @php $curC = old('Vehicle.color', data_get($v, 'color')); @endphp
                    @foreach ($colorOptions as $cv => $cl)
                        <option value="{{ $cv }}" @selected($curC === $cv)>{{ $cl }}</option>
                    @endforeach
                    @if ($curC && !isset($colorOptions[$curC]))
                        <option value="{{ $curC }}" selected>{{ $curC }}</option>
                    @endif
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Interior color</label><br>
                <input type="text" name="Vehicle[interior_color]" value="{{ old('Vehicle.interior_color', data_get($v, 'interior_color')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>MPG city</label> <input type="number" name="Vehicle[mpg_city]" value="{{ old('Vehicle.mpg_city', data_get($v, 'mpg_city', 0)) }}" style="width:80px;">
                <label style="margin-left:12px;">MPG highway</label> <input type="number" name="Vehicle[mpg_hwy]" value="{{ old('Vehicle.mpg_hwy', data_get($v, 'mpg_hwy', 0)) }}" style="width:80px;">
                <span style="font-size:12px;color:#666;">({{ $du === 'KM' ? 'KM' : 'Miles' }})</span>
            </div>
            <div style="margin-bottom:10px;">
                <label>Doors</label><br>
                <input type="number" name="Vehicle[doors]" value="{{ old('Vehicle.doors', data_get($v, 'doors', 4)) }}" style="width:70px;">
            </div>
            <div style="margin-bottom:10px;">
                <label>Equipment</label><br>
                <textarea name="Vehicle[equipment]" rows="2" style="width:100%;">{{ old('Vehicle.equipment', data_get($v, 'equipment')) }}</textarea>
            </div>
            <div style="margin-bottom:10px;">
                <label>Description</label><br>
                <textarea name="Vehicle[details]" rows="3" style="width:100%;">{{ old('Vehicle.details', data_get($v, 'details')) }}</textarea>
            </div>
            <div style="margin-bottom:10px;">
                <label>Disclosures (agreement)</label><br>
                <textarea name="Vehicle[disclosure]" rows="2" maxlength="400" style="width:100%;">{{ old('Vehicle.disclosure', data_get($v, 'disclosure')) }}</textarea>
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">2. Program</legend>
            <div style="margin-bottom:10px;">
                <label>Financing</label><br>
                <select name="Vehicle[financing]">
                    @foreach ($financingOptions as $fk => $fl)
                        <option value="{{ $fk }}" @selected((string)old('Vehicle.financing', data_get($v, 'financing', 2)) === (string)$fk)>{{ $fl }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Allowed miles / day</label><br>
                <input type="text" name="Vehicle[allowed_miles]" value="{{ old('Vehicle.allowed_miles', data_get($v, 'allowed_miles', '33.33')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Insurance included in fee</label><br>
                <select name="Vehicle[insurance_included_fee]">
                    <option value="1" @selected((int)old('Vehicle.insurance_included_fee', data_get($v, 'insurance_included_fee', 1)) === 1)>Yes</option>
                    <option value="0" @selected((int)old('Vehicle.insurance_included_fee', data_get($v, 'insurance_included_fee', 1)) === 0)>No</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Maintenance included in fee</label><br>
                <select name="Vehicle[maintenance_included_fee]">
                    <option value="1" @selected((int)old('Vehicle.maintenance_included_fee', data_get($v, 'maintenance_included_fee', 1)) === 1)>Yes</option>
                    <option value="0" @selected((int)old('Vehicle.maintenance_included_fee', data_get($v, 'maintenance_included_fee', 1)) === 0)>No</option>
                </select>
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">3. Servicing &amp; logistics</legend>
            <div style="margin-bottom:10px;">
                <label>CCM auth #</label><br>
                <input type="text" name="Vehicle[ccm_auth_no]" value="{{ old('Vehicle.ccm_auth_no', data_get($v, 'ccm_auth_no')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>E-ZPass</label><br>
                <select name="Vehicle[toll_enabled]">
                    <option value="0" @selected((int)old('Vehicle.toll_enabled', data_get($v, 'toll_enabled', 0)) === 0)>Disable</option>
                    <option value="1" @selected((int)old('Vehicle.toll_enabled', data_get($v, 'toll_enabled', 0)) === 1)>Enable</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>GPS serial</label><br>
                <input type="text" name="Vehicle[gps_serialno]" value="{{ old('Vehicle.gps_serialno', data_get($v, 'gps_serialno')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Starter / Passtime serial</label><br>
                <input type="text" name="Vehicle[passtime_serialno]" value="{{ old('Vehicle.passtime_serialno', data_get($v, 'passtime_serialno')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Autopi unit id</label><br>
                <input type="text" name="Vehicle[autopi_unit_id]" value="{{ old('Vehicle.autopi_unit_id', data_get($v, 'autopi_unit_id')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Odometer</label><br>
                <input type="text" name="Vehicle[odometer]" value="{{ old('Vehicle.odometer', data_get($v, 'odometer')) }}">
            </div>
            @if ($v)
                <div style="margin-bottom:10px;">
                    <label>Current odometer (read-only)</label><br>
                    <input type="text" value="{{ data_get($v, 'last_mile') }}" readonly style="background:#f5f5f5;">
                </div>
            @endif
            <div style="margin-bottom:10px;">
                <label>Next maintenance odometer</label><br>
                <input type="number" name="Vehicle[total_mileage]" value="{{ old('Vehicle.total_mileage', data_get($v, 'total_mileage', 0)) }}">
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">4. Vehicle addresses</legend>
            <p style="font-size:12px;color:#555;">Enter address + latitude + longitude (Places autocomplete can be added later).</p>
            <div style="margin-bottom:10px;">
                <label>Show all locations</label><br>
                <select name="Vehicle[multi_location]">
                    <option value="0" @selected((int)old('Vehicle.multi_location', data_get($v, 'multi_location', 0)) === 0)>No</option>
                    <option value="1" @selected((int)old('Vehicle.multi_location', data_get($v, 'multi_location', 0)) === 1)>Yes</option>
                </select>
            </div>
            @foreach ($locations as $i => $loc)
                <div style="border:1px solid #eee; padding:8px; margin-bottom:8px;">
                    <strong>Location {{ $i + 1 }}</strong>
                    <input type="hidden" name="VehicleLocation[{{ $i }}][id]" value="{{ data_get($loc, 'id') }}">
                    <div><label>Address</label><br>
                        <input type="text" name="VehicleLocation[{{ $i }}][address]" style="width:100%;" value="{{ old('VehicleLocation.' . $i . '.address', data_get($loc, 'address')) }}"></div>
                    <div style="margin-top:6px;">
                        <label>Lat</label> <input type="text" name="VehicleLocation[{{ $i }}][lat]" value="{{ old('VehicleLocation.' . $i . '.lat', data_get($loc, 'lat')) }}" style="width:140px;">
                        <label>Lng</label> <input type="text" name="VehicleLocation[{{ $i }}][lng]" value="{{ old('VehicleLocation.' . $i . '.lng', data_get($loc, 'lng')) }}" style="width:140px;">
                    </div>
                </div>
            @endforeach
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">5. Documentation</legend>
            <div style="margin-bottom:10px;">
                <label>Registered name</label><br>
                <input type="text" name="Vehicle[registered_name]" value="{{ old('Vehicle.registered_name', data_get($v, 'registered_name')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Plate</label><br>
                <input type="text" name="Vehicle[plate_number]" value="{{ old('Vehicle.plate_number', data_get($v, 'plate_number')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Registered state (abbr)</label><br>
                <input type="text" name="Vehicle[registered_state]" maxlength="3" value="{{ old('Vehicle.registered_state', data_get($v, 'registered_state', 'NY')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Registration date</label><br>
                <input type="text" name="Vehicle[reg_name_date]" value="{{ old('Vehicle.reg_name_date', VehicleAdminSave::formatDateInput(data_get($v, 'reg_name_date'))) }}" placeholder="m/d/Y">
            </div>
            <div style="margin-bottom:10px;">
                <label>Registration exp.</label><br>
                <input type="text" name="Vehicle[reg_name_exp_date]" value="{{ old('Vehicle.reg_name_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'reg_name_exp_date'))) }}" placeholder="m/d/Y">
            </div>
            <div style="margin-bottom:10px;">
                <label>Insurance company</label><br>
                <input type="text" name="Vehicle[insurance_company]" value="{{ old('Vehicle.insurance_company', data_get($v, 'insurance_company')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Policy #</label><br>
                <input type="text" name="Vehicle[insurance_policy_no]" value="{{ old('Vehicle.insurance_policy_no', data_get($v, 'insurance_policy_no')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Policy begin</label><br>
                <input type="text" name="Vehicle[insurance_policy_date]" value="{{ old('Vehicle.insurance_policy_date', data_get($v, 'insurance_policy_date')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Policy expiration</label><br>
                <input type="text" name="Vehicle[insurance_policy_exp_date]" value="{{ old('Vehicle.insurance_policy_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'insurance_policy_exp_date'))) }}" placeholder="m/d/Y">
            </div>
            <div style="margin-bottom:10px;">
                <label>Inspection expiration</label><br>
                <input type="text" name="Vehicle[inspection_exp_date]" value="{{ old('Vehicle.inspection_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'inspection_exp_date'))) }}" placeholder="m/d/Y">
            </div>
            <div style="margin-bottom:10px;">
                <label>State inspection exp.</label><br>
                <input type="text" name="Vehicle[state_insp_exp_date]" value="{{ old('Vehicle.state_insp_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'state_insp_exp_date'))) }}" placeholder="m/d/Y">
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">6. Pricing</legend>
            <div style="margin-bottom:10px;">
                <label>Pricing style*</label><br>
                <select name="Vehicle[fare_type]" id="VehicleFareType">
                    <option value="S" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type', 'S')) === 'S')>Static</option>
                    <option value="D" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type')) === 'D')>Dynamic</option>
                    <option value="L" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type')) === 'L')>Lease Plus</option>
                </select>
            </div>
            <div id="rateBlk" style="margin-bottom:10px;">
                <label>Rate (per hour)</label><br>
                <input type="text" name="Vehicle[rate]" id="VehicleRate" value="{{ old('Vehicle.rate', data_get($v, 'rate')) }}">
            </div>
            <div id="dayBlk" style="margin-bottom:10px;">
                <label>Day rent</label><br>
                <input type="text" name="Vehicle[day_rent]" id="VehicleDayRent" value="{{ old('Vehicle.day_rent', data_get($v, 'day_rent')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Authorize payment</label><br>
                <select name="Vehicle[auth_require]">
                    <option value="0" @selected((int)old('Vehicle.auth_require', data_get($v, 'auth_require', 0)) === 0)>Disable</option>
                    <option value="1" @selected((int)old('Vehicle.auth_require', data_get($v, 'auth_require', 0)) === 1)>Enable</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>MSRP (homenet)</label><br>
                <input type="text" name="Vehicle[homenet_msrp]" value="{{ old('Vehicle.homenet_msrp', data_get($v, 'homenet_msrp')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Dealer selling price (msrp)</label><br>
                <input type="text" name="Vehicle[msrp]" value="{{ old('Vehicle.msrp', data_get($v, 'msrp')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Listed selling price (premium)</label><br>
                <input type="text" name="Vehicle[premium_msrp]" value="{{ old('Vehicle.premium_msrp', data_get($v, 'premium_msrp')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>Vehicle cost incl. recon</label><br>
                <input type="text" name="Vehicle[vehicleCostInclRecon]" value="{{ old('Vehicle.vehicleCostInclRecon', data_get($v, 'vehicleCostInclRecon')) }}">
            </div>
            <div style="margin-bottom:10px;">
                <label>KBB/NADA wholesale</label><br>
                <input type="text" name="Vehicle[kbbnadaWholesaleBook]" value="{{ old('Vehicle.kbbnadaWholesaleBook', data_get($v, 'kbbnadaWholesaleBook')) }}">
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="font-weight:700;">7. Uploads</legend>
            @if ($v && data_get($v, 'registration_image'))
                <p><a href="/img/custom/vehicle_photo/{{ $v->registration_image }}" target="_blank">Current registration file</a></p>
            @endif
            <div style="margin-bottom:10px;">
                <label>Registration doc</label><br>
                <input type="file" name="registration_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
            </div>
            @if ($v && data_get($v, 'insurance_image'))
                <p><a href="/img/custom/vehicle_photo/{{ $v->insurance_image }}" target="_blank">Current insurance file</a></p>
            @endif
            <div style="margin-bottom:10px;">
                <label>Insurance doc</label><br>
                <input type="file" name="insurance_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
            </div>
            @if ($v && data_get($v, 'inspection_image'))
                <p><a href="/img/custom/vehicle_photo/{{ $v->inspection_image }}" target="_blank">Current inspection file</a></p>
            @endif
            <div style="margin-bottom:10px;">
                <label>Inspection doc</label><br>
                <input type="file" name="inspection_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
            </div>
            @if ($v && $vehicleImages->count())
                <p style="font-size:13px;">Gallery images: use existing AJAX uploader on the legacy admin or <code>/admin/vehicles/saveImage</code> after save.</p>
            @endif
        </fieldset>

        <button type="submit">{{ $v ? 'Update' : 'Save' }}</button>
        <a href="{{ $returnListUrl ?? '/admin/vehicles/index' }}" style="margin-left:12px;">Return</a>
    </form>

    @push('scripts')
        <script>
            (function () {
                var ft = document.getElementById('VehicleFareType');
                var dayBlk = document.getElementById('dayBlk');
                var rateBlk = document.getElementById('rateBlk');
                var dayIn = document.getElementById('VehicleDayRent');
                var rateIn = document.getElementById('VehicleRate');
                function syncFare() {
                    if (!ft) return;
                    var v = ft.value;
                    if (v === 'D' || v === 'L') {
                        if (dayBlk) dayBlk.style.display = 'none';
                        if (rateBlk) rateBlk.style.display = 'none';
                        if (dayIn) dayIn.value = '0';
                        if (rateIn) rateIn.value = '0';
                    } else {
                        if (dayBlk) dayBlk.style.display = 'block';
                        if (rateBlk) rateBlk.style.display = 'block';
                    }
                }
                if (ft) {
                    ft.addEventListener('change', syncFare);
                    syncFare();
                }
            })();
        </script>
        @if (!$v && empty($lockedDealerId ?? null))
            <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script>
                (function () {
                    var raw = @json(old('Vehicle.user_id'));
                    var dealerId = raw !== null && raw !== '' ? parseInt(raw, 10) : null;
                    if (dealerId !== null && !Number.isFinite(dealerId)) {
                        dealerId = null;
                    }
                    var $sel = $('#vehicle_user_id');
                    $sel.select2({
                        placeholder: 'Search dealer…',
                        allowClear: true,
                        minimumInputLength: 1,
                        ajax: {
                            url: '/admin/bookings/customerautocomplete',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { term: params.term || '', is_dealer: true };
                            },
                            processResults: function (data) {
                                return {
                                    results: (data || []).map(function (item) {
                                        return { id: item.id, text: item.tag };
                                    })
                                };
                            }
                        }
                    });
                    if (dealerId) {
                        $.getJSON('/admin/bookings/customerautocomplete', { id: dealerId })
                            .done(function (data) {
                                if (data && data.length) {
                                    var item = data[0];
                                    var opt = new Option(item.tag, item.id, true, true);
                                    $sel.append(opt).trigger('change');
                                }
                            });
                    }
                })();
            </script>
        @endif
    @endpush
@endsection
