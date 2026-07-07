@extends('admin.layouts.app')

@php
    $title ??= 'Late Fee';
    $totalPaid ??= 0;
    $csorder ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    Late Fee<span class="text-semibold"> Transactions</span>
                </h4>
            </div>
        </div>
    </div>
    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                @if($csorder)
                    <form id="TransactionAdminLatefeeForm" class="form-horizontal">
                        @csrf
                        <fieldset class="col-lg-8">
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
                                        Adjust To :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="number" name="CsOrder[newtotal]" id="CsOrderNewtotal"
                                            class="number form-control digit required" min="0" max="{{ $totalPaid ?? '' }}"
                                            value="{{ old('newtotal', $totalPaid ?? 0) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4"></label>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-primary"
                                            onClick="adjustLateFee('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                            Proceed
                                        </button>
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-danger btn-ladda btn-ladda-progress"
                                            onClick="Refundtotal('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
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
                        </fieldset>

                        <input type="hidden" name="CsOrder[id]" value="{{ data_get($csorder, 'id', '') }}">
                    </form>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function () {
            $("#TransactionAdminLatefeeForm").validate({
                rules: {
                    'newtotal': {
                        required: true,
                        min: 0,
                        max: '{{ $totalPaid ?? 0 }}'
                    }
                }
            });
        });

        function adjustLateFee(orderid) {
            if (orderid.length > 0 && $("#TransactionAdminLatefeeForm").valid()) {
                var conf = confirm('Are you sure you want to adjust amount?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Processing...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    var params = $("#TransactionAdminLatefeeForm").serialize();
                    $.post(SITE_URL + "admin/transactions/adjustLatefee", params, function (data) {
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

        function Refundtotal(orderid) {
            if (orderid.length > 0) {
                var conf = confirm('Are you sure you want to full refund ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Processing...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    $.post(SITE_URL + "admin/transactions/latefeeRefundtotal", {
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