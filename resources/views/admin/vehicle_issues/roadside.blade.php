@extends('layouts.admin')
@section('content')
@php $d = $issueData['CsVehicleIssue'] ?? []; $images = $issueData['CsVehicleIssueImage'] ?? []; @endphp
<style>.kv-file-upload { display: none; }</style>
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate({ ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)'] });
        jQuery("#CsVehicleIssueVehicleId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Vehicle ", minimumInputLength: 1,
            ajax: {
                url: SITE_URL+"admin/vehicle_issues/getVehicle", dataType: "json", type: "GET",
                data: function(params) { return {term: params}; },
                processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id, user_id: item.user_id}; })}; }
            },
            initSelection: function(element, callback) {
                var id = $(element).val();
                if (id !== "") { $.ajax(SITE_URL+"admin/vehicle_issues/getVehicle", {dataType: "json", type:'POST', data:{id:id}}).done(function(data) { callback(data[0]); }); }
            }
        });
        jQuery("#CsVehicleIssueVehicleId").on('select2-selecting', function(e) { jQuery("#CsVehicleIssueUserId").val(e.choice.user_id); });
        jQuery("#CsVehicleIssueRenterId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Driver ", minimumInputLength: 1,
            ajax: {
                url: SITE_URL+"admin/bookings/customerautocomplete", dataType: "json", type: "GET",
                data: function(params) { return {term: params}; },
                processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id}; })}; }
            },
            initSelection: function(element, callback) {
                var id = $(element).val();
                if (id !== "") { $.ajax(SITE_URL+"admin/bookings/customerautocomplete", {dataType: "json", type:'GET', data:{id:id}}).done(function(data) { callback(data[0]); }); }
            }
        });
    });
</script>
<div class="page-header"><div class="page-header-content"><div class="page-title"><h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}</h4></div></div></div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <form method="POST" name="frmadmin" id="frmadmin" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        <div class="panel-body">
            <div class="form-group">
                <label class="col-lg-2 control-label">Vehicle# :<font class="requiredField">*</font></label>
                <div class="col-lg-4">
                    <input type="text" name="CsVehicleIssue[vehicle_id]" id="CsVehicleIssueVehicleId" class="vehicle_id required textfield" value="{{ $d['vehicle_id'] ?? '' }}" placeholder="Select Vehicle.." style="width:100%;">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Driver :<font class="requiredField">*</font></label>
                <div class="col-lg-4">
                    <input type="text" name="CsVehicleIssue[renter_id]" id="CsVehicleIssueRenterId" class="renter_id required textfield" value="{{ $d['renter_id'] ?? '' }}" placeholder="Select Driver.." style="width:100%;">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"> Status:</label>
                <div class="col-lg-4">
                    <select name="CsVehicleIssue[status]" id="CsVehicleIssueStatus" class="textfield form-control">
                        @foreach($issueStatus as $k => $v)
                            <option value="{{ $k }}" {{ ($d['status'] ?? '0') == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"> Roadside Request Details: <font class="requiredField">*</font></label>
                <div class="col-lg-4">
                    <textarea name="CsVehicleIssue[roadside_request_detail]" id="CsVehicleIssueRoadsideRequestDetail" class="textfield required form-control">{{ $d['roadside_request_detail'] ?? '' }}</textarea>
                    <em> Roadside request details if any</em>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label"> Images</label>
                <div class="col-lg-8">
                    <input type="file" class="fileinputajax" multiple="multiple" name="vehicleimage" data-show-preview="true" data-show-upload="false">
                    <span class="help-block">You can select multiple images.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="button" class="btn btn-primary" onclick="saveForm()">{{ !empty($d['id']) ? 'Update' : 'Save' }}</button>
                    <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button>
                </div>
            </div>
        </div>
        <input type="hidden" name="CsVehicleIssue[id]" id="CsVehicleIssueId" value="{{ $d['id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[user_id]" id="CsVehicleIssueUserId" value="{{ $d['user_id'] ?? '' }}">
        <input type="hidden" name="CsVehicleIssue[type]" value="2">
    </form>
</div>
@php
$initialPreview = []; $initialPreviewConfig = [];
foreach ($images as $img) {
    $preview = ['caption' => $img['image'], 'filename' => $img['image'], 'key' => $img['id'], 'width' => '120px', 'downloadUrl' => config('app.url').'/img/custom/vehicle_issue/'.$img['image']];
    $ext = strtolower(pathinfo($img['image'], PATHINFO_EXTENSION));
    if ($ext == 'pdf') $preview['type'] = 'pdf';
    if (in_array($ext, ['doc','docx'])) $preview['type'] = 'gdocs';
    $initialPreview[] = config('app.url').'/img/custom/vehicle_issue/'.$img['image'];
    $initialPreviewConfig[] = $preview;
}
@endphp
<script type="text/javascript">
$(function() {
    $(".fileinputajax").fileinput({
        showUpload: false, uploadUrl: SITE_URL + "admin/vehicle_issues/saveImage", uploadAsync: true, maxFileCount: 15,
        deleteUrl: SITE_URL + "admin/vehicle_issues/deleteImage",
        allowedFileExtensions: ['jpeg', 'jpg', 'png', 'doc', 'docx', 'pdf'],
        initialPreview: {!! json_encode($initialPreview) !!},
        overwriteInitial: false, initialPreviewAsData: true, initialPreviewFileType: 'image',
        initialPreviewConfig: {!! json_encode($initialPreviewConfig) !!}, maxFileSize: 1024,
        uploadExtraData: function() { return {id: $("#CsVehicleIssueId").val(), _token: '{{ csrf_token() }}'}; },
        fileActionSettings: { showDrag: false, showZoom: true, showUpload: false, removeIcon: '<i class="icon-bin"></i>', removeClass: 'btn btn-link btn-xs btn-icon', uploadIcon: '<i class="icon-upload"></i>', uploadClass: 'btn btn-link btn-xs btn-icon', indicatorNew: '<i class="icon-file-plus text-slate"></i>', indicatorSuccess: '<i class="icon-checkmark3 file-icon-large text-success"></i>', indicatorError: '<i class="icon-cross2 text-danger"></i>', indicatorLoading: '<i class="icon-spinner2 spinner text-muted"></i>' }
    }).on('fileuploaded', function(event, data, previewId, index) { $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key); })
      .on('filebatchuploadcomplete', function() { goBack('/admin/vehicle_issues'); });
});
function saveForm() {
    if ($("#frmadmin").valid()) {
        jQuery.blockUI({message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>'});
        var formdata = $("#frmadmin").serialize();
        $.post(SITE_URL + 'admin/vehicle_issues/saveAdd', formdata, function(data) {
            if (data.status == 'success') { $("#CsVehicleIssueId").val(data.recordid); setTimeout(function() { $('.fileinputajax').fileinput('upload'); }, 1000); } else { alert(data.message); }
        }, 'json').fail(function() { alert("error"); }).always(function() { jQuery.unblockUI(); });
    }
}
</script>
<style>.krajee-default.file-preview-frame .kv-file-content { width: 210px; height: 160px; }</style>
@endsection
