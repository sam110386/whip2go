@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
    <h1>{{ $listTitle }}</h1>
    <p><a href="/admin/customer_balances/index">← Back to balances</a></p>

    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:24px;">
        <div style="flex:1; min-width:280px;">
            <h2 style="font-size:16px;">Charges</h2>
            @forelse ($balances as $bal)
                <div style="border:1px solid #eee; padding:10px; margin-bottom:10px; font-size:13px;">
                    <div><strong>Credit/Debit:</strong> {{ $bal->credit }}</div>
                    <div><strong>Balance:</strong> {{ (int)$bal->balance === 2 ? '0.00' : $bal->balance }}</div>
                    <div>
                        <strong>Charge type:</strong>
                        {{ ucfirst((string)$bal->chargetype) }} /
                        {{ ucfirst((string)$bal->installment_type) }} /
                        {{ $bal->installment }}
                    </div>
                    <div><strong>Last processed:</strong> {{ $formatDt($bal->last_processed ?? null) }}</div>
                </div>
            @empty
                <p>No balance rows.</p>
            @endforelse
        </div>
        <div style="flex:1; min-width:280px;">
            <h2 style="font-size:16px;">Bookings &amp; charges (type 6 payments)</h2>
            @forelse ($payments as $pay)
                <div style="border:1px solid #eee; padding:10px; margin-bottom:10px; font-size:13px;">
                    <div><strong>Booking:</strong> {{ $pay->increment_id }}</div>
                    <div><strong>Amount:</strong> {{ $pay->amount }}</div>
                    <div><strong>Processed:</strong> {{ $formatDt(isset($pay->created) ? (string)$pay->created : null) }}</div>
                </div>
            @empty
                <p>No matching payments.</p>
            @endforelse
        </div>
    </div>
@endsection
