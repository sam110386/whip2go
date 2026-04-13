@foreach($rows as $trip)
@php
    $openTripDetails = "openTripDetails('" . base64_encode((string) $trip->id) . "')";
    $inc = ($bookingId == $trip->id) ? ($trip->increment_id . '-0') : $trip->increment_id;
    $statusLabel = $trip->status == 3 ? 'Completed' : ($trip->status == 2 ? 'Canceled' : 'Incomplete');
    $dist = ($trip->status == 3 && $trip->end_odometer !== null && $trip->start_odometer !== null)
        ? ((float) $trip->end_odometer - (float) $trip->start_odometer)
        : 0;
@endphp
<tr class="child_{{ $bookingId }}" style="background: rgb(225, 245, 254);">
    <td></td>
    <td onclick="{{ $openTripDetails }}">{{ $inc }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $statusLabel }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $trip->vehicle_name }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $trip->start_datetime }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $trip->end_datetime }}</td>
    <td onclick="{{ $openTripDetails }}">—</td>
    <td onclick="{{ $openTripDetails }}">{{ trim(($trip->first_name ?? '') . ' ' . ($trip->last_name ?? '')) }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $dist }}</td>
    <td onclick="{{ $openTripDetails }}">{{ $trip->paid_amount ?? 0 }}</td>
    <td onclick="{{ $openTripDetails }}">{{ (float)($trip->insurance_amt ?? 0) + (float)($trip->dia_insu ?? 0) }}</td>
    <td onclick="{{ $openTripDetails }}">{{ (float)($trip->toll ?? 0) + (float)($trip->pending_toll ?? 0) }}</td>
    <td></td>
</tr>
@endforeach
