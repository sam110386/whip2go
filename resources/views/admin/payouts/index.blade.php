@extends('layouts.admin')

@section('title', 'Payouts')

@section('content')
    <h1>Payouts</h1>
    <p style="font-size:13px;color:#555;">
        @if ($batchMode)
            <strong>Batch view</strong> (Stripe payout batches).
            <a href="{{ request()->fullUrlWithQuery(['listtype' => 'all', 'page' => null]) }}">Show all line items</a>
        @else
            <strong>All payout transactions</strong> (successful lines only, status = 1).
            <a href="{{ request()->fullUrlWithQuery(['listtype' => null, 'page' => null]) }}">Show batches</a>
        @endif
    </p>

    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="POST" action="/admin/payouts/index" style="margin-bottom:16px; padding:12px; border:1px solid #eee;">
        <input type="hidden" name="listtype" value="{{ $listtype }}">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <div>
                <label>Dealer user ID</label><br>
                <input type="number" name="Search[user_id]" value="{{ $user_id }}" placeholder="User id" style="min-width:120px;">
            </div>
            <div>
                <label>Payout #</label><br>
                <input type="text" name="Search[payout_id]" value="{{ $payout_id }}" placeholder="Batch id">
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
            <div>
                <button type="submit" name="search" value="EXPORT" formaction="/admin/payouts/index">Export</button>
            </div>
        </div>
    </form>

    <div id="listing">
        @include('admin.payouts.listing', [
            'payoutlists' => $payoutlists,
            'batchMode' => $batchMode,
            'paymentTypeValue' => $paymentTypeValue,
        ])
    </div>

    <div id="payoutModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9998; align-items:center; justify-content:center;">
        <div style="background:#fff; max-width:900px; width:92%; max-height:85vh; overflow:auto; padding:16px; border-radius:6px; position:relative;">
            <button type="button" onclick="document.getElementById('payoutModal').style.display='none'" style="position:absolute; right:10px; top:8px;">×</button>
            <div id="payoutModalBody"></div>
        </div>
    </div>

    <script>
        function getTransactions(payoutid) {
            var modal = document.getElementById('payoutModal');
            var body = document.getElementById('payoutModalBody');
            if (!modal || !body) return false;
            body.innerHTML = 'Loading…';
            modal.style.display = 'flex';
            var fd = new FormData();
            fd.append('payoutid', payoutid);
            fetch('/admin/payouts/transactions', { method: 'POST', body: fd })
                .then(function (r) { return r.text(); })
                .then(function (html) { body.innerHTML = html; })
                .catch(function () { body.innerHTML = 'Request failed.'; });
            return false;
        }
    </script>
@endsection
