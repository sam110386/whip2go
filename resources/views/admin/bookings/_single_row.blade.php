<td onclick="openBookingDetails({{ (int)($trip->id ?? 0) }});" style="text-align:center;">
    {{ $trip->increment_id ?? ($trip->id ?? '') }}
</td>
<td style="text-align:center;">{{ $trip->insurance_payer ?? 'N/A' }}</td>
<td onclick="openBookingDetails({{ (int)($trip->id ?? 0) }});">{{ $trip->vehicle->vehicle_name ?? ($trip->vehicle_name ?? '') }}</td>
<td onclick="openUserTransactions({{ (int)($trip->renter_id ?? 0) }},'{{ (int)($trip->id ?? 0) }}','{{ $trip->currency ?? '' }}');" style="text-align:center;">
    <span class="btn-link text-blue">
        @if($trip->renter)
            {{ trim($trip->renter->first_name . ' ' . $trip->renter->last_name) }}
        @else
            {{ trim(($trip->driver_first_name ?? '') . ' ' . ($trip->driver_last_name ?? '')) }}
        @endif
    </span>
</td>
<td onclick="openBookingDetails({{ (int)($trip->id ?? 0) }});" style="text-align:center;">{{ $trip->start_datetime ?? '' }}</td>
<td onclick="openBookingDetails({{ (int)($trip->id ?? 0) }});" style="text-align:center;">{{ $trip->end_datetime ?? '' }}</td>
<td style="text-align:center;">{{ number_format((float)($trip->rent ?? 0) + (float)($trip->tax ?? 0) + (float)($trip->dia_fee ?? 0), 2) }}</td>
<td>
    <span class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
            <i class="icon-cog7"></i><span class="caret"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-solid pull-right">
            <li><a href="javascript:void(0)" onclick="return startBooking('{{ base64_encode((string)($trip->id ?? 0)) }}');"><i class="glyphicon glyphicon-ok-circle"></i> Start Booking</a></li>
            <li><a href="javascript:void(0)" onclick="return completeBooking('{{ base64_encode((string)($trip->id ?? 0)) }}');"><i class="glyphicon glyphicon-saved"></i> Complete/Renew</a></li>
            <li><a href="javascript:void(0)" onclick="return cancelBooking('{{ base64_encode((string)($trip->id ?? 0)) }}');"><i class="icon-trash"></i> Cancel Booking</a></li>
        </ul>
    </span>
</td>

