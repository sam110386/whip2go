@extends('admin.layouts.app')

@php
    $title ??= 'Update Booking';
    $curpickuptime ??= '12:01 AM';
    $curendtime ??= '11:59 PM';
    $order ??= collect();
@endphp

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/timepicki.css') }}">

    <style type="text/css">
        .datepicker .prev,
        .datepicker .next {
            background: none;
        }
    </style>

@endpush

@section('content')

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form action="/admin/bookings/editsave" method="POST" name="triplogForm" id="triplogForm" class="form-horizontal">
        @csrf

        <input type="hidden" name="Text[id]" value="{{ $order->id }}">

        <div class="row">
            <div class="col-sm-5">
                <div class="panel panel-flat">
                    <div class="panel-heading">
                        <h5 class="panel-title">Update Booking # {{ $order->increment_id }}</h5>
                    </div>

                    <div class="panel-body">
                        <fieldset>
                            <legend class="text-semibold">Enter All Information</legend>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Vehicle :</label>
                                <div class="col-lg-8">
                                    <input type="text" name="Text[vehicle_id]" id="TextVehicleId"
                                        class="focus_text textfield" style="width:100%;"
                                        value="{{ $order->vehicle->id ?? '' }}" placeholder="Vehicle">
                                    <input type="hidden" name="Text[dealer_id]" id="TextDealerId"
                                        value="{{ $order->user_id }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">Address :</label>
                                <div class="col-lg-8">
                                    <input type="text" name="Text[location]" id="TextLocation"
                                        class="focus_text textfield form-control" placeholder="Vehicle Address"
                                        value="{{ $order->pickup_address }}">
                                    <input type="hidden" name="Text[originlatlng]" id="TextOriginlatlng"
                                        value="{{ $order->lat }},{{ $order->lng }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-12 control-label text-danger">
                                    <input type="checkbox" name="Text[updatebooking]" class="control-warning" />
                                    Would you like to update the rate of this order to match the changed vehicle? (Rate will
                                    be applied to next extension of booking)
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">&nbsp;</label>
                                <div class="col-lg-8">
                                    <button type="button" class="focus_text btn no-margin" id="dispatchBtn"
                                        style="float: right;">Proceed</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>

            <div class="col-sm-7">
                <div class="panel panel-flat">
                    <div class="panel-heading">
                        <h5 class="panel-title">Schedule Information</h5>
                    </div>

                    <div class="panel-body">
                        <legend class="text-semibold">&nbsp;</legend>
                        <div class="row form-group">
                            <div class="col-md-2">From Date </div>
                            <div class="col-md-3">
                                <input type="text" name="daterangefrom" id="daterangefrom"
                                    class="form-control required date"
                                    value="{{ \Carbon\Carbon::parse($order->start_datetime)->format('m/d/Y') }}" {{ $order->status == 1 ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-2"> Time</div>
                            <div class="col-md-3">
                                <input type="text" name="Text[start_time]"
                                    class="form-control {{ $order->status == 1 ? '' : 'timeClass' }}"
                                    value="{{ \Carbon\Carbon::parse($order->start_datetime)->format('h:i A') }}" {{ $order->status == 1 ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-2">To Date</div>
                            <div class="col-md-3">
                                <input type="text" class="form-control required date" name="daterangeto" id="daterangeto"
                                    value="{{ \Carbon\Carbon::parse($order->end_datetime)->format('m/d/Y') }}">
                            </div>
                            <div class="col-md-2"> Time</div>
                            <div class="col-md-3">
                                <input type="text" name="Text[end_time]" class="focus_text textfield form-control timeClass"
                                    value="{{ \Carbon\Carbon::parse($order->end_datetime)->format('h:i A') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-7">
                <div class="panel panel-flat">
                    <div class="panel-heading">
                        <h5 class="panel-title">Payment Information</h5>
                    </div>

                    <div class="panel-body">
                        <legend class="text-semibold">&nbsp;</legend>
                        <div class="row form-group">
                            <div class="col-md-3">Rent (Including Tax): </div>
                            <div class="col-md-2">
                                {{ $order->rent + $order->tax }}
                            </div>
                            <div class="col-md-2"> {{ $order->payment_status == 1 ? 'Paid' : 'Unpaid' }}</div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-3">Deposit: </div>
                            <div class="col-md-2">
                                {{ $order->deposit }}
                            </div>
                            <div class="col-md-2"> {{ $order->dpa_status == 1 ? 'Paid' : 'Unpaid' }}</div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-3">Insurance: </div>
                            <div class="col-md-2">
                                {{ $order->insurance_amt }}
                            </div>
                            <div class="col-md-2"> {{ $order->insu_status == 1 ? 'Paid' : 'Unpaid' }}</div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-3">Initial Fee: </div>
                            <div class="col-md-2">
                                {{ $order->initial_fee }}
                            </div>
                            <div class="col-md-2"> {{ $order->infee_status == 1 ? 'Paid' : 'Unpaid' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script src="{{ legacy_asset('js/timepicki.js') }}"></script>
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/pickers/datetimepicker.js') }}"></script>
    <script type="text/javascript"
        src="https://maps.googleapis.com/maps/api/js?libraries=places&key={{ config('legacy.GOOGLE_MAPS_API_KEY') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>

    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }

        var _autoComplCounter = 0;

        jQuery(document).ready(function () {
            $('.timeClass').timepicki();
            var VehicleIDS = jQuery("#TextVehicleId").select2({
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Vehicle ",
                minimumInputLength: 1,
                ajax: {
                    url: "/admin/bookings/getVehicle",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return {
                            term: params,
                            dealer_id: jQuery("#TextDealerId").val()
                        }
                    },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id,
                                    address: item.address,
                                    lat: item.lat,
                                    lng: item.lng
                                }
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    callback({
                        tag: '{{ $order->vehicle->vehicle_name ?? "" }}',
                        id: {{ $order->vehicle->id ?? 'null' }},
                        address: '',
                        lat: '',
                        lng: ''
                    });
                }
            });

            jQuery("#TextVehicleId").on('select2-selecting', function (e) {
                jQuery("#TextLocation").val(e.choice.address);
                jQuery("#TextOriginlatlng").val(e.choice.lat + ',' + e.choice.lng);
            });

            var input = document.getElementById('TextLocation');
            var options = { types: ['geocode'] };
            autocomplete = new google.maps.places.Autocomplete(input, options);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                var placeorg = autocomplete.getPlace();
                document.getElementById('TextOriginlatlng').value = placeorg.geometry.location.lat() + ',' + placeorg.geometry.location.lng();
            });

            $.ajaxSetup({ cache: false });

            jQuery('#dispatchBtn').click(function () {
                var pickup_address = jQuery('#TextLocation').val();
                var VehicleId = jQuery("#TextVehicleId").val();

                var errMsg = '';
                if ($.trim(VehicleId) == '') {
                    errMsg += 'Please select vehicle\n';
                }
                if ($.trim(pickup_address) == '') {
                    errMsg += 'Please enter Pickup Address\n';
                }

                if (errMsg !== '') {
                    alert(errMsg);
                    return false;
                }
                jQuery.blockUI({
                    message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
                    css: { 'z-index': '9999' }
                });
                var params = $("#triplogForm").serialize();
                jQuery.post("/admin/bookings/editsave", params, function (data) {
                    jQuery.unblockUI();
                    if (data.status) {
                        swal({
                            title: data.message,
                            text: "I will close in 2 seconds.",
                            confirmButtonColor: "#2196F3",
                            timer: 2000
                        });
                        goBack('/admin/bookings');
                    } else {
                        alert(data.message);
                    }
                }, 'json');

                return false;
            });
        });

        $(function () {
            $('#daterangefrom').datetimepicker({ format: 'MM/DD/YYYY' });
            $('#daterangeto').datetimepicker({
                useCurrent: false,
                format: 'MM/DD/YYYY'
            });
            $("#daterangefrom").on("dp.change", function (e) {
                $('#daterangeto').data("DateTimePicker").minDate(e.date);
            });
            $("#daterangeto").on("dp.change", function (e) {
                $('#daterangefrom').data("DateTimePicker").maxDate(e.date);
            });
        });

    </script>
@endpush