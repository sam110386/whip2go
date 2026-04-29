@extends('admin.layouts.app')

@section('title', 'Payouts Transactions')

@php
    $user_id ??= '';
    $payout_id ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $limit ??= 50;
    $listtype ??= '';
    $batchMode ??= empty($listtype);
    $paymentTypeValue ??= null;
    $payoutlists ??= null;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Payouts</span>
                    Transactions
                </h4>
            </div>
            <div class="heading-elements">
                @if ($batchMode)
                    <a href="{{ request()->fullUrlWithQuery(['listtype' => 'all', 'page' => null]) }}" class="btn btn-success">Show All</a>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['listtype' => null, 'page' => null]) }}" class="btn btn-success">Show Batches</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/payouts/index') }}">
                <input type="hidden" name="listtype" value="{{ $listtype }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            Dealer :
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="form-control" style="width:100%;" value="{{ $user_id }}" placeholder="Select Dealer">
                        </div>
                        <div class="col-md-2">
                            Payout # :
                            <input type="text" name="Search[payout_id]" class="form-control" value="{{ $payout_id }}" placeholder="Payout #">
                        </div>
                        <div class="col-md-2">
                            Date From :
                            <input type="text" id="SearchDateFrom" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                        </div>
                        <div class="col-md-2">
                            Date To :
                            <input type="text" id="SearchDateTo" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="search" class="btn btn-primary" alt="Next">APPLY</button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="EXPORT" class="btn btn-primary pull-right" alt="Export">EXPORT</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.payouts.listing', [
                    'payoutlists' => $payoutlists,
                    'batchMode' => $batchMode,
                    'paymentTypeValue' => $paymentTypeValue,
                ])
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    <div id="plaidModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
        tbody tr { cursor: pointer; }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        function getTransactions(payoutid) {
            if (typeof jQuery.blockUI === 'function') {
                jQuery.blockUI({ message: '<h1>Just a moment...</h1>' });
            }
            jQuery.post("{{ url('admin/payouts/transactions') }}", { payoutid: payoutid }, function (data) {
                $("#plaidModal .modal-content").html(data);
                $("#plaidModal").modal('show').find('.modal-dialog').css('width', '800px');
            }).done(function () {
                if (typeof jQuery.unblockUI === 'function') {
                    jQuery.unblockUI();
                }
            });
            return false;
        }

        jQuery(document).ready(function () {
            jQuery("#SearchUserId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Dealer",
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
                            if (data && data[0]) { callback(data[0]); }
                        });
                    }
                }
            });

            if (jQuery.fn.datepicker) {
                jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
                jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
            }

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
