@php
    use Carbon\Carbon;
@endphp
<table style="width:100%; border-collapse:collapse; font-size:13px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">Booking #</th>
            <th style="padding:6px;">Insu. payer</th>
            <th style="padding:6px;">Vehicle</th>
            <th style="padding:6px;">Dealer</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">End</th>
            <th style="padding:6px;">Customer</th>
            <th style="padding:6px;">Rent+tax+DIA</th>
            <th style="padding:6px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($trips as $trip)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $trip->increment_id ?? $trip->id }}</td>
                <td style="padding:6px;">{{ $trip->insurance_payer ?? 'N/A' }}</td>
                <td style="padding:6px;">{{ $trip->vehicle_name }}</td>
                <td style="padding:6px;">{{ trim(($trip->owner_first_name ?? '') . ' ' . ($trip->owner_last_name ?? '')) }}</td>
                <td style="padding:6px; white-space:nowrap;">
                    @if (!empty($trip->start_datetime))
                        {{ Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'America/New_York')->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td style="padding:6px; white-space:nowrap;">
                    @if (!empty($trip->end_datetime))
                        {{ Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? 'America/New_York')->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td style="padding:6px;">{{ trim(($trip->driver_first_name ?? '') . ' ' . ($trip->driver_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ number_format((float)$trip->rent + (float)$trip->tax + (float)$trip->dia_fee, 2) }}</td>
                <td style="padding:6px;">{{ $trip->status }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="padding:12px;">No orders found.</td></tr>
        @endforelse
    </tbody>
</table>

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

