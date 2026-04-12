<div style="font-size:13px;">
    <h3>Payments for order #{{ $id }}</h3>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Type</th>
                <th style="padding:6px;">Amount</th>
                <th style="padding:6px;">Txn ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $p->type }}</td>
                    <td style="padding:6px;">{{ $p->amount }}</td>
                    <td style="padding:6px;">{{ $p->transaction_id }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:10px;">No payment rows.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

