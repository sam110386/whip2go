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
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50" size="30" placeholder="Keyword...">
                        </div>
                        <div class="col-md-2">
                            Search By :
                            <select name="Search[searchin]" class="form-control">
                                <option value="">Search By</option>
                                @foreach ($options as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $fieldname === (string) $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            Status :
                            <select name="Search[show]" class="form-control">
                                <option value="">Status..</option>
                                <option value="1" @selected((string) $show === '1')>Approved</option>
                                <option value="0" @selected((string) $show === '0')>New</option>
                                <option value="2" @selected((string) $show === '2')>Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            Driver :
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="form-control" style="width:100%;" value="{{ $user_id }}" placeholder="Select Driver">
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="APPLY">APPLY</button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning" alt="Clear Filter">Clear Filter</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                <div class="table-responsive">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['field' => 'id', 'title' => 'ID'],
                                    ['field' => 'vehicle_name', 'title' => 'Vehicle'],
                                    ['field' => 'owner_first_name', 'title' => 'Dealer'],
                                    ['field' => 'renter_first_name', 'title' => 'Renter'],
                                    ['field' => 'offer_price', 'title' => 'Price'],
                                    ['field' => 'status', 'title' => 'Status'],
                                    ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                                ]])
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($offers as $o)
                                <tr>
                                    <td>{{ $o->id }}</td>
                                    <td>{{ $o->vehicle_unique_id }} - {{ $o->vehicle_name }}</td>
                                    <td>{{ trim(($o->owner_first_name ?? '') . ' ' . ($o->owner_last_name ?? '')) }}</td>
                                    <td>{{ trim(($o->renter_first_name ?? '') . ' ' . ($o->renter_last_name ?? '')) }}</td>
                                    <td>{{ number_format((float) ($o->offer_price ?? 0), 2) }}</td>
                                    <td>{{ $o->status }}</td>
                                    <td>
                                        <a href="{{ $basePath }}/view/{{ base64_encode((string) $o->id) }}" title="View"><i class="icon-clipboard3"></i></a>
                                        <a href="{{ $basePath }}/add/{{ base64_encode((string) $o->id) }}" title="Edit"><i class="icon-pencil"></i></a>
                                        <a href="{{ $basePath }}/duplicate/{{ base64_encode((string) $o->id) }}" title="Duplicate"><i class="icon-copy3"></i></a>
                                        <a href="{{ $basePath }}/cancel/{{ base64_encode((string) $o->id) }}" title="Cancel"><i class="icon-cross2"></i></a>
                                        <a href="{{ $basePath }}/delete/{{ base64_encode((string) $o->id) }}" onclick="return confirm('Delete this offer?')" title="Delete"><i class="icon-trash"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" align="center">No offers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.dispacher.paging_box', ['paginator' => $offers, 'limit' => $limit])
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
        });
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
