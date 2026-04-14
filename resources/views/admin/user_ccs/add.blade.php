@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Add CC Details')

@push('head_scripts')
    <script src="{{ legacy_asset('assets/js/plugins/forms/inputs/formatter.min.js') }}"></script>
@endpush

@section('content')
<div class="panel">
<script>
    jQuery(document).ready(function() {
        jQuery('#UserCcTokenCreditCardNumber').formatter({
            pattern: '{{9999}} - {{9999}} - {{9999}} - {{9999}}'
        });
        jQuery('#UserCcTokenExpiration').formatter({
            pattern: '{{99}}/{{9999}}'
        });
        jQuery('#UserCcTokenCvv').formatter({
            pattern: '{{9999}}'
        });
        jQuery('#UserCcTokenCreditCardNumber').focusout(function(){
            var cctype = GetCardType(jQuery(this).val());
            jQuery('#UserCcTokenCardType').val(cctype);
        });
        jQuery('#frmadmin').validate();
    });
    function GetCardType(number) {
        number = number.replace(/-/g, '');
        number = number.replace(/ /g, '');
        var re = new RegExp('^4');
        if (number.match(re) != null) return 'Visa';
        if (/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/.test(number)) return 'Mastercard';
        re = new RegExp('^3[47]');
        if (number.match(re) != null) return 'AMEX';
        re = new RegExp('^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)');
        if (number.match(re) != null) return 'Discover';
        re = new RegExp('^36');
        if (number.match(re) != null) return 'Diners';
        re = new RegExp('^30[0-5]');
        if (number.match(re) != null) return 'Diners';
        re = new RegExp('^35(2[89]|[3-8][0-9])');
        if (number.match(re) != null) return 'JCB';
        re = new RegExp('^(4026|417500|4508|4844|491(3|7))');
        if (number.match(re) != null) return 'Visa';
        return '';
    }
</script>
<section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
    <h3 style="width: 80%; float: left;">{{ $listTitle }}</h3>
</section>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
<div class="row">
    <fieldset class="col-lg-12">
        <form method="post" action="/admin/user_ccs/add/{{ $useridB64 }}" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                @php
                    $u = $user ?? null;
                    $fullName = trim((string) (($u->first_name ?? '') . ' ' . ($u->last_name ?? '')));
                @endphp
                <div class="form-group">
                    <label class="col-lg-2 control-label">Card Holder Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[card_holder_name]" maxlength="50" class="form-control required"
                               value="{{ old('UserCcToken.card_holder_name', $fullName) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Credit Card # :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[credit_card_number]" id="UserCcTokenCreditCardNumber" maxlength="25" class="form-control required" value="{{ old('UserCcToken.credit_card_number') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Card Type :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <select name="UserCcToken[card_type]" id="UserCcTokenCardType" class="form-control required">
                            @foreach(['Visa' => 'Visa', 'Mastercard' => 'Master Card', 'Mastro' => 'Mastro', 'Dinners' => 'Dinners', 'AMEX' => 'AMEX', 'Discover' => 'Discover', 'JCB' => 'JCB'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('UserCcToken.card_type', 'Visa') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Expiry Date :</label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[expiration]" id="UserCcTokenExpiration" maxlength="8" class="form-control required" value="{{ old('UserCcToken.expiration') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">CVV :</label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[cvv]" id="UserCcTokenCvv" maxlength="4" class="form-control required" value="{{ old('UserCcToken.cvv') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Address :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[address]" maxlength="150" class="form-control required" value="{{ old('UserCcToken.address', $u->address ?? '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">City :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[city]" maxlength="50" class="form-control required" value="{{ old('UserCcToken.city', $u->city ?? '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">State :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[state]" class="form-control required" value="{{ old('UserCcToken.state', $u->state ?? '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Postal Code :</label>
                    <div class="col-lg-4">
                        <input type="text" name="UserCcToken[zip]" maxlength="50" class="form-control" value="{{ old('UserCcToken.zip', $u->zip ?? '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Make me Default :</label>
                    <div class="col-lg-4">
                        <input type="checkbox" name="UserCcToken[default]" value="1" @checked(old('UserCcToken.default'))>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn left-margin btn-cancel" onclick="window.location.href='/admin/users/index'">Return</button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="UserCcToken[user_id]" value="{{ $useridB64 }}">
        </form>
    </fieldset>
</div>
</div>
@endsection
