@extends('admin.layouts.app')

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

    <div class="panel panel-flat">
        <div class="table-responsive">
            @if (empty($listtype))
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['field' => 'id', 'title' => 'Payout#'],
                                ['field' => 'user_id', 'title' => 'Dealer'],
                                ['field' => 'processed_on', 'title' => 'Processed on'],
                                ['field' => 'amount', 'title' => 'Amount'],
                                ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payoutlists as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->user_id }}</td>
                                <td>{{ $p->processed_on }}</td>
                                <td>{{ number_format((float)($p->amount ?? 0), 2) }}</td>
                                <td><button type="button" class="btn btn-default btn-xs" onclick="loadTransactions({{ (int)$p->id }})">Transactions</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" align="center">No payouts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['field' => 'cs_payout_id', 'title' => 'Payout#'],
                                ['field' => 'increment_id', 'title' => 'Booking'],
                                ['field' => 'vehicle_name', 'title' => 'Vehicle'],
                                ['field' => 'renter_first_name', 'title' => 'Driver'],
                                ['field' => 'type', 'title' => 'Type'],
                                ['field' => 'amount', 'title' => 'Amount']
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payoutlists as $p)
                            <tr>
                                <td>{{ $p->cs_payout_id }}</td>
                                <td>{{ $p->increment_id }}</td>
                                <td>{{ $p->vehicle_name }}</td>
                                <td>{{ trim(($p->renter_first_name ?? '') . ' ' . ($p->renter_last_name ?? '')) }}</td>
                                <td>{{ $p->type }}</td>
                                <td>{{ number_format((float)$p->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" align="center">No transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit ?? 50])

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

