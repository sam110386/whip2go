@extends('admin.layouts.app')

@php
    $title ??= 'Connect with Stripe';
    $user ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">

        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;"> {{ $title }} </h3>
        </section>

        <div class="row">
            @include('partials.flash')
        </div>

        <div class="row">
            <fieldset class="col-lg-12">
                <form action="{{ url('admin/users/bankdetails/', base64_encode(data_get($user, 'id'))) }}" method="POST"
                    id="frmadmin" class="form-horizontal">
                    @csrf

                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Account Type: <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-4">
                                <select name="User[business_type]" id="UserBusinessType" class="form-control required">
                                    <option value="individual" @selected(old('business_type', data_get($user, 'business_type', 'individual')) === 'individual')>
                                        Individual
                                    </option>
                                    <option value="company" @selected(old('business_type', data_get($user, 'business_type', 'individual')) === 'company')>
                                        Company
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="ssn_noblk"
                            style="{{ old('business_type', data_get($user, 'business_type', 'individual')) === 'individual' ? '' : 'display: none;' }}">
                            <label class="col-lg-2 control-label">
                                SSN #: <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-4">
                                <input type="text" name="User[ss_no]" id="UserSsNo" maxlength="50"
                                    class="form-control required"
                                    value="{{ old('ss_no', \App\Helpers\Legacy\Security::decrypt(data_get($user, 'ss_no', ''))) }}"
                                    placeholder="xxx-xx-xxxx">
                            </div>
                        </div>

                        <div class="form-group" id="ein_noblk"
                            style="{{ old('business_type', data_get($user, 'business_type', 'individual')) == 'company' ? '' : 'display: none;' }}">
                            <label class="col-lg-2 control-label">
                                EIN #: <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-4">
                                <input type="text" name="User[ein_no]" id="UserEinNo" maxlength="50"
                                    class="form-control required"
                                    value="{{ old('ein_no', \App\Helpers\Legacy\Security::decrypt(data_get($user, 'ein_no', ''))) }}"
                                    placeholder="xx-xx-xxxx">
                            </div>
                        </div>

                        @if(!empty(data_get($user, 'stripe_key', '')))
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="button" class="btn left-margin btn-cancel"
                                        onClick="getStripeLogin('{{ data_get($user, 'stripe_key', '') }}')">
                                        Login To Stripe Account
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="button" class="left-margin btn-danger"
                                        onClick="getPayoutSchedule('{{ data_get($user, 'stripe_key', '') }}')">
                                        Update Payout Schedule
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="button" class="btn readyforconnect">
                                    Connect
                                </button>
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/users/index')">
                                    Return
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="User[id]" value="{{ data_get($user, 'id', '') }}">
                </form>
            </fieldset>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            jQuery("#frmadmin").validate();
            jQuery('#UserEinNo').mask("00-000-0000", { placeholder: "xx-xxx-xxxx" });
            jQuery('#UserSsNo').mask("000-00-0000", { placeholder: "xxx-xx-xxxx" });

            jQuery("#UserBusinessType").change(function () {
                if (jQuery(this).val() == 'individual') {
                    jQuery("#ein_noblk").hide();
                    jQuery("#ssn_noblk").show();
                } else {
                    jQuery("#ssn_noblk").hide();
                    jQuery("#ein_noblk").show();
                }
            });

            var $fromsec = jQuery("#frmadmin");
            jQuery(".readyforconnect").click(function () {
                if (!$fromsec.valid()) {
                    return;
                }
                var formData = $fromsec.serialize();
                $fromsec.find('.readyforconnect')
                    .html('Processing <i class="fa fa-spinner fa-pulse"></i>')
                    .prop('disabled', true);
                $.post("{{ url('admin/users/getmystripeurl') }}", formData, function (data) {
                    if (data.status) {
                        $fromsec.find('.readyforconnect')
                            .html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
                        window.open(data.result.url);
                        document.location.href = "{{ url('admin/users/index') }}";
                    } else {
                        $fromsec.find('.readyforconnect')
                            .html('There was a problem')
                            .removeClass('success')
                            .addClass('error');

                    }
                }, 'json').done(function (data, textStatus, jqXHR) {
                    $fromsec.find('.readyforconnect')
                        .html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
                });
            });
        });

        function getStripeLogin(stripekey) {
            jQuery.blockUI({
                message: '<h1><img src="' + "{{ legacy_asset('img/select2-spinner.gif') }}" + '" /> Sending...</h1>',
                css: { 'z-index': '9999' }
            });
            $.post("{{ url('admin/users/getstripeloginurl') }}", { stripekey: stripekey }, function (data) {
                jQuery.unblockUI();
                if (data.status == 'success') {
                    window.open(data.url);
                } else {
                    alert(data.message);
                }

            }, 'json');
        }
        function getPayoutSchedule(stripekey) {
            jQuery.blockUI({
                message: '<h1><img src="' + "{{ legacy_asset('img/select2-spinner.gif') }}" + '" /> Sending...</h1>',
                css: { 'z-index': '9999' }
            });
            $("#myModal .modal-content").load("{{ url('admin/users/loadPayoutSchedule') }}", { stripekey: stripekey }, function (data) {
                jQuery.unblockUI();
                $("#myModal").modal('show');
            });
        }

        function savePayoutSchedule() {
            jQuery.blockUI({
                message: '<h1><img src="' + "{{ legacy_asset('img/select2-spinner.gif') }}" + '" /> Sending...</h1>',
                css: { 'z-index': '9999' }
            });
            $.post("{{ url('admin/users/updatePayoutSchedule') }}", $("#payoutfrmadmin").serialize(), function (data) {
                jQuery.unblockUI();
                alert(data.message);
                $("#myModal").modal('hide');
            }, 'json');
        }
    </script>
@endpush