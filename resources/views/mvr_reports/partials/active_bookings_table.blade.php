{{-- Shared MVR active bookings table (Cake `MvrReports/loadactivebooking.ctp`). --}}
<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive" id="mvrtable">
    <thead>
        <tr>
            <th align="center" style="text-align:center;">Booking#</th>
            <th align="center" style="text-align:center;">Vehicle#</th>
            <th align="center" style="text-align:center;">Start Date</th>
            <th align="center" style="text-align:center;">End Date</th>
            <th align="center" style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @if($bookings->isEmpty() && $reservations->isEmpty())
            <tr id="set_hide">
                <th colspan="12">No Booking Available!</th>
            </tr>
        @else
            @foreach($bookings as $trip)
                <tr id="tripRow{{ $trip->id }}">
                    <td style="text-align:center;">{{ $trip->increment_id }}</td>
                    <td style="text-align:center;">{{ $trip->vehicle_name }}</td>
                    <td style="text-align:center;">{{ $formatMvrDt($trip->start_datetime ?? null, $trip->timezone ?? null) }}</td>
                    <td style="text-align:center;">{{ $formatMvrDt($trip->end_datetime ?? null, $trip->timezone ?? null) }}</td>
                    <td>
                        <a href="javascript:void(0)" title="Cancel" onclick="return cancelBookingMvr('{{ base64_encode((string) $trip->id) }}',this);">
                            <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Cancel" style="border:0px;">
                        </a>
                    </td>
                </tr>
            @endforeach
            <tr><td colspan="5" align="center">Pending Bookings</td></tr>
            @foreach($reservations as $reservation)
                <tr id="tripRow{{ $reservation->id }}">
                    <td style="text-align:center;">{{ $reservation->id }}</td>
                    <td style="text-align:center;">{{ $reservation->vehicle_name }}</td>
                    <td style="text-align:center;">{{ $formatMvrDt($reservation->start_datetime ?? null, $reservation->timezone ?? null) }}</td>
                    <td style="text-align:center;">{{ $formatMvrDt($reservation->end_datetime ?? null, $reservation->timezone ?? null) }}</td>
                    <td>
                        <a href="javascript:void(0)" title="Cancel" onclick="return cancelMvrResevationBooking('{{ base64_encode((string) $reservation->id) }}',this);">
                            <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Cancel" style="border:0px;">
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
