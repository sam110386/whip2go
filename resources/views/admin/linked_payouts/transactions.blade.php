<h3 style="margin:0 0 8px;">Payout transactions</h3>
<div class="table-responsive">
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Booking</th>
            <th>Vehicle</th>
            <th>Driver</th>
            <th>Start</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $t)
            <tr>
                <td>{{ $t->increment_id }}</td>
                <td>{{ $t->vehicle_name }}</td>
                <td>{{ trim(($t->renter_first_name ?? '') . ' ' . ($t->renter_last_name ?? '')) }}</td>
                <td>{{ $t->start_datetime }}</td>
                <td>{{ $t->type }}</td>
                <td>{{ number_format((float)$t->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No transactions found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

