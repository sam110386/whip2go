@extends('layouts.admin')
@section('title', 'Eland Account Setting')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Eland Account Setting</span></h4>
        </div>
    </div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="row">
    <fieldset class="col-lg-12">
        <div class="panel">
            <div class="panel-heading"><h5 class="panel-title">Eland Setting</h5></div>
            <div class="panel-body">
                <form action="{{ url('admin/eland/settings/index/' . base64_encode($userid)) }}" method="POST" id="frmadmin" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Dealer Indentifier :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <input type="text" name="ElandSetting[indentifier]" class="form-control required" required value="{{ $formData->indentifier ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">JWT Sub :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <input type="text" name="ElandSetting[jwt_sub]" class="form-control required" placeholder="1200" value="{{ $formData->jwt_sub ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Secret Key :<font class="requiredField">*</font></label>
                        <div class="col-lg-8">
                            <input type="text" name="ElandSetting[jwt_secret]" class="form-control required" value="{{ $formData->jwt_secret ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-5">
                            <button type="submit" class="btn btn-primary">Save <i class="icon-arrow-right14 position-right"></i></button>
                        </div>
                    </div>
                    <input type="hidden" name="ElandSetting[id]" value="{{ $formData->id ?? '' }}">
                    <input type="hidden" name="ElandSetting[user_id]" value="{{ $userid }}">
                </form>
            </div>
        </div>
    </fieldset>
</div>
<script type="text/javascript">jQuery(document).ready(function () { jQuery("#frmadmin").validate(); });</script>
@endsection
