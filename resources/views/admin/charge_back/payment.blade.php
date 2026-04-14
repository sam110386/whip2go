@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
    });
</script>
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#ChargeBackDealerId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params, "is_dealer": true}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag, id: item.id}
                        })
                    };
                }
            }
        });
        jQuery("#ChargeBackUserId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params, "is_dealer": false}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag, id: item.id}
                        })
                    };
                }
            }
        });
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Create-</span> Charge</h4>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="row">
        <form action="{{ url('admin/charge_back/dealer_chargebacks/payment') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                <legend class="text-size-large text-bold">Card Details</legend>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Dealer :<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="ChargeBack[dealer_id]" id="ChargeBackDealerId" class="required" style="width:100%">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Amount :<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="ChargeBack[amt]" class="form-control number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Driver :</label>
                        <div class="col-lg-7">
                            <input type="text" name="ChargeBack[user_id]" id="ChargeBackUserId" style="width:100%">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Note :</label>
                        <div class="col-lg-7">
                            <textarea name="ChargeBack[note]" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Proceed</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/charge_back/dealer_chargebacks/index')">Return</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate()
    });
</script>
@endsection
