@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
<div class="panel">
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>
    <script>
        jQuery(document).ready(function () {
            jQuery('#UserEinNo').mask('00-000-0000', {placeholder: 'xx-xxx-xxxx'});
            jQuery('#UserSsNo').mask('000-00-0000', {placeholder: 'xxx-xx-xxxx'});

            jQuery('#UserBusinessType').change(function () {
                if (jQuery(this).val() === 'individual') {
                    jQuery('#ein_noblk').hide();
                    jQuery('#ssn_noblk').show();
                } else {
                    jQuery('#ssn_noblk').hide();
                    jQuery('#ein_noblk').show();
                }
            }).trigger('change');

            jQuery('.readyforconnect').click(function () {
                alert('Stripe connect flow is not migrated in Laravel yet. Use legacy flow for now.');
            });
        });

        function getStripeLogin() {
            alert('Stripe login link is not migrated in Laravel yet.');
        }
        function getPayoutSchedule(stripekey) {
            jQuery('#myModal .modal-content').load('/admin/users/loadPayoutSchedule', {stripekey: stripekey}, function () {
                jQuery('#myModal').modal('show');
            });
        }
        function savePayoutSchedule() {
            alert('Payout schedule update is not migrated in Laravel yet.');
        }
    </script>

    <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
        <h3 style="width: 80%; float: left;">{{ $listTitle }}</h3>
    </section>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <fieldset class="col-lg-12">
            <form action="/admin/users/bankdetails/{{ base64_encode((string)$user->id) }}" method="POST" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Account Type :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <select name="User[business_type]" id="UserBusinessType" class="form-control required">
                                <option value="individual" @selected(($user->business_type ?? 'individual') === 'individual')>Individual</option>
                                <option value="company" @selected(($user->business_type ?? '') === 'company')>Company</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="ssn_noblk">
                        <label class="col-lg-2 control-label">SSN # :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <input type="text" id="UserSsNo" name="User[ss_no]" class="form-control required" value="{{ $user->ss_no ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group" id="ein_noblk" style="display:none;">
                        <label class="col-lg-2 control-label">EIN # :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <input type="text" id="UserEinNo" name="User[ein_no]" class="form-control required" value="{{ $user->ein_no ?? '' }}">
                        </div>
                    </div>

                    @if (!empty($user->stripe_key))
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="button" class="btn left-margin btn-cancel" onclick="getStripeLogin('{{ $user->stripe_key }}')">Login To Stripe Account</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="button" class="left-margin btn-danger" onclick="getPayoutSchedule('{{ $user->stripe_key }}')">Update Payout Schedule</button>
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn readyforconnect">Connect</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="window.location.href='/admin/users/index'">Return</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="User[id]" value="{{ $user->id }}">
            </form>
        </fieldset>
    </div>
</div>
@endsection
