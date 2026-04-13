@extends('layouts.admin')

@section('content')
<div class="panel">
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("#frmadmin").validate();
            // jQuery('#UserEinNo').mask("00-000-0000",{placeholder: "xx-xxx-xxxx"});
            // jQuery('#UserSsNo').mask("000-00-0000",{placeholder: "xxx-xx-xxxx"});

            jQuery("#UserBusinessType").change(function() {
                if (jQuery(this).val() == 'individual') {
                    jQuery("#ein_noblk").hide();
                    jQuery("#ssn_noblk").show();
                } else {
                    jQuery("#ssn_noblk").hide();
                    jQuery("#ein_noblk").show();
                }
            });
            var $fromsec = jQuery("#frmadmin");
            jQuery(".readyforconnect").click(function() {
                if (!$fromsec.valid()) {
                    return;
                }
                var formData = $fromsec.serialize();
                $fromsec.find('.readyforconnect').html('Processing <i class="fa fa-spinner fa-pulse"></i>').prop('disabled', true);

                $.post("{{ url('admin/users/getmystripeurl') }}", formData, function(data) {
                        if (data.status) {
                            $fromsec.find('.readyforconnect').html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
                            window.open(data.result.url);
                            document.location.href = "{{ url('admin/users/index') }}";
                        } else {
                            $fromsec.find('.readyforconnect').html('There was a problem').removeClass('success').addClass('error');
                        }
                    }, 'json')
                    .done(function(data, textStatus, jqXHR) {
                        if (data.status) {
                            $fromsec.find('.readyforconnect').html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
                        }
                    });
            });
        });
    </script>

    <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
        <h3 style="width: 80%; float: left;">{{ $listTitle }}</h3>
    </section>

    <div class="row">
        @includeif('common.flash-messages')
    </div>

    <div class="row">
        <fieldset class="col-lg-12">
            <form action="{{ url('admin/users/bankdetails/' . base64_encode($id)) }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Account Type :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <select name="business_type" id="UserBusinessType" class="form-control required">
                                <option value="individual" {{ old('business_type', $user->business_type ?? '') == 'individual' ? 'selected' : '' }}>Individual</option>
                                <option value="company" {{ old('business_type', $user->business_type ?? '') == 'company' ? 'selected' : '' }}>Company</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="ssn_noblk" style="{{ old('business_type', $user->business_type ?? 'individual') == 'individual' ? '' : 'display: none;' }}">
                        <label class="col-lg-2 control-label">SSN # :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <input type="text" name="ss_no" id="UserSsNo" maxlength="50" class="form-control required" value="{{ old('ss_no', \App\Helpers\Legacy\Security::decrypt($user->ss_no ?? '')) }}">
                        </div>
                    </div>

                    <div class="form-group" id="ein_noblk" style="{{ old('business_type', $user->business_type ?? 'individual') == 'company' ? '' : 'display: none;' }}">
                        <label class="col-lg-2 control-label">EIN # :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <input type="text" name="ein_no" id="UserEinNo" maxlength="50" class="form-control required" value="{{ old('ein_no', \App\Helpers\Legacy\Security::decrypt($user->ein_no ?? '')) }}">
                        </div>
                    </div>

                    @if(!empty($user->stripe_key))
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn left-margin btn-cancel" onClick="getStripeLogin('{{ $user->stripe_key }}')">Login To Stripe Account</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn left-margin btn-danger" onClick="getPayoutSchedule('{{ $user->stripe_key }}')">Update Payout Schedule</button>
                        </div>
                    </div>
                    @endif

                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn readyforconnect btn-primary">Connect</button>
                            <button type="button" class="btn left-margin btn-cancel" onClick="window.location='{{ url('admin/users/index') }}'">Return</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="id" value="{{ $user->id ?? '' }}">
            </form>
        </fieldset>
    </div>
</div>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
        </div>
    </div>
</div>

<script type="text/javascript">
    function getStripeLogin(stripekey) {
        // Block UI mock
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
        }, function(data) {
            $("#myModal").modal('show');
        });
    }

    function savePayoutSchedule() {
        $.post("{{ url('admin/users/updatePayoutSchedule') }}", $("#payoutfrmadmin").serialize(), function(data) {
            alert(data.message);
            $("#myModal").modal('hide');
        }, 'json');
    }
</script>
@endsection