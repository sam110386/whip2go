@extends('admin.layouts.app')

@section('header_title', 'Credit Driver')

@section('content')
<div class="panel panel-flat">
    <div class="panel-heading"><h5 class="panel-title">Credit Driver</h5></div>
    <div class="panel-body">
        <p class="text-muted">Credit driver view — ported from CakePHP admin_creditdriver.</p>
        @if($order)
            <table class="table table-bordered">
                <tr><th>Order ID</th><td>{{ $order->id }}</td></tr>
                <tr><th>Vehicle</th><td>{{ $order->vehicle_name ?? '' }}</td></tr>
                <tr><th>Owner</th><td>{{ ($order->owner_first_name ?? '') . ' ' . ($order->owner_last_name ?? '') }}</td></tr>
                <tr><th>Total Rent</th><td>{{ $totalRent ?? 0 }}</td></tr>
                <tr><th>Total Tax</th><td>{{ $totalTax ?? 0 }}</td></tr>
                <tr><th>Rev Share</th><td>{{ $revShare ?? 85 }}%</td></tr>
                <tr><th>Dealer Part</th><td>{{ $dealerPart ?? 0 }}</td></tr>
            </table>
        @else
            <p>Order not found.</p>
        @endif
    </div>
</div>
@endsection
