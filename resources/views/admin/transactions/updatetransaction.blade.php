@extends('admin.layouts.app')

@section('title', 'Order transactions')

@section('content')
    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            @if(!empty($csorder))
                <form class="form-horizontal">
                    <fieldset class="col-lg-12">
                        <div class="panelbody">
                            <fieldset class="content-group col-lg-12 text-center">
                                <label>
                                    <h5>Payment Details : </h5>
                                    <strong>Job# :</strong> {{ $csorder->increment_id }}
                                </label>
                            </fieldset>

                            {{-- Rent Transaction Details --}}
                            @if($csorder->status != 2)
                                <fieldset class="content-group col-lg-6">
                                    <legend class="text-bold">Rent Transaction Details</legend>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Total Paid Amount : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->paid_amount) ? $csorder->paid_amount : "N/A" }}
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Rent : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->rent) ? $csorder->rent : "N/A" }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Tax : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->tax) ? $csorder->tax : "N/A" }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Damage Fee : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->damage_fee) ? $csorder->damage_fee : "N/A" }}
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Uncleanness Fee : </label>
                                        <div class="col-lg-6">
                                            {{ !empty($csorder->uncleanness_fee) ? $csorder->uncleanness_fee : "N/A" }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Transaction Id : </label>
                                        <div class="col-lg-6">
                                            {!! isset($transactionIds[2]) ? implode("<br/>", $transactionIds[2]) : "N/A" !!}</div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/updatefare/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-primary">Adjust Payments</a>
                                        </div>
                                        @if(isset($transferedPayouts[2]))
                                            <div class="col-lg-4">
                                                <a href="/admin/transactions/adjustdealerrentaltransfer/{{ base64_encode($csorder->id) }}"
                                                    class="btn btn-success" style="float:right;">Adjust Dealer Transfer</a>
                                            </div>
                                        @endif
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/creditdriver/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-primary">Credit To Driver</a>
                                        </div>
                                    </div>
                                </fieldset>
                            @else
                                {{-- Canceled Transaction Details --}}
                                <fieldset class="content-group col-lg-6">
                                    <legend class="text-bold">Canceled Transaction Details</legend>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Cancellation Fee : </label>
                                        <div class="col-lg-6">
                                            {{ !empty($csorder->cancellation_fee) ? $csorder->cancellation_fee : "N/A" }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Cancellation Note : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->cancel_note) ? $csorder->cancel_note : "N/A" }}
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Transaction Id : </label>
                                        <div class="col-lg-6">
                                            {{ !empty($csorder->transaction_id) ? $csorder->transaction_id : "N/A" }}</div>
                                    </div>
                                </fieldset>
                            @endif

                            {{-- Late fee Transactions --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Late fee Transactions</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Lateness Fee : </label>
                                    <div class="col-lg-6">{{ !empty($csorder->lateness_fee) ? $csorder->lateness_fee : "N/A" }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Transactions : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[19]) ? implode("<br/>", $transactionIds[19]) : "N/A" !!}</div>
                                </div>
                                @if(isset($transactionIds[19]) && !empty($transactionIds[19]))
                                    <div class="form-group">
                                        <div class="col-lg-12">
                                            <a href="/admin/transactions/latefee/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-primary">Adjust Payments</a>
                                        </div>
                                    </div>
                                @endif
                            </fieldset>

                            {{-- Rental EMF --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Rental EMF</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Amount : </label>
                                    <div class="col-lg-6">{{ $csorder->extra_mileage_fee }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Tax : </label>
                                    <div class="col-lg-6">{{ $csorder->emf_tax }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Transaction# : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[16]) ? implode("<br/>", $transactionIds[16]) : "N/A" !!}</div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-4">
                                        <a href="/admin/transactions/updateemf/{{ base64_encode($csorder->id) }}"
                                            class="btn btn-primary">Adjust Payments</a>
                                    </div>
                                    @if(isset($transferedPayouts[16]))
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/adjustdealeremftransfer/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-success" style="float:right;">Adjust Dealer Transfer</a>
                                        </div>
                                    @endif
                                </div>
                            </fieldset>

                            {{-- Insurance EMF --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Insurance EMF</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Amount : </label>
                                    <div class="col-lg-6">{{ $csorder->dia_insu }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Transaction# : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[14]) ? implode("<br/>", $transactionIds[14]) : "N/A" !!}</div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-4">
                                        <a href="/admin/transactions/updatediainsu/{{ base64_encode($csorder->id) }}"
                                            class="btn btn-primary">Adjust Payments</a>
                                    </div>
                                </div>
                            </fieldset>

                            {{-- Initial Fee Transaction Details --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Initial Fee Transaction Details</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Amount : </label>
                                    <div class="col-lg-6">{{ ($csorder->initial_fee) ? $csorder->initial_fee : "N/A" }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Tax : </label>
                                    <div class="col-lg-6">{{ ($csorder->initial_fee_tax) ? $csorder->initial_fee_tax : "N/A" }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 "> Transaction# : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[3]) ? implode("<br/>", $transactionIds[3]) : "N/A" !!}</div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-4">
                                        <a href="/admin/transactions/updateinitialfee/{{ base64_encode($csorder->id) }}"
                                            class="btn btn-primary">Adjust Payments</a>
                                    </div>
                                    @if(isset($transferedPayouts[3]))
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/adjustdealerinitialtransfer/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-success" style="float:right;">Adjust Dealer Transfer</a>
                                        </div>
                                    @endif
                                </div>
                            </fieldset>

                            {{-- Insurance Transaction Details --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Insurance Transaction Details</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Amount : </label>
                                    <div class="col-lg-6">
                                        {{ !empty($csorder->insurance_amt) ? $csorder->insurance_amt : "N/A" }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Transaction# : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[4]) ? implode("<br/>", $transactionIds[4]) : "N/A" !!}</div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-4">
                                        <a href="/admin/transactions/updateinsurance/{{ base64_encode($csorder->id) }}"
                                            class="btn btn-primary">Adjust Payments</a>
                                    </div>
                                    @if(isset($transferedPayouts[4]))
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/adjustdealerinsurancetransfer/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-success" style="float:right;">Adjust Dealer Transfer</a>
                                        </div>
                                    @endif
                                </div>
                            </fieldset>

                            {{-- Toll/Misc Transactions --}}
                            <fieldset class="content-group col-lg-6">
                                <legend class="text-bold">Toll/Misc Transactions</legend>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Amount : </label>
                                    <div class="col-lg-6">{{ !empty($csorder->toll) ? $csorder->toll : "N/A" }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 ">Transaction# : </label>
                                    <div class="col-lg-6">
                                        {!! isset($transactionIds[6]) ? implode("<br/>", $transactionIds[6]) : "N/A" !!}</div>
                                </div>
                                <div class="form-group">
                                    @if(isset($transactionIds[6]))
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/updatetoll/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-primary">Refund Payments</a>
                                        </div>
                                    @endif
                                    @if(isset($transferedPayouts[6]))
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/adjustdealertolltransfer/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-success" style="float:right;">Adjust Dealer Transfer</a>
                                        </div>
                                    @endif
                                </div>
                            </fieldset>

                            {{-- Deposit Transaction Details --}}
                            @if($csorder->deposit_type == 'C')
                                <fieldset class="content-group col-lg-6">
                                    <legend class="text-bold">Deposit Transaction Details</legend>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Amount : </label>
                                        <div class="col-lg-6">{{ !empty($csorder->deposit) ? $csorder->deposit : "N/A" }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 ">Transaction# : </label>
                                        <div class="col-lg-6">
                                            {!! isset($transactionIds[1]) ? implode("<br/>", $transactionIds[1]) : "N/A" !!}</div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-lg-4">
                                            <a href="/admin/transactions/updatedeposit/{{ base64_encode($csorder->id) }}"
                                                class="btn btn-primary">Adjust Deposit</a>
                                        </div>
                                    </div>
                                </fieldset>
                            @endif

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <a href="/admin/transactions/index" class="btn btn-default">Go Back</a>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>
            @else
                <div class="alert alert-danger">Order not found.</div>
            @endif
        </div>
    </div>
@endsection