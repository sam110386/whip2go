{{-- AJAX modal body (Cake `Elements/reportrenters/history.ctp`). --}}
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">Booking#</th>
                <th align="center" style="text-align:center;">Vehicle#</th>
                <th align="center" style="text-align:center;">Location</th>
                <th align="center" style="text-align:center;">Start Date</th>
                <th align="center" style="text-align:center;">End Date</th>
                <th align="center" style="text-align:center;">Cal. Rent</th>
                <th align="center" style="text-align:center;">Tax</th>
                <th align="center" style="text-align:center;">Deposit</th>
            </tr>
        </thead>
        <tbody>
            @if($bookings->isEmpty())
                <tr id="set_hide">
                    <th colspan="9">No Past Booking Available!</th>
                </tr>
            @else
                @foreach($bookings as $trip)
                    <tr id="tripRow{{ $trip->id }}">
                        <td>{{ $trip->increment_id }}</td>
                        <td>{{ $trip->vehicle_unique_id }}</td>
                        <td>{{ $trip->pickup_address }}</td>
                        <td>
                            @if(!empty($trip->start_datetime))
                                {{ \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') }}
                            @endif
                        </td>
                        <td>
                            @if(!empty($trip->end_datetime))
                                {{ \Carbon\Carbon::parse($trip->end_datetime)->format('Y-m-d h:i A') }}
                            @endif
                        </td>
                        <td>{{ $trip->rent }}</td>
                        <td>{{ $trip->tax }}</td>
                        <td>{{ $trip->deposit }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
