@extends('admin.layouts.app')

@section('title', 'Manage Vehicles')

@php
    $searchin ??= '';
    $keyword ??= '';
    $show ??= null;
    $showArr ??= [];
    $userId ??= '';
    $type ??= '';
    $visibility ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> Vehicles
                </h4>
            </div>
            <div class="heading-elements">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        Add New <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="{{ url('admin/vehicles/add') }}">Simple</a></li>
                        <li><a href="{{ url('admin/featured_vehicles/add') }}">Featured</a></li>
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
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/vehicles/index') }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50" placeholder="Keyword...">
                        </div>
                        <div class="col-md-2">
                            Search By :
                            <select name="Search[searchin]" class="form-control">
                                <option value="" @selected($searchin === '')>All (name + VIN)</option>
                                <option value="vehicle_name" @selected($searchin === 'vehicle_name')>Car #</option>
                                <option value="vin_no" @selected($searchin === 'vin_no')>VIN #</option>
                                <option value="plate_number" @selected($searchin === 'plate_number')>Plate Number</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            Owner :
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="form-control" style="width:100%;" value="{{ $userId }}" placeholder="Select Owner">
                        </div>
                    </div>
                </div>
                <div class="row pb-10">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            Status :
                            <select name="Search[show]" class="form-control">
                                <option value="">Status..</option>
                                @foreach ($showArr as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $show === (string) $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            Type :
                            <select name="Search[type]" class="form-control">
                                <option value="" @selected($type === '')>Select Type</option>
                                <option value="regular" @selected($type === 'regular')>Simple</option>
                                <option value="featured" @selected($type === 'featured')>Featured</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            Visibility :
                            <select name="Search[visibility]" class="form-control">
                                <option value="" @selected($visibility === '')>Select visibility</option>
                                <option value="1" @selected($visibility === '1')>Visible</option>
                                <option value="0" @selected($visibility === '0')>Not Visible Individually</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="Search">Search</button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning" alt="Clear Filter">Clear Filter</button>
                        </div>
                        <div class="col-md-1 pull-right">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" name="export" value="Export" class="btn btn-primary btn-lg" alt="Export">Export</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.vehicles._index_table', [
                    'vehicleDetails' => $vehicleDetails,
                    'listContext' => 'admin',
                ])
            </div>
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
    <script src="{{ legacy_asset('js/selectAllCheckbox.js') }}"></script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
    <script src="{{ asset('js/admin_setting.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        jQuery(document).ready(function () {
            jQuery("#SearchUserId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Owner",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/vehicles/ownerautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params };
                    },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return { tag: item.tag, id: item.id };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var userId = "{{ $userId }}";
                    if (userId.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/vehicles/ownerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "user_id": userId },
                            success: function (res) { callback(res); }
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
        });
    </script>
@endpush
