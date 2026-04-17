@extends('admin.layouts.app')

@section('header_title', 'Vehicle Selling Options')

@section('content')
<div class="panel panel-flat">
    <div class="panel-heading"><h5 class="panel-title">Vehicle Selling Options</h5></div>
    <div class="panel-body">
        <p class="text-muted">Selling options view — ported from CakePHP admin_vehicleSellingOpions.</p>
        @if($booking)
            <p>Reservation ID: {{ $booking->id ?? 'N/A' }}</p>
        @else
            <p>No booking data.</p>
        @endif
    </div>
</div>
@endsection
