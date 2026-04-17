@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Featured Vehicle')

@section('content')
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.api_key', '') }}&libraries=places"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}" />
<script src="{{ asset('js/select2.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#VehicleAvailabilityDate").datepicker({
            dateFormat: 'mm/dd/yy',
            changeMonth: true,
            changeYear: true
        });

        jQuery("#VehicleAdminAddForm").validate();

        jQuery("#VehicleRental").change(function() {
            if (jQuery(this).val() == 'hr') {
                jQuery("#dayblk").hide();
                jQuery("#dayblk").find("input").val(0);
                jQuery("#hrblk").find("input").val(jQuery(this).attr('rel_hr'));
                jQuery("#hrblk").show();
            } else {
                jQuery("#hrblk").hide();
                jQuery("#hrblk").find("input").val(0);
                jQuery("#dayblk").find("input").val(jQuery(this).attr('rel_day'));
                jQuery("#dayblk").show();
            }
        });
        jQuery("#VehicleFareType").change(function() {
            if (jQuery(this).val() == 'D') {
                jQuery("#dayblk").hide();
                jQuery("#dayblk").find("input").val(0);
                jQuery("#hrblk").find("input").val(0);
                jQuery("#hrblk").hide();
            } else {
                if (jQuery("#VehicleRental") == 'hr') {
                    jQuery("#dayblk").hide();
                    jQuery("#dayblk").find("input").val(0);
                    jQuery("#hrblk").find("input").val(jQuery(this).attr('rel_hr'));
                    jQuery("#hrblk").show();
                } else {
                    jQuery("#hrblk").hide();
                    jQuery("#hrblk").find("input").val(0);
                    jQuery("#dayblk").find("input").val(jQuery(this).attr('rel_day'));
                    jQuery("#dayblk").show();
                }
            }
        });
        jQuery("#VehicleStockNo").keyup(function() {
            if (jQuery(this).val().length >= 3) {
                jQuery.post("{{ config('app.url') }}admin/featured_vehicles/checkStockDuplicate", {
                    '_token': '{{ csrf_token() }}',
                    'stock_no': jQuery(this).val()
                }, function(resp) {
                    if (resp.status == 'error') {
                        jQuery("#VehicleStockNo").removeClass('valid').addClass('error');
                        jQuery("#VehicleStockNoHelp").removeClass('hide').addClass('show');
                    } else {
                        jQuery("#VehicleStockNo").removeClass('error').addClass('valid');
                        jQuery("#VehicleStockNoHelp").removeClass('show').addClass('hide');
                    }
                }, "json").done(function() {
                    jQuery("#VehicleStockNo").val(jQuery("#VehicleStockNo").val().toLocaleUpperCase());
                });
            }
        });
    });
</script>
<script type="text/javascript">
    var autocomplete = [];
    var options = {
        types: ['geocode']
    };

    function setupAutocomplete(autocomplete, inputs, i) {
        autocomplete.push(new google.maps.places.Autocomplete(inputs[i], options));
        var idx = autocomplete.length - 1;
        idx = idx < 0 ? 0 : idx;
        google.maps.event.addListener(autocomplete[idx], 'place_changed', function() {
            var placeorg = autocomplete[idx].getPlace();
            if (!placeorg.geometry) {
                return;
            }
            $('#VehicleLocation' + parseInt(idx) + 'Lat').val(placeorg.geometry.location.lat());
            $('#VehicleLocation' + parseInt(idx) + 'Lng').val(placeorg.geometry.location.lng());
        });
    }

    function initiategplace(element) {
        autocomplete = [];
        var inputs = document.getElementsByClassName("geocodeinput");
        for (var i = 0; i < inputs.length; i++) {
            setupAutocomplete(autocomplete, inputs, i);
        }
    }

    function address_more(v) {
        var elem = parseInt($("#address_more").parent(".panel-body").attr('rel-address'));
        if (v) {
            if (elem === 5) {
                alert("Sorry, you cant add more than 5 reccords");
                return;
            }
            elem++;
            var element = '<div class="form-group" id="ele-' + elem + '">' +
                '<label class="col-lg-3 control-label">Address ' + elem + '</label>' +
                '<div class="col-lg-8">' +
                '<input name="VehicleLocation[' + elem + '][address]" class="form-control geocodeinput" placeholder="Pickup address" value="" type="text">' +
                '<input id="VehicleLocation' + elem + 'Lat" name="VehicleLocation[' + elem + '][lat]" class="form-control" value="" type="hidden">' +
                '<input id="VehicleLocation' + elem + 'Lng" name="VehicleLocation[' + elem + '][lng]" class="form-control" value="" type="hidden">' +
                '<input name="VehicleLocation[' + elem + '][id]" class="form-control" value="" type="hidden">' +
                '</div>' +
                '<div class="col-lg-1"><a href="javascript:;" onclick="address_more(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
            $("#address_more").append(element);
            initiategplace();
        } else {
            $("#address_more #ele-" + elem).remove();
            elem--;
        }
        $("#address_more").parent(".panel-body").attr('rel-address', elem);
    }
</script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        initiategplace();
        $.ajaxSetup({
            cache: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
    });
</script>

<form action="{{ config('app.url') }}admin/featured_vehicles/add" method="POST" class="form-horizontal" id="VehicleAdminAddForm" enctype="multipart/form-data">
    @csrf
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle }}</span></h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    @if(!empty($vehicle['id']) && (($vehicle['passtime'] ?? '') == 'smartcar' || ($vehicle['gps_provider'] ?? '') == 'smartcar'))
                        <a href="{{ config('app.url') }}admin/smart_cars/connect/{{ base64_encode($vehicle['user_id']) }}" onclick="window.open($(this).attr('href'),'DriveItAway','scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,width=0,height=0,left=-1000,top=-1000');return false;" class="btn">Connect EV to Smart Car</a>
                    @endif
                    @if(empty($vehicle['id']))
                        <button type="submit" class="btn btn-primary w-50">Save</button>
                    @else
                        <button type="submit" class="btn btn-primary">Update</button>
                    @endif
                    <button type="button" class="btn left-margin btn-cancel" onClick="goBack('/admin/vehicles/index')">Return</button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="masonry">
        <div class="item">
            <div class="panel-body">
                <legend class="text-size-large text-bold">1. Details</legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Dealer :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[user_id]" id="VehicleUserId" class="required textfield" placeholder="Select Owner" style="width:100%;" value="{{ $vehicle['user_id'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Listing Type :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <select name="Vehicle[type]" class="required form-control">
                            <option value="real" {{ ($vehicle['type'] ?? '') == 'real' ? 'selected' : '' }}>Real</option>
                            <option value="demo" {{ ($vehicle['type'] ?? '') == 'demo' ? 'selected' : '' }}>Demo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Availability :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <select name="Vehicle[waitlist]" class="required form-control">
                            <option value="0" {{ ($vehicle['waitlist'] ?? '') == '0' ? 'selected' : '' }}>Available</option>
                            <option value="1" {{ ($vehicle['waitlist'] ?? '') == '1' ? 'selected' : '' }}>Waitlist</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Availability Date :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[availability_date]" id="VehicleAvailabilityDate" class="form-control" value="{{ !empty($vehicle['availability_date']) ? date('m/d/Y', strtotime($vehicle['availability_date'])) : '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Stock # :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[stock_no]" id="VehicleStockNo" maxlength="20" class="required form-control" autocomplete="off" value="{{ $vehicle['stock_no'] ?? '' }}" />
                        <label id="VehicleStockNoHelp" class="error hide">Sorry, stock # already exists</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">VIN Number :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[vin_no]" maxlength="100" class="required form-control" style="text-transform:uppercase" value="{{ $vehicle['vin_no'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Make :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[make]" maxlength="100" class="required form-control" placeholder="Make" value="{{ $vehicle['make'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Model :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[model]" maxlength="100" class="required form-control" placeholder="Model" value="{{ $vehicle['model'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Year :</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[year]" class="form-control">
                            @for($y = date('Y') + 1; $y >= date('Y') - 70; $y--)
                                <option value="{{ $y }}" {{ ($vehicle['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Original Odometer :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[odometer]" class="digits form-control" value="{{ $vehicle['odometer'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Current Odometer :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[last_mile]" class="digits form-control" readonly value="{{ $vehicle['last_mile'] ?? '' }}" />
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="panel-body">
                <legend class="text-size-large text-bold">2. Features</legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Trim :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[trim]" class="form-control" placeholder="Trim" value="{{ $vehicle['trim'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Engine :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[engine]" maxlength="50" class="form-control" value="{{ $vehicle['engine'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Transmission Type :</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[transmition_type]" class="form-control">
                            <option value="M" {{ ($vehicle['transmition_type'] ?? '') == 'M' ? 'selected' : '' }}>Manual</option>
                            <option value="A" {{ ($vehicle['transmition_type'] ?? '') == 'A' ? 'selected' : '' }}>Automatic</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Vehicle Type:<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[cab_type]" maxlength="100" class="required form-control" placeholder="Cab Type" value="{{ $vehicle['cab_type'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Exterior Color :</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[color]" class="form-control">
                            <option value="">-- Select --</option>
                            @if(!empty($colors))
                                @foreach($colors as $colorKey => $colorName)
                                    <option value="{{ $colorKey }}" {{ ($vehicle['color'] ?? '') == $colorKey ? 'selected' : '' }}>{{ $colorName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Interior Color :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[interior_color]" class="form-control" placeholder="Interior Color" value="{{ $vehicle['interior_color'] ?? '' }}" />
                    </div>
                </div>
                @php $distUnit = ($vehicle['distance_unit'] ?? '') == 'KM' ? 'KM' : 'Miles'; @endphp
                @php $fuelUnit = ($vehicle['distance_unit'] ?? '') == 'KM' ? 'Liter' : 'Gallon'; @endphp
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{ $distUnit }} per {{ $fuelUnit }}(City) :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[mpg_city]" maxlength="5" class="form-control digit" value="{{ $vehicle['mpg_city'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{ $distUnit }} per {{ $fuelUnit }} (Highway) :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[mpg_hwy]" maxlength="5" class="form-control digit" value="{{ $vehicle['mpg_hwy'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label"># of Doors :</label>
                    <div class="col-lg-8">
                        <input type="number" name="Vehicle[doors]" maxlength="2" class="form-control digit" value="{{ $vehicle['doors'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Standard Equipment :</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[equipment]" class="form-control" value="{{ $vehicle['equipment'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Description :</label>
                    <div class="col-lg-8">
                        <textarea name="Vehicle[details]" class="form-control">{{ $vehicle['details'] ?? '' }}</textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Disclosures :</label>
                    <div class="col-lg-8">
                        <textarea name="Vehicle[disclosure]" class="form-control" rows="2" maxlength="400">{{ $vehicle['disclosure'] ?? '' }}</textarea>
                        <span class="help-block">*Any disclosure that you want to print on agreement doc</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="item">
            <div class="panel-body">
                <legend class="text-size-large text-bold">3. Program</legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Financing :</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[financing]" class="form-control">
                            <option value="none" {{ ($vehicle['financing'] ?? '') == 'none' ? 'selected' : '' }}>None</option>
                            <option value="lease" {{ ($vehicle['financing'] ?? '') == 'lease' ? 'selected' : '' }}>Lease</option>
                            <option value="loan" {{ ($vehicle['financing'] ?? '') == 'loan' ? 'selected' : '' }}>Loan</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Allowed {{ $distUnit }} (Per Day):<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="number" name="Vehicle[allowed_miles]" maxlength="11" class="number form-control required" value="{{ $vehicle['allowed_miles'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Roadside Assistance Included In Fee:</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[roadside_assistance_included]" class="form-control">
                            <option value="1" {{ ($vehicle['roadside_assistance_included'] ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ ($vehicle['roadside_assistance_included'] ?? '') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Maintenance Included In Fee:</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[maintenance_included_fee]" class="form-control">
                            <option value="1" {{ ($vehicle['maintenance_included_fee'] ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ ($vehicle['maintenance_included_fee'] ?? '') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="item">
            <div class="panel-body" rel-address="{{ count($vehicleLocations) }}">
                <legend class="text-size-large text-bold">4. Vehicle Address</legend>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Show All Locations:</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[multi_location]" class="form-control">
                            <option value="0" {{ ($vehicle['multi_location'] ?? '') == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ ($vehicle['multi_location'] ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                </div>
                <div id="address_more">
                    @if(empty($vehicleLocations))
                        <div class="form-group" id="ele-1">
                            <label class="col-lg-3 control-label">Address :</label>
                            <div class="col-lg-8">
                                <input name="VehicleLocation[0][address]" class="required geocodeinput form-control" placeholder="Vehicle Address" value="" type="text" />
                                <input id="VehicleLocation0Lat" name="VehicleLocation[0][lat]" type="hidden" value="" />
                                <input id="VehicleLocation0Lng" name="VehicleLocation[0][lng]" type="hidden" value="" />
                                <input name="VehicleLocation[0][id]" type="hidden" value="" />
                            </div>
                            <div class="col-lg-1"><a href="javascript:;" onclick="address_more(true)"><i class="icon-plus-circle2 icon-2x"></i></a></div>
                        </div>
                    @else
                        @foreach($vehicleLocations as $k => $location)
                            <div class="form-group" id="ele-{{ $k }}">
                                <label class="col-lg-3 control-label">Address {{ $k }}:</label>
                                <div class="col-lg-8">
                                    <input name="VehicleLocation[{{ $k }}][address]" class="required geocodeinput form-control" placeholder="Vehicle Address" value="{{ $location['address'] }}" type="text" />
                                    <input id="VehicleLocation{{ $k }}Lat" name="VehicleLocation[{{ $k }}][lat]" type="hidden" value="{{ $location['lat'] }}" />
                                    <input id="VehicleLocation{{ $k }}Lng" name="VehicleLocation[{{ $k }}][lng]" type="hidden" value="{{ $location['lng'] }}" />
                                    <input name="VehicleLocation[{{ $k }}][id]" type="hidden" value="{{ $location['id'] }}" />
                                </div>
                                @if($k === 0)
                                    <div class="col-lg-1"><a href="javascript:;" onclick="address_more(true)"><i class="icon-plus-circle2 icon-2x"></i></a></div>
                                @else
                                    <div class="col-lg-1"><a href="javascript:;" onclick="address_more(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="item">
            <div class="panel-body">
                <legend class="text-size-large text-bold">5. Pricing</legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Pricing Style :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <select name="Vehicle[fare_type]" id="VehicleFareType" class="required form-control">
                            <option value="D" {{ ($vehicle['fare_type'] ?? '') == 'D' ? 'selected' : '' }}>Dynamic</option>
                            <option value="S" {{ ($vehicle['fare_type'] ?? '') == 'S' ? 'selected' : '' }}>Static</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Pricing Unit :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <select name="Vehicle[rental]" id="VehicleRental" class="required form-control" rel_hr="{{ $vehicle['rate'] ?? 0 }}" rel_day="{{ $vehicle['day_rent'] ?? 0 }}">
                            <option value="hr" {{ (($vehicle['rate'] ?? 0) > 0) ? 'selected' : '' }}>Hour</option>
                            <option value="day" {{ (($vehicle['rate'] ?? 0) == 0 && !empty($vehicle)) ? 'selected' : '' }}>Day</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="hrblk" {!! (($vehicle['rate'] ?? 0) == 0 && !empty($vehicle)) ? "style='display:none'" : "" !!}>
                    <label class="col-lg-4 control-label">Rate (per hour) :<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[rate]" maxlength="15" class="required digits form-control" value="{{ $vehicle['rate'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group" id="dayblk" {!! (isset($vehicle['rate']) && ($vehicle['day_rent'] ?? 0) == 0) ? "style='display:none'" : "" !!}>
                    <label class="col-lg-4 control-label"> Day Rent :<span class="text-danger">*</span></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[day_rent]" maxlength="10" class="number required form-control" value="{{ $vehicle['day_rent'] ?? '' }}" />
                        <span class="help-block">Min/Max Rent Per Day (if you setup this then flat amount per day will be applied)</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Dealer Selling Price:<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[msrp]" class="required form-control" value="{{ $vehicle['msrp'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Listed Selling Price:<font class="requiredField">*</font></label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[premium_msrp]" class="required form-control number" value="{{ $vehicle['premium_msrp'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Vehicle Cost Incl Recon:</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[vehicleCostInclRecon]" class="form-control" value="{{ $vehicle['vehicleCostInclRecon'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Kbbnada Wholesale Book:</label>
                    <div class="col-lg-8">
                        <input type="text" name="Vehicle[kbbnadaWholesaleBook]" class="form-control" value="{{ $vehicle['kbbnadaWholesaleBook'] ?? '' }}" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Authorize Payment :</label>
                    <div class="col-lg-8">
                        <select name="Vehicle[auth_require]" class="form-control">
                            <option value="0" {{ ($vehicle['auth_require'] ?? '') == '0' ? 'selected' : '' }}>Disable</option>
                            <option value="1" {{ ($vehicle['auth_require'] ?? '') == '1' ? 'selected' : '' }}>Enable</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <legend class="panel-title text-bold">
                6. Configured Variants
            </legend>
            <div class="heading-elements">
                @if(empty($vehicle))
                    <button type="button" class="btn left-margin btn-warning pull-right" onClick="featuredVehicleOpenVariantPopup()">Configure Variants</button>
                @else
                    <button type="button" class="btn left-margin btn-warning pull-right" onClick="featuredVehicleAddVariantPopup('{{ $vehicle['id'] ?? '' }}')">Configure Variants</button>
                @endif
            </div>
        </div>
        <div class="panel-body" id="variantVehicleBlockWrapper">
            @include('admin.featured_vehicles._edit_variation_list', [
                'attributes' => $vehicle['config'] ?? null,
                'varitaions' => $vehicle['VehicleVariation'] ?? [],
            ])
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <legend class="panel-title text-bold">7. Images</legend>
        </div>
        <div class="panel-body">
            @if(!empty($vehicle['id']))
                <div class="form-group">
                    <label class="col-lg-2 control-label">Vehicle Images</label>
                    <div class="col-lg-10">
                        <input type="file" class="fileinputajax" multiple="multiple" name="vehicleimage" data-show-preview=true data-show-upload="true">
                        <span class="help-block">You can select multiple images.</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-2">
                    @if(!empty($vehicle['id']))
                        <button type="submit" class="btn btn-primary w-100">Update</button>
                    @else
                        <button type="submit" class="btn btn-primary w-100">Save</button>
                    @endif
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn left-margin btn-cancel w-100" onClick="goBack('/admin/vehicles/index')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="Vehicle[id]" value="{{ $vehicle['id'] ?? '' }}" />
</form>

<script src="{{ asset('js/plugins/uploaders/sortable.min.js') }}"></script>
<script src="{{ asset('js/plugins/uploaders/fileinput.min.js') }}"></script>
<script src="{{ asset('js/plugins/forms/selects/bootstrap_select.min.js') }}"></script>
<script src="{{ asset('Vehicle/js/vehiclevariant.js') }}"></script>

<!-- Modal -->
<div class="modelsidebar modal fade right" id="modelsidebar" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Variation Details</h4>
        </div>
        <!-- Modal content-->
        <div class="modal-content" style="height: 90%;width:100%">
        </div>
    </div>
</div>

@if(!empty($vehicle['id']))
    @php
        $initialPreview = [];
        $initialPreviewConfig = [];
        foreach ($vehicleImages as $VehicleImage) {
            $img = (array) $VehicleImage;
            if ($img['remote']) {
                $initialPreview[] = $img['filename'];
                $initialPreviewConfig[] = ['filename' => $img['filename'], 'key' => $img['id'], 'width' => '120px', 'downloadUrl' => false, 'iorder' => $img['iorder']];
            } else {
                $initialPreview[] = config('app.url') . 'img/custom/vehicle_photo/' . $img['filename'];
                $initialPreviewConfig[] = ['caption' => $img['filename'], 'filename' => $img['filename'], 'key' => $img['id'], 'width' => '120px', 'downloadUrl' => false, 'iorder' => $img['iorder'], 'class' => 'cropme'];
            }
        }
    @endphp
    <script type="text/javascript">
        $(function() {
            $('#VehicleRegistrationImage,#VehicleInspectionImage').fileinput({
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
                uploadUrl: "{{ config('app.url') }}admin/vehicles/saveImage",
                uploadAsync: true,
                maxFileCount: 15,
                deleteUrl: "{{ config('app.url') }}admin/vehicles/deleteImage",
                allowedFileExtensions: ['jpeg', 'jpg', 'png'],
                initialPreview: {!! str_replace('\\', '', json_encode($initialPreview)) !!},
                overwriteInitial: false,
                initialPreviewAsData: true,
                initialPreviewFileType: 'image',
                initialPreviewConfig: {!! json_encode($initialPreviewConfig) !!},
                maxFileSize: 1024,
                uploadExtraData: {
                    'id': {{ $vehicle['id'] }},
                    '_token': '{{ csrf_token() }}'
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
            }).on('fileuploaded', function(event, data, previewId, index) {
                $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
            }).on('filesorted', function(e, params) {
                console.log('File sorted params', params);
                $.post("{{ config('app.url') }}admin/vehicles/reorderImage", params, function(resp) {}, 'json');
            });
        });
    </script>
@endif

<script type="text/javascript">
    $(function() {
        $(".switch").bootstrapSwitch();
    });
</script>

@if(!empty($vehicle['id']))
    <script src="{{ asset('js/plugins/media/cropper.js') }}"></script>
    <!-- Modal -->
    <div id="cropModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="image-cropper-container content-group" style="height: 500px;">
                        <img src="assets/images/placeholder.jpg" alt="" class="cropper">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-lg-4">
                            <p><button id="cropImage" type="button" class="btn btn-info btn-block">Crop</button></p>
                        </div>
                        <div class="col-lg-4">
                            <div class="btn-group">
                                <button id="rotateLeft" type="button" class="btn btn-info"><i class="icon-rotate-ccw3"></i></button>
                                <button id="rotateRight" type="button" class="btn btn-info"><i class="icon-rotate-cw3"></i></button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" title="Move" id="setDragModeMove">
                                    <span class="docs-tooltip" data-toggle="tooltip" title="" data-original-title="Move Image Mode">
                                        <span class="fa fa-arrows-alt"></span>
                                    </span>
                                </button>
                                <button type="button" class="btn btn-primary" title="Crop" id="setDragModeCrop">
                                    <span class="docs-tooltip" data-toggle="tooltip" title="" data-original-title="Crop Mode">
                                        <span class="icon-crop2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var imageUrl = '{{ config("app.url") }}' + 'img/custom/vehicle_photo/';
        var $cropper;
        var IMG;
        $(document).ready(function() {
            $("#cropImage").click(function() {
                jQuery.blockUI({
                    message: '<h1><img src="' + '{{ config("app.url") }}' + 'img/select2-spinner.gif" /> Just a moment...</h1>'
                });
                var blob = $cropper.getCroppedCanvas().toDataURL('image/jpeg');
                var formData = {
                    'vehicleimage': blob,
                    "image": IMG.name,
                    '_token': '{{ csrf_token() }}'
                };
                $.post("{{ config('app.url') }}images/crop", formData, function(resp) {
                    jQuery.unblockUI();
                    $("#cropModal").modal('hide');
                }, 'json');
            });
            $("#rotateLeft").click(function() {
                $cropper.rotate(-45);
            });
            $("#rotateRight").click(function() {
                $cropper.rotate(45);
            });
            $("#setDragModeMove").click(function() {
                $cropper.setDragMode('move');
            });
            $("#setDragModeCrop").click(function() {
                $cropper.setDragMode('crop');
            });
        });

        function calculateCoeff(img_value, property) {
            var x = 0;
            property == "width" ? x = 500 : x = 400;
            return ((x * 100) / img_value) * 0.01;
        }

        function kvcustbtn(file, key) {
            if (file == '') {
                alert("Sorry, you cant edit this image");
                return false;
            }
            IMG = new Image();
            IMG.key = key;
            IMG.name = file;
            IMG.src = imageUrl + file;
            IMG.onload = function() {
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
    </script>
@endif

<style type="text/css">
    .krajee-default.file-preview-frame .kv-file-content {
        width: 210px;
        height: 160px;
    }
    .is-invalid .select2-container .select2-choice {
        border: 2px solid #dc3545;
    }
    .modelsidebar .modal-header {
        position: relative;
        padding-bottom: 10px;
        background: #fff;
    }
    .modelsidebar.modal.fade:not(.in).left .modal-dialog {
        -webkit-transform: translate3d(-25%, 0, 0);
        transform: translate3d(-25%, 0, 0);
    }
    .modelsidebar.modal.fade:not(.in).right .modal-dialog {
        -webkit-transform: translate3d(25%, 0, 0);
        transform: translate3d(25%, 0, 0);
    }
    .modelsidebar.modal.fade:not(.in).bottom .modal-dialog {
        -webkit-transform: translate3d(0, 25%, 0);
        transform: translate3d(0, 25%, 0);
    }
    .modelsidebar.modal.right .modal-dialog {
        position: absolute;
        top: 0;
        right: 0;
        margin: 0;
    }
    .bootstrap-select.btn-group .btn .filter-option {
        display: flex;
        justify-content: flex-start;
    }
</style>

<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#VehicleUserId").select2({
            data: {
                results: {},
                text: 'tag'
            },
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Dealer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function(params) {
                    return {
                        term: params,
                        "is_dealer": true
                    }
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {
                                tag: item.tag,
                                id: item.id
                            }
                        })
                    };
                }
            },
            initSelection: function(element, callback) {
                var dealer_id = "{{ $vehicle['user_id'] ?? '' }}";
                if (dealer_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                        dataType: "json",
                        type: "GET",
                        data: {
                            "id": dealer_id
                        }
                    }).done(function(data) {
                        callback(data[0]);
                    });
                }
            }
        });

        $('#VehicleAdminAddForm').on('submit', function(e) {
            var $select2 = $('#VehicleUserId', $(this));
            $select2.parents('.form-group').removeClass('is-invalid');
            if ($select2.val() === '') {
                $select2.parents('.form-group').addClass('is-invalid');
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endsection
