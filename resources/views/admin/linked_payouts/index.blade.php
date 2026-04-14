@extends('layouts.admin')

@section('title', 'Linked Payouts')

@section('content')
    <h1>Linked payouts</h1>
    <form method="get" action="/cloud/linked_payouts/index" style="margin-bottom:10px;">
        <label>From <input type="date" name="Search[date_from]" value="{{ $date_from ?? '' }}"></label>
        <label>To <input type="date" name="Search[date_to]" value="{{ $date_to ?? '' }}"></label>
        <label>Payout# <input type="text" name="Search[payout_id]" value="{{ $payout_id ?? '' }}" style="width:100px;"></label>
        <label>Dealer
            <select name="Search[user_id]">
                <option value="">All</option>
                @foreach(($dealers ?? []) as $id => $name)
                    <option value="{{ $id }}" @selected((string)($user_id ?? '') === (string)$id)>{{ $name }} ({{ $id }})</option>
                @endforeach
            </select>
        </label>
        <label>Mode
            <select name="Search[listtype]">
                <option value="" @selected(($listtype ?? '') === '')>Batches</option>
                <option value="all" @selected(($listtype ?? '') !== '')>All transactions</option>
            </select>
        </label>
        <button type="submit">Search</button>
        <button type="submit" name="search" value="EXPORT">Export</button>
    </form>

    @if (empty($listtype))
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="border-bottom:2px solid #ccc; text-align:left;">
                    <th style="padding:6px;">Payout#</th>
                    <th style="padding:6px;">Dealer</th>
                    <th style="padding:6px;">Processed on</th>
                    <th style="padding:6px;">Amount</th>
                    <th style="padding:6px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payoutlists as $p)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:6px;">{{ $p->id }}</td>
                        <td style="padding:6px;">{{ $p->user_id }}</td>
                        <td style="padding:6px;">{{ $p->processed_on }}</td>
                        <td style="padding:6px;">{{ number_format((float)($p->amount ?? 0), 2) }}</td>
                        <td style="padding:6px;"><button type="button" onclick="loadTransactions({{ (int)$p->id }})">Transactions</button></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:10px;">No payouts.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="border-bottom:2px solid #ccc; text-align:left;">
                    <th style="padding:6px;">Payout#</th>
                    <th style="padding:6px;">Booking</th>
                    <th style="padding:6px;">Vehicle</th>
                    <th style="padding:6px;">Driver</th>
                    <th style="padding:6px;">Type</th>
                    <th style="padding:6px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payoutlists as $p)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:6px;">{{ $p->cs_payout_id }}</td>
                        <td style="padding:6px;">{{ $p->increment_id }}</td>
                        <td style="padding:6px;">{{ $p->vehicle_name }}</td>
                        <td style="padding:6px;">{{ trim(($p->renter_first_name ?? '') . ' ' . ($p->renter_last_name ?? '')) }}</td>
                        <td style="padding:6px;">{{ $p->type }}</td>
                        <td style="padding:6px;">{{ number_format((float)$p->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:10px;">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{ $payoutlists->links() }}

    <div id="payout-transactions-modal" style="display:none; margin-top:14px; border:1px solid #ddd; padding:10px;"></div>
    <script>
        function loadTransactions(id) {
            fetch('/cloud/linked_payouts/transactions', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({payoutid: id})
            }).then(r => r.text()).then(function (html) {
                var box = document.getElementById('payout-transactions-modal');
                box.innerHTML = html;
                box.style.display = 'block';
            });
        }
    </script>
@endsection

