@extends('layouts.main')

@section('content')
<script src="{{ asset('assets/js/plugins/forms/inputs/formatter.min.js') }}"></script>

<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#InsurancePayerTokenCreditCardNumber').formatter({
            pattern: '{{9999}} - {{9999}} - {{9999}} - {{9999}}'
        });
        jQuery('#InsurancePayerTokenExpiration').formatter({
            pattern: '{{99}}/{{9999}}'
        });
        jQuery('#InsurancePayerTokenCvv').formatter({
            pattern: '{{9999}}'
        });
        
        jQuery("#InsurancePayerTokenCreditCardNumber").focusout(function(){
            var cctype=GetCardType(jQuery(this).val());
            jQuery("#InsurancePayerTokenCardType").val(cctype);
        });
        jQuery("#frmadmin").validate();
    });
    function GetCardType(number)
        {   number=number.replace(/-/g,"");
            number=number.replace(/ /g,"");
            
            // visa
            var re = new RegExp("^4");
            if (number.match(re) != null)
                return "Visa";

            // Mastercard 
            // Updated for Mastercard 2017 BINs expansion
             if (/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/.test(number)) 
                return "Mastercard";

            // AMEX
            re = new RegExp("^3[47]");
            if (number.match(re) != null)
                return "AMEX";

            // Discover
            re = new RegExp("^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)");
            if (number.match(re) != null)
                return "Discover";

            // Diners
            re = new RegExp("^36");
            if (number.match(re) != null)
                return "Diners";

            // Diners - Carte Blanche
            re = new RegExp("^30[0-5]");
            if (number.match(re) != null)
                return "Diners";

            // JCB
            re = new RegExp("^35(2[89]|[3-8][0-9])");
            if (number.match(re) != null)
                return "JCB";

            // Visa Electron
            re = new RegExp("^(4026|417500|4508|4844|491(3|7))");
            if (number.match(re) != null)
                return "Visa";

            return "";
        }   
</script>
    <!--heading starts-->
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle }}</span></h4>
        </div>
    </div>
</div>
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>

<div class="panel">
    <div class="panel-body">
        <form action="{{ url('insurance/payer_tokens/add') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
        @csrf

            <div class="form-group">
                <label class="col-lg-2 control-label">Card Holder Name :<span class="text-danger">*</span></label>
               
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[card_holder_name]" id="InsurancePayerTokenCardHolderName" maxlength="50" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Credit Card # :<span class="text-danger">*</span></label>
                <div class="col-lg-4" >
                    <input type="text" name="InsurancePayerToken[credit_card_number]" id="InsurancePayerTokenCreditCardNumber" maxlength="25" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Card Type :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="InsurancePayerToken[card_type]" id="InsurancePayerTokenCardType" class=" form-control required">
                        <option value="Visa">Visa</option>
                        <option value="Mastercard">Master Card</option>
                        <option value="Mastro">Mastro</option>
                        <option value="Dinners">Dinners</option>
                        <option value="AMEX">AMEX</option>
                        <option value="Discover">Discover</option>
                        <option value="JCB">JCB</option>
                    </select>
                </div>
             </div>
            
            <div class="form-group">
                <label class="col-lg-2 control-label">Expiry Date :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[expiration]" id="InsurancePayerTokenExpiration" maxlength="8" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">CVV :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[cvv]" id="InsurancePayerTokenCvv" maxlength="4" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Address :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[address]" id="InsurancePayerTokenAddress" maxlength="150" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">City :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[city]" id="InsurancePayerTokenCity" maxlength="50" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">State :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <select name="InsurancePayerToken[state]" id="InsurancePayerTokenState" class="form-control required">
                        @foreach($states as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Postal Code :<span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="InsurancePayerToken[zip]" id="InsurancePayerTokenZip" maxlength="50" class="form-control required" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Make me Default :</label>
                <div class="col-lg-4">
                    <input type="checkbox" name="InsurancePayerToken[default]" id="InsurancePayerTokenDefault" value="1" />
                </div>
            </div>
            
            
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                <button type="submit" class="btn">Save</button>
                   
                <button type="button" class="btn left-margin btn-cancel" onClick="goBack('/insurance/payer_tokens/index')">Return</button>
                </div>
           </div>
        </div>
        </form>
   </div>
</div>
@endsection
