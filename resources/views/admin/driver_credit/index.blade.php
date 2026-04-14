@extends('layouts.admin')
@section('title', 'Credits Logs')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
        jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
    });
    function openBooking(){
        jQuery("#myModal").modal('show');
    }
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Driver</span> - Credit Logs</h4>
        </div>
        <div class="heading-elements">
            <div class="btn-group ">
                <button type="button" class="btn btn-primary bg-brown dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Add New <i class="icon-menu7 position-right"></i> <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="{{ url('admin/driver_credit/records/credit') }}">Direct To Driver</a></li>
                    <li class="divider"></li>
                    <li><a href="javascript:;" onclick="openBooking()">To Booking</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('admin/driver_credit/records/index') }}" class="form-horizontal" id="frmSearchadmin">
            @csrf
            <div class="row pb-10">
                <div class="col-md-12">
                    <div class="col-md-3">
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                    </div>
                    <div class="col-md-3">
                        <input type="submit" value="APPLY" class="btn btn-primary">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.driver_credit._admin_index')
    </div>
</div>
<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <legend class="text-semibold">Choose Booking#</legend>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Booking #:</label>
                        <div class="col-lg-8">
                            <input type="text" id="TextBookingid" name="Text[bookingid]" placeholder="Choose Booking #" style="width:100%;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function () {
        jQuery("#TextBookingid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format, formatResult: format,
            placeholder: "Select Driver ", minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/driver_credit/records/bookingautocomplete",
                dataType: "json", type: "GET",
                data: function (params) { return {term: params} },
                processResults: function (data) {
                    return { results: jQuery.map(data, function (item) { return {tag: item.tag, id: item.id, encode: item.encode} }) };
                }
            }
        });
        jQuery("#TextBookingid").on('select2-selecting', function (e) {
            if(confirm("Are you sure?")){
                goBack('/admin/driver_credit/records/creditdriver/'+e.choice.encode);
            }
        });
    });
</script>
@endsection
