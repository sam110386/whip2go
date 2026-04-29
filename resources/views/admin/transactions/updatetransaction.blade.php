@extends('admin.layouts.app')

@section('title', 'Order transactions')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Order</span> #{{ $order->increment_id ?? $order->id }}
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <a href="/admin/transactions/index" class="btn btn-default">
                        <i class="icon-arrow-left16 position-left"></i> Back to transactions
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
            <h5 class="panel-title">Order</h5>
        </div>
        <div class="panel-body">
            <p class="help-block">Read-only snapshot. Cake payment adjustments / refunds are not implemented here yet.</p>
            <table class="table table-bordered">
                <tbody>
                    <tr><th style="width:200px;">Status</th><td>{{ $order->status }}</td></tr>
                    <tr><th>Customer</th><td>{{ trim(($order->renter_first_name ?? '') . ' ' . ($order->renter_last_name ?? '')) }}</td></tr>
                    <tr><th>Rent</th><td>{{ $order->rent }}</td></tr>
                    <tr><th>Tax</th><td>{{ $order->tax }}</td></tr>
                    <tr><th>Paid</th><td>{{ $order->paid_amount }}</td></tr>
                    <tr><th>Deposit</th><td>{{ $order->deposit }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Successful payments (status 1)</h5>
        </div>
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Txn ID</th>
                        <th>Charged</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $p)
                        <tr>
                            <td>{{ $p->type }}</td>
                            <td>{{ $p->amount }}</td>
                            <td>{{ $p->transaction_id }}</td>
                            <td>{{ $p->charged_at ?? $p->created }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No payment rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
