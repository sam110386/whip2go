<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th align="center" style="text-align:center;">Booking#</th>
            <th align="center" style="text-align:center;">Vehicle#</th>
            <th align="center" style="text-align:center;">Begin Date</th>
            <th align="center" style="text-align:center;">Start Date</th>
            <th align="center" style="text-align:center;">End Date</th>
            <th align="center" style="text-align:center;">Customer</th>
            <th align="center" style="text-align:center;">Dealer</th>
            <th align="center" style="text-align:center;">Cal. Rent</th>
            <th align="center" style="text-align:center;">EMF</th>
            <th align="center" style="text-align:center;">EMIF</th>
            <th align="center" style="text-align:center;">Deposit</th>
            <th align="center" style="text-align:center;">Insu. Fee</th>
            <th align="center" style="text-align:center;">Ini. Fee</th>
            <th align="center" style="text-align:center;">Toll</th>
            <th align="center" style="text-align:center;">Toll(P)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($trip_Log as $trip)
            @php
                $class = '';
                if ($trip->payment_status == 2) { $class .= ' text-warning-700'; }
                if ($trip->checkr_status) { $class .= ' alpha-violet'; }
            @endphp
            <tr id="tripRow{{ $trip->id }}" class="anchor {{ $class }} {{ $trip->status }}_status">
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ $trip->increment_id }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});">
                    {{ $trip->vehicle_name }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'UTC')->format('Y-m-d h:i A') }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'UTC')->format('Y-m-d h:i A') }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? 'UTC')->format('Y-m-d h:i A') }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ !empty($trip->renter_id) ? '' : '' }}
                </td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ !empty($trip->user_id) ? '' : '' }}
                </td>
                @if ($trip->payment_status == 2)
                    <td class="text-danger anchortag" onclick="retryrentalfee('{{ base64_encode($trip->id) }}');" style="text-align:center;">
                        {{ $trip->rent + $trip->tax + $trip->dia_fee }}-D
                    </td>
                @else
                    <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                        {{ $trip->rent + $trip->tax + $trip->dia_fee }}@if($trip->payment_status == 2)-D @elseif($trip->payment_status == 1)-P @else-UP @endif
                    </td>
                @endif
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">{{ $trip->extra_mileage_fee }}</td>
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ $trip->dia_insu }}@if($trip->dia_insu_status==2)-D @elseif($trip->dia_insu_status==1)-P @else-UP @endif
                </td>
                @if ($trip->dpa_status == 2)
                    <td class="text-danger anchortag" onclick="retrydepositfee('{{ base64_encode($trip->id) }}');" style="text-align:center;">
                        {{ $trip->deposit }}-D
                    </td>
                @else
                    <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                        {{ $trip->deposit }}@if($trip->dpa_status == 2)-D @elseif($trip->dpa_status == 1)-P @else-UP @endif
                    </td>
                @endif
                @if ($trip->insu_status == 2)
                    <td class="text-danger anchortag" onclick="retryinsurancefee('{{ base64_encode($trip->id) }}');" style="text-align:center;">
                        {{ $trip->insurance_amt }}-D
                    </td>
                @else
                    <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                        {{ $trip->insurance_amt }}@if($trip->insu_status == 2)-D @elseif($trip->insu_status == 1)-P @else-UP @endif
                    </td>
                @endif
                @if ($trip->infee_status == 2)
                    <td class="text-danger anchortag" onclick="retryinitialfee('{{ base64_encode($trip->id) }}');" style="text-align:center;">
                        {{ $trip->initial_fee }}-D
                    </td>
                @else
                    <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                        {{ $trip->initial_fee }}@if($trip->infee_status == 2)-D @elseif($trip->infee_status == 1)-P @else-UP @endif
                    </td>
                @endif
                <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                    {{ $trip->toll }}
                </td>
                @if ($trip->pending_toll > 0)
                    <td class="text-danger anchortag" onclick="retrytollfee('{{ base64_encode($trip->id) }}');" style="text-align:center;">
                        {{ $trip->pending_toll }}-D
                    </td>
                @else
                    <td onclick="cloudOpenBookingDetails({{ $trip->id }});" style="text-align:center;">
                        {{ $trip->pending_toll }}
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
