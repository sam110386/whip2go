@extends('admin.layouts.app')

@section('title', 'Customer Balance')

@section('content')
    <h1>Credits and Debits</h1>
    <p style="margin-bottom:12px;">
        <a href="/admin/customer_balances/add" style="display:inline-block; padding:6px 12px; background:#2a7; color:#fff; text-decoration:none; border-radius:4px;">Create New</a>
    </p>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="get" action="/admin/customer_balances/index" style="margin-bottom:16px; padding:12px; border:1px solid #eee;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <div>
                <label>Keyword (first name)</label><br>
                <input type="text" name="Search[keyword]" value="{{ $keyword }}" maxlength="20" style="min-width:140px;">
            </div>
            <div>
                <label>Driver / Dealer</label><br>
                <select name="Search[type]" class="form-control" style="min-width:120px;">
                    <option value="">Select..</option>
                    <option value="1" @selected($type === '1')>Driver</option>
                    <option value="2" @selected($type === '2')>Dealer</option>
                </select>
            </div>
            <div>
                <label>Status</label><br>
                <select name="Search[status]" style="min-width:120px;">
                    <option value="">Select..</option>
                    <option value="1" @selected($status === '1')>Active</option>
                    <option value="0" @selected($status === '0')>Inactive</option>
                    <option value="2" @selected($status === '2')>Completed</option>
                </select>
            </div>
            <div>
                <label>Rows / page</label><br>
                <select name="Record[limit]" onchange="this.form.submit()" style="width:80px;">
                    @foreach ([25, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit">Search</button>
            </div>
        </div>
    </form>

    <div id="listing">
        @include('admin.customer_balances._listing', [
            'records' => $records,
            'balanceTypes' => $balanceTypes,
            'formatDt' => $formatDt,
            'subscriptionMode' => false,
            'subscriptionUserId' => null,
        ])
    </div>
@endsection
