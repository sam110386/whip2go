@extends('admin.layouts.app')

@section('title', 'Manage Vehicle Offers')

@php
    $keyword ??= '';
    $fieldname ??= '';
    $show ??= '';
    $user_id ??= '';
    $basePath ??= url('admin/vehicle_offers');
    $limit ??= 50;
    $options ??= [];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Search</span> - Offers
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/add" class="btn btn-success left-margin">Add New</a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ $basePath }}/index">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}"
                                maxlength="50" size="30" placeholder="Keyword...">
                        </div>
                        <div class="col-md-2">
                            <select name="Search[searchin]" class="form-control">
                                <option value="">Search By</option>
                                @foreach ($options as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $fieldname === (string) $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="Search[show]" class="form-control">
                                <option value="">Status..</option>
                                <option value="1" @selected((string) $show === '1')>Approved</option>
                                <option value="0" @selected((string) $show === '0')>New</option>
                                <option value="2" @selected((string) $show === '2')>Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="" style="width:100%;"
                                value="{{ $user_id }}" placeholder="Select Driver">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" value="search" class="btn btn-primary" alt="APPLY">APPLY</button>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning"
                                alt="Clear Filter">Clear Filter</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.vehicle_offers.elements.index')
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
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        jQuery(document).ready(function () {
            jQuery("#SearchUserId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Driver ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/vehicle_offers/userautocomplete') }}",
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
                    var user_id = "{{ $user_id }}";
                    if (user_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/vehicle_offers/userautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "user_id": user_id },
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

            $(document).on('click', '#selectAllChildCheckboxs', function () {
                $('.select-item').prop('checked', this.checked);
            });
        });
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush