@extends('admin.layouts.app')

@php
    $vehicleId ??= '';
    $title ??= 'Manage Vehicle Alerts';
@endphp

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">

    <style type="text/css">
        tbody tr {
            cursor: pointer;
        }
    </style>

@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> - Vehicle Alerts
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/vehicle_alerts/index') }}">
                <fieldset class="content-group">
                    <div class="col-md-2">
                        <input type="text" id="SearchVehicleId" name="Search[vehicle_id]" style="width:100%;"
                            value="{{ $vehicleId }}" placeholder="Vehicle">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" value="SEARCH" class="btn btn-primary" alt="SEARCH">
                            SEARCH
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.vehicle_alerts.elements.index')
        </div>
    </div>

@endsection



@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script src="{{ legacy_asset('js/vehiclealert/vehiclealert.js') }}"></script>

    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {

            jQuery("#SearchVehicleId").select2({
                data: {
                    results: {},
                    text: 'tag'
                },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Vehicle ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/vehicle_offers/vehicleautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return {
                            term: params,
                            "is_dealer": true
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id
                                };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var vehicle_id = "{{ $vehicleId }}";
                    if (vehicle_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/vehicle_offers/vehicleautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": vehicle_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:void(0)') {
                    loadListing(url);
                }
            });

            $(document).on('submit', '#frmSearchadmin', function (e) {
                e.preventDefault();
                var form = $(this);
                var isClearFilter = false;

                if (e.originalEvent && e.originalEvent.submitter) {
                    var btn = $(e.originalEvent.submitter);
                    if (btn.attr('name') === 'ClearFilter') {
                        isClearFilter = true;
                    }
                }

                if (isClearFilter) {
                    form[0].reset();
                    var baseUrl = form.attr('action');
                    loadListing(baseUrl + '?ClearFilter=1', baseUrl);
                } else {
                    var formData = form.serialize();
                    var url = form.attr('action') + '?' + formData;
                    loadListing(url);
                }
            });

            $(document).on('change', '.ajax-limit', function (e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var url = window.location.pathname + '?' + $('#frmSearchadmin').serialize() + '&' + form.serialize();
                loadListing(url);
            });

            function loadListing(url, historyUrl) {
                if (typeof historyUrl === 'undefined') {
                    historyUrl = url;
                }
                $('#listing').css('opacity', '0.5');

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $('#listing').html(data);
                        $('#listing').css('opacity', '1');
                        window.history.pushState(null, null, historyUrl);
                    },
                    error: function (xhr) {
                        $('#listing').css('opacity', '1');
                        console.error('AJAX Load Error:', xhr);
                    }
                });
            }

            window.onpopstate = function () {
                loadListing(window.location.href);
            };
        });

    </script>

@endpush