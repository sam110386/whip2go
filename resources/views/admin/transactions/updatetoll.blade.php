@extends('admin.layouts.app')

@php
    $title ??= 'Update Toll';
    $totalPaid ??= '';
    $csorder ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="panel-body">
            @if($csorder)
                <form id="ReportAdminUpdatetollForm" class="form-horizontal">
                    @csrf

                    <div class="form-group">
                        <h3>
                            <div>Toll Transaction Details : </div>
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
                                {{ $totalPaid ?? 0 }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4">
                                New Toll :
                            </label>
                            <div class="col-lg-6">
                                <input type="text" name="CsOrder[toll]" id="CsOrderToll" class="form-control number required calcu"
                                    value="{{ old('toll', $totalPaid ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group">

                            <label class="col-lg-4"></label>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-primary"
                                    onClick="adjustToll('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                    Proceed
                                </button>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-danger btn-ladda btn-ladda-progress"
                                    onClick="tollRefundtotal('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
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

                    <input type="hidden" name="CsOrder[id]" value="{{ data_get($csorder, 'id', '') }}">
                </form>
            @endif
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function () {
            $("#ReportAdminUpdatetollForm").validate();
        });

        function adjustToll(orderid) {
            if (orderid.length > 0 && $("#ReportAdminUpdatetollForm").valid()) {
                var conf = confirm('Are you sure you want to adjust this value ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    var params = $("#ReportAdminUpdatetollForm").serialize();
                    $.post(SITE_URL + "admin/transactions/adjusttollfee", params, function (data) {
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

        function tollRefundtotal(orderid) {
            if (orderid.length > 0) {
                var conf = confirm('Are you sure you want to full refund ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    $.post(SITE _URL + "admin/transactions/tollRefundtotal", {
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