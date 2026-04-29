@extends('admin.layouts.app')

@php
    $mode ??= 'index';
    $limit ??= 50;
    $isAll = $mode === 'all';
@endphp

@section('title', $isAll ? 'All Pending Booking' : 'Pending Booking')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $isAll ? 'All Pending' : 'Pending' }}</span> Booking
                </h4>
            </div>
            <div class="heading-elements">
                @if ($isAll)
                    <a href="{{ url('admin/vehicle_reservations/index') }}" class="btn left-margin btn-cancel">Show pending only</a>
                @else
                    <a href="{{ url('admin/vehicle_reservations/all') }}" class="btn left-margin btn-cancel">All Pending Booking</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ $isAll ? url('admin/vehicle_reservations/all') : url('admin/vehicle_reservations/index') }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            Rows / page :
                            <select name="Record[limit]" class="form-control ajax-limit">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
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
                @include('admin.vehicle_reservations._table', ['bookings' => $bookings ?? collect(), 'mode' => $mode])
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
