<table class="table">
    <thead><tr><th>ID</th><th>Order</th><th>Status</th></tr></thead>
    <tbody>
    @forelse(($reportlists ?? []) as $row)
        <tr>
            <td>{{ $row->id ?? '' }}</td>
            <td>{{ $row->increment_id ?? ($row->order_id ?? '') }}</td>
            <td>{{ $row->booking_status ?? ($row->status ?? '') }}</td>
        </tr>
    @empty
        <tr><td colspan="3">No records found.</td></tr>
    @endforelse
    </tbody>
</table>
