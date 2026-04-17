<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Complete booking</h3>
    <div><strong>Order:</strong> {{ $trip->increment_id ?? $trip->id }}</div>
    <div><strong>Vehicle:</strong> {{ $trip->vehicle_name ?? '' }}</div>
    <div><strong>Current status:</strong> {{ (int)($trip->status ?? 0) }}</div>
</div>
