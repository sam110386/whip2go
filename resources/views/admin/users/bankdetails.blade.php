@extends('layouts.admin')

@section('title', 'Bank & Stripe Connection')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Bank & Stripe Connection
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li class="active">Bank Details</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('layouts.flash-messages')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Stripe Connect Settings</h5>
        </div>

        <div class="panel-body">
            <form action="{{ url('admin/users/bankdetails', base64_encode((string)$id)) }}" method="POST" id="frmadmin" class="form-horizontal">
                @csrf
                <input type="hidden" name="id" value="{{ $user->id ?? '' }}">

                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <fieldset>
                            <legend class="text-semibold">Verification Data</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Account Type:</label>
                                <div class="col-lg-9">
                                    <select name="business_type" id="UserBusinessType" class="form-control" required>
                                        <option value="individual" @selected(old('business_type', $user->business_type ?? '') == 'individual')>Individual</option>
                                        <option value="company" @selected(old('business_type', $user->business_type ?? '') == 'company')>Company</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" id="ssn_noblk" style="{{ old('business_type', $user->business_type ?? 'individual') == 'individual' ? '' : 'display: none;' }}">
                                <label class="col-lg-3 control-label text-semibold">SSN #:</label>
                                <div class="col-lg-9">
                                    <input type="text" name="ss_no" id="UserSsNo" maxlength="50" class="form-control" value="{{ old('ss_no', \App\Helpers\Legacy\Security::decrypt($user->ss_no ?? '')) }}" placeholder="Enter Social Security Number">
                                    <span class="help-block text-muted">Required for individual verification.</span>
                                </div>
                            </div>

                            <div class="form-group" id="ein_noblk" style="{{ old('business_type', $user->business_type ?? 'individual') == 'company' ? '' : 'display: none;' }}">
                                <label class="col-lg-3 control-label text-semibold">EIN #:</label>
                                <div class="col-lg-9">
                                    <input type="text" name="ein_no" id="UserEinNo" maxlength="50" class="form-control" value="{{ old('ein_no', \App\Helpers\Legacy\Security::decrypt($user->ein_no ?? '')) }}" placeholder="Enter Employer Identification Number">
                                    <span class="help-block text-muted">Required for company verification.</span>
                                </div>
                            </div>
                        </fieldset>

                        <div class="text-center mt-20">
                            <hr>
                            
                            @if(!empty($user->stripe_key))
                                <div class="btn-group mb-20">
                                    <button type="button" class="btn btn-info" onClick="getStripeLogin('{{ $user->stripe_key }}')">
                                        <i class="icon-stripe position-left"></i> Login To Stripe Account
                                    </button>
                                    <button type="button" class="btn btn-danger" onClick="getPayoutSchedule('{{ $user->stripe_key }}')">
                                        <i class="icon-calendar52 position-left"></i> Update Payout Schedule
                                    </button>
                                </div>
                                <br>
                            @endif

                            <button type="button" class="btn btn-primary btn-xlg readyforconnect">
                                <i class="icon-link position-left"></i> Connect Stripe Account
                            </button>
                            <a href="{{ url('admin/users/index') }}" class="btn btn-default btn-xlg ml-10">Return</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payout Schedule Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Loaded via AJAX -->
            <div class="modal-body text-center p-20">
                <i class="icon-spinner2 spinner text-muted"></i> Loading schedule...
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Account Type Toggle
    $("#UserBusinessType").change(function() {
        if ($(this).val() == 'individual') {
            $("#ein_noblk").slideUp('fast');
            $("#ssn_noblk").slideDown('fast');
        } else {
            $("#ssn_noblk").slideUp('fast');
            $("#ein_noblk").slideDown('fast');
        }
    });

    // Stripe Connect Form Submission
    var $form = $("#frmadmin");
    $(".readyforconnect").click(function() {
        // Basic validation check
        var busType = $("#UserBusinessType").val();
        if (busType === 'individual' && !$("#UserSsNo").val()) {
            alert('SSN is required for individual accounts.');
            return;
        }
        if (busType === 'company' && !$("#UserEinNo").val()) {
            alert('EIN is required for company accounts.');
            return;
        }

        var btn = $(this);
        var originalHtml = btn.html();
        btn.html('<i class="icon-spinner2 spinner position-left"></i> Connecting...').prop('disabled', true);

        $.post("{{ url('admin/users/getmystripeurl') }}", $form.serialize(), function(data) {
            if (data.status) {
                btn.html('<i class="icon-checkmark3 position-left"></i> Redirecting to Stripe...').addClass('btn-success');
                setTimeout(function() {
                    window.open(data.result.url);
                    window.location.href = "{{ url('admin/users/index') }}";
                }, 1000);
            } else {
                alert(data.message || 'There was a problem generating the Stripe connection URL.');
                btn.html(originalHtml).prop('disabled', false);
            }
        }, 'json').fail(function() {
            alert('An error occurred during communication with the server.');
            btn.html(originalHtml).prop('disabled', false);
        });
    });
});

function getStripeLogin(stripekey) {
    $.post("{{ url('admin/users/getstripeloginurl') }}", {
        stripekey: stripekey,
        _token: '{{ csrf_token() }}'
    }, function(data) {
        if (data.status == 'success') {
            window.open(data.url);
        } else {
            alert(data.message);
        }
    }, 'json');
}

function getPayoutSchedule(stripekey) {
    $("#myModal .modal-content").load("{{ url('admin/users/loadPayoutSchedule') }}", {
        stripekey: stripekey,
        _token: '{{ csrf_token() }}'
    }, function() {
        $("#myModal").modal('show');
    });
}

function savePayoutSchedule() {
    var $form = $("#payoutfrmadmin");
    $.post("{{ url('admin/users/updatePayoutSchedule') }}", $form.serialize(), function(data) {
        alert(data.message);
        $("#myModal").modal('hide');
    }, 'json');
}
</script>

<style>
.btn-xlg {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 500;
}
</style>
@endsection