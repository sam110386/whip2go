@php
    $bookings ??= collect();
    $reservations ??= collect();
@endphp

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
                <tr id="tripRow{{ data_get($trip, 'id', '') }}">
                    <td style="text-align:center;">
                        {{ data_get($trip, 'increment_id', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($trip, 'vehicle_name', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse(data_get($trip, 'start_datetime'))->timezone(data_get($trip, 'timezone', config('app.timezone')))->format('Y-m-d h:i A') }}
                    </td>
                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse(data_get($trip, 'end_datetime'))->timezone(data_get($trip, 'timezone', config('app.timezone')))->format('Y-m-d h:i A') }}
                    </td>
                    <td>
                        <a href="javascript:void(0)" title="Cancel"
                            onclick="return cancelBookingMvr('{{ base64_encode(data_get($trip, 'id', '')) }}',this);">
                            <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Cancel" style="border:0px;">
                        </a>
                    </td>
                </tr>
            @endforeach

            <tr>
                <td colspan="5" align="center">Pending Bookings</td>
            </tr>

            @foreach($reservations as $reservation)
                <tr id="tripRow{{ data_get($reservation, 'id', '') }}">
                    <td style="text-align:center;">
                        {{ data_get($reservation, 'id', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($reservation, 'vehicle_name', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse(data_get($reservation, 'start_datetime'))->timezone(data_get($reservation, 'timezone', config('app.timezone')))->format('Y-m-d h:i A') }}
                    </td>
                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse(data_get($reservation, 'end_datetime'))->timezone(data_get($reservation, 'timezone', config('app.timezone')))->format('Y-m-d h:i A') }}
                    </td>
                    <td>
                        <a href="javascript:void(0)" title="Cancel"
                            onclick="return cancelMvrResevationBooking('{{ base64_encode(data_get($reservation, 'id', '')) }}',this);">
                            <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Cancel" style="border:0px;">
                        </a>
                    </td>
                </tr>
            @endforeach

        @endif
    </tbody>
</table>