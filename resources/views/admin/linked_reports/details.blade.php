@extends('admin.layouts.app')

@section('title', 'Linked report details')

@section('content')
    <p><a href="/cloud/linked_reports/index">← Back</a></p>
    <h1>Linked report details #{{ $order->increment_id ?? $order->id }}</h1>
    <table style="border-collapse:collapse;">
        <tr><td style="padding:6px 12px 6px 0;"><strong>Status</strong></td><td>{{ $order->status }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Start</strong></td><td>{{ $order->start_datetime }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>End</strong></td><td>{{ $order->end_datetime }}</td></tr>
    </table>
@endsection

