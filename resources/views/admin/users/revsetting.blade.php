@extends('layouts.admin')

@section('title', 'Revenue Setting')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Revenue Settings
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li class="active">Revenue Setting</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('layouts.flash-messages')

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Calculation & Transfer Rules</h5>
                </div>

                <div class="panel-body">
                    <form action="{{ url('admin/users/revsetting', base64_encode((string)$user_id)) }}" method="POST" id="frmadmin" class="form-horizontal">
                        @csrf
                        <input type="hidden" name="id" value="{{ $revSetting->id ?? '' }}">
                        <input type="hidden" name="user_id" value="{{ $user_id }}">

                        <fieldset>
                            <legend class="text-semibold">General Revenue</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Revenue (% or $):<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="rev" value="{{ old('rev', $revSetting->rev ?? '') }}" class="form-control" required placeholder="0.00">
                                    <span class="help-block text-muted">This will be used to transfer the dealer's part.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Rental Revenue ($):<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="rental_rev" value="{{ old('rental_rev', $revSetting->rental_rev ?? '') }}" class="form-control" required placeholder="0.00">
                                    <span class="help-block text-muted">Used for rental calculation.</span>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-semibold">Transfer Settings</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Transfer Revenue:</label>
                                <div class="col-lg-9">
                                    <select name="transfer_rev" class="form-control">
                                        <option value="1" @selected(old('transfer_rev', $revSetting->transfer_rev ?? '') == '1')>Yes</option>
                                        <option value="0" @selected(old('transfer_rev', $revSetting->transfer_rev ?? '') == '0')>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Transfer Insurance:</label>
                                <div class="col-lg-9">
                                    <select name="transfer_insu" class="form-control">
                                        <option value="1" @selected(old('transfer_insu', $revSetting->transfer_insu ?? '') == '1')>Yes</option>
                                        <option value="0" @selected(old('transfer_insu', $revSetting->transfer_insu ?? '') == '0')>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Tax Included:</label>
                                <div class="col-lg-9">
                                    <select name="tax_included" class="form-control">
                                        <option value="1" @selected(old('tax_included', $revSetting->tax_included ?? '') == '1')>Yes</option>
                                        <option value="0" @selected(old('tax_included', $revSetting->tax_included ?? '') == '0')>No</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-semibold">Fees</legend>
                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">DIA Fee ($):<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="dia_fee" value="{{ old('dia_fee', $revSetting->dia_fee ?? '') }}" class="form-control" required>
                                    <span class="help-block text-muted">Apply DIA fee on booking.</span>
                                </div>
                            </div>
                        </fieldset>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">Save Settings <i class="icon-database-insert position-right"></i></button>
                            <a href="{{ url('admin/users/index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection