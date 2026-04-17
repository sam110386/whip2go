<div style="font-size:13px;">
    <div><strong>Order:</strong> {{ $trip->increment_id ?? $trip->id }}</div>
    <div><strong>Vehicle:</strong> {{ $trip->vehicle_name ?? '' }}</div>
    <div><strong>Status:</strong> {{ (int)($trip->status ?? 0) }}</div>
</div>
