@extends('admin.layouts.app')

@section('title', 'Manage Transactions')

@php
    $keyword ??= '';
    $transaction_id ??= '';
    $fieldname ??= '';
    $status_type ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> - Transactions
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" id="frmSearchadmin" name="frmSearchadmin" method="POST"
                action="{{ url('admin/transactions/index') }}">
                @csrf
                <div class="row pb-10">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}"
                                placeholder="Keyword">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="Search[transaction_id]" class="form-control" maxlength="80"
                                value="{{ $transaction_id }}" placeholder="Transaction Id">
                        </div>
                        <div class="col-md-3">
                            <select name="Search[searchin]" class="form-control">
                                <option value="">Select In</option>
                                <option value="2" @selected($fieldname === '2')>Vehicle#</option>
                                <option value="3" @selected($fieldname === '3')>Order#</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row pb-10">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <select name="Search[status_type]" class="form-control">
                                <option value="">Select Type</option>
                                <option value="complete" @selected($status_type === 'complete')>Complete</option>
                                <option value="cancel" @selected($status_type === 'cancel')>Cancel</option>
                                <option value="incomplete" @selected($status_type === 'incomplete')>Incomplete</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="Search[date_from]" class="form-control" id="SearchDateFrom" value="{{ $date_from }}"
                                placeholder="Date Range From">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="Search[date_to]" class="form-control" id="SearchDateTo" value="{{ $date_to }}"
                                placeholder="Date Range To">
                        </div>
                        <div class="col-md-1">
                        <label style="margin-bottom:0;">&nbsp;</label>
                        <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                    </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin/transactions/elements/index')
        </div>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-body">
                    
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
    <style type="text/css">
        tbody tr{cursor: pointer;}
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">

        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({
                dateFormat: 'mm/dd/yy'
            });
            jQuery('#SearchDateTo').datepicker({
                dateFormat: 'mm/dd/yy'
            });
        });
        
        function openTripDetails(tripId, thisObj) {
            jQuery.blockUI({
                message: '<h1>Just a moment...</h1>'
            });
            jQuery.post("/admin/reports/details/" + tripId, {}, function (data) {
                jQuery.unblockUI();
                $("#myModal .modal-content").html(data);
                $("#myModal").modal('show').find('.modal-dialog').css('width', '1050px');
            });
            return false;
        }

        function Updateenddatetime(tripId) {
            jQuery.blockUI({
                message: '<h1>loading...</h1>'
            });
            $.post("/admin/transactions/updateenddatetime", { booking_id: tripId }, function (data) {
                jQuery.unblockUI();
                $("#myModal .modal-content").html(data);
                $("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
            });
        }
    </script>
@endpush