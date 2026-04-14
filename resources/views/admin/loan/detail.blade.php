@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#addVehicleForm").validate();
    });
</script>
<script src="{{ asset('assets/js/plugins/forms/editable/editable.min.js') }}"></script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Loan</span> - Stipulations Details</h4>
        </div>
        <div class="heading-elements">
            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/loan/managers/index')">Return</button>
        </div>
    </div>
</div>
@include('partials.flash')
<form method="POST" class="form-horizontal">
    @csrf
    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <fieldset class="col-lg-12">
                    <div class="panel-body">
                        <div class="col-lg-6 col-sm-12">
                            <legend class="text-size-large text-bold">1. Income Details</legend>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Name :</label>
                                <div class="col-lg-8 control-label">{{ $detail->first_name ?? '' }} {{ $detail->last_name ?? '' }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Contact # :</label>
                                <div class="col-lg-8 control-label">{{ $detail->contact_number ?? '' }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Income Stated :</label>
                                <div class="col-lg-8 control-label">
                                    <a href="javascript:;" id="statedIncome" data-title="Edit" data-pk="{{ $detail->user_id ?? '' }}" data-url="{{ config('app.url') }}admin/vehicle_reservations/provenincome">{{ $detail->income ?? 0 }}</a>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Income Proven :</label>
                                <div class="col-lg-8 control-label">
                                    <a href="javascript:;" id="provenIncome" data-title="Edit" data-pk="{{ $detail->user_id ?? '' }}" data-url="{{ config('app.url') }}admin/vehicle_reservations/provenincome">{{ $detail->provenincome ?? 0 }}</a>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Pay Stub :</label>
                                <div class="col-lg-8 control-label">
                                    @if(!empty($detail->pay_stub))
                                        <a href="{{ config('app.url') }}files/userdocs/{{ $detail->pay_stub }}" title="Pay Stub Doc" class="fancybox"><i class="icon-magazine"></i></a>
                                    @endif
                                    @if(!empty($detail->pay_stub_2))
                                        <a href="{{ config('app.url') }}files/userdocs/{{ $detail->pay_stub_2 }}" title="Pay Stub Doc" class="fancybox"><i class="icon-magazine"></i></a>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Utility Bill :</label>
                                <div class="col-lg-8 control-label">
                                    @if(!empty($detail->utility_bill))
                                        <a href="{{ config('app.url') }}files/userdocs/{{ $detail->utility_bill }}" title="Utility Bill" class="fancybox"><i class="icon-magazine"></i></a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-sm-12">
                            <legend class="text-size-large text-bold">2. Plaid Connected Bank Accounts</legend>
                            <div class="form-group" id="palidbankdetail"></div>
                        </div>

                        <div class="col-lg-6 col-sm-12">
                            <legend class="text-size-large text-bold">3. Plaid Uploaded Paystubs</legend>
                            <div class="form-group" id="palidpaystub"></div>
                        </div>

                        <div class="col-lg-6 col-sm-12">
                            <legend class="text-size-large text-bold">4. Employer Account Details</legend>
                            <div class="breadcrumb-line">
                                <ul class="text-center">
                                    <li><h6><span class="text-semibold">MeasureOne Connected Employers </span></h6></li>
                                </ul>
                            </div>
                            <div class="form-group" id="employerdetail"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/loan/managers/index')">Back</button>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</form>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".fancybox").fancybox();
        $("#provenIncome,#statedIncome").editable({
            success: function(response, newValue) {
                if(response.status == 'error') return response.msg;
            }
        });
    });
</script>
<script src="{{ asset('Loan/js/loan.js') }}"></script>
<script src="{{ asset('js/admin_plaid.js') }}"></script>
<script type="text/javascript">
    var userid = "{{ $userid }}";
    var encodeduserid = "{{ base64_encode($userid) }}";
    $(document).ready(function() {
        loadMeasureOneblock();
        loanpullPlaidBank();
        loanpullPlaidPaystubk();
    });
</script>
@endsection
