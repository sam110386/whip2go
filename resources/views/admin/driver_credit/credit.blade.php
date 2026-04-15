@extends('admin.layouts.app')
@section('title', 'Credit To Driver')
@section('content')
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Credit</span> - To Driver</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <div class="panel-body">
        <div class="row">
            <form id="ReportAdminCreditForm" method="POST" action="{{ url('admin/driver_credit/records/credit') }}" class="form-horizontal">
                @csrf
                <fieldset class="col-lg-8">
                    <div class="form-group">
                        <label class="col-lg-3 control-label"><strong>Driver# :</strong> <span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="DriverCredit[renter_id]" id="DriverCreditRenterId" class="formcontrol required" style="width:100%;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label"><strong>Source Dealer# :</strong> <span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="DriverCredit[dealer_id]" id="DriverCreditDealerId" class="formcontrol required" style="width:100%;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Credit To Driver: <span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="DriverCredit[amount]" id="DriverCreditAmount" class="form-control number required">
                        </div>
                        <label class="col-lg-2 control-label"><em>(Plus {{ $stripeFee }}% Extra)</em></label>
                    </div>
                    <div class="form-group total">
                        <label class="col-lg-3 control-label"></label>
                        <div class="col-lg-7"><span id="total"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Credit Note: </label>
                        <div class="col-lg-7">
                            <input type="text" name="DriverCredit[note]" class="form-control required" placeholder="Note If Any">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-2">
                            <button type="button" class="btn left-margin btn-primary" id="Process">Process</button>
                        </div>
                        <div class="col-lg-8">
                            <button type="button" class="btn left-margin btn-cancel pull-right" onClick="goBack('/admin/driver_credit/records/index')">Go Back</button>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#ReportAdminCreditForm").validate({ignore: []});
        $("#Process").click(function(){
            if($("#ReportAdminCreditForm").valid()){
                jQuery.blockUI({
                    message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                    css: {'z-index': '9999'}
                });
                var formdata = $("#ReportAdminCreditForm").serialize();
                $.post(SITE_URL + 'admin/driver_credit/records/processcedit', formdata, function (resp) {
                    jQuery.unblockUI();
                    if (resp.status=='success') {
                        goBack('/admin/driver_credit/records/index');
                    } else {
                        alert(resp.message);
                    }
                }, 'json');
            }
        });
    });
    function format(item) { return item.tag; }
    jQuery(document).ready(function () {
        jQuery("#DriverCreditRenterId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Driver ", minimumInputLength: 1,
            ajax: { url: "{{ config('app.url') }}admin/bookings/customerautocomplete", dataType: "json", type: "GET",
                data: function (params) { return {term: params} },
                processResults: function (data) { return { results: jQuery.map(data, function (item) { return {tag: item.tag, id: item.id} }) }; }
            }
        });
        jQuery("#DriverCreditDealerId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Dealer ", minimumInputLength: 1,
            ajax: { url: "{{ config('app.url') }}admin/bookings/customerautocomplete", dataType: "json", type: "GET",
                data: function (params) { return {term: params, is_dealer: true} },
                processResults: function (data) { return { results: jQuery.map(data, function (item) { return {tag: item.tag, id: item.id} }) }; }
            }
        });
        var stripeFee = '{{ $stripeFee }}';
        jQuery("#DriverCreditAmount").keyup(function(){
            var temp = jQuery(this).val();
            temp = parseFloat(temp) + parseFloat(temp * stripeFee / 100);
            jQuery(".total #total").html("TOTAL CHARGE :" + temp.toFixed(2));
        });
    });
</script>
@endsection
