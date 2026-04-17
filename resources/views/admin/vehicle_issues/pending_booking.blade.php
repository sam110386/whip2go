@extends('admin.layouts.app')
@section('content')
@php $d = $issueData['CsVehicleIssue'] ?? []; @endphp
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate({ ignore: [':hidden:not(.vehicle_id)'] });
        jQuery("#CsVehicleIssueVehicleId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Vehicle ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"admin/vehicle_issues/getVehicle", dataType: "json", type: "GET", data: function(params) { return {term: params}; }, processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id, user_id: item.user_id}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"admin/vehicle_issues/getVehicle", {dataType: "json", type:'POST', data:{id:id}}).done(function(data) { callback(data[0]); }); } }
        });
    });
</script>
<div class="page-header"><div class="page-header-content"><div class="page-title"><h4><i class="icon-arrow-left52 position-left"></i> {{ $listTitle }}</h4></div></div></div>
<div class="row">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif</div>
<div class="panel">
    <form action="{{ url('/admin/vehicle_issues/pendingBooking/' . $id) }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
        @csrf
        @method('PUT')
        <div class="panel-body">
            <div class="form-group"><label class="col-lg-2 control-label">Vehicle# :<font class="requiredField">*</font></label><div class="col-lg-4"><input type="text" name="CsVehicleIssue[vehicle_id]" id="CsVehicleIssueVehicleId" class="vehicle_id required textfield" value="{{ $d['vehicle_id'] ?? '' }}" placeholder="Select Vehicle.." style="width:100%;" readonly></div></div>
            <div class="form-group"><label class="col-lg-2 control-label"> Status:</label><div class="col-lg-4"><select name="CsVehicleIssue[status]" class="textfield form-control">@foreach($issueStatus as $k => $v)<option value="{{ $k }}" {{ ($d['status'] ?? '0') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
            <div class="form-group"><label class="col-lg-2 control-label"> Missing Checklist:</label><div class="col-lg-4 control-label text-bold">{!! $checklist !!}</div></div>
            <div class="form-group"><label class="col-lg-2 control-label"> Note: <font class="requiredField">*</font></label><div class="col-lg-4"><textarea name="CsVehicleIssue[notes]" class="textfield required form-control">{{ $notes }}</textarea><em> Details if any</em></div></div>
            <div class="form-group"><label class="col-lg-2 control-label">&nbsp;</label><div class="col-lg-6"><button type="submit" class="btn btn-primary">{{ !empty($d['id']) ? 'Update' : 'Save' }}</button> <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button></div></div>
        </div>
        <input type="hidden" name="CsVehicleIssue[id]" value="{{ $d['id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[user_id]" value="{{ $d['user_id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[type]" value="8">
    </form>
</div>
@endsection
