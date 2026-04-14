@extends('layouts.admin')

@section('header_title', 'Vehicle Selling — Agree to Sell')

@section('content')
<div class="panel panel-flat">
    <div class="panel-heading"><h5 class="panel-title">Agree to Sell</h5></div>
    <div class="panel-body">
        <p class="text-muted">Agree-to-sell view — ported from CakePHP admin_vehicleSellingOpionAgreeToSell.</p>
        @if($booking)
            <p>Order Deposit Rule ID: {{ $booking->id ?? 'N/A' }}</p>
        @else
            <p>No booking data.</p>
        @endif
    </div>
</div>
@endsection
