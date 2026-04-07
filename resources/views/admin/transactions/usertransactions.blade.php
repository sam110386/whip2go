{{-- Cake Transactions/admin_usertransactions.ctp (modal shell) --}}
<div class="page-header" style="margin-bottom:12px;">
    <h4>Driver — Transactions</h4>
    <p>
        <strong>Wallet balance:</strong> ${{ number_format((float)$wallet_balance, 2) }}
        <span style="margin-left:12px;color:#888;font-size:12px;">Booking #{{ $bookingid }} · {{ $currency }}</span>
    </p>
</div>

<div style="margin-bottom:12px;">
    <label>Range</label>
    <select id="SearchTime" style="margin-left:8px;">
        @foreach (['1 day' => 'Last 1 day', '3 days' => 'Last 3 days', '7 days' => 'Last 7 days', '14 days' => 'Last 14 days', '30 days' => 'Last 30 days'] as $val => $label)
            <option value="{{ $val }}" @selected($time === $val)>{{ $label }}</option>
        @endforeach
    </select>
    <input type="hidden" id="SearchUserId" value="{{ $userid }}">
    <input type="hidden" id="ut_bookingid" value="{{ $bookingid }}">
    <input type="hidden" id="ut_currency" value="{{ $currency }}">
</div>

<div id="transsactionlisting">
    @include('admin.transactions.usertransactions_list', ['rows' => $rows, 'total' => $total, 'userid' => $userid])
</div>

<script>
(function () {
    var uid = document.getElementById('SearchUserId');
    var timeSel = document.getElementById('SearchTime');
    var bid = document.getElementById('ut_bookingid');
    var cur = document.getElementById('ut_currency');
    if (!timeSel || !uid) return;
    timeSel.addEventListener('change', function () {
        var t = encodeURIComponent(timeSel.value);
        var url = '/admin/transactions/usertransactions/' + uid.value + '/' + t + '/1';
        var fd = new FormData();
        if (bid && bid.value) fd.append('bookingid', bid.value);
        if (cur && cur.value) fd.append('currency', cur.value);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var el = document.getElementById('transsactionlisting');
                if (el) el.innerHTML = html;
            });
    });
})();
</script>
