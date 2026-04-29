@extends('admin.layouts.app')

@section('title', 'Manage Driver Credit Logs')

@php
    $keyword ??= '';
    $dateFrom ??= '';
    $dateTo ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Driver</span> - Credit Logs
                </h4>
            </div>
            <div class="heading-elements">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        Add New <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="{{ url('admin/driver_credit/records/credit') }}">Direct To Driver</a></li>
                        <li class="divider"></li>
                        <li><a href="javascript:;" onclick="openBooking()">To Booking</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/driver_credit/records/index') }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                        </div>
                        <div class="col-md-3">
                            Date From :
                            <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                        </div>
                        <div class="col-md-3">
                            Date To :
                            <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary">APPLY</button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning">Clear Filter</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.driver_credit._admin_index')
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <legend class="text-semibold">Choose Booking#</legend>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Booking #:</label>
                            <div class="col-lg-8">
                                <input type="text" id="TextBookingid" name="Text[bookingid]" class="form-control" placeholder="Choose Booking #" style="width:100%;">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <style type="text/css">
        .table > thead > tr > th,
        .table > tbody > tr > th,
        .table > tfoot > tr > th,
        .table > thead > tr > td,
        .table > tbody > tr > td,
        .table > tfoot > tr > td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        function openBooking() {
            jQuery("#myModal").modal('show');
        }

        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });

            jQuery("#TextBookingid").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Driver ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/driver_credit/records/bookingautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params };
                    },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return { tag: item.tag, id: item.id, encode: item.encode };
                            })
                        };
                    }
                }
            });

            jQuery("#TextBookingid").on('select2-selecting', function (e) {
                if (confirm("Are you sure?")) {
                    goBack('/admin/driver_credit/records/creditdriver/' + e.choice.encode);
                }
            });
        });

        $(document).ready(function () {
            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:;') {
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
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
