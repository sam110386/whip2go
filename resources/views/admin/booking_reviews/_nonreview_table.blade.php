<table style="width:100%; border-collapse:collapse; font-size:13px;" border="1" cellpadding="6">
    <thead>
        <tr>
            <th>Booking#</th>
            <th>Vehicle#</th>
            <th>Start</th>
            <th>End</th>
            <th>Customer</th>
            <th>Rent+Tax</th>
            <th>Deposit</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($nonreviews as $o)
            <tr>
                <td style="text-align:center;">{{ $o->increment_id }}</td>
                <td style="text-align:center;">{{ $o->vehicle_unique_id }}</td>
                <td style="text-align:center;">{{ $o->start_datetime }}</td>
                <td style="text-align:center;">{{ $o->end_datetime }}</td>
                <td style="text-align:center;">{{ $o->renter_name }}</td>
                <td style="text-align:center;">{{ (float)$o->rent + (float)$o->tax }}</td>
                <td style="text-align:center;">{{ $o->deposit }}</td>
                <td style="text-align:center;">
                    <a href="{{ $basePath }}/admin_initial/{{ base64_encode((string)$o->id) }}">Initial</a>
                    ·
                    <a href="{{ $basePath }}/admin_finalreview/{{ base64_encode((string)$o->id) }}">Final</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;">No bookings pending review.</td></tr>
        @endforelse
    </tbody>
</table>
@if(isset($nonreviews) && method_exists($nonreviews, 'links'))
    <div style="margin-top:10px;">{{ $nonreviews->links() }}</div>
@endif
