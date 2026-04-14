@extends('layouts.admin')
@section('title', 'Issue Virtual Credit Card')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Issue Virtual</span> - Credit Card</h4>
        </div>
        <div class="heading-elements">
            <button type="button" class="btn left-margin btn-cancel" onClick="goBack('/admin/airwallex/airwallex_credits/index')">Return</button>
        </div>
    </div>
</div>
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form method="POST" action="{{ url('admin/airwallex/airwallex_credits/issuecard') }}" class="form-horizontal">
    @csrf
    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6 col-sm-12">
                    <legend class="text-size-large text-bold">Card Details</legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Name :</label>
                        <div class="col-lg-8 control-label">{{ $user->first_name }} {{ $user->last_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Available Deposits :</label>
                        <div class="col-lg-8 control-label">${{ number_format($totalDeposit, 2) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Card amount :<span class="text-danger">*</span></label>
                        <div class="col-lg-8 control-label">
                            <input type="text" name="AirwallexCredit[amount]" class="form-control number required">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-sm-12">
                    <legend class="text-bold"><center>Card Details</center></legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-right">Card Number :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <input type="text" name="AirwallexCredit[card_number]" id="AirwallexCreditCardNumber" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-right">Expiry :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <input type="text" name="AirwallexCredit[exp]" id="AirwallexCreditExp" maxlength="5" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-right">CVV :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <input type="text" name="AirwallexCredit[cvv]" id="AirwallexCreditCvv" maxlength="4" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-sm-12">
                    <legend class="text-bold"><center>Balance Capture Setting</center></legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-right">Capture As :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="AirwallexCredit[chargetype]" id="AirwallexCreditChargetype" class="form-control">
                                <option value="lumpsum">Lumpsum</option>
                                <option value="installment">Installment</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group installment" style="display:none;">
                        <label class="col-lg-4 control-label text-right">Installment Type :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="AirwallexCredit[installment_type]" class="form-control">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label text-right">Week Day :</label>
                        <div class="col-lg-8">
                            <select name="AirwallexCredit[installment_day]" class="form-control">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group installment" style="display:none;">
                        <label class="col-lg-4 control-label text-right">Installment :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <input type="text" name="AirwallexCredit[installment]" class="digit form-control required">
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 col-sm-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-4">
                            <button type="submit" class="btn left-margin btn-primary w-100">Process</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="AirwallexCredit[current_limit]" value="{{ $totalDeposit }}">
    <input type="hidden" name="AirwallexCredit[user_id]" value="{{ $user->id }}">
</form>

<script src="{{ asset('js/jquery.maskedinput.js') }}"></script>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery("#AirwallexCreditAdminIssueForm").validate();
    $("#AirwallexCreditChargetype").change(function() {
        $(".installment").toggle($(this).val() === 'installment');
    });
    jQuery('#AirwallexCreditCardNumber').mask("9999-9999-9999-9999",{placeholder:"xxxx-xxxx-xxxx-xxxx"});
    jQuery('#AirwallexCreditExp').mask("99/99",{placeholder:"xx/xx"});
    jQuery('#AirwallexCreditCvv').mask("9999",{placeholder:"xxxx"});
});
</script>
@endsection
