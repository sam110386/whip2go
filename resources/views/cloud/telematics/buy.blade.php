@extends('layouts.main')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
    });
</script>

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Buy-</span> Telematics</h4>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="row">
        <form action="/telematics/subscriptions/buy" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                @if(isset($unit) && isset($subcriptionamt))
                <div class="col-lg-7">
                    <legend class="text-size-large text-bold">Card Details</legend>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-5 control-label">Card Holder Name :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[card_holder_name]" size="30" maxlength="50" class="form-control required" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">Credit Card # :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[credit_card_number]" id="UserCcTokenCreditCardNumber" size="30" maxlength="25" class="form-control required">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">Card Type :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <select name="UserCcToken[card_type]" id="UserCcTokenCardType" class="form-control required">
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
                            <label class="col-lg-5 control-label">Expiry Date :</label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[expiration]" id="UserCcTokenExpiration" size="30" maxlength="8" class="form-control required">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">CVV :</label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[cvv]" id="UserCcTokenCvv" size="30" maxlength="4" class="form-control required">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-5 control-label">Address :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[address]" maxlength="150" class="form-control required" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">City :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[city]" maxlength="50" class="form-control required" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">State :<span class="text-danger">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[state]" class="form-control required" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-5 control-label">Postal Code :</label>
                            <div class="col-lg-7">
                                <input type="text" name="UserCcToken[zip]" size="30" maxlength="50" class="form-control" value="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <legend class="text-size-large text-bold">Total</legend>
                    <input type="hidden" name="TelematicsSubscription[units]" value="{{ $unit }}">
                    <input type="hidden" name="TelematicsSubscription[sale]" value="1">
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">Equipment (One Time):</label>
                        <div class="col-lg-2 control-label">${{ $subcriptionamt }}</div>
                        <div class="col-lg-5">
                            <span class="help-block">(${{ config('legacy.TELEMATICUNITPRICE', 0) }}x{{ $unit }})</span>
                        </div>
                    </div>
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">First Month Service:</label>
                        <div class="col-lg-2 control-label">${{ $monthlyServices }}</div>
                        <div class="col-lg-4">
                            <span class="help-block">(${{ config('legacy.TELEMATICUNITMONTHSERVICE', 0) }}x{{ $unit }})</span>
                        </div>
                    </div>
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">Last Month Service:</label>
                        <div class="col-lg-2 control-label">${{ $monthlyServices }}</div>
                        <div class="col-lg-4">
                            <span class="help-block">(${{ config('legacy.TELEMATICUNITMONTHSERVICE', 0) }}x{{ $unit }})</span>
                        </div>
                    </div>
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">Subtotal:</label>
                        <div class="col-lg-2 control-label">${{ $subtotal }}</div>
                    </div>
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">Tax:</label>
                        <div class="col-lg-2 control-label">${{ $tax }}</div>
                    </div>
                    <div class="formgroup pull-left" style="width:100%">
                        <label class="col-lg-5 control-label text-bold text-right">Shipping:</label>
                        <div class="col-lg-2 control-label">${{ $shipping }}</div>
                    </div>
                    <div class="form-group pull-left">
                        <label class="col-lg-5 control-label text-bold text-right">Total Amount:</label>
                        <div class="col-lg-2 control-label">${{ $total }}</div>
                        <div class="col-lg-5">
                            <span class="help-block">(Total Payable Amount Today)</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn" onclick="makePayment()">Make Payment</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/telematics/subscriptions/index')">Return</button>
                        </div>
                    </div>
                </div>
                @else
                <div class="col-lg-12">
                    <p><strong>Total Vision Dealer Package</strong></p>
                    <p>$13.99 per month/per unit</p>
                    <p><strong>&middot;1st and Last Month</strong></p>
                    <p>2 months reserves required. Fully refundable &bull; Must return equipment to end service</p>
                    <p><strong>&middot;OBDII + Starter Interupt</strong></p>
                    <p>One time charge of $45 per OBDII and Hardwired GPS unit. Fleet Plug-n-Play combination quickly identifies the vehicle by VIN, monitor vehicle diagnostics and utilize starter interrupt. Ideal for quick transfers of GPS unit from one vehicle to the next, without the need to manually input the GPS unit # and attached it to a new vehicle in the system. Added benefit of grabbing odometer at install and not just tracking GPS travel distances. Ability to grab fuel levels and other fleet advantages (dependent on OEM).</p>
                    <p><strong>&middot;Payments</strong></p>
                    <p>Credit Card Required: We will charge your account monthly for each device automatically.</p>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label">No. of Units :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="number" name="TelematicsSubscription[units]" maxlength="3" step="1" class="form-control digits required">
                            <span class="help-block">(Please enter # of unit you want to buy)</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Proceed</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/telematics/subscriptions/index')">Return</button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>
<script src="{{ legacy_asset('js/assets/js/plugins/forms/inputs/formatter.min.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#UserCcTokenCreditCardNumber').formatter({ pattern: '{{9999}} - {{9999}} - {{9999}} - {{9999}}' });
        jQuery('#UserCcTokenExpiration').formatter({ pattern: '{{99}}/{{9999}}' });
        jQuery('#UserCcTokenCvv').formatter({ pattern: '{{9999}}' });
        jQuery("#UserCcTokenCreditCardNumber").focusout(function(){
            var cctype = GetCardType(jQuery(this).val());
            jQuery("#UserCcTokenCardType").val(cctype);
        });
    });
    function GetCardType(number) {
        number = number.replace(/-/g,"").replace(/ /g,"");
        var re = new RegExp("^4");
        if (number.match(re) != null) return "Visa";
        if (/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/.test(number)) return "Mastercard";
        re = new RegExp("^3[47]");
        if (number.match(re) != null) return "AMEX";
        re = new RegExp("^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)");
        if (number.match(re) != null) return "Discover";
        re = new RegExp("^36");
        if (number.match(re) != null) return "Diners";
        re = new RegExp("^30[0-5]");
        if (number.match(re) != null) return "Diners";
        re = new RegExp("^35(2[89]|[3-8][0-9])");
        if (number.match(re) != null) return "JCB";
        re = new RegExp("^(4026|417500|4508|4844|491(3|7))");
        if (number.match(re) != null) return "Visa";
        return "";
    }
    function showUIBlocker(ele){
        $(ele).block({ message: '<i class="icon-spinner4 spinner"></i>', overlayCSS: { backgroundColor: '#fff', opacity: 0.8, cursor: 'wait' }, css: {border: 0, padding: 0, backgroundColor: 'transparent'} });
    }
    function makePayment(){
        if(jQuery("#frmadmin").valid()){
            var container = $("#frmadmin").parent();
            showUIBlocker(container);
            let formdata = jQuery("#frmadmin").serialize();
            jQuery.post('{{ config("app.url") }}/telematics/subscriptions/buypayment', formdata, function(resp){
                if(resp.status=='success'){
                    alert('Your subscription is created successfully. We will contact you shortly.');
                    goBack('/telematics/subscriptions/index');
                } else {
                    alert(resp.message);
                }
            },'json').done(function(){ $(container).unblock(); });
        }
    }
</script>
@endsection
