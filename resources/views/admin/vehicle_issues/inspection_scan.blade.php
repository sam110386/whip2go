@extends('layouts.admin')
@section('content')
@php $d = $issueData['CsVehicleIssue'] ?? []; @endphp
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate({ ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)'] });
        jQuery("#CsVehicleIssueVehicleId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Vehicle ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"admin/vehicle_issues/getVehicle", dataType: "json", type: "GET", data: function(params) { return {term: params}; }, processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id, user_id: item.user_id}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"admin/vehicle_issues/getVehicle", {dataType: "json", type:'POST', data:{id:id}}).done(function(data) { callback(data[0]); }); } }
        });
        jQuery("#CsVehicleIssueCsOrderId").on('select2-selecting', function(e) { jQuery("#CsVehicleIssueVehicleId").val(e.choice.vehicle); });
        jQuery("#CsVehicleIssueCsOrderId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Booking ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"admin/bookings/autocomplete", dataType: "json", type: "POST", data: function(params) { return {term: params, _token: '{{ csrf_token() }}'}; }, processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id, vehicle: item.vehicle}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"admin/bookings/autocomplete", {dataType: "json", type:'POST', data:{id:id, _token: '{{ csrf_token() }}'}}).done(function(data) { callback(data[0]); }); } }
        });
    });
</script>
<div class="page-header"><div class="page-header-content"><div class="page-title"><h4><i class="icon-arrow-left52 position-left"></i> {{ $listTitle }}</h4></div></div></div>
<div class="row">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif</div>
<div class="panel">
    <form action="{{ url('/admin/vehicle_issues/inspectionScan') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="form-group"><label class="col-lg-2 control-label">Booking # :<font class="requiredField">*</font></label><div class="col-lg-5"><input type="text" name="CsVehicleIssue[cs_order_id]" id="CsVehicleIssueCsOrderId" class="required textfield" value="{{ $d['cs_order_id'] ?? '' }}" placeholder="Select Booking.." style="width:100%;"></div></div>
            <div class="form-group"><label class="col-lg-2 control-label"></label><div class="col-lg-2"><hr></div><div class="col-lg-1 control-label">OR</div><div class="col-lg-2"><hr></div></div>
            <div class="form-group"><label class="col-lg-2 control-label">Vehicle# :<font class="requiredField">*</font></label><div class="col-lg-5"><input type="text" name="CsVehicleIssue[vehicle_id]" id="CsVehicleIssueVehicleId" class="vehicle_id required textfield" value="{{ $d['vehicle_id'] ?? '' }}" placeholder="Select Vehicle.." style="width:100%;"></div></div>
            <div class="form-group"><label class="col-lg-2 control-label"> Status:</label><div class="col-lg-5"><select name="CsVehicleIssue[status]" class="textfield form-control">@foreach($issueStatus as $k => $v)<option value="{{ $k }}" {{ ($d['status'] ?? '0') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
            <div class="form-group"><label class="col-lg-2 control-label"> Inspection Token:</label><div class="col-lg-5"><input type="text" name="CsVehicleIssue[token]" class="form-control" readonly value="{{ $d['extra']['token'] ?? '' }}"></div><div class="col-lg-5">{{ !empty($d['extra']) ? ($d['extra']['webview_url'] ?? '') : '' }}</div></div>
            <div class="form-group"><label class="col-lg-2 control-label">&nbsp;</label><div class="col-lg-6"><button type="submit" class="btn btn-primary">{{ !empty($d['id']) ? 'Update' : 'Save' }}</button> <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button></div></div>
        </div>
        <input type="hidden" name="CsVehicleIssue[id]" value="{{ $d['id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[user_id]" value="{{ $d['user_id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[type]" value="7">
    </form>
</div>
@endsection
