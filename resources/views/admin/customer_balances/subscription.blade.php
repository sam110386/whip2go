@extends('admin.layouts.app')

@section('title', 'Dealer Charges')

@section('content')
    <h1>Dealer Charges</h1>
    <p style="margin-bottom:12px;">
        <a href="/admin/customer_balances/addsubscription/{{ $useridB64 }}"
           style="display:inline-block; padding:6px 12px; background:#2a7; color:#fff; text-decoration:none; border-radius:4px;">Add New</a>
        <a href="/admin/customer_balances/index" style="margin-left:12px;">← All balances</a>
    </p>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="get" action="/admin/customer_balances/subscription/{{ $useridB64 }}" style="margin-bottom:12px;">
        <label>Rows / page</label>
        <select name="Record[limit]" onchange="this.form.submit()">
            @foreach ([25, 50, 100, 200] as $opt)
                <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </form>

    <div id="listing">
        @include('admin.customer_balances._listing', [
            'records' => $records,
            'balanceTypes' => $balanceTypes,
            'formatDt' => $formatDt,
            'subscriptionMode' => true,
            'subscriptionUserId' => $userid,
        ])
    </div>
@endsection
