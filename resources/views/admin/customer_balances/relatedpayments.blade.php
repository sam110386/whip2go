@extends('admin.layouts.app')

@section('title', 'Credit/Debit Payment Details')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"> </span> {{ $listTitle }}</h4>
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
                @foreach ($balances as $val)
                    <div class="col-lg-12 heading-divided nopadding">
                        <label class="col-lg-3 control-label"><span class="text-semibold">Credit/Debit:</span> {{ $val->credit }}</label>
                        <label class="col-lg-3 control-label"><span class="text-semibold">Balance:</span> {{ (float)$val->balance == 2 ? '0.00' : $val->balance }}</label>
                        <label class="col-lg-3 control-label"><span class="text-semibold">Charge Type:</span> <em><span class="text-semibold">Type:</span> {{ ucfirst($val->chargetype) }}
                                <br><span class="text-semibold">Installment Type:</span> {{ ucfirst($val->installment_type) }}
                                <br><span class="text-semibold">Installment:</span> {{ $val->installment }}</em></label>
                        <label class="col-lg-3 control-label"><span class="text-semibold">Last Processed:</span> {{ $formatDt($val->last_processed) }}</label>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-6">
                <legend class="text-bold">Repective Bookings & Charges</legend>
                @foreach ($payments as $pay)
                    <div class="col-lg-12 heading-divided nopadding">
                        <label class="col-lg-3 control-label"><span class="text-semibold">Booking:</span> {{ $pay->increment_id }}</label>
                        <label class="col-lg-3 control-label"><span class="text-semibold">Amount:</span> {{ $pay->amount }}</label>
                        <label class="col-lg-3 control-label"><span class="text-semibold"> Processed:</span> {{ $formatDt($pay->created) }}</label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="text-right">
    <a href="{{ url('admin/customer_balances/index') }}" class="btn btn-default"><i class="icon-arrow-left8 position-left"></i> Back to List</a>
</div>

@endsection
