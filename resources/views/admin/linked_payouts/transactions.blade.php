<h3 style="margin:0 0 8px;">Payout transactions</h3>
<table style="width:100%; border-collapse:collapse; font-size:13px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">Booking</th>
            <th style="padding:6px;">Vehicle</th>
            <th style="padding:6px;">Driver</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">Type</th>
            <th style="padding:6px;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $t)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $t->increment_id }}</td>
                <td style="padding:6px;">{{ $t->vehicle_name }}</td>
                <td style="padding:6px;">{{ trim(($t->renter_first_name ?? '') . ' ' . ($t->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ $t->start_datetime }}</td>
                <td style="padding:6px;">{{ $t->type }}</td>
                <td style="padding:6px;">{{ number_format((float)$t->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:10px;">No transactions found.</td></tr>
        @endforelse
    </tbody>
</table>

