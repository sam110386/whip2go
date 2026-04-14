@extends('admin.layouts.app')

@section('title', 'Refund Wallet Balance')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="/admin/wallet/index/{{ $userid }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">Refund</span> — Wallet Balance
                </h4>
            </div>
        </div>
    </div>

    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <div class="breadcrumb-line">
        <ul class="text-center pt-20 pb-10">
            <li><h4><span class="text-semibold">Current Balance : </span>${{ $wallet->balance ?? 0 }}</h4></li>
        </ul>
    </div>

    <div class="panel">
        <div class="panel-body">
            <p><em>Wallet refunds to the card require the legacy Stripe PaymentProcessor path; Laravel shows this form for navigation parity only.</em></p>
            <form method="post" action="/admin/wallet/refundbalance/{{ $userid }}" class="form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Amount</label>
                            <div class="col-lg-4">
                                <input type="text" name="Wallet[balance]" maxlength="16" class="form-control" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Note</label>
                            <div class="col-lg-8">
                                <input type="text" name="Wallet[note]" maxlength="100" class="form-control" value="Refunded by DIA Admin">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn left-margin" onclick="window.location.href='/admin/wallet/index/{{ $userid }}'">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
