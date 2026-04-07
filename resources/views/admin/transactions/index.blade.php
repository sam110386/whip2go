@extends('admin.layouts.app')

@section('title', 'Manage — Transactions')

@section('content')
    <h1>Manage — Transactions</h1>
    <p style="font-size:13px;color:#555;">Canceled and completed orders (status 2–3). Filters match Cake <code>admin_index</code>; adjust payment actions are not ported yet.</p>

    <form method="POST" action="/admin/transactions/index" id="frmSearchadmin" style="margin-bottom:16px; padding:12px; border:1px solid #eee;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <div>
                <label>Keyword</label><br>
                <input type="text" name="Search[keyword]" value="{{ $keyword }}" maxlength="50" placeholder="Keyword" style="min-width:140px;">
            </div>
            <div>
                <label>Transaction ID</label><br>
                <input type="text" name="Search[transaction_id]" value="{{ $transaction_id }}" maxlength="80" placeholder="Stripe / txn id" style="min-width:160px;">
            </div>
            <div>
                <label>Search in</label><br>
                <select name="Search[searchin]">
                    <option value="">Select</option>
                    <option value="2" @selected($fieldname === '2')>Vehicle#</option>
                    <option value="3" @selected($fieldname === '3')>Order#</option>
                </select>
            </div>
            <div>
                <label>Status</label><br>
                <select name="Search[status_type]">
                    <option value="">Select type</option>
                    <option value="complete" @selected($status_type === 'complete')>Complete</option>
                    <option value="cancel" @selected($status_type === 'cancel')>Cancel</option>
                    <option value="incomplete" @selected($status_type === 'incomplete')>Incomplete</option>
                </select>
            </div>
            <div>
                <label>Date from</label><br>
                <input type="text" name="Search[date_from]" value="{{ $date_from }}" placeholder="YYYY-MM-DD">
            </div>
            <div>
                <label>Date to</label><br>
                <input type="text" name="Search[date_to]" value="{{ $date_to }}" placeholder="YYYY-MM-DD">
            </div>
            <div>
                <label>Rows / page</label><br>
                <input type="number" name="Record[limit]" value="{{ $limit }}" min="1" max="500" style="width:70px;">
            </div>
            <div>
                <button type="submit" name="search" value="search">Apply</button>
            </div>
        </div>
    </form>

    <div id="listing">
        @include('admin.transactions.listing', ['reportlists' => $reportlists])
    </div>
@endsection
