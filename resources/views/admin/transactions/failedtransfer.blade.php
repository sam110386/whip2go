@extends('admin.layouts.app')

@php
    $title ??= 'Failed Transfer';
    $date_from ??= '';
    $date_to ??= '';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Failed</span> - Transfer
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="get" action="/admin/transactions/failedtransfer" id="frmSearchadmin" name="frmSearchadmin">
                <div class="row">
                    <div class="col-md-3">
                        <input type="input" id="SearchDateFrom" name="Search[date_from]" class="form-control"
                            value="{{ $date_from }}" placeholder="Date Range From">
                    </div>
                    <div class="col-md-3">
                        <input type="input" id="SearchDateTo" name="Search[date_to]" class="form-control"
                            value="{{ $date_to}}" placeholder="Date Range To">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.transactions.elements.failedtransfer')
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({
                dateFormat: 'mm/dd/yy'
            });
            jQuery('#SearchDateTo').datepicker({
                dateFormat: 'mm/dd/yy'
            });

        });
    </script>
@endpush