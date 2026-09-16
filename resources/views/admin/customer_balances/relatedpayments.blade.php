@extends('admin.layouts.app')

@php
    $title ??= 'Credit/Debit Payment Details';
    $csUserBalances ??= collect();
    $csOrderPayments ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold"> </span> {{ $title }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6">
                    <legend class="text-bold">Charges</legend>

                    @foreach ($csUserBalances as $csUserBalance)
                        <div class="col-lg-12 heading-divided nopadding">
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Credit/Debit:</span>
                                {{ data_get($csUserBalance, 'credit', '') }}
                            </label>
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Balance:</span>
                                {{ data_get($csUserBalance, 'balance', '') }}
                            </label>
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Charge Type:</span>
                                <em>
                                    <span class="text-semibold">Type:</span>
                                    {{ ucfirst(data_get($csUserBalance, 'chargetype', '')) }}
                                    <br>
                                    <span class="text-semibold">Installment Type:</span>
                                    {{ ucfirst(data_get($csUserBalance, 'installment_type', '')) }}
                                    <br>
                                    <span class="text-semibold">Installment:</span>
                                    {{ data_get($csUserBalance, 'installment', '') }}
                                </em>
                            </label>
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Last Processed:</span>
                                {{ data_get($csUserBalance, 'last_processed', false) ? \Carbon\Carbon::parse(data_get($csUserBalance, 'last_processed'))->setTimezone(session('timezone'))->format('Y-m-d h:i A') : '' }}
                            </label>
                        </div>
                    @endforeach

                </div>
                <div class="col-lg-6">
                    <legend class="text-bold">Repective Bookings & Charges</legend>

                    @foreach ($csOrderPayments as $csOrderPayment)
                        <div class="col-lg-12 heading-divided nopadding">
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Booking:</span>
                                {{ data_get($csOrderPayment, 'csOrder.increment_id', '') }}
                            </label>
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold">Amount:</span>
                                {{ data_get($csOrderPayment, 'amount', '') }}
                            </label>
                            <label class="col-lg-3 control-label">
                                <span class="text-semibold"> Processed:</span>
                                {{ data_get($csOrderPayment, 'created', false) ? \Carbon\Carbon::parse(data_get($csOrderPayment, 'created'))->setTimezone(session('timezone'))->format('Y-m-d h:i A') : '' }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection