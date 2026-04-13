@extends('admin.layouts.app')

@section('title', 'Reports productivity')

@section('content')
    <h1>Productivity report</h1>
    <p>Range: {{ $dateFrom ?: 'all' }} to {{ $dateTo ?: 'all' }}</p>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Dealer user id</th>
                <th style="padding:6px;">Total orders</th>
                <th style="padding:6px;">Gross</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $r->user_id }}</td>
                    <td style="padding:6px;">{{ $r->total_orders }}</td>
                    <td style="padding:6px;">{{ number_format((float)$r->gross, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:10px;">No rows</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

