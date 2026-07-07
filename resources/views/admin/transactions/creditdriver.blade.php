@extends('admin.layouts.app')

@php
    $title ??= 'Credit Driver';
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

                <form id="ReportAdminCreditdriverForm"
                    action="{{ url('admin/transactions/creditdriver/' . base64_encode(data_get($csorder, 'id', ''))) }}"
                    method="POST" class="form-horizontal">
                    @csrf
                    <fieldset class="col-lg-12">
                        <div class="panel-body">
                            <div class="form-group">
                                <h3>
                                    <div>Credit To Driver : </div>
                                </h3>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4">
                                    <strong>Job# :</strong>
                                </label>
                                <div class="col-lg-6">
                                    {{ data_get($csorder, 'increment_id', data_get($csorder, 'CsOrder.increment_id')) }}
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
                                        {{ data_get($CsOrderPayment, 'rent', 'N/A') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Tax :
                                    </label>
                                    <div class="col-lg-6">
                                        {{ data_get($CsOrderPayment, 'tax', 'N/A') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Dealer Rev Share:
                                    </label>
                                    <div class="col-lg-6">
                                        {{ !empty(data_get($CsOrderPayment, 'rent', '')) ? (sprintf('%0.2f', data_get($CsOrderPayment, 'rent') * data_get($csorder, 'rev', 85) / 100)) : 'N/A' }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Credit To Driver:
                                    </label>
                                    <div class="col-lg-6">
                                        <input type="text" name="CsOrder[credit]" id="CsOrderCredit" class="form-control number required"
                                            value="{{ old('credit', !empty(data_get($CsOrderPayment, 'rent')) ? sprintf('%0.2f', (data_get($CsOrderPayment, 'rent') * data_get($csorder, 'rev', 85) / 100)) : 0) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4">
                                        Credit Note:
                                    </label>
                                    <div class="col-lg-6">
                                        <textarea name="CsOrder[note]" class="form-control" id="CsOrderNote" placeholder="Note If Any">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4"></label>
                                    <div class="col-lg-2">
                                        @if(!empty(data_get($CsOrderPayment, 'rent', '')))
                                            <button type="submit" class="btn btn-primary">
                                                Proceed
                                            </button>
                                        @endif
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

                    <input type="hidden" name="CsOrder[id]" value="{{ data_get($csorder, 'id', '') }}">
                </form>
            @endif
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">
        $(document).ready(function () {
            $("#ReportAdminCreditdriverForm").validate();
        });
    </script>

@endpush