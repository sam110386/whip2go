@extends('layouts.admin')
@section('content')
@php $d = $issueData['CsVehicleIssue'] ?? []; $images = $issueData['CsVehicleIssueImage'] ?? []; @endphp
<style>.kv-file-upload { display: none; }</style>
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate({ ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)'] });
        jQuery("#CsVehicleIssueAccidentDatetime").datetimepicker({});
        jQuery("#CsVehicleIssueVehicleSeenDate,#CsVehicleIssueOtherPartyVehiInsuranceexp,#CsVehicleIssueOtherPartyDriverlicexpdate").datetimepicker({ format: 'MM/DD/YYYY' });
        jQuery("#CsVehicleIssueVehicleId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Vehicle ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"admin/vehicle_issues/getVehicle", dataType: "json", type: "GET", data: function(params) { return {term: params}; }, processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id, user_id: item.user_id}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"admin/vehicle_issues/getVehicle", {dataType: "json", type:'POST', data:{id:id}}).done(function(data) { callback(data[0]); }); } }
        });
        jQuery("#CsVehicleIssueVehicleId").on('select2-selecting', function(e) { jQuery("#CsVehicleIssueUserId").val(e.choice.user_id); });
        jQuery("#CsVehicleIssueRenterId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Driver ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"admin/bookings/customerautocomplete", dataType: "json", type: "GET", data: function(params) { return {term: params}; }, processResults: function(data) { return {results: jQuery.map(data, function(item) { return {tag: item.tag, id: item.id}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"admin/bookings/customerautocomplete", {dataType: "json", type:'GET', data:{id:id}}).done(function(data) { callback(data[0]); }); } }
        });
        $("#CsVehicleIssuePoliceReported").change(function() { if ($(this).is(":checked")) { $("#CsVehicleIssuePoliceReportno").addClass('required'); } else { $("#CsVehicleIssuePoliceReportno").removeClass('required'); } });
        $("#CsVehicleIssueOtherVehicleInvolved").change(function() { if ($(this).is(":checked")) { $("#other_vehicle_involved").addClass('show').removeClass('hide'); } else { $("#other_vehicle_involved").addClass('hide').removeClass('show'); } });
    });
</script>
<div class="page-header"><div class="page-header-content"><div class="page-title"><h4><i class="icon-arrow-left52 position-left"></i> {{ $listTitle }}</h4></div></div></div>
<div class="row">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif</div>
<div class="panel">
    <form method="POST" name="frmadmin" id="frmadmin" class="form-horizontal" enctype="multipart/form-data">@csrf
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label class="col-lg-3 control-label">Vehicle# :<font class="requiredField">*</font></label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[vehicle_id]" id="CsVehicleIssueVehicleId" class="vehicle_id required" value="{{ $d['vehicle_id'] ?? '' }}" placeholder="Select Vehicle.." style="width:100%;"></div></div>
                <div class="form-group"><label class="col-lg-3 control-label">Driver :<font class="requiredField">*</font></label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[renter_id]" id="CsVehicleIssueRenterId" class="renter_id required" value="{{ $d['renter_id'] ?? '' }}" placeholder="Select Driver.." style="width:100%;"></div></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="col-lg-3 control-label"> Status:</label><div class="col-lg-8"><select name="CsVehicleIssue[status]" class="form-control">@foreach($issueStatus as $k => $v)<option value="{{ $k }}" {{ ($d['status'] ?? '0') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                <div class="form-group"><label class="col-lg-3 control-label"> Accident Time: <font class="requiredField">*</font></label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[accident_datetime]" id="CsVehicleIssueAccidentDatetime" class="required form-control" value="{{ isset($d['accident_datetime']) && $d['accident_datetime'] ? \Carbon\Carbon::parse($d['accident_datetime'])->format('m/d/Y h:i A') : '' }}"></div></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="col-lg-3 control-label">Amount paid for:</label><div class="col-lg-8"><div class="input-group"><span class="input-group-addon"><i class="icon-coin-dollar"></i></span><input type="text" name="CsVehicleIssue[service_paid]" class="form-control digit" value="{{ $d['service_paid'] ?? '' }}"></div></div></div>
            </div>
        </div>
        <legend><strong>Loss</strong></legend>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Accident Location: <font class="requiredField">*</font></label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[accident_location]" class="required form-control" value="{{ $d['accident_location'] ?? '' }}"><em> Location name, city, state & Zip</em></div></div></div>
            <div class="col-md-6"><div class="form-group"><div class="col-lg-5"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[police_reported]" id="CsVehicleIssuePoliceReported" {{ !empty($d['police_reported']) ? 'checked' : '' }}> Police Department contacted</label></div><div class="col-lg-6"><input type="text" name="CsVehicleIssue[police_reportno]" id="CsVehicleIssuePoliceReportno" class="form-control" value="{{ $d['police_reportno'] ?? '' }}" placeholder="Police Complained #"></div></div></div>
        </div>
        <div class="row"><div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Police Department Name:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[police_dept_name]" class="form-control" value="{{ $d['police_dept_name'] ?? '' }}"></div></div></div></div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Description of Accident: <font class="requiredField">*</font></label><div class="col-lg-8"><textarea name="CsVehicleIssue[accident_description]" class="required form-control">{{ $d['accident_description'] ?? '' }}</textarea><em> What happened?</em></div></div>
                <div class="form-group"><label class="col-lg-3 control-label"> Insurance Company:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[vehicle_insurance_company_name]" class="form-control" value="{{ $d['vehicle_insurance_company_name'] ?? '' }}"></div></div>
                <div class="form-group"><label class="col-lg-3 control-label"> Insurance Number:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[vehicle_insurance]" class="form-control" value="{{ $d['vehicle_insurance'] ?? '' }}"></div></div>
                <div class="form-group"><label class="col-lg-3 control-label"> Claim Number:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[claim_number]" class="form-control" value="{{ $d['claim_number'] ?? '' }}"></div></div>
            </div>
            <div class="col-md-6"><div class="form-group">
                <div class="col-lg-12"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[on_way_tolift]" {{ !empty($d['on_way_tolift']) ? 'checked' : '' }}><b>Q. </b>Were you on the way to pick up an Uber/Lyft passenger when accident happened?</label></div>
                <div class="col-lg-12"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[have_passenger]" {{ !empty($d['have_passenger']) ? 'checked' : '' }}><b>Q. </b>Did you have a passenger from Uber/Lyft in the vehicle when the accident happened?</label></div>
                <div class="col-lg-12"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[working_with_delivery]" {{ !empty($d['working_with_delivery']) ? 'checked' : '' }}><b>Q. </b>Were you working with any delivery services such as Uber Eats, DoorDash, Grubhub?</label></div>
                <div class="col-lg-12"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[orders_from_delivery]" {{ !empty($d['orders_from_delivery']) ? 'checked' : '' }}><b>Q. </b>Had you accepted any orders from a delivery service when you had the accident?</label></div>
                <div class="col-lg-12"><label class="checkbox-inline checkbox-right"><input type="checkbox" name="CsVehicleIssue[way_to_drop_off_delivery]" {{ !empty($d['way_to_drop_off_delivery']) ? 'checked' : '' }}><b>Q. </b>Were you on the way to drop off the delivery when you had the accident?</label></div>
            </div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Vehicle Damage Details: <font class="requiredField">*</font></label><div class="col-lg-8"><textarea name="CsVehicleIssue[vehicle_damage_description]" class="required form-control">{{ $d['vehicle_damage_description'] ?? '' }}</textarea><em> Insured vehicle damage details</em></div></div></div>
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label">Vehicle Damage Location:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[vehicle_damage_location]" class="form-control" value="{{ $d['vehicle_damage_location'] ?? '' }}"><em> Where can vehicle be seen? Address details</em></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> When can vehicle be seen:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[vehicle_seen_date]" id="CsVehicleIssueVehicleSeenDate" class="date form-control" value="{{ !empty($d['vehicle_seen_date']) ? date('m/d/Y', strtotime($d['vehicle_seen_date'])) : '' }}"></div></div></div>
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Other Insurance on vehicle:</label><div class="col-lg-8"><select name="CsVehicleIssue[vehicle_other_insurance]" class="form-control"><option value="0" {{ ($d['vehicle_other_insurance'] ?? 0)==0?'selected':'' }}>No</option><option value="1" {{ ($d['vehicle_other_insurance'] ?? 0)==1?'selected':'' }}>Yes</option></select></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> CCM Claim #:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[ccm_claim_number]" class="alphanumericwithspace form-control" value="{{ $d['ccm_claim_number'] ?? '' }}"></div></div></div>
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Total Damage:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[total_damage]" class="positiveNumber form-control" value="{{ $d['total_damage'] ?? '' }}"></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Insurance Coverage:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[insurance_coverage]" class="positiveNumber form-control" value="{{ $d['insurance_coverage'] ?? '' }}"></div></div></div>
            <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Company cost:</label><div class="col-lg-8"><input type="text" name="CsVehicleIssue[company_cost]" class="positiveNumber form-control" value="{{ $d['company_cost'] ?? '' }}"></div></div></div>
        </div>
        <legend><strong>Other Vehicle Involved ?</strong> <input type="checkbox" name="CsVehicleIssue[other_vehicle_involved]" id="CsVehicleIssueOtherVehicleInvolved" value="1" {{ ($d['other_vehicle_involved'] ?? 0)==1?'checked':'' }}></legend>
        <div id="other_vehicle_involved" class="{{ ($d['other_vehicle_involved'] ?? 0)==1?'show':'hide' }}">
            <legend><strong>3rd party Property Damage Details</strong></legend>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> 3rd Party Vehicle:</label><div class="col-lg-2"><input name="CsVehicleIssue[other_party_vehi_make]" class="form-control" value="{{ $d['other_party_vehi_make'] ?? '' }}"><em>Make</em></div><div class="col-lg-2"><input name="CsVehicleIssue[other_party_vehi_model]" class="form-control" value="{{ $d['other_party_vehi_model'] ?? '' }}"><em>Model</em></div><div class="col-lg-2"><input name="CsVehicleIssue[other_party_vehi_year]" class="form-control" value="{{ $d['other_party_vehi_year'] ?? '' }}" maxlength="4"><em>Year</em></div><div class="col-lg-2"><input name="CsVehicleIssue[other_party_vehi_vin]" class="form-control" value="{{ $d['other_party_vehi_vin'] ?? '' }}" maxlength="17"><em>Vin#</em></div></div></div>
                <div class="col-md-12"><div class="form-group"><div class="col-lg-3"><input name="CsVehicleIssue[other_party_vehi_insurancecompany]" class="form-control" value="{{ $d['other_party_vehi_insurancecompany'] ?? '' }}"><em>Insurance Company</em></div><div class="col-lg-3"><input name="CsVehicleIssue[other_party_vehi_insurance]" class="form-control" value="{{ $d['other_party_vehi_insurance'] ?? '' }}"><em>Insurance #</em></div><div class="col-lg-3"><input type="text" name="CsVehicleIssue[other_party_vehi_insuranceexp]" id="CsVehicleIssueOtherPartyVehiInsuranceexp" class="date form-control" value="{{ !empty($d['other_party_vehi_insuranceexp'])?date('m/d/Y',strtotime($d['other_party_vehi_insuranceexp'])):'' }}"><em>Insurance Exp.</em></div><div class="col-lg-3"><input name="CsVehicleIssue[other_party_vehi_insurance_claim]" class="form-control" value="{{ $d['other_party_vehi_insurance_claim'] ?? '' }}"><em>Claim #</em></div></div></div>
                <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Owner Name & Address:</label><div class="col-lg-8"><textarea name="CsVehicleIssue[other_party_nameaddress]" class="form-control">{{ $d['other_party_nameaddress'] ?? '' }}</textarea></div></div></div>
                <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label">Residence phone #:</label><div class="col-lg-8"><input name="CsVehicleIssue[other_party_phone]" class="phone form-control" value="{{ $d['other_party_phone'] ?? '' }}"></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-9"><div class="form-group"><label class="col-lg-2 control-label">Driver's Details:</label><div class="col-lg-3"><input name="CsVehicleIssue[other_party_driver]" class="form-control" value="{{ $d['other_party_driver'] ?? '' }}"><em>Driver name</em></div><div class="col-lg-3"><input name="CsVehicleIssue[other_party_driverphone]" class="phone form-control" value="{{ $d['other_party_driverphone'] ?? '' }}"><em>Driver phone #</em></div><div class="col-lg-3"><input name="CsVehicleIssue[other_party_driveradress]" class="form-control" value="{{ $d['other_party_driveradress'] ?? '' }}"><em>Driver address</em></div></div></div>
                <div class="col-md-9"><div class="form-group"><label class="col-lg-2 control-label">Driver's License Details:</label><div class="col-lg-3"><input name="CsVehicleIssue[other_party_driverlicense]" class="form-control" value="{{ $d['other_party_driverlicense'] ?? '' }}"><em>Driver license#</em></div><div class="col-lg-3"><input name="CsVehicleIssue[other_party_driverlicstate]" class="form-control" value="{{ $d['other_party_driverlicstate'] ?? '' }}"><em>Driver license State #</em></div><div class="col-lg-3"><input type="text" name="CsVehicleIssue[other_party_driverlicexpdate]" id="CsVehicleIssueOtherPartyDriverlicexpdate" class="date form-control" value="{{ !empty($d['other_party_driverlicexpdate'])?date('m/d/Y',strtotime($d['other_party_driverlicexpdate'])):'' }}"><em>Driver license Exp</em></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label">Where can damage be seen:</label><div class="col-lg-8"><input name="CsVehicleIssue[other_party_vehiclelocation]" class="form-control" value="{{ $d['other_party_vehiclelocation'] ?? '' }}"></div></div></div>
                <div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Damage Details:</label><div class="col-lg-8"><textarea name="CsVehicleIssue[other_party_damage_detail]" class="form-control">{{ $d['other_party_damage_detail'] ?? '' }}</textarea></div></div></div>
            </div>
            <div class="row"><div class="col-md-8"><div class="form-group"><label class="col-lg-2 control-label"> Damage Vehicle Images</label><div class="col-lg-8"><input type="file" class="othervehicleimage" multiple name="othervehicleimage" data-show-preview="true" data-show-upload="false"><span class="help-block">You can select multiple images.</span></div></div></div></div>
        </div>
        <legend><strong>Injuries Details</strong></legend>
        <div class="row"><div class="col-md-6"><div class="form-group"><label class="col-lg-3 control-label"> Injured Party:</label><div class="col-lg-8"><textarea name="CsVehicleIssue[other_party_injury_details]" class="form-control">{{ $d['other_party_injury_details'] ?? '' }}</textarea></div></div></div></div>
        @for($i=0; $i<3; $i++)
        <div class="row"><div class="col-md-8"><div class="form-group"><label class="col-lg-1 control-label"> #{{ $i+1 }}:</label><div class="col-lg-3"><input name="CsVehicleIssue[injury][{{ $i }}][name]" class="form-control" placeholder="Name" value="{{ $d['injury'][$i]['name'] ?? '' }}"></div><div class="col-lg-3"><input name="CsVehicleIssue[injury][{{ $i }}][address]" class="form-control" placeholder="Address" value="{{ $d['injury'][$i]['address'] ?? '' }}"></div><div class="col-lg-3"><input name="CsVehicleIssue[injury][{{ $i }}][phone]" class="form-control" placeholder="Phone#" value="{{ $d['injury'][$i]['phone'] ?? '' }}"></div></div></div></div>
        @endfor
        <legend><strong>Witnesses Details</strong></legend>
        @for($i=0; $i<3; $i++)
        <div class="row"><div class="col-md-8"><div class="form-group"><label class="col-lg-1 control-label"> #{{ $i+1 }}:</label><div class="col-lg-3"><input name="CsVehicleIssue[witness][{{ $i }}][name]" class="form-control" placeholder="Name" value="{{ $d['witness'][$i]['name'] ?? '' }}"></div><div class="col-lg-3"><input name="CsVehicleIssue[witness][{{ $i }}][address]" class="form-control" placeholder="Address" value="{{ $d['witness'][$i]['address'] ?? '' }}"></div><div class="col-lg-3"><input name="CsVehicleIssue[witness][{{ $i }}][phone]" class="form-control" placeholder="Phone#" value="{{ $d['witness'][$i]['phone'] ?? '' }}"></div></div></div></div>
        @endfor
        <div class="row"><div class="col-md-8"><div class="form-group"><label class="col-lg-2 control-label">Vehicle Damage Images</label><div class="col-lg-8"><input type="file" class="fileinputajax" multiple name="vehicleimage" data-show-preview="true" data-show-upload="false"><span class="help-block">You can select multiple images.</span></div></div></div></div>
        <div class="form-group"><label class="col-lg-2 control-label">&nbsp;</label><div class="col-lg-6"><button type="button" class="btn btn-primary" onclick="saveForm()">{{ !empty($d['id']) ? 'Update' : 'Save' }}</button> <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button></div></div>
    </div>
    <input type="hidden" name="CsVehicleIssue[id]" id="CsVehicleIssueId" value="{{ $d['id'] ?? '' }}">
    <input type="hidden" name="CsVehicleIssue[user_id]" id="CsVehicleIssueUserId" value="{{ $d['user_id'] ?? '' }}">
    <input type="hidden" name="CsVehicleIssue[type]" value="1">
    </form>
</div>
@php
$initialPreview = []; $otherVehicleImages = []; $initialPreviewConfig = []; $otherVehiclePreviewConfig = [];
foreach ($images as $img) {
    $preview = ['caption'=>$img['image'],'filename'=>$img['image'],'key'=>$img['id'],'width'=>'120px','downloadUrl'=>config('app.url').'/img/custom/vehicle_issue/'.$img['image']];
    $ext = strtolower(pathinfo($img['image'], PATHINFO_EXTENSION));
    if ($ext=='pdf') $preview['type']='pdf'; if (in_array($ext,['doc','docx'])) $preview['type']='gdocs';
    if (($img['type'] ?? 0)==1) { $otherVehicleImages[]=config('app.url').'/img/custom/vehicle_issue/'.$img['image']; $otherVehiclePreviewConfig[]=$preview; }
    else { $initialPreview[]=config('app.url').'/img/custom/vehicle_issue/'.$img['image']; $initialPreviewConfig[]=$preview; }
}
@endphp
<script type="text/javascript">
$(function() {
    var fileOpts = { showDrag: false, showZoom: true, showUpload: false, removeIcon: '<i class="icon-bin"></i>', removeClass: 'btn btn-link btn-xs btn-icon' };
    $(".fileinputajax").fileinput({ showUpload: false, uploadUrl: SITE_URL+"admin/vehicle_issues/saveImage", uploadAsync: true, maxFileCount: 15, deleteUrl: SITE_URL+"admin/vehicle_issues/deleteImage", allowedFileExtensions: ['jpeg','jpg','png','doc','docx','pdf'], initialPreview: {!! json_encode($initialPreview) !!}, overwriteInitial: false, initialPreviewAsData: true, initialPreviewFileType: 'image', initialPreviewConfig: {!! json_encode($initialPreviewConfig) !!}, maxFileSize: 1024, uploadExtraData: function() { return {id: $("#CsVehicleIssueId").val(), type: 0, _token: '{{ csrf_token() }}'}; }, fileActionSettings: fileOpts })
    .on('fileuploaded', function(event, data, previewId) { $("#"+previewId+" button.kv-file-remove").attr('data-key', data.response.key); })
    .on('filebatchuploadcomplete', function() { goBack('/admin/vehicle_issues'); });
    $(".othervehicleimage").fileinput({ showUpload: false, uploadUrl: SITE_URL+"admin/vehicle_issues/saveImage", uploadAsync: true, maxFileCount: 15, deleteUrl: SITE_URL+"admin/vehicle_issues/deleteImage", allowedFileExtensions: ['jpeg','jpg','png','doc','docx','pdf'], initialPreview: {!! json_encode($otherVehicleImages) !!}, overwriteInitial: false, initialPreviewAsData: true, initialPreviewFileType: 'image', initialPreviewConfig: {!! json_encode($otherVehiclePreviewConfig) !!}, maxFileSize: 1024, uploadExtraData: function() { return {id: $("#CsVehicleIssueId").val(), type: 1, _token: '{{ csrf_token() }}'}; }, fileActionSettings: fileOpts })
    .on('fileuploaded', function(event, data, previewId) { $("#"+previewId+" button.kv-file-remove").attr('data-key', data.response.key); })
    .on('filebatchuploadcomplete', function() { $('.fileinputajax').fileinput('upload'); });
});
function saveForm() {
    if ($("#frmadmin").valid()) {
        jQuery.blockUI({message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>'});
        $.post(SITE_URL+'admin/vehicle_issues/saveAccident', $("#frmadmin").serialize(), function(data) {
            if (data.status=='success') { $("#CsVehicleIssueId").val(data.recordid); setTimeout(function(){ $('.othervehicleimage').fileinput('upload'); },1000); } else { alert(data.message); }
        }, 'json').fail(function(){ alert("error"); }).always(function(){ jQuery.unblockUI(); });
    }
}
</script>
<style>.krajee-default.file-preview-frame .kv-file-content { width: 210px; height: 160px; }</style>
@endsection
