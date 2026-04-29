@extends('admin.layouts.app')

@section('title', 'Manage Payment Logs')

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
                    <span class="text-semibold">Manage</span> Payment Logs
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/payment_logs/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Date From :
                            <input type="date" name="Search[date_from]" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-3">
                            Date To :
                            <input type="date" name="Search[date_to]" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50">
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="Search">APPLY</button>
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
                <div class="table-responsive">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['field' => 'id', 'title' => 'ID'],
                                    ['field' => 'user_id', 'title' => 'User'],
                                    ['field' => 'transaction_id', 'title' => 'Txn ID'],
                                    ['field' => 'reference_id', 'title' => 'Reference'],
                                    ['field' => 'message', 'title' => 'Message'],
                                    ['field' => 'created', 'title' => 'Created'],
                                ]])
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($rows ?? []) as $r)
                                <tr>
                                    <td>{{ $r->id }}</td>
                                    <td>{{ $r->user_id ?? '' }}</td>
                                    <td>{{ $r->transaction_id ?? '' }}</td>
                                    <td>{{ $r->reference_id ?? '' }}</td>
                                    <td>{{ $r->message ?? '' }}</td>
                                    <td>{{ $r->created ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" align="center">No logs found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.dispacher.paging_box', ['paginator' => $rows ?? null, 'limit' => $limit])
            </div>

        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
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
