@extends('admin.layouts.app')

@section('title', 'Order transactions')

@section('content')
    <p><a href="/admin/transactions/index">← Back to transactions</a></p>
    <h1>Order #{{ $order->increment_id ?? $order->id }}</h1>
    <p style="font-size:13px;color:#555;">Read-only snapshot. Cake payment adjustments / refunds are not implemented here yet.</p>

    <h2>Order</h2>
    <table style="border-collapse:collapse; font-size:13px;">
        <tbody>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Status</strong></td><td>{{ $order->status }}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Customer</strong></td><td>{{ trim(($order->renter_first_name ?? '') . ' ' . ($order->renter_last_name ?? '')) }}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Rent</strong></td><td>{{ $order->rent }}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Tax</strong></td><td>{{ $order->tax }}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Paid</strong></td><td>{{ $order->paid_amount }}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;"><strong>Deposit</strong></td><td>{{ $order->deposit }}</td></tr>
        </tbody>
    </table>

    <h2 style="margin-top:20px;">Successful payments (status 1)</h2>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Type</th>
                <th style="padding:6px;">Amount</th>
                <th style="padding:6px;">Txn ID</th>
                <th style="padding:6px;">Charged</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $p)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $p->type }}</td>
                    <td style="padding:6px;">{{ $p->amount }}</td>
                    <td style="padding:6px;">{{ $p->transaction_id }}</td>
                    <td style="padding:6px;">{{ $p->charged_at ?? $p->created }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding:12px;">No payment rows.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
