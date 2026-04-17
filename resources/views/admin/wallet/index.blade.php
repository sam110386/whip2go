@extends('admin.layouts.app')

@section('title', 'Wallet Balance')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/users/index') }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">{{ 'Wallet' }}</span> — {{ 'Balance' }}
                </h4>
            </div>
            <div class="heading-elements">
                @if(empty($is_dealer))
                    <a href="{{ url('admin/airwallex_credits/issue', $userid) }}" class="btn btn-danger pull-left mr-5">Credit Deposit to Virtual Card</a>
                    <a href="{{ url('admin/wallet/diacredit', $userid) }}" class="btn btn-danger pull-left mr-5">Add DIA Credits</a>
                @endif
                <a href="javascript:;" class="btn btn-success left-margin" onclick="chargePartialAmtPopup('{{ $userid }}')">Charge Partial Amount</a>
                @if(!empty($is_dealer))
                    <a href="{{ url('admin/wallet/updatebalance', $userid) }}" class="btn btn-success pull-left mr-5">Update Balance</a>
                @else
                    <a href="{{ url('admin/wallet/refundbalance', $userid) }}" class="btn btn-success pull-left mr-5">Refund Balance</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title text-center">
                <span class="text-semibold">Current Balance:</span> 
                <span class="text-success">${{ number_format($wallet->balance ?? 0, 2) }}</span>
            </h5>
        </div>
        <div class="panel-body">
            <h6 class="text-center font-weight-semibold">Transaction History</h6>
        </div>
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

@section('scripts')
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endsection
