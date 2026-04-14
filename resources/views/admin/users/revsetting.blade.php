@extends('admin.layouts.app')

@section('title', 'Revenue Setting')

@section('content')
<script>
    jQuery(document).ready(function () {
        jQuery('#frmadmin').validate();
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Revenue</span> Setting</h4>
        </div>
    </div>
</div>
@if (session('success'))
    <div class="row"><div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div></div>
@endif
@if (session('error'))
    <div class="row"><div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div></div>
@endif
<div class="panel">
    <div class="panel-body">
        <form action="/admin/users/revsetting/{{ base64_encode((string)$user_id) }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="form-group">
                <label class="col-lg-2 control-label">Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="RevSetting[rev]" class="digit form-control required" value="{{ $revSetting->rev ?? '' }}">
                </div>
                <em>This will be used to transfer the dealer part</em>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Transfer Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="RevSetting[transfer_rev]" class="form-control required">
                        <option value="1" @selected((int)($revSetting->transfer_rev ?? 0) === 1)>Yes</option>
                        <option value="0" @selected((int)($revSetting->transfer_rev ?? 0) === 0)>No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Transfer Insurance :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="RevSetting[transfer_insu]" class="form-control required">
                        <option value="1" @selected((int)($revSetting->transfer_insu ?? 0) === 1)>Yes</option>
                        <option value="0" @selected((int)($revSetting->transfer_insu ?? 0) === 0)>No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Rental Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="RevSetting[rental_rev]" class="digit form-control required" value="{{ $revSetting->rental_rev ?? '' }}">
                </div>
                <em>This will be used to Rental Calculation</em>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Tax Included :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="RevSetting[tax_included]" class="form-control required">
                        <option value="1" @selected((int)($revSetting->tax_included ?? 0) === 1)>Yes</option>
                        <option value="0" @selected((int)($revSetting->tax_included ?? 0) === 0)>No</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">DIA Fee :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="RevSetting[dia_fee]" class="digit form-control required" value="{{ $revSetting->dia_fee ?? '' }}">
                    <em>Enter value to apply DIA fee on booking</em>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" class="btn left-margin btn-cancel" onclick="window.location.href='/admin/users/index'">Return</button>
                </div>
            </div>
            <input type="hidden" name="RevSetting[id]" value="{{ $revSetting->id ?? '' }}">
            <input type="hidden" name="RevSetting[user_id]" value="{{ $user_id }}">
        </form>
    </div>
</div>
@endsection
