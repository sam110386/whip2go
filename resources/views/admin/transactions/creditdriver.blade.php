@extends('admin.layouts.app')

@section('title', 'Credit Driver')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Credit</span> Driver
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <a href="/admin/transactions/index" class="btn btn-default">
                        <i class="icon-arrow-left16 position-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Credit Driver</h5>
        </div>
        <div class="panel-body">
            <p class="text-muted">Credit driver view &mdash; ported from CakePHP admin_creditdriver.</p>
            @if ($order)
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
                <p class="text-muted">Order not found.</p>
            @endif
        </div>
    </div>
@endsection
