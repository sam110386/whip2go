@extends('admin.layouts.app')

@section('title', $title ?? 'Vehicle Revenue - Report')

@php
    $datefrom ??= '';
    $dateto ??= '';
    $dealerid ??= '';
    $vehicleid ??= '';
@endphp

@section('content')

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

            </div>
        </div>
    </div>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Vehicle Revenue</span> - Report
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="POST"
                action="{{ url('admin/report/revenue_reports/index') }}" class="form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-md-2">
                        <input type="text" name="Search[datefrom]" id="SearchDatefrom" class="date form-control"
                            value="{{ $datefrom }}" placeholder="Date from">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[dateto]" id="SearchDateto" class="date form-control"
                            value="{{ $dateto }}" placeholder="Date to">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[dealerid]" id="SearchDealerid" style="width:100%;"
                            value="{{ $dealerid }}" placeholder="Dealers">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[vehicleid]" id="SearchVehicleid" style="width:100%;"
                            value="{{ $vehicleid }}" placeholder="Vehicle">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="refresh" value="refresh" class="btn btn-warning" alt="Refresh Report">
                            Refresh Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
            @include('admin.report.revenue_reports.elements._revenue_report')
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>

    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {
            jQuery("#SearchVehicleid").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Customer ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/bookings/getVehicle') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params, dealer_id: jQuery("#SearchDealerid").val() };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return { tag: item.tag, id: item.id };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var vehicleid = "{{ $vehicleid }}";
                    if (vehicleid.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/getVehicle') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": vehicleid }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            jQuery("#SearchDealerid").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Customer ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/bookings/customerautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params, "is_dealer": true };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return { tag: item.tag, id: item.id };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var dealer_id = "{{ $dealerid }}";
                    if (dealer_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/customerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": dealer_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            if (typeof $.fn.datetimepicker !== 'undefined') {
                $('#SearchDatefrom').datetimepicker({ format: 'MM/YYYY' });
                $('#SearchDateto').datetimepicker({
                    useCurrent: false,
                    format: 'MM/YYYY'
                });
            }

            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:;') {
                    loadListing(url);
                }
            });

            $(document).on('change', '.ajax-limit', function (e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var url = window.location.pathname + '?' + form.serialize();
                loadListing(url);
            });

            function loadListing(url, historyUrl) {
                if (typeof historyUrl === 'undefined') {
                    historyUrl = url;
                }
                $('#postsPaging').css('opacity', '0.5');

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $('#postsPaging').html(data);
                        $('#postsPaging').css('opacity', '1');
                        window.history.pushState(null, null, historyUrl);
                    },
                    error: function (xhr) {
                        $('#postsPaging').css('opacity', '1');
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