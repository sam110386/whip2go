<table style="width:100%; border-collapse:collapse; font-size:13px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">Order</th>
            <th style="padding:6px;">Dealer</th>
            <th style="padding:6px;">Renter</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">End</th>
            <th style="padding:6px;">Status</th>
            <th style="padding:6px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportlists as $r)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $r->increment_id ?? $r->id }}</td>
                <td style="padding:6px;">{{ trim(($r->owner_first_name ?? '') . ' ' . ($r->owner_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ trim(($r->renter_first_name ?? '') . ' ' . ($r->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ $r->start_datetime }}</td>
                <td style="padding:6px;">{{ $r->end_datetime }}</td>
                <td style="padding:6px;">{{ $r->status }}</td>
                <td style="padding:6px;"><a href="/admin/reports/details/{{ base64_encode((string)$r->id) }}">Details</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:10px;">No rows found.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $reportlists->links() }}

