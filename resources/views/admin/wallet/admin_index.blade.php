@extends('admin.layouts.app')

@section('title', 'Wallet Balance')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="/admin/users"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">Wallet</span> — Balance
                </h4>
            </div>
            <div class="heading-elements">
                @if(empty($is_dealer))
                    {{-- Airwallex / Stripe DIA credits: legacy plugins not ported --}}
                    <span class="btn btn-default disabled pull-left mr-5">Airwallex (legacy)</span>
                    <a href="/admin/wallet/diacredit/{{ $userid }}" class="btn btn-danger pull-left mr-5">Add DIA Credits</a>
                @endif
                <button type="button" class="btn btn-success left-margin" disabled title="Requires legacy admin_booking.js + charge endpoint">Charge Partial Amount</button>
                @if(!empty($is_dealer))
                    <a href="/admin/wallet/updatebalance/{{ $userid }}" class="btn btn-success pull-left mr-5">Update Balance</a>
                @else
                    <a href="/admin/wallet/refundbalance/{{ $userid }}" class="btn btn-success pull-left mr-5">Refund Balance</a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <div class="breadcrumb-line">
        <ul class="text-center pt-20 pb-10">
            <li><h4><span class="text-semibold">Balance : </span>${{ $wallet->balance ?? 0 }}</h4></li>
        </ul>
    </div>
    <div class="breadcrumb-line">
        <ul class="text-center">
            <li><h6><span class="text-semibold">Transaction History</span></h6></li>
        </ul>
    </div>

    <div class="panel">
        <div class="panel-body" id="postsPaging">
            @include('admin.wallet._transaction_panel', [
                'transactions' => $transactions,
                'keyword' => $keyword,
                'limit' => $limit,
                'adminContext' => true,
                'useridB64' => $userid,
            ])
        </div>
    </div>
@endsection
