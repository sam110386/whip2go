@extends('admin.layouts.app')

@section('title', 'Manage Accounting Reports')

@php
    $keyword ??= '';
    $dateFrom ??= '';
    $dateTo ??= '';
    $rtype ??= '';
    $type ??= '';
    $userid ??= '';
    $useridB64 ??= '';
    $timezone ??= config('app.timezone');
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Accounting</span> - Reports
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/accounting_reports/index', $useridB64) }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                        </div>
                        <div class="col-md-2">
                            Date From :
                            <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                        </div>
                        <div class="col-md-2">
                            Date To :
                            <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                        </div>
                        <div class="col-md-2">
                            Payment :
                            <select name="Search[rtype]" class="form-control">
                                <option value="">Payment</option>
                                <option value="D" @selected($rtype == 'D')>Debit</option>
                                <option value="C" @selected($rtype == 'C')>Credit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            Type :
                            <select name="Search[type]" class="form-control">
                                <option value="">Type</option>
                                @foreach($reportlib->getPaymentType(true) as $k => $v)
                                    <option value="{{ $k }}" @selected($type == $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="Next">APPLY</button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning" alt="Clear Filter">Clear Filter</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.accounting.elements.index', [
                    'reportlists' => $reportlists,
                    'reportlib' => $reportlib,
                    'timezone' => $timezone,
                    'limit' => $limit,
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
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
        });

        function bookingDetail(orderid) {
            $.ajax({
                url: "{{ url('admin/accounting_reports/booking') }}",
                data: { orderid: orderid },
                success: function (data) {
                    $('#myModal .modal-content').html(data);
                    $('#myModal').modal('show');
                }
            });
        }

        function payoutDetail(payoutid) {
            $.ajax({
                url: "{{ url('admin/accounting_reports/payout') }}",
                data: { payoutid: payoutid },
                success: function (data) {
                    $('#myModal .modal-content').html(data);
                    $('#myModal').modal('show');
                }
            });
        }

        function transactionDetail(transaction) {
            $.ajax({
                url: "{{ url('admin/accounting_reports/transaction') }}",
                data: { transaction: transaction },
                success: function (data) {
                    $('#myModal .modal-content').html(data);
                    $('#myModal').modal('show');
                }
            });
        }

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
