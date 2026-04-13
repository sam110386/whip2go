@extends('layouts.admin')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Revenue</span> Setting</h4>
        </div>
    </div>
</div>
<div class="row">
    @includeif('common.flash-messages')
</div>
<div class="panel">
    <div class="panel-body">
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery("#frmadmin").validate();
            });
        </script>

        <form action="{{ url('admin/users/revsetting/' . base64_encode($user_id)) }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf

            <div class="form-group">
                <label class="col-lg-2 control-label">Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="rev" value="{{ old('rev', $revSetting->rev ?? '') }}" class="digit form-control required">
                </div>
                <em>This will be used to transfer the dealer part</em>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">Transfer Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="transfer_rev" class="form-control required">
                        <option value="1" {{ old('transfer_rev', $revSetting->transfer_rev ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('transfer_rev', $revSetting->transfer_rev ?? '') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">Transfer Insurance :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="transfer_insu" class="form-control required">
                        <option value="1" {{ old('transfer_insu', $revSetting->transfer_insu ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('transfer_insu', $revSetting->transfer_insu ?? '') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">Rental Revenue :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="rental_rev" value="{{ old('rental_rev', $revSetting->rental_rev ?? '') }}" class="digit form-control required">
                </div>
                <em>This will be used to Rental Calculation</em>
            </div>

            <div class="form-group" id="ssn_noblk">
                <label class="col-lg-2 control-label">Tax Included :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="tax_included" class="form-control required">
                        <option value="1" {{ old('tax_included', $revSetting->tax_included ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('tax_included', $revSetting->tax_included ?? '') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">DIA Fee :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="dia_fee" value="{{ old('dia_fee', $revSetting->dia_fee ?? '') }}" class="digit form-control required">
                    <em>Enter value to apply DIA fee on booking</em>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-default left-margin btn-cancel" onClick="window.location='{{ url('admin/users/index') }}'">Return</button>
                </div>
            </div>

            <input type="hidden" name="id" value="{{ $revSetting->id ?? '' }}">
            <input type="hidden" name="user_id" value="{{ $user_id }}">

        </form>
    </div>
</div>
@endsection