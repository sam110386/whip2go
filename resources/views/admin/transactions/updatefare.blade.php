@extends('admin.layouts.app')

@php
    $title ??= 'Update Insurance';
    $csorder ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            @if($csorder)
                <form id="ReportAdminUpdatefareForm" class="form-horizontal">
                    @csrf
                    <fieldset class="col-lg-12">
                        <div class="panel-body">
                            <div class="form-group">
                                <h3>
                                    <div>Rent Transaction Details : </div>
                                </h3>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4">
                                    <strong>Job# :</strong>
                                </label>
                                <div class="col-lg-6">
                                    {{ data_get($csorder, 'increment_id', '') }}
                                </div>
                            </div>

                            <fieldset class="content-group">
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Total Paid Amount :
                                    </label>
                                    <div class="col-lg-6">
                                        {{ data_get($csorder, 'paid_amount', 'N/A') }}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Rent :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="rent" id="CsOrderRent"
                                            class="form-control number required calcu"
                                            value="{{ old('rent', data_get($csorder, 'rent', '')) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Tax :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="tax" id="CsOrderTax" class="form-control number required calcu"
                                            value="{{ old('tax', data_get($csorder, 'tax', '')) }}">
                                        <small><em>Tax Will be calculated dynamically</em></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Lateness Fee :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="lateness_fee" id="CsOrderLatenessFee"
                                            class="form-control number required calcu"
                                            value="{{ old('lateness_fee', data_get($csorder, 'lateness_fee', '')) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Damage Fee :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="damage_fee" id="CsOrderDamageFee"
                                            class="form-control number required calcu"
                                            value="{{ old('damage_fee', data_get($csorder, 'damage_fee', '')) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Uncleanness Fee :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="uncleanness_fee" id="CsOrderUncleannessFee"
                                            class="form-control number required calcu"
                                            value="{{ old('uncleanness_fee', data_get($csorder, 'uncleanness_fee', '')) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Grand Total :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="newtotal" id="CsOrderNewtotal"
                                            class="number form-control digit required"
                                            value="{{ data_get($csorder, 'paid_amount', '') }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4"></label>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-primary"
                                            onClick="adjustTotal('{{ base64_encode(data_get($csorder, 'id', ''))}}')">
                                            Proceed
                                        </button>
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-danger btn-ladda btn-ladda-progress"
                                            onClick="rentRefundtotal('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                            Refund Total
                                        </button>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="button" class="btn left-margin btn-cancel"
                                        onClick="goBack('/admin/transactions/updatetransaction/{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                        Go Back
                                    </button>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="id" value="{{ data_get($csorder, 'id', '') }}">
                </form>
            @endif
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function () {
            $("#ReportAdminUpdatefareForm").validate();

            $(".calcu").focusout(function () {
                var total = 0;
                $("input.calcu").each(function () {
                    var val = $(this).val() || 0;
                    total = parseFloat(total) + parseFloat(val);
                });
                $("#CsOrderNewtotal").val(total.toFixed(2));
            });
        });

        function adjustTotal(orderid) {
            if (orderid.length > 0 && $("#ReportAdminUpdatefareForm").valid()) {
                var conf = confirm('Are you sure you want to adjust rent ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    var params = $("#ReportAdminUpdatefareForm").serialize();
                    $.post(SITE_URL + "admin/transactions/adjustTotal", params, function (data) {
                        jQuery.unblockUI();
                        if (data.status == 'success') {
                            alert(data.message);
                            goBack("/admin/transactions/updatetransaction/" + orderid);
                        } else {
                            alert(data.message);
                        }
                    }, 'json');
                }
            }
        }

        function rentRefundtotal(orderid) {
            if (orderid.length > 0) {
                var conf = confirm('Are you sure you want to full refund ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });

                    $.post(SITE_URL + "admin/transactions/rentRefundtotal", {
                        "orderid": orderid,
                        "_token": "{{ csrf_token() }}"
                    }, function (data) {
                        jQuery.unblockUI();
                        if (data.status) {
                            alert(data.message);
                            goBack("/admin/transactions/updatetransaction/" + orderid);
                        } else {
                            alert(data.message);
                        }
                    }, 'json');
                }
            }
        }
    </script>

@endpush