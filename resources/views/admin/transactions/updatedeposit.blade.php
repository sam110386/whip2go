@extends('layouts.admin')

@section('header_title', 'Update Deposit')

@section('content')
<div class="panel panel-flat">
    <div class="panel-heading"><h5 class="panel-title">Update Deposit</h5></div>
    <div class="panel-body">
        <p class="text-muted">Deposit update view — ported from CakePHP admin_updatedeposit.</p>
        @if($order)
            <table class="table table-bordered">
                <tr><th>Order ID</th><td>{{ $order->id }}</td></tr>
                <tr><th>Deposit</th><td>{{ $order->deposit ?? 0 }}</td></tr>
                <tr><th>Deposit Type</th><td>{{ $order->deposit_type ?? '' }}</td></tr>
            </table>
        @else
            <p>Order not found.</p>
        @endif
    </div>
</div>
@endsection
