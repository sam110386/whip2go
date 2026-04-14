@extends('layouts.admin')

@section('title', $title ?? 'Adjust dealer transfer')

@section('content')
    <p><a href="/admin/transactions/updatetransaction/{{ base64_encode((string)$order->id) }}">← Back to order transactions</a></p>
    <h1>{{ $title ?? 'Adjust dealer transfer' }}</h1>
    <p>Order #{{ $order->increment_id ?? $order->id }} | Transfer type {{ $type }} | Total {{ number_format((float)$total, 2) }}</p>

    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Amount</th>
                <th style="padding:6px;">Transfer ID</th>
                <th style="padding:6px;">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $p)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $p->amount }}</td>
                    <td style="padding:6px;">{{ $p->transfer_id }}</td>
                    <td style="padding:6px;">{{ $p->created ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:12px;">No transfer rows.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

