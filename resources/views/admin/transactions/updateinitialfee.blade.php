@extends('admin.layouts.app')

@php
    $title ??= 'Update Initial Fee';
    $csorder ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            @if(!empty($csorder))
                <form id="ReportAdminUpdateinitialfeeForm" class="form-horizontal">
                    @csrf

                    <fieldset class="col-lg-12">
                        <div class="panel-body">
                            <div class="form-group">
                                <h3>
                                    <div>Fixed Amount Transaction Details : </div>
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
                                        Paid Amount :
                                    </label>
                                    <div class="col-lg-6">
                                        {{ data_get($csorder, 'initial_fee', '') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Tax :
                                    </label>
                                    <div class="col-lg-6">
                                        {{ data_get($csorder, 'initial_fee_tax', '') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        New Amount :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="CsOrder[newtotal]" id="CsOrderNewtotal"
                                            class="number form-control digit required"
                                            value="{{ data_get($csorder, 'initial_fee', '') }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4"></label>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-primary"
                                            onClick="adjustInitialfee('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                            Proceed
                                        </button>
                                    </div>

                                    @if(
                                            !empty(data_get($csorder, 'initial_fee', ''))
                                            || !empty(data_get($csorder, 'initial_fee_tax', ''))
                                        )
                                        <div class="col-lg-2">
                                            <button type="button" class="btn btn-danger btn-ladda btn-ladda-progress"
                                                onClick="initialfeeRefund('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                                Refund Total
                                            </button>
                                        </div>
                                    @endif

                                </div>
                            </fieldset>

                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="button" class="btn left-margin btn-cancel"
                                        onClick="goBack('/admin/transactions/updatetransaction/{{ base64_encode(data_get($csorder, 'id', ''))}}')">
                                        Go Back
                                    </button>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="CsOrder[id]" value="{{ data_get($csorder, 'id', '') }}">
                </form>
            @endif
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function () {
            $("#ReportAdminUpdateinitialfeeForm").validate();
        });

        function adjustInitialfee(orderid) {
            if (orderid.length > 0 && $("#ReportAdminUpdateinitialfeeForm").valid()) {
                var conf = confirm('Are you sure you want to adjust amount ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    var params = $("#ReportAdminUpdateinitialfeeForm").serialize();
                    $.post(SITE_URL + "admin/transactions/adjustinitialfee", params, function (data) {
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

        function initialfeeRefund(orderid) {
            if (orderid.length > 0) {
                var conf = confirm('Are you sure you want to full refund ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    $.post(SITE_URL + "admin/transactions/initialfeeRefund", {
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