<div style="font-size:13px;">
    <div><strong>Reservation:</strong> {{ $b->id }}</div>
    <div><strong>Vehicle:</strong> {{ $b->vehicle_unique_id }} - {{ $b->vehicle_name }}</div>
    <div><strong>Renter:</strong> {{ trim(($b->renter_first_name ?? '') . ' ' . ($b->renter_last_name ?? '')) }}</div>
    <div><strong>Window:</strong> {{ $b->start_datetime ?? '' }} to {{ $b->end_datetime ?? '' }}</div>
    <div><strong>Status:</strong> {{ (int)$b->status }}</div>
</div>

