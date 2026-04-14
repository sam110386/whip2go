@php $class = 'even'; @endphp
@foreach ($subbookinglists as $trip)
    @php
        $class = trim($class) === 'even' ? 'odd' : 'even';
        $openTripDetails = "openTripDetails('" . base64_encode($trip->id) . "')";
    @endphp
    <tr class="child_{{ $booking_id }}" style="background: rgb(225, 245, 254);">
        <td></td>
        <td onclick="{{ $openTripDetails }}">
            {{ $booking_id == $trip->id ? $trip->increment_id . '-0' : $trip->increment_id }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            @if ($trip->status == 3) Completed
            @elseif ($trip->status == 2) Canceled
            @else Incomplete
            @endif
        </td>
        <td onclick="{{ $openTripDetails }}">{{ $trip->vehicle_name }}</td>
        <td onclick="{{ $openTripDetails }}">
            {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'UTC')->format('m/d/Y h:i A') }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? 'UTC')->format('m/d/Y h:i A') }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ \Carbon\Carbon::parse($trip->start_datetime)->diffInDays(\Carbon\Carbon::parse($trip->end_datetime)) }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip->first_name }} {{ $trip->last_name }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip->status == 3 ? $trip->end_odometer - $trip->start_odometer : 0 }}
        </td>
        <td onclick="{{ $openTripDetails }}">{{ $trip->paid_amount }}</td>
        <td onclick="{{ $openTripDetails }}">{{ $trip->insurance_amt + $trip->dia_insu }}</td>
        <td onclick="{{ $openTripDetails }}">{{ $trip->toll }}</td>
        <td onclick="{{ $openTripDetails }}">{{ $trip->dia_fee }}</td>
        <td>
            @if ($trip->status == 3)
                <a href="javascript:void(0)" title="Review Images" onclick="reviewimages('{{ base64_encode($trip->id) }}')"><i class="icon-clipboard3"></i></a>
            @endif
            <a href="javascript:void(0)" title="Agreement Doc" onclick="return getagreement('{{ base64_encode($trip->id) }}');"><i class="icon-file-pdf"></i></a>
        </td>
    </tr>
@endforeach
