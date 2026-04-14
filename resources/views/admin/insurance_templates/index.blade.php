@extends('admin.layouts.app')

@section('title', $title ?? 'Insurance')

@php
    $ct = $CsInsuranceTemplate ?? [];
    $ctId = $ct['id'] ?? null;
@endphp

@push('scripts')
<script>
    jQuery(document).ready(function () {
        jQuery('.insurance_policy_exp_date,.insurance_policy_date').datepicker({
            dateFormat: 'mm/dd/yy',
            changeMonth: true,
            changeYear: true
        });
        jQuery('#frmadmin').validate();
    });
    function syncVehicleInsurance(templateid) {
        if (confirm('Are you sure you want to sync with vehicles?')) {
            jQuery.post(SITE_URL + 'admin/insurance_templates/syncVehicleInsurance', { templateid: templateid }, function (data) {
                alert(data.message);
            }, 'json');
        }
    }
</script>
@endpush

@section('content')
<form method="POST"
      action="{{ url('/admin/insurance_templates/index/' . ($userParamEncoded ?? base64_encode((string)($userid ?? '')))) }}"
      name="frmadmin"
      id="frmadmin"
      class="form-horizontal">
    @csrf
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> Insurance <span class="text-semibold"></span></h4>
            </div>
            <div class="heading-elements">
                @if (empty($ctId))
                    <button type="submit" class="btn">Save</button>
                @else
                    <button type="button" class="btn" onclick="syncVehicleInsurance({{ (int) $ctId }})">Sync All Vehicles</button>
                    <button type="submit" class="btn">Update</button>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
    </div>
    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <legend class="text-size-large text-bold">1. Program</legend>
                <div class="col-lg-6 col-xs-12">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Vehicle Program :</label>
                        <div class="col-lg-8">
                            <select name="CsInsuranceTemplate[program]" class="required form-control">
                                @foreach ($programOptions ?? [] as $optVal => $optLabel)
                                    <option value="{{ $optVal }}" @selected((string)($ct['program'] ?? '1') === (string) $optVal)>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-12">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Insurance Token Name :</label>
                        <div class="col-lg-8">
                            <input type="text" name="CsInsuranceTemplate[insu_token_name]" class="required form-control"
                                   placeholder="Enter name here"
                                   value="{{ old('CsInsuranceTemplate.insu_token_name', $ct['insu_token_name'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-xs-12">
                    <legend class="text-size-large text-bold">Insurance Provider Details</legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Insurance Company :</label>
                        <div class="col-lg-8">
                            <input type="text" name="CsInsuranceTemplate[insurance_company]" maxlength="100" class="form-control"
                                   value="{{ old('CsInsuranceTemplate.insurance_company', $ct['insurance_company'] ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label"> Policy # :</label>
                        <div class="col-lg-8">
                            <input type="text" name="CsInsuranceTemplate[insurance_policy_no]" maxlength="100" class="form-control"
                                   value="{{ old('CsInsuranceTemplate.insurance_policy_no', $ct['insurance_policy_no'] ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label"> Begin Date :</label>
                        <div class="col-lg-8">
                            <input type="text" name="CsInsuranceTemplate[insurance_policy_date]"
                                   class="form-control date insurance_policy_date"
                                   value="{{ old('CsInsuranceTemplate.insurance_policy_date', $ct['insurance_policy_date'] ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label"> Expiration Date :</label>
                        <div class="col-lg-8">
                            <input type="text" name="CsInsuranceTemplate[insurance_policy_exp_date]"
                                   class="form-control date insurance_policy_exp_date"
                                   value="{{ old('CsInsuranceTemplate.insurance_policy_exp_date', $ct['insurance_policy_exp_date'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            @if (!empty($ctId))
                                <button type="submit" class="btn btn-primary">Update</button>
                            @else
                                <button type="submit" class="btn btn-primary">Save</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="CsInsuranceTemplate[id]" value="{{ $ct['id'] ?? '' }}">
    <input type="hidden" name="CsInsuranceTemplate[user_id]" value="{{ $userid }}">
</form>
@endsection
