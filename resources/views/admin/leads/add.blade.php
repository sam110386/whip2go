@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate();
        jQuery("#LeadType").change(function() {
            if (jQuery(this).val() == 1) {
                jQuery(".dealername").hide();
                jQuery(".drivername").show();
            } else {
                jQuery(".drivername").hide();
                jQuery(".dealername").show();
            }
        });
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e){{ $e }}<br/>@endforeach</div>@endif
</div>
<div class="panel">
    <div class="panel-body">
        <div class="row">
            <form action="{{ url('/admin/lead/leads/add') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Lead Type :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <select name="Lead[type]" id="LeadType" class="form-control required">
                                <option value="1" {{ ($data['type'] ?? 1) == 1 ? 'selected' : '' }}>Driver</option>
                                <option value="2" {{ ($data['type'] ?? 1) == 2 ? 'selected' : '' }}>Dealer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Phone :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="text" name="Lead[phone]" maxlength="16" class="form-control phone required" value="{{ $data['phone'] ?? '' }}" {{ !empty($data['id'] ?? null) ? 'readonly' : '' }} />
                        </div>
                    </div>
                    <div class="form-group dealername" {!! (empty($data['type'] ?? null) || ($data['type'] ?? 1) == 1) ? "style='display:none'" : '' !!}>
                        <label class="col-lg-3 control-label">Dealer Name :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="text" name="Lead[dealer_name]" class="form-control required" value="{{ $data['dealer_name'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group drivername" {!! (isset($data['type']) && $data['type'] == 2) ? "style='display:none'" : '' !!}>
                        <label class="col-lg-3 control-label">First Name :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="text" name="Lead[first_name]" class="form-control required" value="{{ $data['first_name'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group drivername" {!! (isset($data['type']) && $data['type'] == 2) ? "style='display:none'" : '' !!}>
                        <label class="col-lg-3 control-label">Last Name :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="text" name="Lead[last_name]" class="form-control required" value="{{ $data['last_name'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Email :</label>
                        <div class="col-lg-9">
                            <input type="text" name="Lead[email]" class="form-control email" value="{{ $data['email'] ?? '' }}" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Address :</label>
                        <div class="col-lg-9"><input type="text" name="Lead[address]" maxlength="60" class="form-control" value="{{ $data['address'] ?? '' }}" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">City :</label>
                        <div class="col-lg-9"><input type="text" name="Lead[city]" maxlength="30" class="form-control" value="{{ $data['city'] ?? '' }}" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">State :</label>
                        <div class="col-lg-9"><input type="text" name="Lead[state]" maxlength="30" class="form-control" value="{{ $data['state'] ?? '' }}" /></div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Postal Code :</label>
                        <div class="col-lg-9"><input type="text" name="Lead[postal]" class="form-control" value="{{ $data['postal'] ?? '' }}" /></div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            @if(empty($data['id'] ?? null))
                                <button type="submit" class="btn">Save</button>
                            @else
                                <button type="submit" class="btn">Update</button>
                            @endif
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/lead/leads/index')">Return</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="Lead[id]" value="{{ $data['id'] ?? '' }}" />
            </form>
        </div>
    </div>
</div>
@endsection
