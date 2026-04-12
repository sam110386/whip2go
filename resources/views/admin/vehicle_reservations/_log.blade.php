<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Reservation log #{{ $id }}</h3>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">ID</th>
                <th style="padding:6px;">Message</th>
                <th style="padding:6px;">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $l)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $l->id }}</td>
                    <td style="padding:6px;">{{ $l->message ?? $l->note ?? '-' }}</td>
                    <td style="padding:6px;">{{ $l->created ?? $l->created_at ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:10px;">No logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

