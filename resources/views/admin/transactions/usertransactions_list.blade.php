@php
    use Carbon\Carbon;
@endphp
<div class="table-responsive">
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Booking#</th>
            <th>Start</th>
            <th>End</th>
            <th>Amount</th>
            <th>Transaction #</th>
            <th>Charged</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $trip)
            @php
                $tz = $trip->timezone ?? 'America/New_York';
                $charged = $trip->charged_at ?? $trip->created ?? null;
            @endphp
            <tr>
                <td>{{ $trip->increment_id }}</td>
                <td>
                    @if (!empty($trip->start_datetime))
                        {{ Carbon::parse($trip->start_datetime)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td>
                    @if (!empty($trip->end_datetime))
                        {{ Carbon::parse($trip->end_datetime)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
                <td>{{ $trip->amount }}</td>
                <td>{{ $trip->transaction_id }}</td>
                <td>
                    @if ($charged)
                        {{ Carbon::parse($charged)->timezone($tz)->format('Y-m-d g:i A') }}
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No payments in range.</td></tr>
        @endforelse
        <tr>
            <td colspan="6" class="text-center"><strong>Total {{ number_format((float)$total, 2) }}</strong></td>
        </tr>
    </tbody>
</table>
</div>
