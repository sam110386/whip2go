@extends('layouts.admin')

@section('title', 'Update Wallet Balance')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/wallet/index', $userid) }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">{{ 'Wallet' }}</span> — {{ 'Update Balance' }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('layouts.flash-messages')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="post" action="{{ url('admin/wallet/updatebalance', $userid) }}" class="form-horizontal" id="UpdateBalanceForm">
                @csrf
                <div class="form-group">
                    <label class="col-lg-2 control-label">Current Balance:</label>
                    <div class="col-lg-4">
                        <input type="text" class="form-control" id="current-balance" value="{{ $wallet->balance ?? 0 }}" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Amount to Deduct:</label>
                    <div class="col-lg-4">
                        <input type="number" name="Wallet[balance]" id="deduction-amount" class="form-control" step="0.01" required value="0">
                    </div>
                    <div class="col-lg-2">
                        <label class="control-label"><span class="text-bold">New Balance: </span><span id="new-balance-display" class="text-success text-bold">${{ $wallet->balance ?? 0 }}</span></label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <button type="submit" class="btn btn-primary">Update Balance</button>
                        <a href="{{ url('admin/wallet/index', $userid) }}" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            function updateNewBalance() {
                const current = parseFloat($('#current-balance').val()) || 0;
                const deduct = parseFloat($('#deduction-amount').val()) || 0;
                const newVal = (current - deduct).toFixed(2);
                $('#new-balance-display').text('$' + newVal);
                
                if (parseFloat(newVal) < 0) {
                    $('#new-balance-display').removeClass('text-success').addClass('text-danger');
                } else {
                    $('#new-balance-display').removeClass('text-danger').addClass('text-success');
                }
            }

            $('#deduction-amount').on('keyup change', updateNewBalance);
        });
    </script>
@endsection
