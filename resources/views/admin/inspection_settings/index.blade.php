@extends('admin.layouts.app')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() { jQuery("#frmadmin").validate(); });
</script>
<div class="page-header"><div class="page-header-content"><div class="page-title"><h4><i class="icon-arrow-left52 position-left"></i> {{ $listTitle }}</h4></div></div></div>
<div class="row">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif</div>
<div class="panel">
    <form action="{{ url('/admin/inspection_settings') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <legend>Inspection Schedule Setting</legend>
            <div class="form-group">
                <label class="col-lg-2 control-label">Active:<font class="requiredField">*</font></label>
                <div class="col-lg-5">
                    <select name="InspectionSetting[status]" class="required form-control" style="width:100%;">
                        <option value="1" {{ ($settingData['status'] ?? 1)==1?'selected':'' }}>Yes</option>
                        <option value="0" {{ ($settingData['status'] ?? 1)==0?'selected':'' }}>No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Create Schedule Every<font class="requiredField">*</font></label>
                <div class="col-lg-5">
                    <select name="InspectionSetting[schedule]" class="required form-control" style="width:100%;">
                        @foreach($scheduels as $k => $v)
                            <option value="{{ $k }}" {{ ($settingData['schedule'] ?? 1)==$k?'selected':'' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button>
                </div>
            </div>
        </div>
        <input type="hidden" name="InspectionSetting[id]" value="{{ $settingData['id'] ?? '' }}">
    </form>
</div>
@endsection
