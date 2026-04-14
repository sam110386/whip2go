@extends('layouts.admin')

@section('title', 'Failed Transfer')

@section('content')
    <h1>Failed transfer</h1>
    <form method="get" action="/admin/transactions/failedtransfer" style="margin-bottom:10px;">
        <label>From <input type="date" name="Search[date_from]" value="{{ $date_from ?? '' }}"></label>
        <label>To <input type="date" name="Search[date_to]" value="{{ $date_to ?? '' }}"></label>
        <button type="submit">Search</button>
    </form>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Order</th>
                <th style="padding:6px;">Type</th>
                <th style="padding:6px;">Amount</th>
                <th style="padding:6px;">Txn ID</th>
                <th style="padding:6px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportlists as $r)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $r->increment_id ?? $r->cs_order_id }}</td>
                    <td style="padding:6px;">{{ $r->type }}</td>
                    <td style="padding:6px;">{{ $r->amount }}</td>
                    <td style="padding:6px;">{{ $r->transaction_id }}</td>
                    <td style="padding:6px;"><button onclick="requeue({{ (int)$r->id }})" type="button">Requeue</button></td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding:12px;">No rows.</td></tr>
            @endforelse
        </tbody>
    </table>
    <script>
        function requeue(id) {
            fetch('/admin/transactions/requeuefailedtransfer', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({id: id})
            }).then(r => r.json()).then(function (res) {
                alert(res.message || 'Done');
                if (res.status) window.location.reload();
            });
        }
    </script>
@endsection

