@extends('admin.layouts.app')

@section('title', 'Manage Reports')

@php
    $keyword ??= '';
    $dealerid ??= '';
    $renterid ??= '';
    $fieldname ??= '';
    $status_type ??= '';
    $dateFrom ??= '';
    $dateTo ??= '';
    $status ??= '';
    $limit ??= 50;
    $search_in ??= [
        1 => 'Pickup Address',
        2 => 'Vehicle#',
        3 => 'Order#',
    ];
    $status_opt ??= [
        'complete' => 'Complete',
        'cancel' => 'Cancel',
        'incomplete' => 'InComplete',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Reports</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal" method="GET"
                action="{{ url('admin/reports/index') }}">
                <fieldset class="content-group">
                    <div class="col-md-2">
                        <input type="text" id="SearchDealerId" name="Search[dealer_id]" class="" style="width:100%;"
                            value="{{ $dealerid }}" placeholder="Dealers">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50"
                            placeholder="Keyword">
                    </div>
                    <div class="col-md-2">
                        <select name="Search[searchin]" class="form-control">
                            <option value="">Search By</option>
                            @foreach ($search_in as $k => $label)
                                <option value="{{ $k }}" @selected((string) $fieldname === (string) $k)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="Search[status_type]" class="form-control">
                            <option value="">Status</option>
                            @foreach ($status_opt as $k => $label)
                                <option value="{{ $k }}" @selected((string) $status_type === (string) $k)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_from]" class="form-control" value="{{ $dateFrom }}"
                            placeholder="Date Range From" id="SearchDateFrom">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_to]" class="form-control" value="{{ $dateTo }}"
                            placeholder="Date Range To" id="SearchDateTo">
                    </div>
                </fieldset>
                <fieldset class="content-group">
                    <div class="col-md-2">
                        <input type="text" id="SearchRenterId" name="Search[renter_id]" class="" style="width:100%;"
                            value="{{ $renterid }}" placeholder="Customer..">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" value="SEARCH" name="search" class="btn btn-primary" alt="SEARCH">
                            SEARCH
                        </button>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning"
                            alt="Clear Filter">
                            Clear Filter
                        </button>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="search" value="EXPORT" class="btn btn-primary" alt="EXPORT">
                            <i class="icon-file-excel"></i>
                            EXPORT
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.reports.elements.index', ['reportlists' => $reportlists ?? []])
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <style type="text/css">
        tbody tr {
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker && jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker && jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });

            jQuery("#SearchRenterId").select2({
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
                        return { term: params };
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
                    var renter_id = "{{ $renterid }}";
                    if (renter_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/customerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": renter_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            jQuery("#SearchDealerId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Dealer ",
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

            $(document).on('click', '#selectAllChildCheckboxs', function () {
                $('.select-item').prop('checked', this.checked);
            });
        });
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush