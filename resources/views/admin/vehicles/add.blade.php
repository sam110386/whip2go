@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Vehicle')

@php
    use App\Support\VehicleAdminSave;
    $v = $vehicle;
    $du = $owner?->distance_unit ?? 'MI';
    $formBase = $vehicleFormActionBase ?? '/admin/vehicles/add';
    $returnUrl = $returnListUrl ?? '/admin/vehicles/index';
    $formAction = $v ? $formBase . '/' . base64_encode((string) $v->id) : $formBase;
    $submitLabel = $v ? 'Update' : 'Save';
    $showDealerPicker = !$v && empty($lockedDealerId ?? null);
@endphp

@if ($showDealerPicker)
    @push('styles')
        <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    @endpush
@endif

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $listTitle ?? 'Vehicle' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="vehicleAdminForm" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $formAction }}"
          id="vehicleAdminForm" name="vehicleAdminForm" class="form-horizontal">
        @csrf

        @if ($v)
            <input type="hidden" name="Vehicle[id]" value="{{ $v->id }}">
        @endif

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">1. Details</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Dealer (owner) :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        @if (!empty($lockedDealerId ?? null))
                            <input type="hidden" name="Vehicle[user_id]" value="{{ $lockedDealerId }}">
                            <p class="form-control-static">User id {{ $lockedDealerId }} (your dealer account)</p>
                        @elseif ($v)
                            <input type="hidden" name="Vehicle[user_id]" value="{{ $v->user_id }}">
                            <p class="form-control-static">{{ $v->user_id }}</p>
                        @else
                            <select name="Vehicle[user_id]" id="vehicle_user_id" class="w-100" required></select>
                            <span class="help-block">Search by name, phone, or email (dealers only).</span>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Listing type :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="Vehicle[type]" class="form-control">
                            <option value="real" @selected(old('Vehicle.type', data_get($v, 'type', 'demo')) === 'real')>Real</option>
                            <option value="demo" @selected(old('Vehicle.type', data_get($v, 'type', 'demo')) === 'demo')>Demo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Availability :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="Vehicle[waitlist]" class="form-control">
                            @foreach ($availabilityOptions as $ok => $olab)
                                <option value="{{ $ok }}" @selected((string)old('Vehicle.waitlist', data_get($v, 'waitlist', 2)) === (string)$ok)>{{ $olab }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Availability date :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[availability_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.availability_date', VehicleAdminSave::formatDateInput(data_get($v, 'availability_date'))) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Stock # :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[stock_no]" maxlength="20" class="form-control" required
                               value="{{ old('Vehicle.stock_no', data_get($v, 'stock_no')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Model number :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[homenet_modelnumber]" maxlength="50" class="form-control"
                               value="{{ old('Vehicle.homenet_modelnumber', data_get($v, 'homenet_modelnumber')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">VIN :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[vin_no]" maxlength="17" class="form-control text-uppercase" required
                               value="{{ old('Vehicle.vin_no', data_get($v, 'vin_no')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Make :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="Vehicle[make]" class="form-control" required
                               value="{{ old('Vehicle.make', data_get($v, 'make')) }}">
                    </div>
                    <label class="col-lg-1 control-label">Model :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="Vehicle[model]" class="form-control" required
                               value="{{ old('Vehicle.model', data_get($v, 'model')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Year :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[year]" class="form-control" placeholder="YYYY"
                               value="{{ old('Vehicle.year', data_get($v, 'year')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Trim :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[trim]" class="form-control"
                               value="{{ old('Vehicle.trim', data_get($v, 'trim')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Engine :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[engine]" class="form-control"
                               value="{{ old('Vehicle.engine', data_get($v, 'engine')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Transmission :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[transmition_type]" class="form-control">
                            <option value="M" @selected(old('Vehicle.transmition_type', data_get($v, 'transmition_type', 'M')) === 'M')>Manual</option>
                            <option value="A" @selected(old('Vehicle.transmition_type', data_get($v, 'transmition_type')) === 'A')>Automatic</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Vehicle type (cab) :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[cab_type]" class="form-control" required
                               value="{{ old('Vehicle.cab_type', data_get($v, 'cab_type', 'Regular Sedan')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Exterior color :</label>
                    <div class="col-lg-9">
                        @php $curC = old('Vehicle.color', data_get($v, 'color')); @endphp
                        <select name="Vehicle[color]" class="form-control">
                            @foreach ($colorOptions as $cv => $cl)
                                <option value="{{ $cv }}" @selected($curC === $cv)>{{ $cl }}</option>
                            @endforeach
                            @if ($curC && !isset($colorOptions[$curC]))
                                <option value="{{ $curC }}" selected>{{ $curC }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Interior color :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[interior_color]" class="form-control"
                               value="{{ old('Vehicle.interior_color', data_get($v, 'interior_color')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">MPG city :</label>
                    <div class="col-lg-3">
                        <input type="number" name="Vehicle[mpg_city]" class="form-control"
                               value="{{ old('Vehicle.mpg_city', data_get($v, 'mpg_city', 0)) }}">
                    </div>
                    <label class="col-lg-2 control-label">MPG highway :</label>
                    <div class="col-lg-3">
                        <input type="number" name="Vehicle[mpg_hwy]" class="form-control"
                               value="{{ old('Vehicle.mpg_hwy', data_get($v, 'mpg_hwy', 0)) }}">
                        <span class="help-block">({{ $du === 'KM' ? 'KM' : 'Miles' }})</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Doors :</label>
                    <div class="col-lg-9">
                        <input type="number" name="Vehicle[doors]" class="form-control"
                               value="{{ old('Vehicle.doors', data_get($v, 'doors', 4)) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Equipment :</label>
                    <div class="col-lg-9">
                        <textarea name="Vehicle[equipment]" rows="2" class="form-control">{{ old('Vehicle.equipment', data_get($v, 'equipment')) }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Description :</label>
                    <div class="col-lg-9">
                        <textarea name="Vehicle[details]" rows="3" class="form-control">{{ old('Vehicle.details', data_get($v, 'details')) }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Disclosures (agreement) :</label>
                    <div class="col-lg-9">
                        <textarea name="Vehicle[disclosure]" rows="2" maxlength="400" class="form-control">{{ old('Vehicle.disclosure', data_get($v, 'disclosure')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">2. Program</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Financing :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[financing]" class="form-control">
                            @foreach ($financingOptions as $fk => $fl)
                                <option value="{{ $fk }}" @selected((string)old('Vehicle.financing', data_get($v, 'financing', 2)) === (string)$fk)>{{ $fl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Allowed miles / day :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[allowed_miles]" class="form-control"
                               value="{{ old('Vehicle.allowed_miles', data_get($v, 'allowed_miles', '33.33')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Insurance included in fee :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[insurance_included_fee]" class="form-control">
                            <option value="1" @selected((int)old('Vehicle.insurance_included_fee', data_get($v, 'insurance_included_fee', 1)) === 1)>Yes</option>
                            <option value="0" @selected((int)old('Vehicle.insurance_included_fee', data_get($v, 'insurance_included_fee', 1)) === 0)>No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Maintenance included in fee :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[maintenance_included_fee]" class="form-control">
                            <option value="1" @selected((int)old('Vehicle.maintenance_included_fee', data_get($v, 'maintenance_included_fee', 1)) === 1)>Yes</option>
                            <option value="0" @selected((int)old('Vehicle.maintenance_included_fee', data_get($v, 'maintenance_included_fee', 1)) === 0)>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">3. Servicing &amp; logistics</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">CCM auth # :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[ccm_auth_no]" class="form-control"
                               value="{{ old('Vehicle.ccm_auth_no', data_get($v, 'ccm_auth_no')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">E-ZPass :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[toll_enabled]" class="form-control">
                            <option value="0" @selected((int)old('Vehicle.toll_enabled', data_get($v, 'toll_enabled', 0)) === 0)>Disable</option>
                            <option value="1" @selected((int)old('Vehicle.toll_enabled', data_get($v, 'toll_enabled', 0)) === 1)>Enable</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">GPS serial :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[gps_serialno]" class="form-control"
                               value="{{ old('Vehicle.gps_serialno', data_get($v, 'gps_serialno')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Starter / Passtime serial :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[passtime_serialno]" class="form-control"
                               value="{{ old('Vehicle.passtime_serialno', data_get($v, 'passtime_serialno')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Autopi unit id :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[autopi_unit_id]" class="form-control"
                               value="{{ old('Vehicle.autopi_unit_id', data_get($v, 'autopi_unit_id')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Odometer :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[odometer]" class="form-control"
                               value="{{ old('Vehicle.odometer', data_get($v, 'odometer')) }}">
                    </div>
                </div>

                @if ($v)
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Current odometer :</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control" value="{{ data_get($v, 'last_mile') }}" readonly>
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label class="col-lg-3 control-label">Next maintenance odometer :</label>
                    <div class="col-lg-9">
                        <input type="number" name="Vehicle[total_mileage]" class="form-control"
                               value="{{ old('Vehicle.total_mileage', data_get($v, 'total_mileage', 0)) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">4. Vehicle addresses</h5></div>
            <div class="panel-body">
                <p class="help-block">Enter address + latitude + longitude (Places autocomplete can be added later).</p>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Show all locations :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[multi_location]" class="form-control">
                            <option value="0" @selected((int)old('Vehicle.multi_location', data_get($v, 'multi_location', 0)) === 0)>No</option>
                            <option value="1" @selected((int)old('Vehicle.multi_location', data_get($v, 'multi_location', 0)) === 1)>Yes</option>
                        </select>
                    </div>
                </div>

                @foreach ($locations as $i => $loc)
                    <fieldset class="content-group">
                        <legend class="text-bold">Location {{ $i + 1 }}</legend>
                        <input type="hidden" name="VehicleLocation[{{ $i }}][id]" value="{{ data_get($loc, 'id') }}">

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Address :</label>
                            <div class="col-lg-9">
                                <input type="text" name="VehicleLocation[{{ $i }}][address]" class="form-control"
                                       value="{{ old('VehicleLocation.' . $i . '.address', data_get($loc, 'address')) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Lat :</label>
                            <div class="col-lg-3">
                                <input type="text" name="VehicleLocation[{{ $i }}][lat]" class="form-control"
                                       value="{{ old('VehicleLocation.' . $i . '.lat', data_get($loc, 'lat')) }}">
                            </div>
                            <label class="col-lg-1 control-label">Lng :</label>
                            <div class="col-lg-3">
                                <input type="text" name="VehicleLocation[{{ $i }}][lng]" class="form-control"
                                       value="{{ old('VehicleLocation.' . $i . '.lng', data_get($loc, 'lng')) }}">
                            </div>
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">5. Documentation</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Registered name :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[registered_name]" class="form-control"
                               value="{{ old('Vehicle.registered_name', data_get($v, 'registered_name')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Plate :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[plate_number]" class="form-control"
                               value="{{ old('Vehicle.plate_number', data_get($v, 'plate_number')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Registered state (abbr) :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[registered_state]" maxlength="3" class="form-control"
                               value="{{ old('Vehicle.registered_state', data_get($v, 'registered_state', 'NY')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Registration date :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[reg_name_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.reg_name_date', VehicleAdminSave::formatDateInput(data_get($v, 'reg_name_date'))) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Registration exp. :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[reg_name_exp_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.reg_name_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'reg_name_exp_date'))) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Insurance company :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[insurance_company]" class="form-control"
                               value="{{ old('Vehicle.insurance_company', data_get($v, 'insurance_company')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Policy # :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[insurance_policy_no]" class="form-control"
                               value="{{ old('Vehicle.insurance_policy_no', data_get($v, 'insurance_policy_no')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Policy begin :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[insurance_policy_date]" class="form-control"
                               value="{{ old('Vehicle.insurance_policy_date', data_get($v, 'insurance_policy_date')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Policy expiration :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[insurance_policy_exp_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.insurance_policy_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'insurance_policy_exp_date'))) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Inspection expiration :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[inspection_exp_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.inspection_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'inspection_exp_date'))) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">State inspection exp. :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[state_insp_exp_date]" class="form-control" placeholder="m/d/Y"
                               value="{{ old('Vehicle.state_insp_exp_date', VehicleAdminSave::formatDateInput(data_get($v, 'state_insp_exp_date'))) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">6. Pricing</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Pricing style :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="Vehicle[fare_type]" id="VehicleFareType" class="form-control">
                            <option value="S" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type', 'S')) === 'S')>Static</option>
                            <option value="D" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type')) === 'D')>Dynamic</option>
                            <option value="L" @selected(old('Vehicle.fare_type', data_get($v, 'fare_type')) === 'L')>Lease Plus</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="rateBlk">
                    <label class="col-lg-3 control-label">Rate (per hour) :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[rate]" id="VehicleRate" class="form-control"
                               value="{{ old('Vehicle.rate', data_get($v, 'rate')) }}">
                    </div>
                </div>

                <div class="form-group" id="dayBlk">
                    <label class="col-lg-3 control-label">Day rent :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[day_rent]" id="VehicleDayRent" class="form-control"
                               value="{{ old('Vehicle.day_rent', data_get($v, 'day_rent')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Authorize payment :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[auth_require]" class="form-control">
                            <option value="0" @selected((int)old('Vehicle.auth_require', data_get($v, 'auth_require', 0)) === 0)>Disable</option>
                            <option value="1" @selected((int)old('Vehicle.auth_require', data_get($v, 'auth_require', 0)) === 1)>Enable</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">MSRP (homenet) :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[homenet_msrp]" class="form-control"
                               value="{{ old('Vehicle.homenet_msrp', data_get($v, 'homenet_msrp')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Dealer selling price (msrp) :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[msrp]" class="form-control"
                               value="{{ old('Vehicle.msrp', data_get($v, 'msrp')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Listed selling price (premium) :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[premium_msrp]" class="form-control"
                               value="{{ old('Vehicle.premium_msrp', data_get($v, 'premium_msrp')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Vehicle cost incl. recon :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[vehicleCostInclRecon]" class="form-control"
                               value="{{ old('Vehicle.vehicleCostInclRecon', data_get($v, 'vehicleCostInclRecon')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">KBB/NADA wholesale :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[kbbnadaWholesaleBook]" class="form-control"
                               value="{{ old('Vehicle.kbbnadaWholesaleBook', data_get($v, 'kbbnadaWholesaleBook')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading"><h5 class="panel-title">7. Uploads</h5></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Registration doc :</label>
                    <div class="col-lg-9">
                        @if ($v && data_get($v, 'registration_image'))
                            <p><a href="/img/custom/vehicle_photo/{{ $v->registration_image }}" target="_blank">Current registration file</a></p>
                        @endif
                        <input type="file" name="registration_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Insurance doc :</label>
                    <div class="col-lg-9">
                        @if ($v && data_get($v, 'insurance_image'))
                            <p><a href="/img/custom/vehicle_photo/{{ $v->insurance_image }}" target="_blank">Current insurance file</a></p>
                        @endif
                        <input type="file" name="insurance_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Inspection doc :</label>
                    <div class="col-lg-9">
                        @if ($v && data_get($v, 'inspection_image'))
                            <p><a href="/img/custom/vehicle_photo/{{ $v->inspection_image }}" target="_blank">Current inspection file</a></p>
                        @endif
                        <input type="file" name="inspection_image" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                    </div>
                </div>

                @if ($v && $vehicleImages->count())
                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <p class="help-block">Gallery images: use existing AJAX uploader on the legacy admin or <code>/admin/vehicles/saveImage</code> after save.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection

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
    @if ($showDealerPicker)
        <script src="{{ legacy_asset('js/select2.js') }}"></script>
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
