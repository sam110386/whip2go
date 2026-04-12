<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Review waiting orders</title>
</head>
<body>
<h1>Review waiting orders</h1>
@if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Booking</th>
            <th>Vehicle</th>
            <th>Start</th>
            <th>End</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($nonreviews as $o)
            <tr>
                <td>{{ $o->increment_id }}</td>
                <td>{{ $o->vehicle_unique_id }}</td>
                <td>{{ $o->start_datetime }}</td>
                <td>{{ $o->end_datetime }}</td>
                <td>
                    <a href="/booking_reviews/initial/{{ base64_encode((string)$o->id) }}">Initial</a>
                    ·
                    <a href="/booking_reviews/finalreview/{{ base64_encode((string)$o->id) }}">Final</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" align="center">No orders waiting for review.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
