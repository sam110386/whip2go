@php
    use Carbon\Carbon;
@endphp
<div class="table-responsive">
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Booking #</th>
            <th>Insu. payer</th>
            <th>Vehicle</th>
            <th>Dealer</th>
            <th>Start</th>
            <th>End</th>
            <th>Customer</th>
            <th>Rent+tax+DIA</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($trips as $trip)
            <tr>
                <td>{{ $trip->increment_id ?? $trip->id }}</td>
                <td>{{ $trip->insurance_payer ?? 'N/A' }}</td>
                <td>{{ $trip->vehicle_name }}</td>
                <td>{{ trim(($trip->owner_first_name ?? '') . ' ' . ($trip->owner_last_name ?? '')) }}</td>
                <td style="white-space:nowrap;">
                    @if (!empty($trip->start_datetime))
                        {{ Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'America/New_York')->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    @if (!empty($trip->end_datetime))
                        {{ Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? 'America/New_York')->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td>{{ trim(($trip->driver_first_name ?? '') . ' ' . ($trip->driver_last_name ?? '')) }}</td>
                <td>{{ number_format((float)$trip->rent + (float)$trip->tax + (float)$trip->dia_fee, 2) }}</td>
                <td>{{ $trip->status }}</td>
            </tr>
        @empty
            <tr><td colspan="9">No orders found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

@if ($trips->hasPages())
    <div style="margin-top:14px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <span>Page {{ $trips->currentPage() }} of {{ $trips->lastPage() }} ({{ $trips->total() }} total)</span>
        @if (!$trips->onFirstPage())
            <a href="{{ $trips->previousPageUrl() }}">Previous</a>
        @endif
        @if ($trips->hasMorePages())
            <a href="{{ $trips->nextPageUrl() }}">Next</a>
        @endif
    </div>
@endif

