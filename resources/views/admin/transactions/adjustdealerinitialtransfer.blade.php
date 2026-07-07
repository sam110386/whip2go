@extends('admin.layouts.app')

@php
    $title ??= 'Adjust Dealer Initial Transfer';
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
                <form id="dealeradjustinitialfee" class="form-horizontal">
                    @csrf
                    <fieldset class="col-lg-12">
                        <div class="panel-body">
                            <div class="form-group">
                                <h3>
                                    <div>Initial Fee Transaction Details : </div>
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
                                        Transfered To Dealer :
                                    </label>
                                    <div class="col-lg-6">
                                        {{ data_get($orderPayments, '0.total', 'N/A') }}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Adjust To :
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="CsOrder[dealerpart]" id="CsOrderDealerpart"
                                            class="number form-control digit required"
                                            value="{{ old('dealerpart', data_get($csorder, 'dealerpart', '')) }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4">Transaction Id : </label>
                                    <div class="col-lg-6">
                                        @if(!empty($transactionIds) && is_array($transactionIds))
                                            {!! implode("<br/>", array_map('e', $transactionIds)) !!}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4"></label>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-primary"
                                            onClick="adjustDealerInitialFeePart('{{ base64_encode(data_get($csorder, 'id', '')) }}')">
                                            Proceed To Adjust
                                        </button>
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-danger btn-ladda btn-ladda-progress"
                                            onClick="initialfeeReversetotal('{{ base64_encode(data_get($csorder, 'id', ''))}}')">
                                            Reverse Total
                                        </button>
                                    </div>
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
            $("#dealeradjustinitialfee").validate();
        });

        function adjustDealerInitialFeePart(orderid) {
            if (orderid.length > 0 && $("#dealeradjustinitialfee").valid()) {
                var conf = confirm('Are you sure you want to adjust insurance ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    var params = $("#dealeradjustinitialfee").serialize();
                    $.post(SITE_URL + "admin/transactions/adjustDealerInitialFeePart", params, function (data) {
                        jQuery.unblockUI();
                        swal({
                            title: "",
                            text: data.message,
                            confirmButtonClass: "btn-success",
                            closeOnConfirm: true,
                            closeOnCancel: true,
                            buttons: { confirm: { text: "OK", value: true, visible: true, className: "", closeModal: true } },
                        },
                            function (isConfirm) {
                                if (isConfirm) {
                                    goBack("/admin/transactions/updatetransaction/" + orderid);
                                }
                            });
                    }, 'json');
                }
            }
        }

        function initialfeeReversetotal(orderid) {
            if (orderid.length > 0) {
                var conf = confirm('Are you sure you want to full refund ?');
                if (conf) {
                    jQuery.blockUI({
                        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                        css: { 'z-index': '9999' }
                    });
                    $.post(SITE_URL + "admin/transactions/initialfeeReversetotal", {
                        "orderid": orderid,
                        "_token": "{{ csrf_token() }}"
                    }, function (data) {
                        jQuery.unblockUI();
                        swal({
                            title: "",
                            text: data.message,
                            confirmButtonClass: "btn-success",
                            closeOnConfirm: true,
                            closeOnCancel: true,
                            buttons: { confirm: { text: "OK", value: true, visible: true, className: "", closeModal: true } },
                        },
                            function (isConfirm) {
                                if (isConfirm) {
                                    goBack("/admin/transactions/updatetransaction/" + orderid);
                                }
                            });
                    }, 'json');
                }
            }
        }
    </script>
@endpush