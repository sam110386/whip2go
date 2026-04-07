@php
    use Carbon\Carbon;
@endphp
<table style="width:100%; border-collapse:collapse; font-size:12px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">Booking#</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">End</th>
            <th style="padding:6px;">Amount</th>
            <th style="padding:6px;">Transaction #</th>
            <th style="padding:6px;">Charged</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $trip)
            @php
                $tz = $trip->timezone ?? 'America/New_York';
                $charged = $trip->charged_at ?? $trip->created ?? null;
            @endphp
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $trip->increment_id }}</td>
                <td style="padding:6px;">
                    @if (!empty($trip->start_datetime))
                        {{ Carbon::parse($trip->start_datetime)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td style="padding:6px;">
                    @if (!empty($trip->end_datetime))
                        {{ Carbon::parse($trip->end_datetime)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td style="padding:6px;">{{ $trip->amount }}</td>
                <td style="padding:6px;">{{ $trip->transaction_id }}</td>
                <td style="padding:6px;">
                    @if ($charged)
                        {{ Carbon::parse($charged)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:12px;">No payments in range.</td></tr>
        @endforelse
        <tr>
            <td colspan="6" style="padding:8px; text-align:center;"><strong>Total {{ number_format((float)$total, 2) }}</strong></td>
        </tr>
    </tbody>
</table>
