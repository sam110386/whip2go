@extends('admin.layouts.app')

@section('title', 'Report - Cash Flow')

@php
    $user_id ??= '';
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Report</span> - Cash Flow
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/report/cashflow') }}">
                <div class="row pb-10">
                    <div class="col-md-12">
                        <div class="col-md-4">
                            Dealer :
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="form-control" style="width:100%;" value="{{ $user_id }}" placeholder="Select Dealer..">
                        </div>
                        <div class="col-md-2">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" name="search" value="search" class="btn btn-primary" alt="APPLY">APPLY</button>
                        </div>
                        <div class="col-md-2">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning" alt="Clear Filter">Clear Filter</button>
                        </div>
                        <div class="col-md-2">
                            <label style="margin-bottom:0;">&nbsp;</label>
                            <a href="#" download="cashflow.csv" class="btn btn-primary" onclick="return ExcellentExport.csv(this, 'portfolio');">Export</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @if (!empty($vehicles))
                    @include('admin.report.elements._cashflow')
                    <style type="text/css">
                        .table.panel { border-color: unset; border: none; }
                    </style>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td colspan="7" class="text-center">No record found</td>
                            </tr>
                        </table>
                    </div>
                @endif
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
    <script src="{{ asset('js/report/papaparser.js') }}"></script>
    <script src="{{ asset('js/report/excellentexport.js') }}"></script>
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("body").addClass('sidebar-xs');
        });

        function format(item) { return item.tag; }

        jQuery(document).ready(function () {
            jQuery("#SearchUserId").select2({
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
                        return { term: params, is_dealer: true };
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
                    var id = $(element).val();
                    if (id !== "") {
                        $.ajax("{{ url('admin/bookings/customerautocomplete') }}", {
                            dataType: "json",
                            type: 'GET',
                            data: { id: id }
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
        });
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
