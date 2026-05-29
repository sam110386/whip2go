@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Vehicle')

@php
    use App\Support\VehicleAdminSave;
    $du = $owner?->distance_unit ?? 'MI';
    $formBase = $vehicleFormActionBase ?? '/admin/vehicles/add';
    $returnUrl = $returnListUrl ?? '/admin/vehicles/index';
    $showDealerPicker = !$vehicle && empty($lockedDealerId ?? null);
    $locations = $vehicle?->locations ?? collect();
    $availabilityOptions = $commonService->getAvailabilityOptions();
    $financingOptions = $commonService->getVehicleFinancing();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@push('head_scripts')
    <script type="text/javascript"
        src="https://maps.googleapis.com/maps/api/js?key={{ config('legacy.GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
@endpush

@section('content')

    <form
        action="{{!empty($vehicle->id) ? url('admin/vehicles/add' . base64_encode($vehicle->id)) : url('/admin/vehicles/add')}}"
        method="POST" enctype="multipart/form-data" id="vehicleAdminForm" name="vehicleAdminForm" class="form-horizontal">
        @csrf

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
                        @if (!empty($vehicle->id) && ($vehicle?->csSetting?->passtime === 'smartcar' || $vehicle?->csSetting?->gps_provider === 'smartcar'))
                            <a href="{{ url('admin/smart_cars/connect' . base64_encode($vehicle->user_id)) }}" class="btn"
                                onclick="window.open($(this).attr('href'), 'DriveItAway', 'scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,width=0,height=0,left=-1000,top=-1000'); return false;">
                                Connect EV to Smart Car
                            </a>
                        @endif

                        <button type="submit" class="btn">
                            {{ !empty($vehicle->id) ? 'Update' : 'Save' }}
                        </button>
                        <button type="submit" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicles/index')">
                            Return
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="masonry">
            <div class="item">
                <div class="panel-body">
                    <legend class="text-size-large text-bold">1. Details</legend>
                    @if (!empty($vehicle->id))
                        <input type="hidden" name="Vehicle[id]" value="{{ data_get($vehicle, 'id') }}">
                    @else
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Dealer: <font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="Vehicle[user_id]" value="{{ data_get($vehicle, 'user_id') }}"
                                    id="VehicleUserId" class="required textfield" placeholder="Select Owner"
                                    style="width:100%;">
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Listing Type :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <select name="Vehicle[type]" class="form-control">
                                <option value="real" @selected(old('Vehicle.type', data_get($vehicle, 'type', 'demo')) === 'real')>
                                    Real
                                </option>
                                <option value="demo" @selected(old('Vehicle.type', data_get($vehicle, 'type', 'demo')) === 'demo')>
                                    Demo
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Availability :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <select name="Vehicle[waitlist]" class="form-control">
                                @foreach ($availabilityOptions as $ok => $olab)
                                    <option value="{{ $ok }}" @selected((string) old('Vehicle.waitlist', data_get($vehicle, 'waitlist', 2)) === (string) $ok)>
                                        {{ $olab }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Availability date :
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[availability_date]" id="VehicleAvailabilityDate"
                                class="form-control"
                                value="{{ old('Vehicle.availability_date', data_get($vehicle, 'availability_date') ? \Carbon\Carbon::parse(data_get($vehicle, 'availability_date'))->format('m/d/Y') : '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Stock # :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[stock_no]" maxlength="20" class="form-control required"
                                required value="{{ old('Vehicle.stock_no', data_get($vehicle, 'stock_no')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Model number :
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[homenet_modelnumber]" maxlength="10" class="form-control"
                                value="{{ old('Vehicle.homenet_modelnumber', data_get($vehicle, 'homenet_modelnumber')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            VIN Number:<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[vin_no]" maxlength="100"
                                class="form-control required text-uppercase" required
                                value="{{ old('Vehicle.vin_no', data_get($vehicle, 'vin_no')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Make :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[make]" class="required form-control" required
                                value="{{ old('Vehicle.make', data_get($vehicle, 'make')) }}" maxlength="100"
                                placeholder="Make">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Model :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[model]" class="required form-control" required
                                value="{{ old('Vehicle.model', data_get($vehicle, 'model')) }}" maxlength="100"
                                placeholder="Model">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Year :
                        </label>
                        <div class="col-lg-8">
                            <select name="Vehicle[year]" class="form-control">
                                @php
                                    $currentYear = date('Y');
                                    $startYear = $currentYear + 1; // maxYear: current year + 1
                                    $endYear = $currentYear - 70;  // minYear: current year - 70
                                    $selectedYear = old('Vehicle.year', data_get($vehicle, 'year'));
                                @endphp

                                @for ($year = $startYear; $year >= $endYear; $year--)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Trim :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[trim]" class="form-control"
                                value="{{ old('Vehicle.trim', data_get($vehicle, 'trim')) }}" placeholder="Trim">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Engine :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[engine]" class="form-control"
                                value="{{ old('Vehicle.engine', data_get($vehicle, 'engine')) }}" maxlength="50">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Transmission Type :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[transmition_type]" class="form-control">
                                <option value="M" @selected(old('Vehicle.transmition_type', data_get($vehicle, 'transmition_type', 'M')) === 'M')>
                                    Manual
                                </option>
                                <option value="A" @selected(old('Vehicle.transmition_type', data_get($vehicle, 'transmition_type')) === 'A')>
                                    Automatic
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            Vehicle type :<font class="requiredField">*</font>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[cab_type]" class="form-control required" required
                                value="{{ old('Vehicle.cab_type', data_get($vehicle, 'cab_type')) }}" maxlength="100"
                                placeholder="Cab Type">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Exterior color :</label>
                        <div class="col-lg-8">
                            @php $curC = old('Vehicle.color', data_get($vehicle, 'color')); @endphp
                            <select name="Vehicle[color]" class="form-control">
                                @foreach ($colorOptions as $cv => $cl)
                                    <option value="{{ $cv }}" @selected($curC === $cv)>{{ $cl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Interior color :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[interior_color]" class="form-control"
                                value="{{ old('Vehicle.interior_color', data_get($vehicle, 'interior_color')) }}"
                                placeholder="Interior color">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{ data_get($vehicle, 'user.distance_unit') == 'KM' ? 'KM per' : 'Miles per' }}
                            {{ data_get($vehicle, 'user.distance_unit') == 'KM' ? 'Liter (City) :' : 'Gallon (City) :' }}
                        </label>
                        <div class="col-lg-8">
                            <input type="number" name="Vehicle[mpg_city]" class="form-control digit"
                                value="{{ old('Vehicle.mpg_city', data_get($vehicle, 'mpg_city')) }}" min="0" max="99999">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{ data_get($vehicle, 'user.distance_unit') == 'KM' ? 'KM per' : 'Miles per' }}
                            {{ data_get($vehicle, 'user.distance_unit') == 'KM' ? 'Liter (Highway) :' : 'Gallon (Highway) :' }}
                        </label>
                        <div class="col-lg-8">
                            <input type="number" name="Vehicle[mpg_hwy]" class="form-control digit"
                                value="{{ old('Vehicle.mpg_hwy', data_get($vehicle, 'mpg_hwy')) }}" min="0" max="99999">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label"># of Doors :</label>
                        <div class="col-lg-8">
                            <input type="number" name="Vehicle[doors]" class="form-control digit"
                                value="{{ old('Vehicle.doors', data_get($vehicle, 'doors')) }}" min="0" max="99">
                        </div>
                    </div>

                    @if (data_get($vehicle, 'is_featured') == 0 && !empty(data_get($vehicle, 'config')))
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Variation Config:</label>
                            <div class="col-lg-8">
                                @php
                                    $options = is_array(data_get($vehicle, 'config'))
                                        ? data_get($vehicle, 'config')
                                        : json_decode(data_get($vehicle, 'config'), true);
                                @endphp

                                {!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($options), $options)) !!}
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Standard Equipment :</label>
                        <div class="col-lg-8">
                            <textarea name="Vehicle[equipment]" rows="2"
                                class="form-control">{{ old('Vehicle.equipment', data_get($vehicle, 'equipment')) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Description :</label>
                        <div class="col-lg-8">
                            <textarea name="Vehicle[details]" rows="3"
                                class="form-control">{{ old('Vehicle.details', data_get($vehicle, 'details')) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Disclosures :</label>
                        <div class="col-lg-8">
                            <textarea name="Vehicle[disclosure]" rows="2" maxlength="400"
                                class="form-control">{{ old('Vehicle.disclosure', data_get($vehicle, 'disclosure')) }}</textarea>
                            <span class="help-block">*Any disclosure that you want to print on agreement doc</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel-body">
                    <legend class="text-size-large text-bold">2. Program</legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Financing :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[financing]" class="form-control">
                                @foreach ($financingOptions as $fk => $fl)
                                    <option value="{{ $fk }}" @selected((string) old('Vehicle.financing', data_get($vehicle, 'financing')) === (string) $fk)>
                                        {{ $fl }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{ data_get($vehicle, 'user.distance_unit') === 'KM' ? 'Allowed KM (Per Day):' : 'Allowed Miles (Per Day):' }}
                        </label>
                        <div class="col-lg-8">
                            <input type="number" name="Vehicle[allowed_miles]" class="number form-control"
                                value="{{ old('Vehicle.allowed_miles', data_get($vehicle, 'allowed_miles')) }}" min="0"
                                max="99999999999">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Insurance included in Fee :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[insurance_included_fee]" class="form-control">
                                <option value="1" @selected((int) old('Vehicle.insurance_included_fee', data_get($vehicle, 'insurance_included_fee', 1)) === 1)>
                                    Yes
                                </option>
                                <option value="0" @selected((int) old('Vehicle.insurance_included_fee', data_get($vehicle, 'insurance_included_fee', 1)) === 0)>
                                    No
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Maintenance included in fee :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[maintenance_included_fee]" class="form-control">
                                <option value="1" @selected((int) old('Vehicle.maintenance_included_fee', data_get($vehicle, 'maintenance_included_fee', 1)) === 1)>
                                    Yes
                                </option>
                                <option value="0" @selected((int) old('Vehicle.maintenance_included_fee', data_get($vehicle, 'maintenance_included_fee', 1)) === 0)>
                                    No
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel-body">
                    <legend class="text-size-large text-bold">3. Servicing & Logistics</legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">CCM Auth Number :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[ccm_auth_no]" class="form-control"
                                value="{{ old('Vehicle.ccm_auth_no', data_get($vehicle, 'ccm_auth_no')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">EZ Pass Installed :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[toll_enabled]" class="form-control">
                                <option value="0" @selected((int) old('Vehicle.toll_enabled', data_get($vehicle, 'toll_enabled', 0)) === 0)>
                                    Disable
                                </option>
                                <option value="1" @selected((int) old('Vehicle.toll_enabled', data_get($vehicle, 'toll_enabled', 0)) === 1)>
                                    Enable
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">GPS Device Serial# :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[gps_serialno]" class="form-control"
                                value="{{ old('Vehicle.gps_serialno', data_get($vehicle, 'gps_serialno')) }}"
                                maxlength="40">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Starter Interrupt Device Serial# :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[passtime_serialno]" class="form-control"
                                value="{{ old('Vehicle.passtime_serialno', data_get($vehicle, 'passtime_serialno')) }}"
                                maxlength="40">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Autopi Unit ID :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[autopi_unit_id]" class="form-control"
                                value="{{ old('Vehicle.autopi_unit_id', data_get($vehicle, 'autopi_unit_id')) }}"
                                maxlength="100">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Original Odometer :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[odometer]" class="digits form-control"
                                value="{{ old('Vehicle.odometer', data_get($vehicle, 'odometer')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Current odometer :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[last_mile]" class="digits form-control"
                                value="{{ data_get($vehicle, 'last_mile') }}" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Next maintenance odometer :</label>
                        <div class="col-lg-8">
                            <input type="number" name="Vehicle[total_mileage]" class="digits form-control"
                                value="{{ old('Vehicle.total_mileage', data_get($vehicle, 'total_mileage')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel-body" rel-address="{{ count($locations) > 0 ? count($locations) : 1 }}">
                    <legend class="text-size-large text-bold">4.Vehicle Address</legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Show all locations :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[multi_location]" class="form-control">
                                <option value="0" @selected((int) old('Vehicle.multi_location', data_get($vehicle, 'multi_location', 0)) === 0)>No</option>
                                <option value="1" @selected((int) old('Vehicle.multi_location', data_get($vehicle, 'multi_location', 0)) === 1)>Yes</option>
                            </select>
                        </div>
                    </div>

                    <div id="address_more">
                        @if ($locations->isEmpty())
                            <div class="form-group" id="ele-0">
                                <label class="col-lg-4 control-label">Address 1 :</label>
                                <div class="col-lg-8">
                                    <input name="VehicleLocation[0][address]" class="required geocodeinput form-control"
                                        placeholder="Vehicle Address" value="" type="text">
                                    <input id="VehicleLocation0Lat" name="VehicleLocation[0][lat]" type="hidden">
                                    <input id="VehicleLocation0Lng" name="VehicleLocation[0][lng]" type="hidden">
                                    <input name="VehicleLocation[0][id]" type="hidden">
                                </div>
                                <div class="col-lg-1">
                                    <a href="javascript:;" onclick="address_more(true)"><i
                                            class="icon-plus-circle2 icon-2x"></i></a>
                                </div>
                            </div>
                        @else
                            @foreach ($locations as $k => $location)
                                <div class="form-group" id="ele-{{ $k }}">
                                    <label class="col-lg-4 control-label">Address {{ $k + 1 }} :</label>
                                    <div class="col-lg-8">
                                        <input name="VehicleLocation[{{ $k }}][address]" class="required geocodeinput form-control"
                                            placeholder="Vehicle Address"
                                            value="{{ old('VehicleLocation.' . $k . '.address', data_get($location, 'address')) }}"
                                            type="text">
                                        <input id="VehicleLocation{{ $k }}Lat" name="VehicleLocation[{{ $k }}][lat]"
                                            value="{{ old('VehicleLocation.' . $k . '.lat', data_get($location, 'lat')) }}"
                                            type="hidden">
                                        <input id="VehicleLocation{{ $k }}Lng" name="VehicleLocation[{{ $k }}][lng]"
                                            value="{{ old('VehicleLocation.' . $k . '.lng', data_get($location, 'lng')) }}"
                                            type="hidden">
                                        <input name="VehicleLocation[{{ $k }}][id]" value="{{ data_get($location, 'id') }}"
                                            type="hidden">
                                    </div>
                                    @if ($k === 0)
                                        <div class="col-lg-1">
                                            <a href="javascript:;" onclick="address_more(true)"><i
                                                    class="icon-plus-circle2 icon-2x"></i></a>
                                        </div>
                                    @else
                                        <div class="col-lg-1">
                                            <a href="javascript:;" onclick="address_more(false)"><i
                                                    class="icon-minus-circle2 icon-2x"></i></a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel-heading">
                    <h5 class="panel-title">5. Documentation</h5>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Registered name :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[registered_name]" class="form-control"
                                value="{{ old('Vehicle.registered_name', data_get($vehicle, 'registered_name')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Plate :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[plate_number]" class="form-control"
                                value="{{ old('Vehicle.plate_number', data_get($vehicle, 'plate_number')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Registered state (abbr) :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[registered_state]" maxlength="3" class="form-control"
                                value="{{ old('Vehicle.registered_state', data_get($vehicle, 'registered_state', 'NY')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Registration date :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[reg_name_date]" class="form-control reg_name_date"
                                placeholder="m/d/Y"
                                value="{{ old('Vehicle.reg_name_date', VehicleAdminSave::formatDateInput(data_get($vehicle, 'reg_name_date'))) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Registration exp. :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[reg_name_exp_date]" class="form-control reg_name_exp_date"
                                placeholder="m/d/Y"
                                value="{{ old('Vehicle.reg_name_exp_date', VehicleAdminSave::formatDateInput(data_get($vehicle, 'reg_name_exp_date'))) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Insurance company :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[insurance_company]" class="form-control"
                                value="{{ old('Vehicle.insurance_company', data_get($vehicle, 'insurance_company')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Policy # :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[insurance_policy_no]" class="form-control"
                                value="{{ old('Vehicle.insurance_policy_no', data_get($vehicle, 'insurance_policy_no')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Policy begin :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[insurance_policy_date]"
                                class="form-control insurance_policy_date" placeholder="m/d/Y"
                                value="{{ old('Vehicle.insurance_policy_date', data_get($vehicle, 'insurance_policy_date')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Policy expiration :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[insurance_policy_exp_date]"
                                class="form-control insurance_policy_exp_date" placeholder="m/d/Y"
                                value="{{ old('Vehicle.insurance_policy_exp_date', VehicleAdminSave::formatDateInput(data_get($vehicle, 'insurance_policy_exp_date'))) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Inspection expiration :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[inspection_exp_date]" class="form-control inspection_exp_date"
                                placeholder="m/d/Y"
                                value="{{ old('Vehicle.inspection_exp_date', VehicleAdminSave::formatDateInput(data_get($vehicle, 'inspection_exp_date'))) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">State inspection exp. :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[state_insp_exp_date]" class="form-control state_insp_exp_date"
                                placeholder="m/d/Y"
                                value="{{ old('Vehicle.state_insp_exp_date', VehicleAdminSave::formatDateInput(data_get($vehicle, 'state_insp_exp_date'))) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel-heading">
                    <h5 class="panel-title">6. Pricing</h5>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Pricing style :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <select name="Vehicle[fare_type]" id="VehicleFareType" class="form-control required">
                                <option value="S" @selected(old('Vehicle.fare_type', data_get($vehicle, 'fare_type', 'S')) === 'S')>
                                    Static</option>
                                <option value="D" @selected(old('Vehicle.fare_type', data_get($vehicle, 'fare_type')) === 'D')>
                                    Dynamic
                                </option>
                                <option value="L" @selected(old('Vehicle.fare_type', data_get($vehicle, 'fare_type')) === 'L')>
                                    Lease
                                    Plus</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="pricingUnitBlk">
                        <label class="col-lg-4 control-label">Pricing Unit :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            @php
                                $curRental = old('Vehicle.rental', data_get($vehicle, 'rate', 0) > 0 ? 'hr' : 'day');
                            @endphp
                            <select name="Vehicle[rental]" id="VehicleRental" class="form-control required"
                                rel_hr="{{ data_get($vehicle, 'rate', 0) }}"
                                rel_day="{{ data_get($vehicle, 'day_rent', 0) }}">
                                <option value="hr" @selected($curRental === 'hr')>Hour</option>
                                <option value="day" @selected($curRental === 'day')>Day</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="rateBlk"
                        style="display: {{ (old('Vehicle.fare_type', data_get($vehicle, 'fare_type', 'S')) === 'S' && $curRental === 'hr') ? 'block' : 'none' }}">
                        <label class="col-lg-4 control-label">Rate (per hour) :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[rate]" id="VehicleRate" class="form-control required digits"
                                value="{{ old('Vehicle.rate', data_get($vehicle, 'rate')) }}">
                        </div>
                    </div>

                    <div class="form-group" id="dayBlk"
                        style="display: {{ (old('Vehicle.fare_type', data_get($vehicle, 'fare_type', 'S')) === 'S' && $curRental === 'day') ? 'block' : 'none' }}">
                        <label class="col-lg-4 control-label">Day rent :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[day_rent]" id="VehicleDayRent"
                                class="form-control required number"
                                value="{{ old('Vehicle.day_rent', data_get($vehicle, 'day_rent')) }}">
                            <span class="help-block">Min/Max Rent Per Day (if you setup this then flat amount per day will
                                be applied)</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Authorize payment :</label>
                        <div class="col-lg-8">
                            <select name="Vehicle[auth_require]" class="form-control">
                                <option value="0" @selected((int) old('Vehicle.auth_require', data_get($vehicle, 'auth_require', 0)) === 0)>Disable</option>
                                <option value="1" @selected((int) old('Vehicle.auth_require', data_get($vehicle, 'auth_require', 0)) === 1)>Enable</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">MSRP (homenet) :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[homenet_msrp]" class="form-control"
                                value="{{ old('Vehicle.homenet_msrp', data_get($vehicle, 'homenet_msrp')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Dealer selling price (msrp) :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[msrp]" class="form-control"
                                value="{{ old('Vehicle.msrp', data_get($vehicle, 'msrp')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Listed selling price (premium) :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[premium_msrp]" class="form-control"
                                value="{{ old('Vehicle.premium_msrp', data_get($vehicle, 'premium_msrp')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Vehicle cost incl. recon :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[vehicleCostInclRecon]" class="form-control"
                                value="{{ old('Vehicle.vehicleCostInclRecon', data_get($vehicle, 'vehicleCostInclRecon')) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">KBB/NADA wholesale :</label>
                        <div class="col-lg-8">
                            <input type="text" name="Vehicle[kbbnadaWholesaleBook]" class="form-control"
                                value="{{ old('Vehicle.kbbnadaWholesaleBook', data_get($vehicle, 'kbbnadaWholesaleBook')) }}">
                        </div>
                    </div>
                </div>
            </div>

            @php
                $initialPreview = [];
                $initialPreviewConfig = [];
                if (!empty($vehicle?->id) && $vehicle->images) {
                    foreach ($vehicle->images as $img) {
                        if ($img->remote) {
                            $initialPreview[] = $img->filename;
                            $initialPreviewConfig[] = [
                                'filename' => $img->filename,
                                'key' => (int) $img->id,
                                'width' => '120px',
                                'downloadUrl' => false,
                                'iorder' => $img->iorder
                            ];
                        } else {
                            $initialPreview[] = legacy_site_url() . 'img/custom/vehicle_photo/' . $img->filename;
                            $initialPreviewConfig[] = [
                                'caption' => $img->filename,
                                'filename' => $img->filename,
                                'key' => (int) $img->id,
                                'width' => '120px',
                                'downloadUrl' => false,
                                'iorder' => $img->iorder,
                                'class' => 'cropme'
                            ];
                        }
                    }
                }
            @endphp

            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">7. Upload Documents</h5>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-semibold">Registration Doc:</label>
                        <div class="col-lg-8">
                            @if (!empty($vehicle?->registration_image))
                                @php
                                    $regExt = strtolower(pathinfo($vehicle->registration_image, PATHINFO_EXTENSION));
                                @endphp
                                <div style="margin-bottom:10px;">
                                    @if (in_array($regExt, ['doc', 'docx', 'pdf']))
                                        <iframe height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->registration_image }}"></iframe>
                                    @else
                                        <img height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->registration_image }}" />
                                    @endif
                                </div>
                            @endif
                            <input type="file" class="form-control" name="registration_image" id="VehicleRegistrationImage"
                                data-show-preview="false" data-show-upload="false">
                            <span class="help-block">Please upload registration doc. (MAX File Size
                                {{ ini_get('upload_max_filesize') }})</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label text-semibold">Insurance Doc:</label>
                        <div class="col-lg-8">
                            @if (!empty($vehicle?->insurance_image))
                                @php
                                    $insExt = strtolower(pathinfo($vehicle->insurance_image, PATHINFO_EXTENSION));
                                @endphp
                                <div style="margin-bottom:10px;">
                                    @if (in_array($insExt, ['doc', 'docx', 'pdf']))
                                        <iframe height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->insurance_image }}"></iframe>
                                    @else
                                        <img height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->insurance_image }}" />
                                    @endif
                                </div>
                            @endif
                            <input type="file" class="form-control" name="insurance_image" id="VehicleInsuranceImage"
                                data-show-preview="false" data-show-upload="false">
                            <span class="help-block">Please upload insurance doc. (MAX File Size
                                {{ ini_get('upload_max_filesize') }})</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label text-semibold">Vehicle Inspection:</label>
                        <div class="col-lg-8">
                            @if (!empty($vehicle?->inspection_image))
                                @php
                                    $inspExt = strtolower(pathinfo($vehicle->inspection_image, PATHINFO_EXTENSION));
                                @endphp
                                <div style="margin-bottom:10px;">
                                    @if (in_array($inspExt, ['doc', 'docx', 'pdf']))
                                        <iframe height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->inspection_image }}"></iframe>
                                    @else
                                        <img height="150px" width="150px"
                                            src="{{ legacy_site_url() }}img/custom/vehicle_photo/{{ $vehicle->inspection_image }}" />
                                    @endif
                                </div>
                            @endif
                            <input type="file" class="form-control" name="inspection_image" id="VehicleInspectionImage"
                                data-show-preview="false" data-show-upload="false">
                            <span class="help-block">Please upload inspection doc. (MAX File Size
                                {{ ini_get('upload_max_filesize') }})</span>
                        </div>
                    </div>

                    @if (!empty($vehicle?->id))
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Vehicle Images</label>
                            <div class="col-lg-8">
                                <input type="file" class="fileinputajax" multiple="multiple" name="vehicleimage"
                                    data-show-preview="true" data-show-upload="true">
                                <span class="help-block">You can select multiple images.</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-primary">
                        {{ !empty($vehicle->id) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>

            <!-- cropModal Modal -->
            <div id="cropModal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="image-cropper-container content-group" style="height: 500px;">
                                <img src="" alt="" class="cropper">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <div class="row">
                                <div class="col-lg-4">
                                    <p><button id="cropImage" type="button" class="btn btn-info btn-block">Crop</button></p>
                                </div>
                                <div class="col-lg-8 text-right">
                                    <div class="btn-group" style="margin-right:10px;">
                                        <button id="rotateLeft" type="button" class="btn btn-info"><i
                                                class="icon-rotate-ccw3"></i></button>
                                        <button id="rotateRight" type="button" class="btn btn-info"><i
                                                class="icon-rotate-cw3"></i></button>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" title="Move" id="setDragModeMove">
                                            <span class="docs-tooltip" data-toggle="tooltip" title="Move Image Mode">
                                                <i class="icon-move"></i>
                                            </span>
                                        </button>
                                        <button type="button" class="btn btn-primary" title="Crop" id="setDragModeCrop">
                                            <span class="docs-tooltip" data-toggle="tooltip" title="Crop Mode">
                                                <i class="icon-crop2"></i>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/sortable.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/media/cropper.js') }}"></script>
    <script>
        (function () {
            var ft = document.getElementById('VehicleFareType');
            var rental = document.getElementById('VehicleRental');
            var dayBlk = document.getElementById('dayBlk');
            var rateBlk = document.getElementById('rateBlk');
            var dayIn = document.getElementById('VehicleDayRent');
            var rateIn = document.getElementById('VehicleRate');
            var pricingUnitBlk = document.getElementById('pricingUnitBlk');

            function syncFare() {
                if (!ft) return;
                var fareVal = ft.value;
                if (fareVal === 'D' || fareVal === 'L') {
                    if (pricingUnitBlk) pricingUnitBlk.style.display = 'none';
                    if (dayBlk) dayBlk.style.display = 'none';
                    if (rateBlk) rateBlk.style.display = 'none';
                    if (dayIn) dayIn.value = '0';
                    if (rateIn) rateIn.value = '0';
                } else {
                    if (pricingUnitBlk) pricingUnitBlk.style.display = 'block';
                    if (rental) {
                        var rentalVal = rental.value;
                        if (rentalVal === 'hr') {
                            if (dayBlk) dayBlk.style.display = 'none';
                            if (dayIn) dayIn.value = '0';
                            if (rateIn && (!rateIn.value || rateIn.value === '0' || rateIn.value === '0.00')) {
                                rateIn.value = rental.getAttribute('rel_hr') || '0';
                            }
                            if (rateBlk) rateBlk.style.display = 'block';
                        } else {
                            if (rateBlk) rateBlk.style.display = 'none';
                            if (rateIn) rateIn.value = '0';
                            if (dayIn && (!dayIn.value || dayIn.value === '0' || dayIn.value === '0.00')) {
                                dayIn.value = rental.getAttribute('rel_day') || '0';
                            }
                            if (dayBlk) dayBlk.style.display = 'block';
                        }
                    }
                }
            }

            if (ft) {
                ft.addEventListener('change', syncFare);
            }
            if (rental) {
                rental.addEventListener('change', syncFare);
            }
            syncFare();

            jQuery(document).ready(function () {
                jQuery(".inspection_exp_date,.state_insp_exp_date,.reg_name_exp_date,.reg_name_date,.insurance_policy_exp_date,.insurance_policy_date,#VehicleAvailabilityDate").datepicker({
                    dateFormat: 'mm/dd/yy',
                    changeMonth: true,
                    changeYear: true
                });
            });

            var autocomplete = [];
            var options = {
                types: ['geocode']
            };

            function setupAutocomplete(autocomplete, inputs, i) {
                autocomplete.push(new google.maps.places.Autocomplete(inputs[i], options));
                var idx = autocomplete.length - 1;
                idx = idx < 0 ? 0 : idx;
                google.maps.event.addListener(autocomplete[idx], 'place_changed', function () {
                    var placeorg = autocomplete[idx].getPlace();
                    if (!placeorg.geometry) {
                        return;
                    }
                    $('#VehicleLocation' + parseInt(idx) + 'Lat').val(placeorg.geometry.location.lat());
                    $('#VehicleLocation' + parseInt(idx) + 'Lng').val(placeorg.geometry.location.lng());
                });
            }

            function initiategplace() {
                autocomplete = [];
                var inputs = document.getElementsByClassName("geocodeinput");
                for (var i = 0; i < inputs.length; i++) {
                    setupAutocomplete(autocomplete, inputs, i);
                }
            }

            window.address_more = function (v) {
                var $panel = $("#address_more").closest(".panel-body");
                var elem = parseInt($panel.attr('rel-address'), 10);
                if (isNaN(elem)) {
                    elem = 1;
                }
                if (v) {
                    if (elem === 5) {
                        alert("Sorry, you cant add more than 5 records");
                        return;
                    }
                    var nextIndex = elem;
                    elem++;
                    var element = '<div class="form-group" id="ele-' + nextIndex + '">' +
                        '<label class="col-lg-4 control-label">Address ' + elem + ' :</label>' +
                        '<div class="col-lg-8">' +
                        '<input name="VehicleLocation[' + nextIndex + '][address]" class="form-control geocodeinput" placeholder="Vehicle Address" value="" type="text">' +
                        '<input id="VehicleLocation' + nextIndex + 'Lat" name="VehicleLocation[' + nextIndex + '][lat]" value="" type="hidden">' +
                        '<input id="VehicleLocation' + nextIndex + 'Lng" name="VehicleLocation[' + nextIndex + '][lng]" value="" type="hidden">' +
                        '<input name="VehicleLocation[' + nextIndex + '][id]" value="" type="hidden">' +
                        '</div>' +
                        '<div class="col-lg-1"><a href="javascript:;" onclick="address_more(false)"><i class="icon-minus-circle2 icon-2x"></i></a></div></div>';
                    $("#address_more").append(element);
                    initiategplace();
                } else {
                    $("#address_more #ele-" + (elem - 1)).remove();
                    elem--;
                }
                $panel.attr('rel-address', elem);
            }

            jQuery(document).ready(function () {
                initiategplace();
                $.ajaxSetup({
                    cache: false
                });
            });

            @if (!empty($vehicle?->id))
                $(function () {
                    $('#VehicleRegistrationImage,#VehicleInspectionImage,#VehicleInsuranceImage').fileinput({
                        initialPreview: false,
                        browseLabel: 'Browse',
                        browseIcon: '<i class="icon-file-plus"></i>',
                        uploadIcon: '<i class="icon-file-upload2"></i>',
                        removeIcon: '<i class="icon-cross3"></i>',
                        layoutTemplates: {
                            icon: '<i class="icon-file-check"></i>'
                        },
                        initialCaption: "No file selected"
                    });

                    var btns = '<button type="button" onclick="kvcustbtn(\'{caption}\',{key})" class="kvcustbtn btn btn-kv btn-secondary" title="Edit" data-url="{caption}" {dataKey}>' +
                        '<i class="glyphicon glyphicon-edit"></i>' +
                        '</button>';

                    $(".fileinputajax").fileinput({
                        showUpload: false,
                        otherActionButtons: btns,
                        uploadUrl: SITE_URL + "admin/vehicles/saveImage",
                        uploadAsync: true,
                        maxFileCount: 15,
                        deleteUrl: SITE_URL + "admin/vehicles/deleteImage",
                        allowedFileExtensions: ['jpeg', 'jpg', 'png'],
                        initialPreview: @json($initialPreview),
                        overwriteInitial: false,
                        initialPreviewAsData: true,
                        initialPreviewFileType: 'image',
                        initialPreviewConfig: @json($initialPreviewConfig),
                        maxFileSize: 1024,
                        uploadExtraData: {
                            'id': {{ $vehicle->id }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            },
                        fileActionSettings: {
                            removeIcon: '<i class="icon-bin"></i>',
                            removeClass: 'btn btn-link btn-xs btn-icon',
                            uploadIcon: '<i class="icon-upload"></i>',
                            uploadClass: 'btn btn-link btn-xs btn-icon',
                            indicatorNew: '<i class="icon-file-plus text-slate"></i>',
                            indicatorSuccess: '<i class="icon-checkmark3 file-icon-large text-success"></i>',
                            indicatorError: '<i class="icon-cross2 text-danger"></i>',
                            indicatorLoading: '<i class="icon-spinner2 spinner text-muted"></i>',
                            showZoom: true,
                            showCaption: false,
                        }
                    }).on('fileuploaded', function (event, data, previewId, index) {
                        $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
                    }).on('filesorted', function (e, params) {
                        console.log('File sorted params', params);
                        $.post(SITE_URL + "admin/vehicles/reorderImage", params, function (resp) {
                        }, 'json');
                    });
                });

                var imageUrl = SITE_URL + 'img/custom/vehicle_photo/';
                var $cropper;
                var IMG;
                $(document).ready(function () {
                    $("#cropImage").click(function () {
                        jQuery.blockUI({
                            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>'
                        });
                        var blob = $cropper.getCroppedCanvas().toDataURL('image/jpeg');
                        var formData = {
                            'vehicleimage': blob,
                            "image": IMG.name
                        };
                        $.post(SITE_URL + "images/crop", formData, function (resp) {
                            jQuery.unblockUI();
                            $("#cropModal").modal('hide');
                        }, 'json');
                    });
                    $("#rotateLeft").click(function () {
                        $cropper.rotate(-45);
                    });
                    $("#rotateRight").click(function () {
                        $cropper.rotate(45);
                    });
                    $("#setDragModeMove").click(function () {
                        $cropper.setDragMode('move');
                    });
                    $("#setDragModeCrop").click(function () {
                        $cropper.setDragMode('crop');
                    });
                });

                function calculateCoeff(img_value, property) {
                    var x = 0;
                    property == "width" ? x = 500 : x = 400;
                    return ((x * 100) / img_value) * 0.01;
                }

                window.kvcustbtn = function (file, key) {
                    if (file == '') {
                        alert("Sorry, you cant edit this image");
                        return false;
                    }
                    IMG = new Image();
                    IMG.key = key;
                    IMG.name = file;
                    IMG.src = imageUrl + file;
                    IMG.onload = function () {
                        var image = document.createElement('img');
                        image.src = IMG.src;
                        var coef = 0;
                        IMG.width > IMG.height ? coef = calculateCoeff(IMG.width, "width") : coef = calculateCoeff(IMG.height, "height");
                        var height = (coef * IMG.height);
                        var width = (coef * IMG.width);
                        $cropper = new Cropper(image, {
                            aspectRatio: "",
                            cropBoxMovable: true,
                            toggleDragModeOnDblclick: true,
                            minContainerHeight: height,
                            minContainerWidth: width,
                            minCanvasHeight: height,
                            minCanvasWidth: width
                        });

                        $("#cropModal .image-cropper-container").html(image);
                        $("#cropModal").modal('show');
                    }
                }
            @endif
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