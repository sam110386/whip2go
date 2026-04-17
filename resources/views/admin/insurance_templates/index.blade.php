@extends('admin.layouts.app')

@section('title', 'Insurance Templates')

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
            var siteUrl = "{{ url('/') }}/";
            jQuery.post(siteUrl + 'admin/insurance_templates/syncVehicleInsurance', { templateid: templateid, _token: "{{ csrf_token() }}" }, function (data) {
                alert(data.message);
            }, 'json');
        }
    }
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <a href="{{ url('admin/users/index') }}"><i class="icon-arrow-left52 position-left"></i></a>
                <span class="text-semibold">{{ 'User' }}</span> — {{ 'Insurance Templates' }}
            </h4>
        </div>
        <div class="heading-elements">
            @if ($ctId)
                <button type="button" class="btn btn-info" onclick="syncVehicleInsurance({{ (int) $ctId }})">
                    <i class="icon-sync position-left"></i> Sync All Vehicles
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row">
    @include('partials.flash')
</div>

<form method="POST"
      action="{{ url('/admin/insurance_templates/index', $userParamEncoded ?? base64_encode((string)($userid ?? ''))) }}"
      name="frmadmin"
      id="frmadmin"
      class="form-horizontal">
    @csrf
    
    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Insurance Configuration</h5>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <fieldset>
                        <legend class="text-semibold"><i class="icon-reading position-left"></i> 1. Program Details</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Vehicle Program:</label>
                            <div class="col-lg-8">
                                <select name="CsInsuranceTemplate[program]" class="required form-control">
                                    @foreach ($programOptions ?? [] as $optVal => $optLabel)
                                        <option value="{{ $optVal }}" @selected((string)($ct['program'] ?? '1') === (string) $optVal)>{{ $optLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Insurance Token Name:</label>
                            <div class="col-lg-8">
                                <input type="text" name="CsInsuranceTemplate[insu_token_name]" class="required form-control"
                                       placeholder="Enter name here"
                                       value="{{ old('CsInsuranceTemplate.insu_token_name', $ct['insu_token_name'] ?? '') }}">
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="col-md-6">
                    <fieldset>
                        <legend class="text-semibold"><i class="icon-shield-check position-left"></i> 2. Provider Details</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Insurance Company:</label>
                            <div class="col-lg-8">
                                <input type="text" name="CsInsuranceTemplate[insurance_company]" maxlength="100" class="form-control"
                                       value="{{ old('CsInsuranceTemplate.insurance_company', $ct['insurance_company'] ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Policy #:</label>
                            <div class="col-lg-8">
                                <input type="text" name="CsInsuranceTemplate[insurance_policy_no]" maxlength="100" class="form-control"
                                       value="{{ old('CsInsuranceTemplate.insurance_policy_no', $ct['insurance_policy_no'] ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Begin Date:</label>
                            <div class="col-lg-8">
                                <input type="text" name="CsInsuranceTemplate[insurance_policy_date]"
                                       class="form-control date insurance_policy_date"
                                       value="{{ old('CsInsuranceTemplate.insurance_policy_date', $ct['insurance_policy_date'] ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label text-semibold">Expiration Date:</label>
                            <div class="col-lg-8">
                                <input type="text" name="CsInsuranceTemplate[insurance_policy_exp_date]"
                                       class="form-control date insurance_policy_exp_date"
                                       value="{{ old('CsInsuranceTemplate.insurance_policy_exp_date', $ct['insurance_policy_exp_date'] ?? '') }}">
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="text-right mt-20">
                <button type="submit" class="btn btn-primary">
                    {{ !empty($ctId) ? 'Update Configuration' : 'Save Configuration' }} 
                    <i class="icon-arrow-right14 position-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <input type="hidden" name="CsInsuranceTemplate[id]" value="{{ $ct['id'] ?? '' }}">
    <input type="hidden" name="CsInsuranceTemplate[user_id]" value="{{ $userid }}">
</form>
@endsection@endsection
